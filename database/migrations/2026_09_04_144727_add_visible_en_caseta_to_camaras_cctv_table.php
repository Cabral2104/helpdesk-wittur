<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('camaras_cctv', function (Blueprint $table) {
            // Agregamos la columna booleana (por defecto 0 = No visible en caseta)
            $table->boolean('visible_en_caseta')->default(0)->after('estatus_red');
        });
    }

    public function down()
    {
        Schema::table('camaras_cctv', function (Blueprint $table) {
            $table->dropColumn('visible_en_caseta');
        });
    }
};