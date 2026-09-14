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
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('entrada_stock_id')->nullable()->constrained('entradas_stock')->nullOnDelete();
            $table->enum('tipo', ['compra', 'consumo', 'transferencia', 'correccion', 'apertura']);
            $table->decimal('cantidad', 12, 3);
            $table->foreignId('ubicacion_origen_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('ubicacion_destino_id')->nullable()->constrained('ubicaciones')->nullOnDelete();

            // Quién ejecuta la acción (de la sesión/token activo) vs. a quién se
            // atribuye el movimiento (elegible libremente en el formulario).
            $table->foreignId('usuario_registrador_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('usuario_atribuido_id')->constrained('users')->restrictOnDelete();

            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
