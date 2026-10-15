<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las optimizaciones de indexación para acelerar consultas frecuentes.
     */
    public function up()
    {
        // 1. Índices en usuarios (acelera login por nómina y filtros por rol)
        if (Schema::hasTable('usuarios')) {
            Schema::table('usuarios', function (Blueprint $table) {
                if (Schema::hasColumn('usuarios', 'numero_nomina')) {
                    $table->index('numero_nomina', 'idx_usuarios_numero_nomina');
                }
                if (Schema::hasColumns('usuarios', ['status', 'rol'])) {
                    $table->index(['status', 'rol'], 'idx_usuarios_status_rol');
                }
            });
        }

        // 2. Índices en tickets (filtros principales de dashboard, ordenamiento y listados)
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                if (Schema::hasColumns('tickets', ['status', 'date_created'])) {
                    $table->index(['status', 'date_created'], 'idx_tickets_status_fecha');
                }
                if (Schema::hasColumns('tickets', ['status', 'usuario_reporta_id'])) {
                    $table->index(['status', 'usuario_reporta_id'], 'idx_tickets_status_usuario');
                }
                if (Schema::hasColumns('tickets', ['status', 'estatus'])) {
                    $table->index(['status', 'estatus'], 'idx_tickets_status_estatus');
                }
                if (Schema::hasColumns('tickets', ['status', 'categoria_id'])) {
                    $table->index(['status', 'categoria_id'], 'idx_tickets_status_categoria');
                }
            });
        }

        // 3. Índices en cámaras CCTV (acelera caseta y métricas de red)
        if (Schema::hasTable('camaras_cctv')) {
            Schema::table('camaras_cctv', function (Blueprint $table) {
                if (Schema::hasColumns('camaras_cctv', ['status', 'visible_en_caseta'])) {
                    $table->index(['status', 'visible_en_caseta'], 'idx_camaras_status_caseta');
                }
                if (Schema::hasColumns('camaras_cctv', ['status', 'estatus_red'])) {
                    $table->index(['status', 'estatus_red'], 'idx_camaras_status_red');
                }
            });
        }

        // 4. Índices en reportes de seguridad
        if (Schema::hasTable('reportes_seguridad')) {
            Schema::table('reportes_seguridad', function (Blueprint $table) {
                if (Schema::hasColumns('reportes_seguridad', ['status', 'date_created'])) {
                    $table->index(['status', 'date_created'], 'idx_reportes_status_fecha');
                } elseif (Schema::hasColumn('reportes_seguridad', 'date_created')) {
                    $table->index('date_created', 'idx_reportes_fecha');
                }
            });
        }

        // 5. Índices en equipos de inventario
        if (Schema::hasTable('equipos_inventario')) {
            Schema::table('equipos_inventario', function (Blueprint $table) {
                if (Schema::hasColumn('equipos_inventario', 'status')) {
                    $table->index('status', 'idx_equipos_status');
                }
            });
        }
    }

    /**
     * Revierte los índices agregados.
     */
    public function down()
    {
        if (Schema::hasTable('usuarios')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropIndex('idx_usuarios_numero_nomina');
                $table->dropIndex('idx_usuarios_status_rol');
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('idx_tickets_status_fecha');
                $table->dropIndex('idx_tickets_status_usuario');
                $table->dropIndex('idx_tickets_status_estatus');
                $table->dropIndex('idx_tickets_status_categoria');
            });
        }

        if (Schema::hasTable('camaras_cctv')) {
            Schema::table('camaras_cctv', function (Blueprint $table) {
                $table->dropIndex('idx_camaras_status_caseta');
                $table->dropIndex('idx_camaras_status_red');
            });
        }

        if (Schema::hasTable('reportes_seguridad')) {
            Schema::table('reportes_seguridad', function (Blueprint $table) {
                $table->dropIndex(['idx_reportes_status_fecha', 'idx_reportes_fecha']);
            });
        }

        if (Schema::hasTable('equipos_inventario')) {
            Schema::table('equipos_inventario', function (Blueprint $table) {
                $table->dropIndex('idx_equipos_status');
            });
        }
    }
};
