<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los triggers rompían todos los inserts (MySQL 1442: no se puede
     * actualizar la misma tabla dentro de su trigger). La garantía de
     * "un solo principal" pasa al modelo (evento Eloquent).
     */
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_ins');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_upd');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
