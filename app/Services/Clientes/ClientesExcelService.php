<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use App\Models\EstadoCliente;
use App\Models\Localidad;
use App\Models\Provincia;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Importación / exportación masiva de clientes en Excel (.xlsx).
 *
 * Formato (una fila por cliente):
 *   Razón social | Nombre fantasía | CUIT | Tipo | Estado | Email facturación |
 *   Teléfono | Dirección | Provincia | Localidad | Código postal |
 *   Nombre contacto | Email contacto | Teléfono contacto
 *
 * Reglas:
 *  - Razón social y CUIT son obligatorios.
 *  - El CUIT se normaliza a XX-XXXXXXXX-X y se valida el dígito verificador.
 *  - Tipo y Estado se resuelven por código o nombre del catálogo (vacíos = B2B / Activo).
 *  - Se hace upsert por CUIT: si ya existe se actualiza, si no se crea.
 *  - No se crean ni tocan usuarios ni contraseñas de acceso.
 */
class ClientesExcelService
{
    public const ENCABEZADOS = [
        'Razón social', 'Nombre fantasía', 'CUIT', 'Tipo', 'Estado', 'Email facturación',
        'Teléfono', 'Dirección', 'Provincia', 'Localidad', 'Código postal',
        'Nombre contacto', 'Email contacto', 'Teléfono contacto',
    ];

    /** Tipos de cliente que se pueden importar. */
    private const TIPOS_IMPORTABLES = ['PUBLICO', 'B2B'];

    /** Estados de cliente que se pueden importar. */
    private const ESTADOS_IMPORTABLES = ['ACTIVO', 'INACTIVO', 'SUSPENDIDO', 'MOROSO', 'PROSPECTO'];

    /** Alias de encabezados (normalizados) → campo interno. */
    private const ALIAS = [
        'razon social' => 'razon_social', 'razonsocial' => 'razon_social', 'empresa' => 'razon_social',
        'nombre fantasia' => 'nombre_fantasia', 'nombre fantasía' => 'nombre_fantasia',
        'nombre_fantasia' => 'nombre_fantasia', 'fantasia' => 'nombre_fantasia', 'fantasía' => 'nombre_fantasia',
        'cuit' => 'cuit',
        'tipo' => 'tipo', 'tipo cliente' => 'tipo', 'tipo_de_cliente' => 'tipo',
        'estado' => 'estado', 'estado cliente' => 'estado', 'estado_de_cliente' => 'estado',
        'email facturacion' => 'email_facturacion', 'email facturación' => 'email_facturacion',
        'email' => 'email_facturacion', 'correo' => 'email_facturacion',
        'telefono' => 'telefono', 'teléfono' => 'telefono', 'tel' => 'telefono',
        'direccion' => 'direccion', 'dirección' => 'direccion', 'domicilio' => 'direccion',
        'provincia' => 'provincia',
        'localidad' => 'localidad',
        'codigo postal' => 'codigo_postal', 'cod postal' => 'codigo_postal', 'cp' => 'codigo_postal',
        'nombre contacto' => 'nombre_contacto', 'contacto' => 'nombre_contacto',
        'email contacto' => 'email_contacto', 'email del contacto' => 'email_contacto',
        'telefono contacto' => 'telefono_contacto', 'teléfono contacto' => 'telefono_contacto',
    ];

    // =====================================================================
    // EXPORTAR
    // =====================================================================

    /** Genera un XLSX con los clientes según el alcance solicitado. */
    public function exportar(string $rutaDestino, string $alcance = 'todos'): void
    {
        $query = Cliente::with(['tipoCliente', 'estado', 'localidad.provincia', 'contactos']);

        if ($alcance === 'activos') {
            $query->whereHas('estado', fn ($q) => $q->where('codigo', 'ACTIVO'));
        }

        $clientes = $query->orderBy('razon_social')->get();

        $writer = new Writer();
        $writer->openToFile($rutaDestino);
        $writer->getCurrentSheet()->setName('Clientes');
        $writer->addRow(Row::fromValues(self::ENCABEZADOS));

        foreach ($clientes as $c) {
            $contacto = $c->contactos->first(fn ($ct) => (bool) ($ct->es_principal ?? false));
            $contacto = $contacto ?? ($c->contactos->isEmpty() ? null : $c->contactos->first());

            $writer->addRow(Row::fromValues([
                $c->razon_social,
                $c->nombre_fantasia,
                $c->cuit,
                $c->tipoCliente?->nombre,
                $c->estado?->nombre,
                $c->email_facturacion,
                $c->telefono,
                $c->direccion,
                $c->localidad?->provincia?->nombre,
                $c->localidad?->nombre,
                $c->localidad?->codigo_postal,
                $contacto?->nombre,
                $contacto?->email,
                $contacto?->telefono,
            ]));
        }

        $writer->close();
    }

