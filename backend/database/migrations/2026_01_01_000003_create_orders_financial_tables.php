<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Cupons (precisa existir antes de pedidos) ─────────────
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->enum('tipo', ['percent', 'fixed', 'frete']);
            $table->decimal('valor', 10, 2)->comment('% para percent, R$ para fixed, 0 para frete grátis');
            $table->decimal('valor_minimo_pedido', 10, 2)->default(0);
            $table->integer('limite_uso_total')->nullable()->comment('null = ilimitado');
            $table->integer('limite_uso_por_cliente')->default(1);
            $table->integer('usos_atuais')->default(0);
            $table->timestamp('validade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Pedidos ───────────────────────────────────────────────
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('endereco_id')->constrained('enderecos_cliente')->restrictOnDelete();
            $table->foreignId('cupom_id')->nullable()->constrained('cupons')->nullOnDelete();
            $table->enum('status', [
                'aguardando_pagamento',
                'em_separacao',
                'em_envio',
                'enviado',
                'entregue',
                'cancelado',
                'devolvido',
            ])->default('aguardando_pagamento');
            $table->string('codigo_rastreio')->nullable();
            $table->string('url_rastreio')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('desconto_cupom', 10, 2)->default(0);
            $table->decimal('desconto_pontos', 10, 2)->default(0);
            $table->decimal('valor_frete', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('observacoes')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // ─── Histórico de Status do Pedido ─────────────────────────
        Schema::create('historico_status_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('status_anterior')->nullable();
            $table->string('status_novo');
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete()
                  ->comment('null = sistema automático (webhook)');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
        });

        // ─── Itens do Pedido (com snapshot imutável) ───────────────
        Schema::create('itens_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->restrictOnDelete();
            $table->foreignId('variacao_id')->nullable()->constrained('variacoes_produto')->nullOnDelete();

            // ─ Snapshots imutáveis (congelados no momento da venda) ─
            $table->string('nome_snapshot');
            $table->string('sku_snapshot', 100);
            $table->enum('tipo_estoque_snapshot', ['proprio', 'dropshipping']);
            $table->decimal('preco_custo_snapshot', 10, 2);
            $table->decimal('preco_venda_snapshot', 10, 2);
            $table->decimal('lucro_snapshot', 10, 2)->storedAs('preco_venda_snapshot - preco_custo_snapshot');

            $table->integer('quantidade');
            $table->decimal('subtotal', 10, 2);
        });

        // ─── Pagamentos ────────────────────────────────────────────
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('gateway')->comment('mercadopago|pagseguro|stripe|manual');
            $table->string('gateway_id_externo')->nullable();
            $table->string('metodo')->nullable()->comment('pix|boleto|cartao_credito|cartao_debito');
            $table->enum('status', [
                'pendente',
                'processando',
                'aprovado',
                'recusado',
                'expirado',
                'cancelado',
                'estornado',
            ])->default('pendente');
            $table->decimal('valor', 10, 2);
            $table->json('payload_json')->nullable()->comment('Resposta da criação do pagamento');
            $table->json('webhook_json')->nullable()->comment('Último webhook recebido');
            $table->timestamp('expires_at')->nullable()->comment('Para controle de reserva de estoque');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'status']);
            $table->index('gateway_id_externo');
        });

        // ─── Confirmações Manuais de Pagamento ─────────────────────
        Schema::create('confirmacoes_pagamento_manual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('pagamento_id')->constrained('pagamentos')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamp('confirmed_at');
            $table->timestamps();
        });

        // ─── Contas Bancárias ──────────────────────────────────────
        Schema::create('contas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->string('banco');
            $table->string('agencia', 20)->nullable();
            $table->string('conta', 30)->nullable();
            $table->enum('tipo', ['corrente', 'poupanca', 'pix', 'outro'])->default('corrente');
            $table->string('titular');
            $table->string('chave_pix', 200)->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });

        // ─── Lançamentos Financeiros ───────────────────────────────
        Schema::create('lancamentos_financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->enum('tipo', ['entrada', 'saida']);
            $table->string('categoria')->nullable()->comment('Ex: venda, compra_fornecedor, devolucao');
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_lancamento');
            $table->date('data_competencia');
            $table->boolean('conciliado')->default(false);
            $table->foreignId('conciliado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->timestamp('conciliado_em')->nullable();
            $table->boolean('pago_fornecedor')->default(false)->comment('Dropshipping: repasse ao fornecedor');
            $table->date('data_pago_fornecedor')->nullable();
            $table->string('forma_pago_fornecedor')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'data_lancamento']);
            $table->index(['pago_fornecedor', 'fornecedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_financeiros');
        Schema::dropIfExists('contas_bancarias');
        Schema::dropIfExists('confirmacoes_pagamento_manual');
        Schema::dropIfExists('pagamentos');
        Schema::dropIfExists('itens_pedido');
        Schema::dropIfExists('historico_status_pedido');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('cupons');
    }
};
