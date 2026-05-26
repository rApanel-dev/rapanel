<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_main')->table('login', function (Blueprint $table) {
            if (!Schema::connection('mysql_main')->hasColumn('login', 'master_id')) {
                $table->unsignedBigInteger('master_id')->nullable()->after('account_id');
                $table->index('master_id');
            }
            if (!Schema::connection('mysql_main')->hasColumn('login', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('master_id');
            }
        });
    }

    public function down(): void
    {
        // Para cuando queramos revertir los cambios
        Schema::connection('mysql_main')->table('login', function (Blueprint $table) {
            $table->dropIndex(['master_id']);
            $table->dropColumn(['master_id', 'created_at']);
        });
    }
};
