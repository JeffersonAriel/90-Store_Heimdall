<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tarefas CRM ───────────────────────────────────────────────
        Schema::create('crm_tarefas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('criado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('automacao_id')->nullable()->comment('Referência à automação que criou esta tarefa');
            $table->string('titulo', 191);
            $table->text('descricao')->nullable();
            $table->enum('tipo', [
                'contato', 'ligacao', 'visita', 'whatsapp', 'email',
                'proposta', 'cobranca', 'pos_venda', 'reuniao', 'pesquisa', 'outro'
            ])->default('contato');
            $table->enum('status', ['pendente', 'em_andamento', 'concluida', 'cancelada'])->default('pendente');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');
            $table->dateTime('vencimento_em')->nullable();
            $table->dateTime('concluida_em')->nullable();
            $table->text('resultado')->nullable()->comment('Observação ao concluir a tarefa');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['responsavel_id', 'status']);
            $table->index(['cliente_id', 'status']);
            $table->index(['lead_id', 'status']);
            $table->index(['vencimento_em', 'status']);
        });

        // ─── Pesquisas de Satisfação ───────────────────────────────────
        Schema::create('crm_satisfacao_pesquisas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 191);
            $table->enum('tipo', ['nps', 'csat', 'ces', 'personalizada'])->default('nps');
            $table->json('perguntas')->comment('Array de perguntas configuráveis');
            $table->boolean('ativa')->default(true);
            $table->unsignedSmallInteger('disparo_dias_apos_entrega')->default(5);
            $table->boolean('disparo_automatico')->default(true);
            $table->timestamps();
        });

        // ─── Respostas de Pesquisas de Satisfação ─────────────────────
        Schema::create('crm_satisfacao_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesquisa_id')->constrained('crm_satisfacao_pesquisas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->json('respostas')->comment('Respostas indexadas por id da pergunta');
            $table->tinyInteger('nps_score')->nullable()->comment('0-10');
            $table->decimal('csat_score', 3, 1)->nullable()->comment('1.0-5.0');
            $table->decimal('ces_score', 3, 1)->nullable()->comment('1.0-7.0');
            $table->text('comentario')->nullable();
            $table->dateTime('respondido_em')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'created_at']);
            $table->index(['pesquisa_id', 'respondido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_satisfacao_respostas');
        Schema::dropIfExists('crm_satisfacao_pesquisas');
        Schema::dropIfExists('crm_tarefas');
    }
};