    // =====================================================================
    // IMPORTAR
    // =====================================================================

    /**
     * Importa clientes desde un XLSX. Es "todo o nada": si alguna fila tiene
     * errores no se guarda nada y se devuelven los errores.
     *
     * @return array{creadas:int, actualizadas:int, errores:array<int,string>}
     */
    public function importar(string $rutaArchivo, ?int $usuarioId): array
    {
        $filas = $this->leerFilas($rutaArchivo);

        if (isset($filas['error'])) {
            return ['creadas' => 0, 'actualizadas' => 0, 'errores' => [$filas['error']]];
        }

        $catalogos = $this->cargarCatalogos();

        $errores = [];
        $registros = [];

        foreach ($filas as $numero => $fila) {
            $resultado = $this->normalizarFila($fila, $catalogos);
            if (is_string($resultado)) {
                $errores[] = "Fila {$numero}: {$resultado}";
                continue;
            }
            $registros[$numero] = $resultado;
        }

        if ($errores === [] && $registros === []) {
            return ['creadas' => 0, 'actualizadas' => 0, 'errores' => ['El archivo no tiene filas de clientes para importar.']];
        }

        if ($errores !== []) {
            return ['creadas' => 0, 'actualizadas' => 0, 'errores' => $errores];
        }

        $creadas = 0;
        $actualizadas = 0;

        DB::transaction(function () use ($registros, $usuarioId, &$creadas, &$actualizadas) {
            foreach ($registros as $reg) {
                $cuit = $reg['cuit'];

                $existente = Cliente::where('cuit', $cuit)->orderByDesc('id')->first();

                $datos = [
                    'cuit'             => $reg['cuit'],
                    'razon_social'     => $reg['razon_social'],
                    'nombre_fantasia'  => $reg['nombre_fantasia'],
                    'tipo_cliente_id'  => $reg['tipo_cliente_id'],
                    'estado_id'        => $reg['estado_id'],
                    'email_facturacion'=> $reg['email_facturacion'],
                    'telefono'         => $reg['telefono'],
                    'direccion'        => $reg['direccion'],
                    'localidad_id'     => $reg['localidad_id'],
                    'updated_by'       => $usuarioId,
                ];

                if ($existente) {
                    $existente->update($datos);
                    $actualizadas++;
                    $cliente = $existente;
                } else {
                    $datos['created_by'] = $usuarioId;
                    $cliente = Cliente::create($datos);
                    $creadas++;
                }

                $this->sincronizarContactoPrincipal($cliente, $reg, $existente === null, $usuarioId);
            }
        });

        return ['creadas' => $creadas, 'actualizadas' => $actualizadas, 'errores' => []];
    }

