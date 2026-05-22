<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mvp_cards', function (Blueprint $table) {
            $table->unsignedInteger('mob_id')->nullable()->after('item_id');
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('mvp_cards', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('mob_id');
            $table->dropColumn('mob_id');
        });
    }
};
