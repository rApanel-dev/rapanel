<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Creamos la tabla 'action_logs'. 
        // Laravel le pondrá el prefijo (ej: ra_action_logs) automáticamente.
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();

            // Relación con el usuario maestro del panel.
            // nullable: un login fallido (login_failed) se registra sin usuario asociado.
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users') // Referencia a la tabla 'users' (ra_users)
                  ->onDelete('cascade'); // Si se borra el usuario, se borran sus logs

            // Categoría para filtrar rápido (GAME_ACCOUNT, AUTH, SHOP, etc.)
            $table->string('category', 50)->index();

            // Descripción legible de lo que pasó
            $table->string('action');

            // Datos de red y seguridad
            $table->string('ip_address', 45); // Soporta IPv4 e IPv6
            $table->text('user_agent')->nullable(); // Navegador/Sistema Operativo

            // Campo JSON para guardar datos técnicos adicionales si los necesitas
            $table->json('metadata')->nullable();

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
