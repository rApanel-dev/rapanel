<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('woe_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->nullable();
            $table->unsignedTinyInteger('type')->default(1); // 1=WOE1/FE, 2=WOE2/SE, 3=WOE TE
            $table->unsignedTinyInteger('start_day');        // 0=Sun ... 6=Sat
            $table->time('start_time');
            $table->unsignedTinyInteger('end_day');
            $table->time('end_time');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woe_schedules');
    }
};
