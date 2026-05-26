<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('drop_rates', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->integer('value')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drop_rates');
    }
};