    /**
     * Crea el contacto principal al importar un cliente nuevo, o lo actualiza
     * solo si el Excel trae nombre o email de contacto.
     */
    private function sincronizarContactoPrincipal(Cliente $cliente, array $reg, bool $creado, int $usuarioId): void
    {
        $tieneContacto = $reg['nombre_contacto'] !== null || $reg['email_contacto'] !== null || $reg['telefono_contacto'] !== null;

        if (! $tieneContacto && ! $creado) {
            return;
        }

        $contacto = $cliente->contactos->first(fn ($ct) => (bool) ($ct->es_principal ?? false))
            ?? ($cliente->contactos->isEmpty() ? null : $cliente->contactos->first());

        $nombre = $reg['nombre_contacto'] ?? ($cliente->nombre_fantasia ?: $cliente->razon_social);
        $email = $reg['email_contacto'] ?? ($creado ? $cliente->email_facturacion : null);
        $telefono = $reg['telefono_contacto'] ?? ($creado ? $cliente->telefono : null);

        if ($contacto) {
            $contacto->update([
                'nombre'      => $nombre,
                'email'       => $email,
                'telefono'    => $telefono,
                'es_principal'=> true,
                'updated_by'  => $usuarioId,
            ]);
        } else {
            $cliente->contactos()->create([
                'nombre'      => $nombre,
                'email'       => $email,
                'telefono'    => $telefono,
                'es_principal'=> true,
                'created_by'  => $usuarioId,
                'updated_by'  => $usuarioId,
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Lectura
    // ---------------------------------------------------------------------

    /**
     * Lee la primera hoja y devuelve [nroFilaExcel => [campo => valor]].
     * Devuelve ['error' => '...'] si el formato es inválido.
     */
    private function leerFilas(string $ruta): array
    {
        $reader = new Reader();
        $reader->open($ruta);

        $mapa = null;
        $filas = [];

        try {
            foreach ($reader->getSheetIterator() as $hoja) {
                $nro = 0;
                foreach ($hoja->getRowIterator() as $row) {
                    $nro++;
                    $celdas = $row->toArray();

                    if ($mapa === null) {
                        $mapa = $this->mapearEncabezados($celdas);
                        if ($mapa === null) {
                            return ['error' => 'No se encontraron los encabezados esperados en la primera fila (Razón social, CUIT, ...). Descargá la plantilla para ver el formato.'];
                        }
                        continue;
                    }

                    $fila = [];
                    $vacia = true;
                    foreach ($mapa as $indice => $campo) {
                        $valor = $celdas[$indice] ?? null;
                        if (is_string($valor)) {
                            $valor = trim($valor);
                            if ($valor === '') {
                                $valor = null;
                            }
                        }
                        if ($valor !== null) {
                            $vacia = false;
                        }
                        $fila[$campo] = $valor;
                    }

                    if (! $vacia) {
                        $filas[$nro] = $fila;
                    }
                }
                break; // solo la primera hoja
            }
        } finally {
            $reader->close();
        }

        return $filas;
    }

    /** @return array<int,string>|null  índice de columna → campo interno */
    private function mapearEncabezados(array $celdas): ?array
    {
        $mapa = [];
        foreach ($celdas as $i => $titulo) {
            if (! is_string($titulo)) {
                continue;
            }
            $campo = self::ALIAS[$this->normalizar($titulo)] ?? null;
            if ($campo && ! in_array($campo, $mapa, true)) {
                $mapa[$i] = $campo;
            }
        }

        foreach (['razon_social', 'cuit'] as $obligatorio) {
            if (! in_array($obligatorio, $mapa, true)) {
                return null;
            }
        }

        return $mapa;
    }

    // ---------------------------------------------------------------------
    // Validación de una fila
    // ---------------------------------------------------------------------

    /** @return array<string,mixed>|string  registro listo para guardar o mensaje de error */
    private function normalizarFila(array $f, array &$cat): array|string
    {
        // Razón social
        $razonSocial = trim((string) ($f['razon_social'] ?? ''));
        if ($razonSocial === '') {
            return 'Razón social es obligatoria.';
        }

        // CUIT
        $cuit = $this->normalizarCuit($f['cuit'] ?? null);
        if ($cuit === null) {
            return 'CUIT inválido: usá formato XX-XXXXXXXX-X con dígito verificador correcto.';
        }

        // Tipo
        $tipoTxt = $f['tipo'] ?? null;
        $tipoId = $tipoTxt !== null ? ((($cat['tipos'][$this->normalizar((string) $tipoTxt)] ?? null)['id'] ?? null)) : null;
        if ($tipoId === null) {
            $tipoId = $cat['tipoDefault'];
        }

        // Estado
        $estadoTxt = $f['estado'] ?? null;
        $estadoId = $estadoTxt !== null ? ((($cat['estados'][$this->normalizar((string) $estadoTxt)] ?? null)['id'] ?? null)) : null;
        if ($estadoId === null) {
            $estadoId = $cat['estadoDefault'];
        }

        // Provincia / Localidad
        $provinciaTxt = $f['provincia'] ?? null;
        $provinciaId = null;
        if ($provinciaTxt !== null) {
            $provinciaId = $cat['provincias'][$this->normalizar((string) $provinciaTxt)] ?? null;
            if (! $provinciaId) {
                return 'provincia "' . $provinciaTxt . '" no encontrada.';
            }
        }

        $localidadId = null;
        $localidadTxt = $f['localidad'] ?? null;
        $cp = $f['codigo_postal'] ?? null;
        if ($localidadTxt !== null) {
            if ($provinciaId === null) {
                return 'para indicar Localidad también se requiere Provincia.';
            }
            $localidadId = $this->buscarLocalidad($provinciaId, (string) $localidadTxt, $cp, $cat);
            if (! $localidadId) {
                return 'localidad "' . $localidadTxt . '" no encontrada en la provincia indicada.';
            }
        }

        // Email facturación
        $email = $f['email_facturacion'] ?? null;
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'email facturación inválido.';
        }

        return [
            'razon_social'     => $razonSocial,
            'nombre_fantasia'  => $f['nombre_fantasia'] ?? null,
            'cuit'             => $cuit,
            'tipo_cliente_id'  => $tipoId,
            'estado_id'        => $estadoId,
            'email_facturacion'=> $email,
            'telefono'         => $f['telefono'] ?? null,
            'direccion'        => $f['direccion'] ?? null,
            'provincia_id'     => $provinciaId,
            'localidad_id'     => $localidadId,
            'nombre_contacto'  => $f['nombre_contacto'] ?? null,
            'email_contacto'   => $f['email_contacto'] ?? null,
            'telefono_contacto'=> $f['telefono_contacto'] ?? null,
        ];
    }

    private function buscarLocalidad(int $provinciaId, string $nombre, mixed $cp, array &$cat): ?int
    {
        if (! isset($cat['localidades'][$provinciaId])) {
            $cat['localidades'][$provinciaId] = Localidad::where('provincia_id', $provinciaId)
                ->get(['id', 'nombre', 'codigo_postal'])
                ->groupBy(fn ($l) => $this->normalizar($l->nombre));
        }

        $candidatas = $cat['localidades'][$provinciaId][$this->normalizar($nombre)] ?? null;
        if (! $candidatas || $candidatas->isEmpty()) {
            return null;
        }

        if ($cp !== null && $candidatas->count() > 1) {
            $cpNorm = trim((string) $cp);
            $match = $candidatas->first(fn ($l) => trim((string) $l->codigo_postal) === $cpNorm);
            if ($match) {
                return $match->id;
            }
        }

        return $candidatas->first()->id;
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function cargarCatalogos(): array
    {
        $tipos = [];
        foreach (TipoCliente::whereIn('codigo', self::TIPOS_IMPORTABLES)->get() as $t) {
            $datos = ['id' => $t->id, 'codigo' => $t->codigo];
            $tipos[$this->normalizar($t->codigo)] = $datos;
            $tipos[$this->normalizar($t->nombre)] = $datos;
        }

        $estados = [];
        foreach (EstadoCliente::whereIn('codigo', self::ESTADOS_IMPORTABLES)->get() as $e) {
            $datos = ['id' => $e->id, 'codigo' => $e->codigo];
            $estados[$this->normalizar($e->codigo)] = $datos;
            $estados[$this->normalizar($e->nombre)] = $datos;
        }

        $provincias = [];
        foreach (Provincia::all(['id', 'nombre']) as $p) {
            $provincias[$this->normalizar($p->nombre)] = $p->id;
        }

        return [
            'tipos'         => $tipos,
            'estados'       => $estados,
            'provincias'    => $provincias,
            'localidades'   => [],
            'tipoDefault'   => TipoCliente::where('codigo', 'B2B')->value('id'),
            'estadoDefault' => EstadoCliente::where('codigo', 'ACTIVO')->value('id'),
        ];
    }

    /** Minúsculas, sin acentos, "_" como espacio y espacios colapsados. */
    private function normalizar(string $texto): string
    {
        $t = Str::of($texto)->ascii()->lower()->replace('_', ' ')->squish();

        return (string) $t;
    }

    /**
     * Normaliza un CUIT a XX-XXXXXXXX-X y valida el dígito verificador.
     * Devuelve null si no es válido.
     */
    private function normalizarCuit(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $soloDigitos = preg_replace('/\D/', '', (string) $valor);
        if (strlen($soloDigitos) !== 11) {
            return null;
        }

        if (! $this->validarDigitoVerificador($soloDigitos)) {
            return null;
        }

        return substr($soloDigitos, 0, 2) . '-' . substr($soloDigitos, 2, 8) . '-' . substr($soloDigitos, 10, 1);
    }

    /** Valida el dígito verificador del CUIT (módulo 11). */
    private function validarDigitoVerificador(string $cuit11): bool
    {
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $cuit11[$i] * $pesos[$i];
        }

        $resto = $suma % 11;
        $esperado = $resto === 0 ? 0 : ($resto === 1 ? 9 : 11 - $resto);

        return (int) $cuit11[10] === $esperado;
    }
}
