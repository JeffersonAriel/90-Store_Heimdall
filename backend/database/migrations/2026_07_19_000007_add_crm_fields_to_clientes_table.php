<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona campos CRM à tabela clientes existente
        Schema::table('clientes', function (Blueprint $table) {
            // Vendedor / Responsável CRM
            $table->foreignId('vendedor_id')
                ->nullable()
                ->after('referral_by')
                ->constrained('funcionarios')
                ->nullOnDelete()
                ->comment('Vendedor/atendente responsável pelo cliente');

            // Segmentação e Score
            $table->string('segmento_crm', 60)
                ->nullable()
                ->after('vendedor_id')
                ->comment('VIP|Premium|Ouro|Prata|Padrao');

            $table->json('tags_crm')
                ->nullable()
                ->after('segmento_crm')
                ->comment('Tags livres do CRM');

            $table->json('campos_extras_crm')
                ->nullable()
                ->after('tags_crm')
                ->comment('Campos personalizados configuráveis');

            $table->unsignedInteger('pontuacao_crm')
                ->default(0)
                ->after('campos_extras_crm')
                ->comment('Score calculado pelo sistema');

            // Métricas calculadas (cached para performance)
            $table->decimal('ltv', 14, 2)
                ->nullable()
                ->after('pontuacao_crm')
                ->comment('Lifetime Value total calculado');

            $table->dateTime('primeiro_pedido_em')
                ->nullable()
                ->after('ltv');

            $table->dateTime('ultimo_pedido_em')
                ->nullable()
                ->after('primeiro_pedido_em');

            $table->unsignedInteger('total_pedidos_count')
                ->default(0)
                ->after('ultimo_pedido_em');

            $table->decimal('total_gasto', 14, 2)
                ->default(0)
                ->after('total_pedidos_count');

            $table->decimal('ticket_medio_crm', 10, 2)
                ->nullable()
                ->after('total_gasto');

            $table->unsignedSmallInteger('media_dias_entre_compras')
                ->nullable()
                ->after('ticket_medio_crm')
                ->comment('Média de dias entre compras consecutivas');

            // Risco e Canal
            $table->enum('risco_churn', ['baixo', 'medio', 'alto'])
                ->nullable()
                ->after('media_dias_entre_compras')
                ->comment('Risco de abandono calculado pela IA');

            $table->decimal('nps_score_medio', 4, 1)
                ->nullable()
                ->after('risco_churn')
                ->comment('Média das respostas NPS do cliente');

            $table->string('origem_crm', 60)
                ->nullable()
                ->after('nps_score_medio')
                ->comment('site|whatsapp|indicacao|importacao|ecommerce|outro');

            $table->string('canal_aquisicao', 100)
                ->nullable()
                ->after('origem_crm')
                ->comment('Google Ads, Instagram, etc.');

            $table->date('data_expiracao_vip')
                ->nullable()
                ->after('canal_aquisicao')
                ->comment('Validade do status VIP');

            // Índices para queries de CRM
            $table->index('segmento_crm');
            $table->index('vendedor_id');
            $table->index('risco_churn');
            $table->index('ultimo_pedido_em');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropIndex(['segmento_crm']);
            $table->dropIndex(['vendedor_id']);
            $table->dropIndex(['risco_churn']);
            $table->dropIndex(['ultimo_pedido_em']);
            $table->dropColumn([
                'vendedor_id', 'segmento_crm', 'tags_crm', 'campos_extras_crm',
                'pontuacao_crm', 'ltv', 'primeiro_pedido_em', 'ultimo_pedido_em',
                'total_pedidos_count', 'total_gasto', 'ticket_medio_crm',
                'media_dias_entre_compras', 'risco_churn', 'nps_score_medio',
                'origem_crm', 'canal_aquisicao', 'data_expiracao_vip',
            ]);
        });
    }
};
