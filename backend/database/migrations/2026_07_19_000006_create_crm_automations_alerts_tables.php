<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Automações CRM ────────────────────────────────────────────
        Schema::create('crm_automacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('nome', 191);
            $table->text('descricao')->nullable();
            $table->boolean('ativa')->default(true);
            $table->string('gatilho', 50)->default('apos_entrega');
            $table->unsignedSmallInteger('delay_dias')->nullable()
                ->comment('Quantos dias após o gatilho executar a automação');
            $table->unsignedSmallInteger('delay_horas')->nullable()
                ->comment('Quantas horas após o gatilho (para carrinhos, etc.)');
            $table->json('condicoes')->nullable()
                ->comment('Condições adicionais: [{campo, operador, valor}]');
            $table->json('acoes')->nullable()
                ->comment('Array de ações: [{tipo: criar_tarefa|enviar_whatsapp|enviar_email|mover_etapa, dados: {...}}]');
            $table->unsignedInteger('total_execucoes')->default(0);
            $table->unsignedInteger('total_sucesso')->default(0);
            $table->unsignedInteger('total_erros')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ativa', 'gatilho']);
        });

        // ─── Logs de Execução de Automações ───────────────────────────
        Schema::create('crm_automacao_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automacao_id')->constrained('crm_automacoes')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->string('acao_executada', 100);
            $table->enum('status', ['sucesso', 'erro', 'pendente'])->default('pendente');
            $table->json('detalhes')->nullable()->comment('Payload da execução e resultado');
            $table->text('erro_msg')->nullable();
            $table->dateTime('executado_em')->nullable();
            $table->timestamps();

            $table->index(['automacao_id', 'status']);
            $table->index(['cliente_id', 'created_at']);
        });

        // ─── Alertas Inteligentes ──────────────────────────────────────
        Schema::create('crm_alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('tipo', 60)
                ->comment('sem_compra_30|sem_compra_60|sem_compra_90|aniversario|orcamento_aberto|carrinho_abandonado|pedido_cancelado|compras_caindo|vip_atingido|boleto_vencido|chamado_aberto|sem_contato|aguardando_retorno');
            $table->string('titulo', 191);
            $table->text('descricao')->nullable();
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');
            $table->boolean('lido')->default(false);
            $table->dateTime('lido_em')->nullable();
            $table->boolean('resolvido')->default(false);
            $table->dateTime('resolvido_em')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['responsavel_id', 'lido', 'created_at']);
            $table->index(['tipo', 'resolvido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_alertas');
        Schema::dropIfExists('crm_automacao_logs');
        Schema::dropIfExists('crm_automacoes');
    }
};
