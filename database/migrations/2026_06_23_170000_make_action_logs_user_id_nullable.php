<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite user_id NULL en action_logs para poder registrar intentos de
     * login fallidos (login_failed) cuando no hay un usuario asociado.
     * Antes era NOT NULL (foreignId()->constrained()) y el listener
     * LogFailedLogin reventaba con un error de integridad → 500 en el login.
     *
     * Usa la conexión por defecto (igual que la migración que creó la tabla).
     */
    public function up(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverso: requiere que no existan filas con user_id NULL.
        Schema::table('action_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
