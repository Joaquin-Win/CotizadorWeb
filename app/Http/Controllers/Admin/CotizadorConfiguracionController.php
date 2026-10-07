<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionCotizador;
use App\Models\CostoAdicional;
use App\Models\Localidad;
use App\Models\Proveedor;
use App\Models\Provincia;
use App\Models\Tarifa;
use App\Models\TipoServicio;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CotizadorConfiguracionController extends Controller
{
    /**
     * Muestra la pantalla integral de configuración del Cotizador:
     * - Precios y Parámetros (seguro, IVA, costos adicionales)
     * - Tarifas de flete (escalones, rutas, servicios)
     * - Cobertura Geográfica (provincias y localidades activas/inactivas)
     */
    public function index(Request $request): Response
    {
        // 1. Asegurar proveedor principal
        $proveedor = Proveedor::principal();
        if (! $proveedor) {
            $proveedor = Proveedor::create([
                'nombre'    => 'SET Logística S.A.',
                'cuit'      => '30-71123456-9',
                'email'     => 'contacto@setlogistica.com',
                'telefono'  => '0810-122-0738',
                'activo'    => true,
            ]);
        }

        // 2. Parámetros generales
        $configuraciones = ConfiguracionCotizador::all()->keyBy('clave');

        // 3. Costos adicionales (Carga, Descarga, etc.)
        $costosAdicionales = CostoAdicional::orderBy('nombre')->get();

        // 4. Tarifas cargadas
        $tarifas = Tarifa::with([
            'provinciaOrigen:id,nombre',
            'provinciaDestino:id,nombre',
            'localidadDestino:id,nombre,codigo_postal',
            'tipoServicio:id,codigo,nombre',
            'unidadMedida:id,codigo,nombre',
        ])
            ->latest('id')
            ->get();

        // 5. Provincias con conteo de localidades totales y activas
        $provincias = Provincia::withCount([
            'localidades as total_localidades',
            'localidades as localidades_activas' => fn ($q) => $q->where('activo', true),
        ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_georef', 'tiene_deposito', 'activo']);

        // 6. Catálogos para formulario de tarifas
        $tiposServicio = TipoServicio::where('activo', true)->get(['id', 'codigo', 'nombre']);
        $unidadesMedida = UnidadMedida::where('activo', true)->get(['id', 'codigo', 'nombre']);

        return Inertia::render('admin/cotizador/configuracion', [
            'configuraciones'   => $configuraciones,
            'costosAdicionales' => $costosAdicionales,
            'tarifas'           => $tarifas,
            'provincias'        => $provincias,
            'tiposServicio'     => $tiposServicio,
            'unidadesMedida'    => $unidadesMedida,
            'proveedor'         => $proveedor,
            'activeTab'         => $request->query('tab', 'precios'),
        ]);
    }

    /**
     * Guarda los parámetros de precios (Seguro, IVA) y montos de costos adicionales.
     */
    public function updatePrecios(Request $request): RedirectResponse
    {
        $request->validate([
            'seguro_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'iva_porcentaje'    => ['required', 'numeric', 'min:0', 'max:100'],
            'popup_activo'      => ['nullable', 'boolean'],
            'popup_titulo'      => ['nullable', 'string', 'max:150'],
            'popup_mensaje'     => ['nullable', 'string', 'max:2000'],
            'costos'            => ['nullable', 'array'],
            'costos.*.id'       => ['required', 'integer', 'exists:costos_adicionales,id'],
            'costos.*.monto'    => ['required', 'numeric', 'min:0'],
            'costos.*.unidad'   => ['required', 'string', 'in:$,%'],
            'costos.*.activo'   => ['required', 'boolean'],
        ]);

        // Actualizar parámetros clave-valor
        ConfiguracionCotizador::updateOrCreate(
            ['clave' => 'seguro_porcentaje'],
            [
                'valor'       => (string) $request->input('seguro_porcentaje'),
                'tipo'        => 'NUMBER',
                'descripcion' => 'Porcentaje del valor declarado para calcular el seguro.',
                'grupo'       => 'seguro',
                'editable'    => true,
            ]
        );

        ConfiguracionCotizador::updateOrCreate(
            ['clave' => 'iva_porcentaje'],
            [
                'valor'       => (string) $request->input('iva_porcentaje'),
                'tipo'        => 'NUMBER',
                'descripcion' => 'Porcentaje de IVA aplicable.',
                'grupo'       => 'impuestos',
                'editable'    => true,
            ]
        );

        // Popup informativo del cotizador
        foreach ([
            'popup_activo'  => [$request->boolean('popup_activo') ? '1' : '0', 'BOOLEAN', 'Muestra un popup informativo al ingresar al cotizador.'],
            'popup_titulo'  => [(string) $request->input('popup_titulo', ''), 'STRING', 'Título del popup informativo.'],
            'popup_mensaje' => [(string) $request->input('popup_mensaje', ''), 'STRING', 'Mensaje del popup informativo.'],
        ] as $clave => [$valor, $tipo, $desc]) {
            ConfiguracionCotizador::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor, 'tipo' => $tipo, 'descripcion' => $desc, 'grupo' => 'popup', 'editable' => true]
            );
        }

        // Actualizar costos adicionales
        if ($request->has('costos')) {
            foreach ($request->input('costos') as $c) {
                CostoAdicional::where('id', $c['id'])->update([
                    'monto'  => $c['monto'],
                    'unidad' => $c['unidad'],
                    'activo' => $c['activo'],
                ]);
            }
        }

        return back()->with('success', 'Precios y parámetros actualizados correctamente.');
    }

    /**
     * Agrega un nuevo costo adicional personalizado.
     */
    public function storeCostoAdicional(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo'      => ['required', 'string', 'max:40', 'unique:costos_adicionales,codigo'],
            'nombre'      => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto'       => ['required', 'numeric', 'min:0'],
            'unidad'      => ['required', 'string', 'in:$,%'],
            'activo'      => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $data['created_by'] = auth()->id();

        CostoAdicional::create($data);

        return back()->with('success', 'Costo adicional registrado correctamente.');
    }

    /**
     * Elimina un costo adicional.
     */
    public function destroyCostoAdicional(CostoAdicional $costo): RedirectResponse
    {
        $costo->delete();
        return back()->with('success', 'Costo adicional eliminado.');
    }

    /**
     * Crea una nueva tarifa de flete.
     */
    public function storeTarifa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provincia_origen_id'  => ['required', 'integer', 'exists:provincias,id'],
            'provincia_destino_id' => ['required', 'integer', 'exists:provincias,id'],
            'localidad_destino_id' => ['nullable', 'integer', 'exists:localidades,id'],
            'tipo_servicio_id'     => ['required', 'integer', 'exists:tipos_servicio,id'],
            'unidad_medida_id'     => ['required', 'integer', 'exists:unidades_medida,id'],
            'costo_unitario'       => ['required', 'numeric', 'min:0'],
            'maximo'               => ['nullable', 'numeric', 'min:0'],
            'vigente_desde'        => ['required', 'date'],
            'vigente_hasta'        => ['nullable', 'date', 'after_or_equal:vigente_desde'],
        ]);

        $proveedor = Proveedor::principal();
        $data['proveedor_id'] = $proveedor?->id;
        $data['created_by']   = auth()->id();

        Tarifa::create($data);

        return back()->with('success', 'Tarifa registrada con éxito.');
    }

    /**
     * Actualiza una tarifa existente.
     */
    public function updateTarifa(Request $request, Tarifa $tarifa): RedirectResponse
    {
        $data = $request->validate([
            'provincia_origen_id'  => ['required', 'integer', 'exists:provincias,id'],
            'provincia_destino_id' => ['required', 'integer', 'exists:provincias,id'],
            'localidad_destino_id' => ['nullable', 'integer', 'exists:localidades,id'],
            'tipo_servicio_id'     => ['required', 'integer', 'exists:tipos_servicio,id'],
            'unidad_medida_id'     => ['required', 'integer', 'exists:unidades_medida,id'],
            'costo_unitario'       => ['required', 'numeric', 'min:0'],
            'maximo'               => ['nullable', 'numeric', 'min:0'],
            'vigente_desde'        => ['required', 'date'],
            'vigente_hasta'        => ['nullable', 'date', 'after_or_equal:vigente_desde'],
        ]);

        $data['updated_by'] = auth()->id();
        $tarifa->update($data);

        return back()->with('success', 'Tarifa actualizada correctamente.');
    }

    /**
     * Elimina una tarifa.
     */
    public function destroyTarifa(Tarifa $tarifa): RedirectResponse
    {
        $tarifa->delete();
        return back()->with('success', 'Tarifa eliminada.');
    }

    /**
     * Activa / desactiva una provincia completa.
     */
    public function toggleProvincia(Provincia $provincia): JsonResponse|RedirectResponse
    {
        $provincia->activo = ! $provincia->activo;
        $provincia->save();

        if (request()->wantsJson()) {
            return response()->json([
                'ok'     => true,
                'activo' => $provincia->activo,
                'nombre' => $provincia->nombre,
            ]);
        }

        return back()->with('success', "Provincia {$provincia->nombre} " . ($provincia->activo ? 'activada' : 'desactivada') . '.');
    }

    /**
     * Retorna las localidades de una provincia para el administrador (todas, activas e inactivas).
     */
    public function localidadesProvincia(Provincia $provincia, Request $request): JsonResponse
    {
        $query = Localidad::where('provincia_id', $provincia->id)->whereNull('deleted_at');

        if ($buscar = $request->query('q')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo_postal', 'like', "%{$buscar}%");
            });
        }

        $localidades = $query->orderBy('nombre')->get(['id', 'nombre', 'codigo_postal', 'codigo_georef', 'activo']);

        return response()->json([
            'provincia'   => $provincia->only(['id', 'nombre', 'activo']),
            'localidades' => $localidades,
            'total'       => $localidades->count(),
            'activas'     => $localidades->where('activo', true)->count(),
        ]);
    }

    /**
     * Activa / desactiva una localidad individual.
     */
    public function toggleLocalidad(Localidad $localidad): JsonResponse|RedirectResponse
    {
        $localidad->activo = ! $localidad->activo;
        $localidad->save();

        if (request()->wantsJson()) {
            return response()->json([
                'ok'     => true,
                'activo' => $localidad->activo,
                'nombre' => $localidad->nombre,
            ]);
        }

        return back()->with('success', "Localidad {$localidad->nombre} " . ($localidad->activo ? 'activada' : 'desactivada') . '.');
    }

    /**
     * Activa o desactiva TODAS las localidades de una provincia en lote.
     */
    public function toggleTodasLocalidades(Provincia $provincia, Request $request): JsonResponse|RedirectResponse
    {
        $activo = $request->boolean('activo', true);

        Localidad::where('provincia_id', $provincia->id)->update(['activo' => $activo]);

        if (request()->wantsJson()) {
            return response()->json([
                'ok'     => true,
                'activo' => $activo,
            ]);
        }

        return back()->with(
            'success',
            $activo
                ? "Todas las localidades de {$provincia->nombre} fueron activadas."
                : "Todas las localidades de {$provincia->nombre} fueron desactivadas."
        );
    }
}
