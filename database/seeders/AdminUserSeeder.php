<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea el usuario administrador del sistema.
     *
     * Credenciales guardadas en: CREDENCIALES_ADMIN.md (raíz del proyecto)
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cotizador.com'],
            [
                'name'              => 'Admin',
                'email'             => 'admin@cotizador.com',
                'password'          => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Usuario admin creado: admin@cotizador.com / admin123');
    }
}
