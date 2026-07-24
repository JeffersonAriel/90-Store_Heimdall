<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $assuntoTexto }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0b0f19; padding: 30px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 600px; background-color: #111827; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
          
          <!-- Banner / Header 90 Store -->
          <tr>
            <td style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); padding: 30px 24px; text-align: center; border-bottom: 2px solid #6366f1;">
              <table role="presentation" width="100%">
                <tr>
                  <td align="center">
                    <img src="{{ config('app.url') }}/logo-heimdall.png" alt="90 Store 90 Mais" style="max-height: 55px; width: auto; display: block; margin: 0 auto 10px auto; border: 0;" />
                    <h1 style="color: #ffffff; font-size: 24px; font-weight: 800; margin: 4px 0 0 0; text-transform: uppercase; letter-spacing: 1px;">
                      90 Store
                    </h1>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Mensagem Principal -->
          <tr>
            <td style="padding: 32px 28px; background-color: #111827;">
              <h2 style="color: #f8fafc; font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">
                Olá, {{ $clienteNome }}! 👋
              </h2>

              <div style="color: #cbd5e1; font-size: 15px; line-height: 1.7; margin-bottom: 24px;">
                {!! nl2br(e($mensagemTexto)) !!}
              </div>

              <!-- Bloco do Pedido (Se houver pedido vinculado) -->
              @if(!empty($pedido))
              <table role="presentation" width="100%" style="margin-top: 24px; margin-bottom: 24px; background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                <tr>
                  <td style="padding: 16px 20px; background: rgba(99,102,241,0.1); border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <table role="presentation" width="100%">
                      <tr>
                        <td>
                          <span style="color: #818cf8; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">DETALHES DO SEU PEDIDO</span>
                          <h3 style="color: #ffffff; font-size: 17px; font-weight: 800; margin: 4px 0 0 0;">
                            Pedido #{{ $pedido->id }}
                          </h3>
                        </td>
                        <td align="right">
                          <span style="background: rgba(16,185,129,0.2); color: #34d399; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                            {{ ucfirst($pedido->status) }}
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Tabela de Itens -->
                <tr>
                  <td style="padding: 16px 20px;">
                    <table role="presentation" width="100%" style="border-collapse: collapse;">
                      <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                          <th align="left" style="color: #94a3b8; font-size: 11px; text-transform: uppercase; padding-bottom: 8px;">Item / Produto</th>
                          <th align="center" style="color: #94a3b8; font-size: 11px; text-transform: uppercase; padding-bottom: 8px;">Qtd</th>
                          <th align="right" style="color: #94a3b8; font-size: 11px; text-transform: uppercase; padding-bottom: 8px;">Valor</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($pedido->itens as $item)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                          <td style="padding: 10px 0; color: #f1f5f9; font-size: 13px; font-weight: 600;">
                            {{ $item->nome_produto_snapshot }}
                          </td>
                          <td align="center" style="padding: 10px 0; color: #94a3b8; font-size: 13px;">
                            {{ $item->quantidade }}x
                          </td>
                          <td align="right" style="padding: 10px 0; color: #f1f5f9; font-size: 13px; font-weight: 600;">
                            R$ {{ number_format($item->preco_venda_snapshot * $item->quantidade, 2, ',', '.') }}
                          </td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>

                    <!-- Totais -->
                    <table role="presentation" width="100%" style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed rgba(255,255,255,0.1);">
                      <tr>
                        <td style="color: #94a3b8; font-size: 13px;">Frete</td>
                        <td align="right" style="color: #cbd5e1; font-size: 13px;">R$ {{ number_format($pedido->valor_frete ?? 0, 2, ',', '.') }}</td>
                      </tr>
                      <tr>
                        <td style="color: #ffffff; font-size: 15px; font-weight: 700; padding-top: 6px;">Valor Total</td>
                        <td align="right" style="color: #34d399; font-size: 16px; font-weight: 800; padding-top: 6px;">
                          R$ {{ number_format($pedido->total, 2, ',', '.') }}
                        </td>
                      </tr>
                    </table>

                    @if($pedido->endereco)
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05); color: #94a3b8; font-size: 12px; line-height: 1.5;">
                      <strong style="color: #cbd5e1;">📍 Endereço de Entrega:</strong><br>
                      {{ $pedido->endereco->logradouro }}, {{ $pedido->endereco->numero }} {{ $pedido->endereco->complemento ?? '' }}<br>
                      {{ $pedido->endereco->bairro }} — {{ $pedido->endereco->cidade }}/{{ $pedido->endereco->estado }} — CEP {{ $pedido->endereco->cep }}
                    </div>
                    @endif
                  </td>
                </tr>
              </table>
              @endif

              <!-- Botão CTA -->
              <table role="presentation" width="100%" style="margin-top: 28px;">
                <tr>
                  <td align="center">
                    <a href="{{ config('app.url') }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 14px rgba(99,102,241,0.4);">
                      🛒 Acesse a 90 Store
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Rodapé -->
          <tr>
            <td style="background-color: #0f172a; padding: 24px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; font-size: 12px; line-height: 1.6;">
              <p style="margin: 0 0 6px 0; font-weight: 700; color: #94a3b8;">90 Store ★ 90 Mais — Excelência e Qualidade</p>
              <p style="margin: 0;">Você recebeu este e-mail referente ao seu cadastro/pedido em nossa loja oficial.</p>
              <p style="margin: 6px 0 0 0; color: #475569;">© {{ date('Y') }} 90 Store. Todos os direitos reservados.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
