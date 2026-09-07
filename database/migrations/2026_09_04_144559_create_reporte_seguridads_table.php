<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. ELIMINAMOS LA TABLA ZOMBIE DEL INTENTO ANTERIOR
        Schema::dropIfExists('reportes_seguridad');

        // 2. CREAMOS LA TABLA LIMPIA
        Schema::create('reportes_seguridad', function (Blueprint $table) {
            $table->id();
            
            // Si después de esto te vuelve a dar error de Foránea, 
            // cambia 'integer' por 'unsignedInteger'
            $table->integer('camara_id')->nullable(); 
            
            $table->foreignId('usuario_reporta_id')->constrained('users')->onDelete('cascade');
            
            $table->dateTime('fecha_incidente');
            $table->string('tipo_incidente'); 
            $table->text('descripcion');
            $table->string('estatus')->default('Pendiente'); 
            
            $table->timestamp('date_created')->useCurrent();
            $table->timestamp('date_edited')->useCurrent()->useCurrentOnUpdate();
        });

        // 3. AGREGAMOS LA LLAVE FORÁNEA
        Schema::table('reportes_seguridad', function (Blueprint $table) {
            $table->foreign('camara_id')->references('id')->on('camaras_cctv')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reportes_seguridad');
    }
};