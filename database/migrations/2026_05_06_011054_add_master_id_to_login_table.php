<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ATENCIÓN: Usamos la conexión 'mysql_main' para que NO use el prefijo 'ra_' 
        // y modifique la tabla 'login' real del emulador.
        Schema::connection('mysql_main')->table('login', function (Blueprint $table) {
            
            // Agregamos la columna master_id. 
            // Es "nullable" (permite nulos) porque tus cuentas antiguas de rAthena 
            // aún no tendrán un dueño maestro asignado hasta que las vinculen en el panel.
            $table->unsignedBigInteger('master_id')->nullable()->after('account_id');
            
            // Creamos un índice para que buscar qué cuentas le pertenecen a un usuario sea rapidísimo
            $table->index('master_id');
        });
    }

    public function down(): void
    {
        // Para cuando queramos revertir los cambios
        Schema::connection('mysql_main')->table('login', function (Blueprint $table) {
            $table->dropIndex(['master_id']);
            $table->dropColumn('master_id');
        });
    }
};