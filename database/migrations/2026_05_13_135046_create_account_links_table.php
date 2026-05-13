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
        Schema::create('account_links', function (Blueprint $table) {
            $table->id();
            // Relación con tu usuario de la web
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // El código que pondrán in-game (ej: RA-X8Y2Z)
            $table->string('token', 20)->unique();
            
            // Cuándo deja de ser válido el código
            $table->timestamp('expires_at');
            
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_links');
    }
};
