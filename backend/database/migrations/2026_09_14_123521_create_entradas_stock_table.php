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
        Schema::create('entradas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->restrictOnDelete();
            $table->decimal('cantidad_restante', 12, 3);
            $table->date('fecha_compra');
            $table->date('fecha_caducidad')->nullable();
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->boolean('abierto')->default(false);
            $table->text('nota')->nullable();
            $table->timestamps();

            // Consultas FEFO: entradas de un producto ordenadas por caducidad.
            $table->index(['producto_id', 'fecha_caducidad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_stock');
    }
};
