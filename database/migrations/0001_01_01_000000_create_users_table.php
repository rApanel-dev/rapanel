<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('country')->default('CL');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Campos de seguridad (2FA)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            
            // Economía Global de la Cuenta Maestra
            $table->integer('donation_points')->default(0);
            $table->integer('vote_points')->default(0);
            
            // Roles y Estado
            $table->enum('role', ['User', 'Admin'])->default('User');
            $table->integer('status')->default(1); // 1 = Activo, 0 = Baneado/Suspendido
            
            // Tokens y Timestamps
            $table->rememberToken();
            $table->timestamps();
            $table->string('api_token', 80)->unique()->nullable()->default(null);
        });

        // Opcional: Forzar a que el ID de la Cuenta Maestra empiece en 5,000,000
        DB::statement('ALTER TABLE ra_users AUTO_INCREMENT = 5000000;');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
