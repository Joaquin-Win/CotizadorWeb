<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Documento;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
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
    public function store(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;

        $validated = $request->validate([
            'tipo_documento_id' => ['required', 'integer', 'exists:tipos_documento,id'],
            'numero_documento' => ['required', 'string', 'max:50'],
            'fecha' => ['required', 'date'],
            'archivo' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ]);

        $path = $request->file('archivo')->store('documentos', 'local');

        try {
            $client->documentos()->create([
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documento cargado.']);

        return to_route('portal.documentos', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Baja el archivo original.
     */
    public function descargar(Team $current_team, Client $client, Documento $documento): BinaryFileResponse
    {
        abort_if($documento->cliente_id !== $client->id, 404);

        return response()->download(
            Storage::disk('local')->path($documento->url_archivo),
            "{$documento->tipo->nombre}-{$documento->numero_documento}.".pathinfo($documento->url_archivo, PATHINFO_EXTENSION)
        );
    }

    /**
     * Elimina el comprobante y su archivo.
     */
    public function destroy(Team $current_team, Client $client, Documento $documento): RedirectResponse
    {
        $team = $current_team;

        abort_if($documento->cliente_id !== $client->id, 404);

        Storage::disk('local')->delete($documento->url_archivo);
        $documento->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documento eliminado.']);

        return to_route('portal.documentos', ['current_team' => $team->slug, 'client' => $client->id]);
    }
}
