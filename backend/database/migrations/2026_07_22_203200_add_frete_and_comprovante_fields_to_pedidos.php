<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('servico_frete_nome')->nullable()->after('valor_frete')->comment('Ex: Jadlog Package, SEDEX, PAC, Loggi');
            $table->integer('prazo_frete_dias')->nullable()->after('servico_frete_nome')->comment('Prazo estimado em dias uteis');
            $table->string('url_comprovante_pagamento', 500)->nullable()->after('observacoes')->comment('URL do comprovante de pagamento InfinitePay/Gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'servico_frete_nome',
                'prazo_frete_dias',
                'url_comprovante_pagamento',
            ]);
        });
    }
};
