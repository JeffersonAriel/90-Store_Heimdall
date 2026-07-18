<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Templates de Mensagem ─────────────────────────────────────
        Schema::create('crm_templates_mensagem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('nome', 191);
            $table->string('categoria', 60)->default('geral')
                ->comment('boas_vindas|pedido_aprovado|pedido_enviado|entrega|pos_venda|aniversario|vip|inativo|campanha|cobranca|obrigado|orcamento|recompra|pesquisa');
            $table->enum('tipo', ['whatsapp', 'email', 'sms'])->default('whatsapp');
            $table->string('assunto', 191)->nullable()->comment('Assunto do email');
            $table->text('conteudo');
            $table->json('variaveis')->nullable()
                ->comment('Lista de variáveis usadas: {{cliente}}, {{pedido}}, etc.');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'categoria', 'ativo']);
        });

        // ─── Segmentos de Clientes ─────────────────────────────────────
        Schema::create('crm_segmentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 191);
            $table->text('descricao')->nullable();
            $table->string('cor', 20)->default('#6366f1');
            $table->string('icone', 50)->nullable();
            $table->enum('tipo', ['automatico', 'manual'])->default('automatico');
            $table->json('regras')->nullable()
                ->comment('Regras de elegibilidade: [{campo, operador, valor}]');
            $table->unsignedInteger('total_clientes')->default(0);
            $table->dateTime('atualizado_em')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Clientes nos Segmentos ────────────────────────────────────
        Schema::create('crm_segmento_clientes', function (Blueprint $table) {
            $table->foreignId('segmento_id')->constrained('crm_segmentos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestamp('adicionado_em')->useCurrent();

            $table->primary(['segmento_id', 'cliente_id']);
            $table->index('cliente_id');
        });

        // ─── Campanhas de Comunicação ──────────────────────────────────
        Schema::create('crm_campanhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segmento_id')->nullable()->constrained('crm_segmentos')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('crm_templates_mensagem')->nullOnDelete();
            $table->foreignId('criado_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('nome', 191);
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['whatsapp', 'email', 'sms', 'push'])->default('whatsapp');
            $table->enum('status', ['rascunho', 'agendada', 'enviando', 'enviada', 'pausada', 'cancelada'])->default('rascunho');
            $table->text('conteudo')->nullable()->comment('Sobrescreve o template se preenchido');
            $table->json('variaveis_valores')->nullable()->comment('Valores fixos para substituição');
            $table->json('filtros')->nullable()->comment('Filtros manuais adicionais');
            $table->dateTime('agendada_para')->nullable();
            $table->dateTime('iniciada_em')->nullable();
            $table->dateTime('concluida_em')->nullable();
            $table->unsignedInteger('total_destinatarios')->default(0);
            $table->unsignedInteger('total_enviados')->default(0);
            $table->unsignedInteger('total_abertos')->default(0);
            $table->unsignedInteger('total_erros')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'agendada_para']);
        });

        // ─── Envios de Campanha (por destinatário) ─────────────────────
        Schema::create('crm_campanha_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained('crm_campanhas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('status', ['pendente', 'enviado', 'erro', 'aberto', 'respondido'])->default('pendente');
            $table->dateTime('enviado_em')->nullable();
            $table->dateTime('aberto_em')->nullable();
            $table->string('erro_msg', 500)->nullable();
            $table->timestamps();

            $table->index(['campanha_id', 'status']);
            $table->unique(['campanha_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_campanha_envios');
        Schema::dropIfExists('crm_campanhas');
        Schema::dropIfExists('crm_segmento_clientes');
        Schema::dropIfExists('crm_segmentos');
        Schema::dropIfExists('crm_templates_mensagem');
    }
};
