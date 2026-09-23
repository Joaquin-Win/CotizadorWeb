<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los miembros e invitadores de equipo viven en `usuarios`,
     * no en la vieja `users`. Reapunta ambas FK.
     */
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->foreign('invited_by')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->foreign('invited_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
