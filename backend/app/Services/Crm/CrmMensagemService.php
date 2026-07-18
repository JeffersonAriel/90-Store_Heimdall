<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmTemplateMensagem;

/**
 * CrmMensagemService
 *
 * Renderiza templates de mensagem substituindo variáveis {{campo}}.
 * Suporta WhatsApp, email e SMS.
 */
class CrmMensagemService
{
    /**
     * Variáveis disponíveis para substituição em templates.
     */
    public static function getVariaveisDisponiveis(): array
    {
        return [
            '{{cliente}}'      => 'Nome do cliente',
            '{{empresa}}'      => 'Nome da empresa/loja',
            '{{pedido}}'       => 'Número do pedido',
            '{{produto}}'      => 'Nome do produto',
            '{{valor}}'        => 'Valor formatado (R$)',
            '{{vendedor}}'     => 'Nome do vendedor responsável',
            '{{cupom}}'        => 'Código do cupom',
            '{{data}}'         => 'Data formatada',
            '{{cidade}}'       => 'Cidade do cliente',
            '{{link_pesquisa}}'=> 'Link da pesquisa de satisfação',
            '{{nps_link}}'     => 'Link NPS direto',
            '{{saldo_pontos}}' => 'Saldo de pontos do cliente',
            '{{status_pedido}}'=> 'Status atual do pedido',
            '{{codigo_rastreio}}' => 'Código de rastreio',
        ];
    }

    /**
     * Renderiza um template com os dados fornecidos.
     */
    public static function renderizar(string $conteudo, array $dados): string
    {
        $appNome = config('app.name', 'Nossa Loja');

        $variaveis = array_merge([
            '{{empresa}}'  => $appNome,
            '{{data}}'     => now()->format('d/m/Y'),
        ], $dados);

        foreach ($variaveis as $chave => $valor) {
            $conteudo = str_replace($chave, (string) $valor, $conteudo);
        }

        return $conteudo;
    }

    /**
     * Renderiza um template buscando pelo ID.
     */
    public static function renderizarTemplate(int $templateId, array $dados): ?string
    {
        $template = CrmTemplateMensagem::find($templateId);
        if (!$template) return null;
        return self::renderizar($template->conteudo, $dados);
    }

    /**
     * Constrói array de variáveis a partir de um cliente + pedido opcional.
     */
    public static function buildVarsCliente(
        \App\Models\Cliente $cliente,
        ?\App\Models\Pedido $pedido = null,
        array $extra = []
    ): array {
        $vars = [
            '{{cliente}}'      => $cliente->nome_social ?: $cliente->nome_completo,
            '{{cidade}}'       => $cliente->enderecos()->where('is_principal', true)->value('cidade') ?? '',
            '{{saldo_pontos}}' => $cliente->pontos_saldo ?? 0,
            '{{vendedor}}'     => $cliente->vendedor?->nome ?? 'nossa equipe',
        ];

        if ($pedido) {
            $vars['{{pedido}}']          = $pedido->id;
            $vars['{{valor}}']           = 'R$ ' . number_format($pedido->total, 2, ',', '.');
            $vars['{{status_pedido}}']   = $pedido->status;
            $vars['{{codigo_rastreio}}'] = $pedido->codigo_rastreio ?? 'não disponível';
        }

        return array_merge($vars, $extra);
    }
}
