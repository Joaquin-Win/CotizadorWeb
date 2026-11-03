<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\Provincia;
use App\Models\Tarifa;
use App\Models\TipoServicio;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Services\Cotizador\TarifasExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tarifario personalizado de un cliente (tarifas con user_id = cliente).
 *
 * Las tarifas generales (user_id NULL) se gestionan en CotizadorConfiguracionController.
 * El motor de cotización prioriza estas tarifas cuando el cliente autenticado las tiene
 * (ver TarifaResolver).
 */
class UsuarioTarifaController extends Controller
{
    private const CODIGOS_EXCLUIDOS = ['EXPRESO', 'FLEX', 'PUERTA_PUERTA'];

    /** Lista las tarifas exclusivas del cliente + catálogos para el formulario. */
    public function index(User $user): JsonResponse
    {
        $tarifas = Tarifa::deUsuario($user->id)
            ->with([
                'provinciaOrigen:id,nombre',
                'provinciaDestino:id,nombre',
                'localidadDestino:id,nombre,codigo_postal',
                'tipoServicio:id,codigo,nombre',
                'unidadMedida:id,codigo,nombre',
            ])
            ->latest('id')
            ->get();

        return response()->json([
            'usuario'        => $user->only(['id', 'name', 'email']),
            'tarifas'        => $tarifas,
            'provincias'     => Provincia::orderBy('nombre')->get(['id', 'nombre']),
            'tiposServicio'  => TipoServicio::where('activo', true)
                ->whereNotIn('codigo', self::CODIGOS_EXCLUIDOS)
                ->get(['id', 'codigo', 'nombre']),
            'unidadesMedida' => UnidadMedida::where('activo', true)
                ->whereIn('codigo', ['KG', 'M3', 'PALLET', 'BULTO'])
                ->get(['id', 'codigo', 'nombre']),
        ]);
    }

