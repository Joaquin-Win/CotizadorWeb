<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Documento;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Comprobantes de una empresa: ver, subir (pdf/png/jpg),
 * descargar y eliminar.
 */
class DocumentoController extends Controller
{
    /**
     * Guarda el archivo y crea el comprobante.
     */
    public function store(Request $request, Cliente $cliente): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_documento_id' => ['required', 'integer', 'exists:tipos_documento,id'],
            'numero_documento' => ['required', 'string', 'max:50'],
            'fecha' => ['required', 'date'],
            'archivo' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ]);

        $path = $request->file('archivo')->store('documentos', 'local');

        try {
            Documento::create([
                'cliente_id' => $cliente->id,
                'tipo_documento_id' => $validated['tipo_documento_id'],
                'punto_venta' => 1,
                'numero_documento' => $validated['numero_documento'],
                'fecha' => $validated['fecha'],
                'url_archivo' => $path,
            ]);
        } catch (QueryException $e) {
            Storage::disk('local')->delete($path);

            abort(422, 'Ese número ya existe para ese tipo de documento.');
        }

        return back()->with('success', 'Documento cargado.');
    }

    /**
     * Baja el archivo original (o lo muestra inline para previsualizar).
     */
    public function descargar(Request $request, Cliente $cliente, Documento $documento): BinaryFileResponse
    {
        abort_if($documento->cliente_id !== $cliente->id, 404);

        $path = Storage::disk('local')->path($documento->url_archivo);

        if ($request->boolean('inline')) {
            return response()->file($path);
        }

        return response()->download(
            $path,
            "{$documento->tipo->nombre}-{$documento->numero_documento}.".pathinfo($documento->url_archivo, PATHINFO_EXTENSION)
        );
    }

    /**
     * Elimina el comprobante y su archivo.
     */
    public function destroy(Cliente $cliente, Documento $documento): RedirectResponse
    {
        abort_if($documento->cliente_id !== $cliente->id, 404);

        Storage::disk('local')->delete($documento->url_archivo);
        $documento->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
