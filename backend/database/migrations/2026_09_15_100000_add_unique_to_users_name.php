<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El nombre pasa a ser también el identificador de acceso: en el móvil
 * teclear "Jaime" es bastante mejor que teclear un correo, y el nombre ya
 * es la identidad del miembro en toda la interfaz (avatares, "a nombre
 * de…", histórico). Para eso tiene que ser único.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidos = DB::table('users')
            ->selectRaw('LOWER(name) as nombre')
            ->groupByRaw('LOWER(name)')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nombre');

        // Sin esto el fallo llegaría como un error de índice duplicado del
        // motor, que no dice qué hacer.
        if ($repetidos->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede aplicar la migración: el nombre pasa a ser el '
                .'identificador de acceso y hay miembros que lo repiten ('
                .$repetidos->join(', ').'). Renómbralos antes de migrar.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
