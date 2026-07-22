<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta e Declaração - Pedido #{{ $order->id }}</title>
    <!-- JsBarcode para gerar código de barras nativo e escaneável -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            background-color: #f3f4f6;
            color: #000;
            padding: 20px;
        }
        .no-print {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
        }
        .btn {
            background-color: #10b981;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-secondary {
            background-color: #64748b;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        /* Estilo da Etiqueta de Envio (10x15 cm) */
        .etiqueta-box {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 30px;
            width: 100%;
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
        }
        .etiqueta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .etiqueta-header h2 {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .destinatario-section {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 12px;
            background: #fff;
        }
        .destinatario-title {
            font-size: 12px;
            font-weight: bold;
            background: #000;
            color: #fff;
            padding: 2px 6px;
            display: inline-block;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .destinatario-nome {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .destinatario-end {
            font-size: 13px;
            line-height: 1.4;
        }
        .cep-destaque {
            font-size: 16px;
            font-weight: bold;
            margin-top: 6px;
        }
        .remetente-section {
            border-top: 1px dashed #000;
            padding-top: 8px;
            font-size: 11px;
            line-height: 1.3;
        }
        .barcode-container {
            text-align: center;
            margin: 10px 0;
        }
        .barcode-container svg {
            max-width: 100%;
            height: 50px;
        }

        /* Estilo da Declaração de Conteúdo */
        .declaracao-box {
            border: 1px solid #000;
            padding: 15px;
            font-size: 11px;
        }
        .declaracao-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .table-decl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-decl th, .table-decl td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }
        .table-decl th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .termo-legal {
            font-size: 9px;
            text-align: justify;
            margin-top: 10px;
            line-height: 1.3;
        }
        .assinatura-area {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .linha-assinatura {
            border-top: 1px solid #000;
            width: 250px;
            text-align: center;
            padding-top: 4px;
            font-size: 10px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .etiqueta-box {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Etiqueta de Envio & Declaração de Conteúdo - Pedido #{{ $order->id }}</strong>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn">🖨️ Baixar / Imprimir (PDF)</button>
            <a href="javascript:history.back()" class="btn btn-secondary">Voltar ao Pedido</a>
        </div>
    </div>

    <div class="page-container">
        
        <!-- ETIQUETA DE POSTAGEM -->
        <div class="etiqueta-box">
            <div class="etiqueta-header">
                <h2>DECLARAÇÃO DE ENVIO</h2>
                <div style="font-size: 12px; font-weight: bold;">PEDIDO #{{ $order->id }}</div>
            </div>

            <!-- CÓDIGO DE BARRAS DE RASTREIO -->
            <div class="barcode-container">
                <svg id="barcode-rastreio"></svg>
                <div style="font-size: 11px; font-weight: bold; margin-top: -4px;">
                    RASTREIO: {{ $order->codigo_rastreio ?? ('HD' . str_pad($order->id, 8, '0', STR_PAD_LEFT) . 'BR') }}
                </div>
            </div>

            <!-- DESTINATÁRIO -->
            <div class="destinatario-section">
                <span class="destinatario-title">DESTINATÁRIO</span>
                <div class="destinatario-nome">{{ $order->cliente->nome_completo ?? 'Cliente Heimdall' }}</div>
                <div class="destinatario-end">
                    {{ $order->endereco->logradouro ?? 'Endereço não cadastrado' }}, {{ $order->endereco->numero ?? 'S/N' }} 
                    {{ $order->endereco->complemento ? '- ' . $order->endereco->complemento : '' }}<br>
                    <strong>Bairro:</strong> {{ $order->endereco->bairro ?? '—' }}<br>
                    <strong>Cidade/UF:</strong> {{ $order->endereco->cidade ?? '—' }} / {{ $order->endereco->estado ?? '—' }}<br>
                    <div class="cep-destaque">CEP: {{ $order->endereco->cep ?? '00000-000' }}</div>
                    @if(!empty($order->cliente->telefone))
                    <strong>Tel:</strong> {{ $order->cliente->telefone }}
                    @endif
                </div>
            </div>

            <!-- REMETENTE -->
            <div class="remetente-section">
                <strong>REMETENTE:</strong> 90 STORE<br>
                <strong>CEP de Origem:</strong> {{ $freteRegra->cep_origem ?? '08230-600' }} — São Paulo / SP<br>
                <strong>Contato:</strong> sac@90store.com.br
            </div>
        </div>

        <!-- DECLARAÇÃO DE CONTEÚDO (EXIGIDA PELOS CORREIOS E TRANSPORTADORAS) -->
        <div class="declaracao-box">
            <div class="declaracao-title">DECLARAÇÃO DE CONTEÚDO</div>

            <table class="table-decl">
                <tr>
                    <td style="width: 50%;">
                        <strong>REMETENTE:</strong> 90 STORE<br>
                        <strong>Endereço:</strong> São Paulo - SP<br>
                        <strong>CEP:</strong> {{ $freteRegra->cep_origem ?? '08230-600' }}
                    </td>
                    <td style="width: 50%;">
                        <strong>DESTINATÁRIO:</strong> {{ $order->cliente->nome_completo ?? '—' }}<br>
                        <strong>CPF/CNPJ:</strong> {{ $order->cliente->cpf ?? '—' }}<br>
                        <strong>Endereço:</strong> {{ $order->endereco->logradouro ?? '' }}, {{ $order->endereco->numero ?? '' }} - {{ $order->endereco->cidade ?? '' }}/{{ $order->endereco->estado ?? '' }}<br>
                        <strong>CEP:</strong> {{ $order->endereco->cep ?? '' }}
                    </td>
                </tr>
            </table>

            <table class="table-decl">
                <thead>
                    <tr>
                        <th style="width: 8%;">Item</th>
                        <th>Conteúdo / Discriminação do Produto</th>
                        <th style="width: 10%; text-align: center;">Qtd</th>
                        <th style="width: 15%; text-align: right;">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalValor = 0; $totalQtd = 0; @endphp
                    @foreach($order->itens as $index => $item)
                        @php 
                            $subtotal = $item->preco_venda_snapshot * $item->quantidade;
                            $totalValor += $subtotal;
                            $totalQtd += $item->quantidade;
                        @endphp
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>{{ $item->produto->nome ?? $item->nome_produto_snapshot }}</td>
                            <td style="text-align: center;">{{ $item->quantidade }}</td>
                            <td style="text-align: right;">R$ {{ number_format($item->preco_venda_snapshot, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL:</td>
                        <td style="text-align: center; font-weight: bold;">{{ $totalQtd }}</td>
                        <td style="text-align: right; font-weight: bold;">R$ {{ number_format($totalValor, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="termo-legal">
                <strong>ATENÇÃO:</strong> Declaro que não me enquadro no conceito de contribuinte do ICMS, nos termos do art. 4º da Lei Complementar nº 87/1996, e que não pretendo a prática de atos de comércio com a mercadoria acima descrita. Declaro ainda sob as penas da lei que o conteúdo desta encomenda é exatamente o declarado acima.
            </div>

            <div class="assinatura-area">
                <div>Data: {{ date('d/m/Y') }}</div>
                <div class="linha-assinatura">Assinatura do Declarante / Remetente</div>
            </div>
        </div>

    </div>

    <script>
        // Inicializa o código de barras escaneável
        const rastreioCode = "{{ $order->codigo_rastreio ?? ('HD' . str_pad($order->id, 8, '0', STR_PAD_LEFT) . 'BR') }}";
        JsBarcode("#barcode-rastreio", rastreioCode, {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 45,
            displayValue: false
        });

        // Dispara o diálogo de impressão em PDF automaticamente
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 600);
        };
    </script>
</body>
</html>
