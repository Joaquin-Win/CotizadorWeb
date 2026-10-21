<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificacionesController extends Controller
{
    /** Página "Notificaciones" (últimas 100, leídas y no leídas). */
    public function vista(Request $request): Response
    {
        return Inertia::render('admin/notificaciones', [
            'notificaciones' => $request->user()->notifications()->limit(100)->get()
                ->map(fn ($n) => $this->formatear($n))->values(),
        ]);
    }

    /** GET /api/admin/notifications — no leídas. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'data'  => $user->unreadNotifications()->limit(100)->get()
                ->map(fn ($n) => $this->formatear($n))->values(),
        ]);
    }

    /** POST /api/admin/notifications/{id}/read */
    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    /** POST /api/admin/notifications/read-all */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['count' => 0]);
    }

    /** @return array<string, mixed> */
    private function formatear(DatabaseNotification $n): array
    {
        return [
            'id'         => $n->id,
            'codigo'     => $n->data['codigo'] ?? null,
            'cliente'    => $n->data['cliente'] ?? null,
            'url'        => $n->data['url'] ?? null,
            'leida'      => $n->read_at !== null,
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
