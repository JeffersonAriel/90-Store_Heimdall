<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pedidos = DB::table('pedidos')->get();

        foreach ($pedidos as $pedido) {
            $hasPagamento = DB::table('pagamentos')->where('pedido_id', $pedido->id)->exists();

            if (!$hasPagamento) {
                $obs = strtolower($pedido->observacoes ?? '');
                $comp = strtolower($pedido->url_comprovante_pagamento ?? '');

                $gateway = 'pix_manual';
                $metodo  = 'pix';

                if (str_contains($obs, 'infinitepay') || str_contains($comp, 'infinitepay') || str_contains($comp, 'pay.infinitepay.io')) {
                    $gateway = 'infinitepay';
                    $metodo  = (str_contains($obs, 'pix') || str_contains($comp, 'pix')) ? 'pix' : 'cartao_credito';
                }

                $status = ($pedido->status === 'aguardando_pagamento') ? 'pendente' : 'aprovado';

                DB::table('pagamentos')->insert([
                    'pedido_id'  => $pedido->id,
                    'gateway'    => $gateway,
                    'metodo'     => $metodo,
                    'status'     => $status,
                    'valor'      => $pedido->total,
                    'created_at' => $pedido->created_at ?? now(),
                    'updated_at' => $pedido->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
