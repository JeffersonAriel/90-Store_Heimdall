<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Contatos / Interações registradas ────────────────────────
        Schema::create('crm_contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->enum('tipo', ['whatsapp', 'email', 'ligacao', 'visita', 'reuniao', 'videoconferencia', 'sms', 'outro'])->default('outro');
            $table->string('assunto', 191)->nullable();
            $table->text('descricao')->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->nullable();
            $table->dateTime('realizado_em');
            $table->timestamps();

            $table->index(['cliente_id', 'realizado_em']);
            $table->index(['lead_id', 'realizado_em']);
        });

        // ─── Notas Internas ────────────────────────────────────────────
        Schema::create('crm_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('titulo', 191)->nullable();
            $table->text('conteudo');
            $table->boolean('privada')->default(false)->comment('Visível apenas para quem criou');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'created_at']);
            $table->index(['lead_id', 'created_at']);
        });

        // ─── Ocorrências / Tickets ─────────────────────────────────────
        Schema::create('crm_ocorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('resolvido_por')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('tipo', 60)->default('reclamacao')->comment('reclamacao|duvida|elogio|sugestao|troca|devolucao|outro');
            $table->string('assunto', 191);
            $table->text('descricao');
            $table->enum('status', ['aberta', 'em_andamento', 'aguardando_cliente', 'resolvida', 'fechada'])->default('aberta');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');
            $table->dateTime('resolvido_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'status']);
            $table->index(['status', 'prioridade']);
        });

        // ─── Documentos e Anexos ───────────────────────────────────────
        Schema::create('crm_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->enum('tipo', ['contrato', 'proposta', 'foto', 'anexo', 'nota_fiscal', 'comprovante', 'outro'])->default('outro');
            $table->string('nome', 191);
            $table->string('caminho', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_documentos');
        Schema::dropIfExists('crm_ocorrencias');
        Schema::dropIfExists('crm_notas');
        Schema::dropIfExists('crm_contatos');
    }
};
