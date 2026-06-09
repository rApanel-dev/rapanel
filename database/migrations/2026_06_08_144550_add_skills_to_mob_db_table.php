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
        Schema::table('mob_db', function (Blueprint $table) {
            $table->json('skills')->nullable()->after('mvp_drops');
        });
    }

    public function down(): void
    {
        Schema::table('mob_db', function (Blueprint $table) {
            $table->dropColumn('skills');
        });
    }
};
