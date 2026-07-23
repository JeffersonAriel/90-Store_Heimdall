@component('mail::message')
# Olá, {{ $pedido->cliente ? $pedido->cliente->nome_completo : 'Cliente' }}!

Atualizamos o status do seu pedido **#{{ $pedido->id }}** na **90 Store**.

<div style="background-color: #f8fafc; border-left: 4px solid #6366f1; padding: 16px; margin: 16px 0; border-radius: 4px;">
    <strong style="font-size: 1.1em; color: #1e293b;">Status Atual:</strong> 
    <span style="font-size: 1.1em; color: #4f46e5; font-weight: bold;">{{ $statusLabel }}</span>
</div>

@if($pedido->codigo_rastreio)
---
### 📦 Informações de Rastreamento
- **Código de Rastreio:** `{{ $pedido->codigo_rastreio }}`
- **Transportadora / Serviço:** {{ $pedido->servico_frete_nome ?? 'SuperFrete' }}

@if($pedido->url_rastreio)
@component('mail::button', ['url' => $pedido->url_rastreio, 'color' => 'success'])
 Rastrear Minha Encomenda
@endcomponent
@endif
@endif

---

### 🛒 Resumo do Pedido

@component('mail::table')
| Produto / Variação | Qtd | Valor Unit. | Total |
| :--- | :---: | :---: | :---: |
@foreach($pedido->itens as $item)
| **{{ $item->nome_produto_snapshot }}** | {{ $item->quantidade }} | R$ {{ number_format($item->preco_venda_snapshot, 2, ',', '.') }} | R$ {{ number_format($item->preco_venda_snapshot * $item->quantidade, 2, ',', '.') }} |
@endforeach
@endcomponent

- **Valor dos Produtos:** R$ {{ number_format($pedido->total - $pedido->valor_frete, 2, ',', '.') }}
- **Valor do Frete:** R$ {{ number_format($pedido->valor_frete, 2, ',', '.') }}
- **Total Pago:** **R$ {{ number_format($pedido->total, 2, ',', '.') }}**

@if($pedido->endereco)
---
### 📍 Endereço de Entrega
{{ $pedido->endereco->logradouro }}, {{ $pedido->endereco->numero }} {{ $pedido->endereco->complemento ?? '' }}<br>
{{ $pedido->endereco->bairro }} — {{ $pedido->endereco->cidade }}/{{ $pedido->endereco->estado }} — CEP {{ $pedido->endereco->cep }}
@endif

@component('mail::button', ['url' => config('app.url') . '/orders', 'color' => 'primary'])
 Ver Meus Pedidos na Loja
@endcomponent

Atenciosamente,<br>
Equipe **{{ config('app.name', '90 Store') }}**
@endcomponent
