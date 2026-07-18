<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Timeline de Eventos CRM ───────────────────────────────────
        Schema::create('crm_timeline_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('usuario_nome', 191)->nullable()->comment('Snapshot do nome, preservado mesmo se usuário deletado');
            $table->enum('tipo', [
                'cliente_criado', 'login', 'pedido_criado', 'pedido_cancelado',
                'pagamento_aprovado', 'pagamento_recusado', 'boleto_vencido', 'pix_pago',
                'produto_entregue', 'mensagem_enviada', 'email_enviado', 'cupom_utilizado',
                'ticket_aberto', 'anotacao_criada', 'visita_realizada', 'ligacao_registrada',
                'reuniao_realizada', 'alteracao_cadastral', 'mudanca_vendedor',
                'whatsapp_recebido', 'proposta_enviada', 'tarefa_criada', 'nota_criada',
                'campanha_enviada', 'pesquisa_respondida', 'carrinho_abandonado',
                'produto_visualizado', 'etapa_pipeline', 'lead_criado', 'lead_convertido',
                'automacao_executada', 'custom'
            ])->default('custom');
            $table->string('titulo', 191);
            $table->text('descricao')->nullable();
            $table->enum('origem', ['sistema', 'manual', 'whatsapp', 'email', 'telefone', 'visita', 'ecommerce', 'automacao'])->default('sistema');
            $table->string('icone', 50)->nullable()->comment('Nome do ícone para o frontend');
            $table->string('cor', 20)->nullable()->comment('Cor HEX do evento na timeline');
            $table->json('metadata')->nullable()->comment('Dados extras: pedido_id, valor, produto, etc.');
            $table->timestamps();

            $table->index(['cliente_id', 'created_at']);
            $table->index(['lead_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_timeline_eventos');
    }
};
