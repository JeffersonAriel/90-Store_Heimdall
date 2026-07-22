<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Pedido;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pedido = Pedido::find(21);
        if ($pedido) {
            $realTracking = '13191900522997';
            $realSfOrderId = '01KY5PHXBB8VYCRHBX9RXB9WNR';
            $base64Token = base64_encode(json_encode(['order_id' => $realSfOrderId]));
            $realPdfUrl = "https://etiqueta.superfrete.com/_etiqueta/pdf/{$base64Token}?format=A6";

            $pedido->update([
                'codigo_rastreio'    => $realTracking,
                'url_rastreio'       => $realPdfUrl,
                'servico_frete_nome' => 'Jadlog Econômico'
            ]);
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
