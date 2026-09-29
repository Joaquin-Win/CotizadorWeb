<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el usuario administrador del sistema.
 * Credenciales guardadas en: CREDENCIALES_ADMIN.md (raíz del proyecto)
 *
 * User::$table = 'usuarios' (ya definido en app/Models/User.php)
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cotizador.com'],
            [
                'rol_id'            => 1,
                'name'              => 'Admin',
                'email'             => 'admin@cotizador.com',
                'password'          => Hash::make('admin123'),
                'email_verified_at' => now(),
                'activo'            => true,
            ]
        );

        $this->command->info('✅ Usuario admin creado: admin@cotizador.com / admin123');
    }
}
