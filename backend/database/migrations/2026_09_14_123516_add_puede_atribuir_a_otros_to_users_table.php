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
        Schema::table('users', function (Blueprint $table) {
            // Permite a un usuario registrar movimientos de stock a nombre de otros
            // miembros del hogar. Sin este permiso, solo puede atribuirse a sí mismo.
            $table->boolean('puede_atribuir_a_otros')->default(false)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('puede_atribuir_a_otros');
        });
    }
};
