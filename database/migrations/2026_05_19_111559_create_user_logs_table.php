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
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Campo modificado: 'name', 'email', 'birthdate'
            $table->string('field', 30)->index();

            $table->string('old_value')->nullable();
            $table->string('new_value');

            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};
