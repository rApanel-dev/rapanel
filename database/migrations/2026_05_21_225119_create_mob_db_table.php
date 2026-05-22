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
        Schema::create('mob_db', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('aegis_name', 50)->index();
            $table->string('name', 50);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedBigInteger('hp')->default(1);
            $table->unsignedBigInteger('base_exp')->default(0);
            $table->unsignedBigInteger('mvp_exp')->default(0);
            $table->boolean('is_mvp')->default(false)->index();
            $table->string('element', 20)->default('Neutral');
            $table->string('race', 30)->default('Formless');
            $table->string('size', 20)->default('Small');
            $table->string('class', 20)->default('Normal');
            $table->json('stats')->nullable();
            $table->json('modes')->nullable();
            $table->json('race_groups')->nullable();
            $table->json('drops')->nullable();
            $table->json('mvp_drops')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mob_db');
    }
};
