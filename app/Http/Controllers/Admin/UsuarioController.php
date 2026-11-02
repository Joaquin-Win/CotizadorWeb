<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MargenGanancia;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /**
     * Lista todos los usuarios del sistema (admin y clientes).
     */
    public function index(): Response
    {
        $usuarios = User::with('cliente')
            ->orderBy('rol_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'rol_id', 'cliente_id', 'activo', 'ultimo_acceso', 'created_at']);

        $tiposCliente = TipoCliente::orderBy('nombre')->get(['id', 'codigo', 'nombre']);

        $clientes = \App\Models\Cliente::with(['tipoCliente:id,nombre', 'usuarios:id,name,email,cliente_id,activo'])
            ->orderBy('razon_social')
            ->get(['id', 'razon_social', 'nombre_fantasia', 'cuit', 'email_facturacion', 'tipo_cliente_id']);

        return Inertia::render('admin/usuarios/index', [
            'usuarios'     => $usuarios,
            'clientes'     => $clientes,
            'tiposCliente' => $tiposCliente,
        ]);
    }

    /**
     * Muestra el formulario de edición de un usuario.
     */
    public function edit(string $id): Response
    {
        $usuario = User::with('cliente')->findOrFail($id);

        $tiposCliente = TipoCliente::orderBy('nombre')->get(['id', 'codigo', 'nombre']);

        // Márgenes vigentes del tipo de cliente del usuario (si tiene cliente asociado)
        $tipoClienteId = $usuario->cliente?->tipo_cliente_id;
        $margenes = [];
        if ($tipoClienteId) {
            $margenes = MargenGanancia::with('tipoServicio:id,codigo,nombre')
                ->where('tipo_cliente_id', $tipoClienteId)
                ->orderBy('id')
                ->get()
                ->toArray();
        } else {
            // Para admin sin cliente, mostrar márgenes globales
            $margenes = MargenGanancia::with('tipoServicio:id,codigo,nombre')
                ->whereNull('tipo_cliente_id')
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        return Inertia::render('admin/usuarios/edit', [
            'usuario'      => $usuario,
            'tiposCliente' => $tiposCliente,
            'margenes'     => $margenes,
        ]);
    }

    /**
     * Actualiza nombre, email y/o contraseña de un usuario.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:usuarios,email,{$id},id"],
            // Descuento por cliente (opcional en el payload: la pestaña de contraseña no lo envía)
            'tipo_descuento'  => ['sometimes', 'in:porcentaje,fijo,ninguno'],
            'valor_descuento' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];

        // Solo validar contraseña si se envió
        if ($request->filled('password')) {
            $rules['password']              = ['required', 'confirmed', Password::min(8)];
            $rules['password_confirmation'] = ['required'];
        }

        $data = $request->validate($rules);

        if (($data['tipo_descuento'] ?? null) === 'porcentaje' && (float) ($data['valor_descuento'] ?? 0) > 100) {
            return back()->withErrors(['valor_descuento' => 'El porcentaje no puede superar 100.'])->withInput();
        }

        DB::transaction(function () use ($usuario, $data, $request) {
            $usuario->name  = $data['name'];
            $usuario->email = $data['email'];

            if (isset($data['tipo_descuento'])) {
                $usuario->tipo_descuento  = $data['tipo_descuento'];
                $usuario->valor_descuento = $data['tipo_descuento'] === 'ninguno'
                    ? 0
                    : (float) ($data['valor_descuento'] ?? 0);
            }

            if ($request->filled('password')) {
                $usuario->password = Hash::make($data['password']);
            }

            $usuario->updated_by = auth()->id();
            $usuario->save();
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Actualiza solo el descuento comercial del usuario (llamado desde lista de clientes).
     */
    public function updateDescuento(Request $request, string $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);

        $this->abortSiAdmin($usuario);

        $data = $request->validate([
            'tipo_descuento'  => ['required', 'in:porcentaje,fijo,ninguno'],
            'valor_descuento' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        if ($data['tipo_descuento'] === 'porcentaje' && (float) ($data['valor_descuento'] ?? 0) > 100) {
            return back()->withErrors(['valor_descuento' => 'El porcentaje no puede superar 100.']);
        }

        $usuario->tipo_descuento  = $data['tipo_descuento'];
        $usuario->valor_descuento = $data['tipo_descuento'] === 'ninguno'
            ? 0
            : (float) ($data['valor_descuento'] ?? 0);
        $usuario->updated_by = auth()->id();
        $usuario->save();

        return back()->with('success', 'Descuento actualizado.');
    }

    /**
     * Actualiza los márgenes de ganancia asociados al tipo de cliente del usuario.
     */
    public function updateMargenes(Request $request, string $id): RedirectResponse
    {
        $usuario = User::with('cliente')->findOrFail($id);

        $request->validate([
            'margenes'               => ['required', 'array'],
            'margenes.*.id'          => ['required', 'integer', 'exists:margenes_ganancia,id'],
            'margenes.*.porcentaje'  => ['required', 'numeric', 'min:0', 'max:999'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('margenes') as $m) {
                MargenGanancia::where('id', $m['id'])->update([
                    'porcentaje'  => $m['porcentaje'],
                    'updated_by'  => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Márgenes actualizados correctamente.');
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function toggleActivo(string $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);
        $this->abortSiAdmin($usuario);
        $usuario->activo = ! $usuario->activo;
        $usuario->save();

        $estado = $usuario->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Usuario {$estado} correctamente.");
    }

    /**
     * Fija el estado de un usuario desde el dropdown (con confirmación en el front).
     */
    public function setEstado(Request $request, string $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);
        $this->abortSiAdmin($usuario);

        $data = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $usuario->activo = $data['activo'];
        $usuario->save();

        $estado = $usuario->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Usuario {$estado} correctamente.");
    }

    // Métodos requeridos por el resource pero no usados actualmente
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    /**
     * Elimina (soft delete) la cuenta de un usuario. Un administrador no puede
     * eliminarse ni ser eliminado: responde 403.
     */
    public function destroy(string $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);
        $this->abortSiAdmin($usuario);
        abort_if($usuario->id === auth()->id(), 403, 'No podés eliminar tu propia cuenta.');

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Los administradores no se pueden eliminar ni desactivar.
     */
    private function abortSiAdmin(User $usuario): void
    {
        abort_if($usuario->esAdmin(), 403, 'Un administrador no puede eliminarse ni desactivarse.');
    }
}