    /** Crea una tarifa exclusiva para el cliente. */
    public function store(Request $request, User $user): JsonResponse
    {
        $data = $this->validar($request);

        if ($error = $this->validarReglas($data)) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $error], 422);
        }

        $tarifa = Tarifa::create($data + [
            'user_id'      => $user->id,
            'proveedor_id' => Proveedor::principal()?->id,
            'created_by'   => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'tarifa' => $tarifa], 201);
    }

    /** Actualiza una tarifa exclusiva del cliente. */
    public function update(Request $request, User $user, Tarifa $tarifa): JsonResponse
    {
        $this->abortSiNoEsDelUsuario($user, $tarifa);

        $data = $this->validar($request);

        if ($error = $this->validarReglas($data)) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $error], 422);
        }

        $tarifa->update($data + ['updated_by' => auth()->id()]);

        return response()->json(['ok' => true, 'tarifa' => $tarifa->fresh()]);
    }

    /** Elimina una tarifa exclusiva del cliente. */
    public function destroy(User $user, Tarifa $tarifa): JsonResponse
    {
        $this->abortSiNoEsDelUsuario($user, $tarifa);

        $tarifa->delete();

        return response()->json(['ok' => true]);
    }

    /** Excel con las tarifas del cliente (mismo formato que el general). */
    public function exportar(User $user, TarifasExcelService $excel): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'tarifas_cliente_') . '.xlsx';
        $excel->exportar($ruta, $user->id);

        return response()->download($ruta, "tarifas-cliente-{$user->id}-" . now()->format('Y-m-d') . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    /** Plantilla de importación (idéntica a la general). */
    public function plantilla(TarifasExcelService $excel): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'plantilla_') . '.xlsx';
        $excel->plantilla($ruta);

        return response()->download($ruta, 'plantilla-tarifas.xlsx')->deleteFileAfterSend(true);
    }

    /** Importa un Excel creando/actualizando SOLO tarifas de este cliente. */
    public function importar(Request $request, User $user, TarifasExcelService $excel): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'archivo.required' => 'Seleccioná un archivo Excel (.xlsx).',
            'archivo.mimes'    => 'El archivo debe ser un Excel (.xlsx).',
            'archivo.max'      => 'El archivo no puede superar los 10 MB.',
        ]);

        try {
            $resultado = $excel->importar($request->file('archivo')->getRealPath(), auth()->id(), $user->id);
        } catch (\Throwable $e) {
            report($e);
            $resultado = [
                'creadas' => 0, 'actualizadas' => 0,
                'errores' => ['No se pudo leer el archivo. Verificá que sea un .xlsx válido y con el formato de la plantilla.'],
            ];
        }

        return response()->json([
            'ok'            => $resultado['errores'] === [],
            'creadas'       => $resultado['creadas'],
            'actualizadas'  => $resultado['actualizadas'],
            'total_errores' => count($resultado['errores']),
            'errores'       => array_slice($resultado['errores'], 0, 50),
        ]);
    }

    // ------------------------------------------------------------------

    private function abortSiNoEsDelUsuario(User $user, Tarifa $tarifa): void
    {
        abort_if((int) $tarifa->user_id !== (int) $user->id, 404);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'provincia_origen_id'  => ['required', 'integer', 'exists:provincias,id'],
            'provincia_destino_id' => ['required', 'integer', 'exists:provincias,id'],
            'localidad_destino_id' => ['nullable', 'integer', 'exists:localidades,id'],
            'tipo_servicio_id'     => ['required', 'integer', 'exists:tipos_servicio,id'],
            'unidad_medida_id'     => ['required', 'integer', 'exists:unidades_medida,id'],
            'costo_unitario'       => ['required', 'numeric', 'min:0'],
            'maximo'               => ['nullable', 'numeric', 'min:0'],
            'max_m3_bulto'         => ['nullable', 'numeric', 'min:0'],
            'max_unidades'         => ['nullable', 'integer', 'min:1', 'gte:min_unidades'],
            'min_unidades'         => ['nullable', 'integer', 'min:1'],
            'min_m3_bulto'         => ['nullable', 'numeric', 'min:0', 'lte:max_m3_bulto'],
            'max_kg_bulto'         => ['nullable', 'numeric', 'gt:0'],
            'tiempo_estimado'      => ['nullable', 'integer', 'min:0', 'max:99999'],   // horas
            'vigente_desde'        => ['required', 'date'],
            'vigente_hasta'        => ['nullable', 'date', 'after_or_equal:vigente_desde'],
        ]);
    }

    /** Reglas de negocio iguales a las de las tarifas generales. @return array<string,string>|null */
    private function validarReglas(array $data): ?array
    {
        $tipo = TipoServicio::find($data['tipo_servicio_id']);
        if (! $tipo || ! $tipo->activo || in_array($tipo->codigo, self::CODIGOS_EXCLUIDOS, true)) {
            return ['tipo_servicio_id' => 'El tipo de servicio seleccionado no está disponible.'];
        }

        $unidad = UnidadMedida::find($data['unidad_medida_id']);
        if (! $unidad || ! $unidad->activo || ! in_array($unidad->codigo, ['KG', 'M3', 'PALLET', 'BULTO'], true)) {
            return ['unidad_medida_id' => 'La unidad de medida no es válida. Use: KG, M3, PALLET o BULTO.'];
        }

        if ($unidad->codigo === 'BULTO') {
            $errores = [];
            if (empty($data['max_m3_bulto']) || (float) $data['max_m3_bulto'] <= 0) {
                $errores['max_m3_bulto'] = 'Indicá el máximo de m³ por bulto para esta tarifa.';
            }
            if (empty($data['max_kg_bulto']) || (float) $data['max_kg_bulto'] <= 0) {
                $errores['max_kg_bulto'] = 'Indicá el máximo de kg por bulto para esta tarifa.';
            }
            if ($errores) {
                return $errores;
            }
        }

        if ($tipo->codigo === 'TRONCAL' && (int) $data['provincia_origen_id'] === (int) $data['provincia_destino_id']) {
            return ['provincia_destino_id' => 'En una tarifa TRONCAL, la provincia de origen y destino deben ser diferentes.'];
        }

        return null;
    }
}

