<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona FKs que não puderam ser criadas na ordem original
     * por dependências circulares entre tabelas.
     */
    public function up(): void
    {
        // FK: movimentacoes_estoque.pedido_id → pedidos
        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->foreign('pedido_id')
                  ->references('id')
                  ->on('pedidos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
        });
    }
};
