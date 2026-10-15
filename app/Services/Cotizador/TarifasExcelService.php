<?php

namespace App\Services\Cotizador;

use App\Models\Localidad;
use App\Models\Proveedor;
use App\Models\Provincia;
use App\Models\Tarifa;
use App\Models\TipoServicio;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Importación / exportación masiva de tarifas en Excel (.xlsx).
 *
 * Formato (una fila por escalón de tarifa):
 *   Servicio | Provincia origen | Provincia destino | Localidad | Código postal |
 *   Unidad | Costo | Máximo | Vigente desde | Vigente hasta
 *
 * Convenciones (las mismas que usa el motor de cotización):
 *  - TRONCAL:        provincia origen → provincia destino, sin localidad.
 *  - PRIMERA_MILLA:  provincia origen + localidad de ORIGEN.
 *  - ULTIMA_MILLA:   provincia destino + localidad de DESTINO (vacía = toda la provincia).
 *  - Máximo vacío = escalón abierto (sin tope).
 */
class TarifasExcelService
{
    public const ENCABEZADOS = [
        'Servicio', 'Provincia origen', 'Provincia destino', 'Localidad', 'Código postal',
        'Unidad', 'Costo', 'Máximo', 'Vigente desde', 'Vigente hasta',
    ];

    /** Servicios que se pueden cargar por Excel. */
    private const SERVICIOS_IMPORTABLES = ['TRONCAL', 'PRIMERA_MILLA', 'ULTIMA_MILLA', 'PUERTA_PUERTA'];

    /** Alias de encabezados (normalizados) → campo interno. */
    private const ALIAS = [
        'servicio' => 'servicio', 'tipo de servicio' => 'servicio', 'tramo' => 'servicio',
        'provincia origen' => 'origen', 'origen' => 'origen',
        'provincia destino' => 'destino', 'destino' => 'destino',
        'localidad' => 'localidad',
        'codigo postal' => 'cp', 'cp' => 'cp',
        'unidad' => 'unidad', 'unidad de medida' => 'unidad', 'tipo de bulto' => 'unidad', 'bulto' => 'unidad',
        'costo' => 'costo', 'costo unitario' => 'costo', 'precio' => 'costo', 'tarifa' => 'costo',
        'maximo' => 'maximo', 'hasta' => 'maximo', 'tope' => 'maximo',
        'vigente desde' => 'desde', 'desde' => 'desde',
        'vigente hasta' => 'hasta',
    ];

    // =====================================================================
    // EXPORTAR
    // =====================================================================

    /** Genera un XLSX con todas las tarifas cargadas. */
    public function exportar(string $rutaDestino): void
    {
        $tarifas = Tarifa::with([
            'provinciaOrigen:id,nombre', 'provinciaDestino:id,nombre',
            'localidadDestino:id,nombre,codigo_postal',
            'tipoServicio:id,codigo,nombre', 'unidadMedida:id,codigo,nombre',
        ])
            ->orderBy('tipo_servicio_id')->orderBy('provincia_origen_id')
            ->orderBy('provincia_destino_id')->orderBy('localidad_destino_id')
            ->orderBy('unidad_medida_id')->orderBy('maximo')
            ->get();

        $writer = new Writer();
        $writer->openToFile($rutaDestino);
        $writer->getCurrentSheet()->setName('Tarifas');
        $writer->addRow(Row::fromValues(self::ENCABEZADOS));

        foreach ($tarifas as $t) {
            $writer->addRow(Row::fromValues([
                $t->tipoServicio?->nombre,
                $t->provinciaOrigen?->nombre,
                $t->provinciaDestino?->nombre,
                $t->localidadDestino?->nombre,
                $t->localidadDestino?->codigo_postal,
                $t->unidadMedida?->nombre,
                (float) $t->costo_unitario,
                $t->maximo !== null ? (float) $t->maximo : null,
                $t->vigente_desde?->format('Y-m-d'),
                $t->vigente_hasta?->format('Y-m-d'),
            ]));
        }

        $writer->close();
    }

