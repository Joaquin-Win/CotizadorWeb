<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Clientes\ClientesExcelService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Inertia;
use Illuminate\Support\Facades\Log;

/**
 * Plantilla, exportación e importación masiva de clientes por Excel.
 */
class ClientesExcelController extends Controller
{
    /**
     * Descarga los clientes en Excel.
     *
     * El alcance puede ser "todos" (default) o "activos".
     */
    public function exportar(Request $request, ClientesExcelService $excel): BinaryFileResponse
    {
        $alcance = $request->query('alcance', 'todos');
        if (! in_array($alcance, ['todos', 'activos'], true)) {
            $alcance = 'todos';
        }

        $ruta = tempnam(sys_get_temp_dir(), 'clientes_') . '.xlsx';
        $excel->exportar($ruta, $alcance);

        $sufijo = $alcance === 'activos' ? '-activos' : '';

        return response()->download($ruta, 'clientes' . $sufijo . '-' . now()->format('Y-m-d') . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Importa clientes desde un Excel (crea las nuevas y actualiza las existentes por CUIT).
     */
    public function importar(Request $request, ClientesExcelService $excel): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'archivo.required' => 'Seleccioná un archivo Excel (.xlsx).',
            'archivo.mimes'    => 'El archivo debe ser un Excel (.xlsx).',
            'archivo.max'      => 'El archivo no puede superar los 10 MB.',
        ]);

        try {
            $resultado = $excel->importar($request->file('archivo')->getRealPath(), auth()->id());
        } catch (\Throwable $e) {
            Log::error('[ClientesExcelController] Error al importar clientes', [
                'error' => $e->getMessage(),
                // Stack trace omitido: puede contener rutas y datos sensibles.
                // Consultar logs detallados solo en desarrollo con APP_DEBUG=true.
            ]);

            $resultado = [
                'creadas'     => 0,
                'actualizadas'=> 0,
                'errores'     => ['No se pudo leer el archivo. Verificá que sea un .xlsx válido y con el formato de la plantilla.'],
            ];
        }

        $ok = $resultado['errores'] === [];

        Inertia::flash('toast', $ok
            ? ['type' => 'success', 'message' => "Importación lista: {$resultado['creadas']} creadas, {$resultado['actualizadas']} actualizadas."]
            : ['type' => 'error', 'message' => 'No se importó nada: el archivo tiene errores.']);

        Inertia::flash('importacion', [
            'ok'           => $ok,
            'creadas'      => $resultado['creadas'],
            'actualizadas' => $resultado['actualizadas'],
            'total_errores'=> count($resultado['errores']),
            'errores'      => array_slice($resultado['errores'], 0, 50),
        ]);

        return back();
    }
}
