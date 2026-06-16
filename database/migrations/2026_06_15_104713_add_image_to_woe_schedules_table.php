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
        Schema::table('woe_schedules', function (Blueprint $table) {
            $table->string('image', 255)->nullable()->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('woe_schedules', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