    /** Genera una plantilla con filas de ejemplo y una hoja de referencias. */
    public function plantilla(string $rutaDestino): void
    {
        $writer = new Writer();
        $writer->openToFile($rutaDestino);
        $writer->getCurrentSheet()->setName('Tarifas');
        $writer->addRow(Row::fromValues(self::ENCABEZADOS));

        $hoy = now()->toDateString();
        foreach ([
            ['Primera milla', 'Santa Fe', null, 'Reconquista', null, 'Kilogramo', 10, 100, $hoy, null],
            ['Primera milla', 'Santa Fe', null, 'Reconquista', null, 'Kilogramo', 8, null, $hoy, null],
            ['Troncal', 'Santa Fe', 'Córdoba', null, null, 'Kilogramo', 25, 100, $hoy, null],
            ['Troncal', 'Santa Fe', 'Córdoba', null, null, 'Kilogramo', 20, null, $hoy, null],
            ['Última milla', null, 'Córdoba', 'Córdoba', null, 'Bulto', 1500, null, $hoy, null],
        ] as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Referencias');
        $writer->addRow(Row::fromValues(['Instrucciones']));
        foreach ([
            'Una fila por escalón de tarifa. Columnas obligatorias: Servicio, Unidad, Costo.',
            'Servicio: Troncal, Primera milla, Última milla (o Puerta a puerta).',
            'Troncal: completar Provincia origen y Provincia destino (Localidad vacía).',
            'Primera milla: completar Provincia origen y Localidad (de origen).',
            'Última milla: completar Provincia destino y Localidad (vacía = toda la provincia).',
            'Máximo: tope del escalón. Vacío = escalón abierto (sin tope).',
            'Vigente desde vacío = hoy. Vigente hasta vacío = sin vencimiento.',
            'Si ya existe una tarifa igual (mismo servicio, ruta, localidad, unidad y máximo) se actualiza; si no, se crea.',
            'Código postal es opcional: sirve para distinguir localidades con el mismo nombre.',
        ] as $linea) {
            $writer->addRow(Row::fromValues([$linea]));
        }

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Servicios válidos']));
        foreach (TipoServicio::whereIn('codigo', self::SERVICIOS_IMPORTABLES)->orderBy('id')->get() as $s) {
            $writer->addRow(Row::fromValues([$s->nombre]));
        }

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Unidades válidas']));
        foreach (UnidadMedida::where('activo', true)->orderBy('id')->get() as $u) {
            $writer->addRow(Row::fromValues([$u->nombre]));
        }

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Provincias válidas']));
        foreach (Provincia::orderBy('nombre')->get(['nombre']) as $p) {
            $writer->addRow(Row::fromValues([$p->nombre]));
        }

        $writer->close();
    }

    // =====================================================================
    // IMPORTAR
    // =====================================================================

    /**
     * Importa tarifas desde un XLSX. Es "todo o nada": si alguna fila tiene
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
        $proveedorId = Proveedor::principal()?->id;

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
            return ['creadas' => 0, 'actualizadas' => 0, 'errores' => ['El archivo no tiene filas de tarifas para importar.']];
        }

        if ($errores !== []) {
            return ['creadas' => 0, 'actualizadas' => 0, 'errores' => $errores];
        }

        $creadas = 0;
        $actualizadas = 0;

        DB::transaction(function () use ($registros, $proveedorId, $usuarioId, &$creadas, &$actualizadas) {
            $vistas = [];

            foreach ($registros as $reg) {
                $clave = [
                    'proveedor_id'         => $proveedorId,
                    'tipo_servicio_id'     => $reg['tipo_servicio_id'],
                    'provincia_origen_id'  => $reg['provincia_origen_id'],
                    'provincia_destino_id' => $reg['provincia_destino_id'],
                    'localidad_destino_id' => $reg['localidad_destino_id'],
                    'unidad_medida_id'     => $reg['unidad_medida_id'],
                    'maximo'               => $reg['maximo'],
                ];

                $query = Tarifa::query();
                foreach ($clave as $campo => $valor) {
                    $valor === null ? $query->whereNull($campo) : $query->where($campo, $valor);
                }
                $existente = $query->orderByDesc('id')->first();

                $valores = [
                    'costo_unitario' => $reg['costo_unitario'],
                    'vigente_desde'  => $reg['vigente_desde'],
                    'vigente_hasta'  => $reg['vigente_hasta'],
                ];

                if ($existente) {
                    $existente->update($valores + ['updated_by' => $usuarioId]);
                    $actualizadas++;
                } else {
                    Tarifa::create($clave + $valores + ['created_by' => $usuarioId]);
                    $creadas++;
                }
            }
        });

        return ['creadas' => $creadas, 'actualizadas' => $actualizadas, 'errores' => []];
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
                            return ['error' => 'No se encontraron los encabezados esperados en la primera fila (Servicio, Unidad, Costo, ...). Descargá la plantilla para ver el formato.'];
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

        foreach (['servicio', 'unidad', 'costo'] as $obligatorio) {
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
        // Servicio
        $servicio = $cat['servicios'][$this->normalizar((string) ($f['servicio'] ?? ''))] ?? null;
        if (! $servicio) {
            return 'servicio "' . ($f['servicio'] ?? '') . '" no válido (usá Troncal, Primera milla o Última milla).';
        }
        $codigo = $servicio['codigo'];

        // Unidad
        $unidadId = $cat['unidades'][$this->normalizar((string) ($f['unidad'] ?? ''))] ?? null;
        if (! $unidadId) {
            return 'unidad "' . ($f['unidad'] ?? '') . '" no válida.';
        }

        // Costo / máximo
        $costo = $this->numero($f['costo'] ?? null);
        if ($costo === null || $costo < 0) {
            return 'costo inválido.';
        }

        $maximo = null;
        if (($f['maximo'] ?? null) !== null) {
            $maximo = $this->numero($f['maximo']);
            if ($maximo === null || $maximo < 0) {
                return 'máximo inválido.';
            }
        }

        // Provincias
        $origenTxt  = $f['origen'] ?? null;
        $destinoTxt = $f['destino'] ?? null;

        // Primera milla: sólo importa origen. Última milla: sólo destino.
        if ($codigo === 'PRIMERA_MILLA' && $origenTxt === null) {
            return 'la primera milla requiere Provincia origen.';
        }
        if ($codigo === 'ULTIMA_MILLA' && $destinoTxt === null) {
            return 'la última milla requiere Provincia destino.';
        }
        if (in_array($codigo, ['TRONCAL', 'PUERTA_PUERTA'], true) && ($origenTxt === null || $destinoTxt === null)) {
            return 'requiere Provincia origen y Provincia destino.';
        }

        $origenId  = $origenTxt !== null ? ($cat['provincias'][$this->normalizar((string) $origenTxt)] ?? null) : null;
        $destinoId = $destinoTxt !== null ? ($cat['provincias'][$this->normalizar((string) $destinoTxt)] ?? null) : null;

        if ($origenTxt !== null && ! $origenId) {
            return 'provincia origen "' . $origenTxt . '" no encontrada.';
        }
        if ($destinoTxt !== null && ! $destinoId) {
            return 'provincia destino "' . $destinoTxt . '" no encontrada.';
        }

        // El schema exige ambas provincias: se completa la que no aplica.
        $origenId  ??= $destinoId;
        $destinoId ??= $origenId;

        // Localidad
        $localidadId = null;
        $localidadTxt = $f['localidad'] ?? null;

        if ($codigo === 'TRONCAL' && $localidadTxt !== null) {
            return 'el troncal es de provincia a provincia: dejá Localidad vacía.';
        }
        if ($codigo === 'PRIMERA_MILLA' && $localidadTxt === null) {
            return 'la primera milla requiere Localidad de origen.';
        }

        if ($localidadTxt !== null) {
            $provinciaLoc = $codigo === 'PRIMERA_MILLA' ? $origenId : $destinoId;
            $localidadId = $this->buscarLocalidad($provinciaLoc, (string) $localidadTxt, $f['cp'] ?? null, $cat);
            if (! $localidadId) {
                return 'localidad "' . $localidadTxt . '" no encontrada en la provincia indicada.';
            }
        }

        // Vigencia
        $desde = $this->fecha($f['desde'] ?? null) ?? now()->toDateString();
        $hasta = null;
        if (($f['hasta'] ?? null) !== null) {
            $hasta = $this->fecha($f['hasta']);
            if (! $hasta) {
                return 'fecha "Vigente hasta" inválida (usá AAAA-MM-DD o DD/MM/AAAA).';
            }
            if ($hasta < $desde) {
                return '"Vigente hasta" no puede ser anterior a "Vigente desde".';
            }
        }
        if (($f['desde'] ?? null) !== null && $this->fecha($f['desde']) === null) {
            return 'fecha "Vigente desde" inválida (usá AAAA-MM-DD o DD/MM/AAAA).';
        }

        return [
            'tipo_servicio_id'     => $servicio['id'],
            'provincia_origen_id'  => $origenId,
            'provincia_destino_id' => $destinoId,
            'localidad_destino_id' => $localidadId,
            'unidad_medida_id'     => $unidadId,
            'costo_unitario'       => round($costo, 2),
            'maximo'               => $maximo !== null ? round($maximo, 2) : null,
            'vigente_desde'        => $desde,
            'vigente_hasta'        => $hasta,
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
        $servicios = [];
        foreach (TipoServicio::whereIn('codigo', self::SERVICIOS_IMPORTABLES)->get() as $s) {
            $datos = ['id' => $s->id, 'codigo' => $s->codigo];
            $servicios[$this->normalizar($s->codigo)] = $datos;
            $servicios[$this->normalizar($s->nombre)] = $datos;
        }

        $unidades = [];
        foreach (UnidadMedida::where('activo', true)->get() as $u) {
            $unidades[$this->normalizar($u->codigo)] = $u->id;
            $unidades[$this->normalizar($u->nombre)] = $u->id;
        }

        $provincias = [];
        foreach (Provincia::all(['id', 'nombre']) as $p) {
            $provincias[$this->normalizar($p->nombre)] = $p->id;
        }

        return ['servicios' => $servicios, 'unidades' => $unidades, 'provincias' => $provincias, 'localidades' => []];
    }

    /** Minúsculas, sin acentos, "_" como espacio y espacios colapsados. */
    private function normalizar(string $texto): string
    {
        $t = Str::of($texto)->ascii()->lower()->replace('_', ' ')->squish();

        return (string) $t;
    }

    private function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $s = preg_replace('/[^\d,.\-]/', '', (string) $valor);
        if ($s === '' || $s === null) {
            return null;
        }

        // "1.234,56" → 1234.56 ; "1234,56" → 1234.56 ; "1,234.56" → 1234.56
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = strrpos($s, ',') > strrpos($s, '.')
                ? str_replace(['.', ','], ['', '.'], $s)
                : str_replace(',', '', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        if (is_numeric($valor)) {
            // Serial de Excel
            $dias = (int) floor((float) $valor);
            if ($dias < 1) {
                return null;
            }

            return (new \DateTimeImmutable('1899-12-30'))->modify("+{$dias} days")->format('Y-m-d');
        }

        $s = trim((string) $valor);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            $d = \DateTimeImmutable::createFromFormat('!' . $formato, $s);
            if ($d && $d->format($formato) === $s) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }
}
