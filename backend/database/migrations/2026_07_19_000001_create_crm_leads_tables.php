<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Etapas do Pipeline CRM ───────────────────────────────────
        Schema::create('crm_pipeline_etapas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->integer('ordem')->default(0);
            $table->string('cor', 20)->default('#6366f1')->comment('Cor HEX para o Kanban');
            $table->unsignedTinyInteger('probabilidade_default')->default(50)->comment('0-100%');
            $table->enum('tipo', ['normal', 'ganho', 'perdido'])->default('normal');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ─── Leads CRM ────────────────────────────────────────────────
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('etapa_id')->nullable()->constrained('crm_pipeline_etapas')->nullOnDelete();
            $table->string('nome', 191);
            $table->string('email', 191)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('empresa', 191)->nullable();
            $table->enum('origem', [
                'site', 'landing_page', 'whatsapp', 'instagram', 'facebook',
                'google_ads', 'marketplace', 'indicacao', 'telefone', 'importacao', 'outro'
            ])->default('outro');
            $table->text('interesse')->nullable();
            $table->enum('temperatura', ['frio', 'morno', 'quente'])->default('frio');
            $table->unsignedTinyInteger('probabilidade')->default(0)->comment('0-100%');
            $table->decimal('valor_esperado', 12, 2)->nullable();
            $table->enum('status', ['ativo', 'convertido', 'perdido'])->default('ativo');
            $table->text('motivo_perda')->nullable();
            $table->text('proxima_acao')->nullable();
            $table->dateTime('data_proxima_acao')->nullable();
            $table->json('tags')->nullable();
            $table->json('campos_extras')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'etapa_id']);
            $table->index(['responsavel_id', 'status']);
            $table->index('origem');
        });

        // ─── Histórico de Movimentação no Pipeline ─────────────────────
        Schema::create('crm_lead_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('etapa_de_id')->nullable()->constrained('crm_pipeline_etapas')->nullOnDelete();
            $table->foreignId('etapa_para_id')->nullable()->constrained('crm_pipeline_etapas')->nullOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->unsignedSmallInteger('dias_na_etapa')->default(0);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_etapas');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_pipeline_etapas');
    }
};
