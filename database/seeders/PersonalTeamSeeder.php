<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un Team personal para cada usuario que no tenga uno.
 * Correr después de haber creado usuarios con AdminUserSeeder.
 *
 * Uso: php artisan db:seed --class=PersonalTeamSeeder
 */
class PersonalTeamSeeder extends Seeder
{
    public function run(): void
    {
        User::withoutGlobalScopes()->get()->each(function (User $user) {
            // Si ya tiene un team personal, no hacer nada
            $existing = Team::whereHas('memberships', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('role', TeamRole::Owner->value);
            })->where('is_personal', true)->first();

            if ($existing) {
                $this->command->info("  ⏭  Usuario {$user->email} ya tiene team personal.");
                return;
            }

            // Crear el team personal
            $team = Team::create([
                'name'        => $user->name,
                'is_personal' => true,
            ]);

            // Agregar al usuario como owner
            $team->members()->attach($user->id, ['role' => TeamRole::Owner->value]);

            // Establecerlo como team activo
            $user->update(['current_team_id' => $team->id]);

            $this->command->info("  ✅ Team personal creado para {$user->email}: [{$team->slug}]");
        });
    }
}
