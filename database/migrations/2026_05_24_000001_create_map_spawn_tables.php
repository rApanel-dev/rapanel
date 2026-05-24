<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_sizes', function (Blueprint $table) {
            $table->string('map_name', 32)->primary();
            $table->unsignedSmallInteger('width')->default(0);
            $table->unsignedSmallInteger('height')->default(0);
            $table->timestamps();
        });

        Schema::create('spawn_entries', function (Blueprint $table) {
            $table->id();
            $table->string('map_name', 32)->index();
            $table->unsignedSmallInteger('mob_id');
            $table->string('mob_name', 64)->default('');
            $table->unsignedSmallInteger('x')->default(0);
            $table->unsignedSmallInteger('y')->default(0);
            $table->unsignedSmallInteger('rx')->default(0);
            $table->unsignedSmallInteger('ry')->default(0);
            $table->unsignedTinyInteger('amount')->default(1);
            $table->unsignedInteger('delay1')->default(0);
            $table->unsignedInteger('delay2')->default(0);
            $table->timestamps();

            $table->index('mob_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spawn_entries');
        Schema::dropIfExists('map_sizes');
    }
};
