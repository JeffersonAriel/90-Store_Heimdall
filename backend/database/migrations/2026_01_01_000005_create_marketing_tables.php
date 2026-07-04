<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Uso de Cupons ─────────────────────────────────────────
        Schema::create('uso_cupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cupom_id', 'cliente_id', 'pedido_id']);
        });

        // ─── Pontos de Fidelidade ──────────────────────────────────
        Schema::create('pontos_fidelidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->enum('tipo', ['acumulo', 'resgate', 'expirado', 'bonus', 'indicacao']);
            $table->integer('quantidade')->comment('Positivo=ganhou, Negativo=gastou');
            $table->string('descricao');
            $table->timestamp('expira_em')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'tipo']);
        });

        // ─── Regras de Pontos ──────────────────────────────────────
        Schema::create('regras_pontos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['compra', 'indicacao', 'aniversario', 'bonus']);
            $table->decimal('multiplicador', 5, 2)->default(1)->comment('Pontos por R$ gasto');
            $table->decimal('valor_por_ponto', 8, 4)->default(0.01)->comment('Valor R$ de cada ponto no resgate');
            $table->integer('minimo_pontos_resgate')->default(100);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ─── Indicações ────────────────────────────────────────────
        Schema::create('indicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_indicador_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_indicado_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->decimal('recompensa_indicador', 10, 2)->default(0)->comment('R$ ou pontos');
            $table->decimal('recompensa_indicado', 10, 2)->default(0);
            $table->enum('status', ['pendente', 'concluida', 'expirada'])->default('pendente');
            $table->timestamps();

            $table->index('cliente_indicador_id');
        });

        // ─── Avaliações de Produto ─────────────────────────────────
        Schema::create('avaliacoes_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->unsignedTinyInteger('estrelas'); // 1-5
            $table->string('titulo', 100)->nullable();
            $table->text('comentario')->nullable();
            $table->boolean('aprovado')->default(false)->comment('Moderação pelo Heimdall');
            $table->timestamps();

            $table->unique(['produto_id', 'cliente_id', 'pedido_id']);
            $table->index(['produto_id', 'aprovado']);
        });

        // ─── Devoluções / Trocas ───────────────────────────────────
        Schema::create('devolucoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->foreignId('item_pedido_id')->nullable()->constrained('itens_pedido')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->enum('tipo', ['devolucao', 'troca'])->default('devolucao');
            $table->enum('status', ['solicitada', 'aprovada', 'recusada', 'concluida'])->default('solicitada');
            $table->text('motivo');
            $table->string('produto_substituto')->nullable()->comment('Para trocas');
            $table->decimal('valor_reembolso', 10, 2)->nullable();
            $table->text('observacao_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucoes');
        Schema::dropIfExists('avaliacoes_produto');
        Schema::dropIfExists('indicacoes');
        Schema::dropIfExists('regras_pontos');
        Schema::dropIfExists('pontos_fidelidade');
        Schema::dropIfExists('uso_cupons');
    }
};
