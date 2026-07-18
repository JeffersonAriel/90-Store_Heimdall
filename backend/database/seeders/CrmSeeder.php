<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Etapas padrão do Pipeline CRM ─────────────────────────
        $etapas = [
            ['nome' => 'Novo Lead',       'ordem' => 1, 'cor' => '#94a3b8', 'probabilidade_default' => 5,   'tipo' => 'normal'],
            ['nome' => 'Contato Inicial', 'ordem' => 2, 'cor' => '#60a5fa', 'probabilidade_default' => 15,  'tipo' => 'normal'],
            ['nome' => 'Qualificação',    'ordem' => 3, 'cor' => '#a78bfa', 'probabilidade_default' => 30,  'tipo' => 'normal'],
            ['nome' => 'Proposta',        'ordem' => 4, 'cor' => '#fb923c', 'probabilidade_default' => 50,  'tipo' => 'normal'],
            ['nome' => 'Negociação',      'ordem' => 5, 'cor' => '#f59e0b', 'probabilidade_default' => 70,  'tipo' => 'normal'],
            ['nome' => 'Fechamento',      'ordem' => 6, 'cor' => '#34d399', 'probabilidade_default' => 90,  'tipo' => 'normal'],
            ['nome' => 'Venda',           'ordem' => 7, 'cor' => '#10b981', 'probabilidade_default' => 100, 'tipo' => 'ganho'],
            ['nome' => 'Pós-venda',       'ordem' => 8, 'cor' => '#06b6d4', 'probabilidade_default' => 100, 'tipo' => 'ganho'],
            ['nome' => 'Perdido',         'ordem' => 9, 'cor' => '#f87171', 'probabilidade_default' => 0,   'tipo' => 'perdido'],
        ];

        foreach ($etapas as $etapa) {
            DB::table('crm_pipeline_etapas')->updateOrInsert(
                ['nome' => $etapa['nome']],
                array_merge($etapa, [
                    'ativo'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // ─── 2. Templates de Mensagem Padrão ──────────────────────────
        $templates = [
            [
                'nome'      => 'Boas-vindas',
                'categoria' => 'boas_vindas',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 👋\n\nSeja bem-vindo(a) à {{empresa}}!\nEstamos muito felizes em tê-lo(a) conosco.\n\nQualquer dúvida, estamos à disposição! 😊",
                'variaveis' => ['{{cliente}}', '{{empresa}}'],
            ],
            [
                'nome'      => 'Pedido Aprovado',
                'categoria' => 'pedido_aprovado',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! ✅\n\nSeu pedido #{{pedido}} foi aprovado e já está sendo preparado!\n\nValor: {{valor}}\nVendedor: {{vendedor}}\n\nEm breve você receberá as informações de rastreio. 📦",
                'variaveis' => ['{{cliente}}', '{{pedido}}', '{{valor}}', '{{vendedor}}'],
            ],
            [
                'nome'      => 'Pedido Enviado',
                'categoria' => 'pedido_enviado',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 🚚\n\nSeu pedido #{{pedido}} foi enviado!\n\nAcompanhe sua entrega pelo link de rastreio que você recebeu por email.\n\nPrazo estimado: {{data}}\n\nDúvidas? Estamos aqui! 💬",
                'variaveis' => ['{{cliente}}', '{{pedido}}', '{{data}}'],
            ],
            [
                'nome'      => 'Confirmação de Entrega (Pós-venda)',
                'categoria' => 'pos_venda',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 😊\n\nPassamos para confirmar se seu pedido chegou corretamente.\n\nFicou tudo certo? Estamos à disposição para qualquer dúvida.\n\nSe puder, nos deixe uma avaliação — ela nos ajuda muito! ⭐",
                'variaveis' => ['{{cliente}}'],
            ],
            [
                'nome'      => 'Aniversário do Cliente',
                'categoria' => 'aniversario',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Feliz aniversário, {{cliente}}! 🎂🎉\n\nNeste dia especial, a {{empresa}} deseja a você tudo de melhor!\n\nTemos um presente para você: {{cupom}}\n\nCelebra com a gente? 🥳",
                'variaveis' => ['{{cliente}}', '{{empresa}}', '{{cupom}}'],
            ],
            [
                'nome'      => 'Cliente VIP — Reconhecimento',
                'categoria' => 'vip',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 👑\n\nVocê acaba de atingir o status **VIP** na {{empresa}}!\n\nComo nosso cliente especial, você terá acesso a:\n✅ Descontos exclusivos\n✅ Atendimento prioritário\n✅ Ofertas antecipadas\n\nObrigado pela sua confiança! 💎",
                'variaveis' => ['{{cliente}}', '{{empresa}}'],
            ],
            [
                'nome'      => 'Reativação — Cliente Inativo',
                'categoria' => 'inativo',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 👋\n\nSentimos sua falta! Faz um tempo que não te vemos por aqui.\n\nTemos novidades incríveis esperando por você na {{empresa}}.\n\nQue tal dar uma olhada? Usou esse cupom especial: {{cupom}} 🎁",
                'variaveis' => ['{{cliente}}', '{{empresa}}', '{{cupom}}'],
            ],
            [
                'nome'      => 'Lembrete de Recompra',
                'categoria' => 'recompra',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 🛒\n\nPercebemos que você costuma comprar {{produto}} na {{empresa}}.\n\nEstá na hora de reabastecer? Clique aqui para garantir o seu! 🔗",
                'variaveis' => ['{{cliente}}', '{{produto}}', '{{empresa}}'],
            ],
            [
                'nome'      => 'Pesquisa de Satisfação NPS',
                'categoria' => 'pesquisa',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}! 📊\n\nSua opinião é muito importante para a {{empresa}}!\n\nEm uma escala de 0 a 10, o quanto você indicaria a gente para um amigo?\n\nResponda aqui: {{link_pesquisa}}\n\nNão leva nem 1 minutinho! 🙏",
                'variaveis' => ['{{cliente}}', '{{empresa}}', '{{link_pesquisa}}'],
            ],
            [
                'nome'      => 'Cobrança Amigável',
                'categoria' => 'cobranca',
                'tipo'      => 'whatsapp',
                'conteudo'  => "Olá {{cliente}}!\n\nIdentificamos um pagamento pendente no valor de {{valor}} referente ao pedido #{{pedido}}.\n\nCaso já tenha efetuado o pagamento, desconsidere esta mensagem.\n\nPrecisando de ajuda, estamos à disposição! 😊",
                'variaveis' => ['{{cliente}}', '{{valor}}', '{{pedido}}'],
            ],
        ];

        foreach ($templates as $template) {
            DB::table('crm_templates_mensagem')->updateOrInsert(
                ['nome' => $template['nome'], 'tipo' => $template['tipo']],
                array_merge($template, [
                    'variaveis'  => json_encode($template['variaveis']),
                    'ativo'      => true,
                    'criado_por' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // ─── 3. Segmentos Automáticos Padrão ──────────────────────────
        $segmentos = [
            [
                'nome'      => 'Clientes VIP',
                'descricao' => 'Clientes com status VIP ativo',
                'cor'       => '#f59e0b',
                'icone'     => 'crown',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'segmento_crm', 'operador' => '=', 'valor' => 'VIP']],
            ],
            [
                'nome'      => 'Clientes Premium',
                'descricao' => 'Clientes com status Premium',
                'cor'       => '#8b5cf6',
                'icone'     => 'star',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'segmento_crm', 'operador' => '=', 'valor' => 'Premium']],
            ],
            [
                'nome'      => 'Clientes Inativos (30+ dias)',
                'descricao' => 'Clientes sem compras nos últimos 30 dias',
                'cor'       => '#94a3b8',
                'icone'     => 'sleep',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'ultimo_pedido_em', 'operador' => '<', 'valor' => '30_days_ago']],
            ],
            [
                'nome'      => 'Alto Risco de Churn',
                'descricao' => 'Clientes com risco alto de abandono',
                'cor'       => '#ef4444',
                'icone'     => 'alert',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'risco_churn', 'operador' => '=', 'valor' => 'alto']],
            ],
            [
                'nome'      => 'Recorrentes',
                'descricao' => 'Clientes com 3 ou mais pedidos',
                'cor'       => '#10b981',
                'icone'     => 'repeat',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'total_pedidos_count', 'operador' => '>=', 'valor' => 3]],
            ],
            [
                'nome'      => 'Primeira Compra',
                'descricao' => 'Clientes com exatamente 1 pedido',
                'cor'       => '#60a5fa',
                'icone'     => 'shopping-bag',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'total_pedidos_count', 'operador' => '=', 'valor' => 1]],
            ],
            [
                'nome'      => 'Grandes Compradores',
                'descricao' => 'Clientes com gasto total acima de R$2.000',
                'cor'       => '#f97316',
                'icone'     => 'trending-up',
                'tipo'      => 'automatico',
                'regras'    => [['campo' => 'total_gasto', 'operador' => '>=', 'valor' => 2000]],
            ],
        ];

        foreach ($segmentos as $segmento) {
            DB::table('crm_segmentos')->updateOrInsert(
                ['nome' => $segmento['nome']],
                array_merge($segmento, [
                    'regras'       => json_encode($segmento['regras']),
                    'ativo'        => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ])
            );
        }

        // ─── 4. Automações Padrão ──────────────────────────────────────
        $automacoes = [
            [
                'nome'       => 'Pós-venda: Confirmação de Entrega',
                'descricao'  => 'Cria tarefa de contato 5 dias após a entrega do pedido',
                'gatilho'    => 'apos_entrega',
                'delay_dias' => 5,
                'acoes'      => [
                    [
                        'tipo'  => 'criar_tarefa',
                        'dados' => [
                            'titulo'    => 'Confirmar recebimento do pedido com {{cliente}}',
                            'tipo'      => 'pos_venda',
                            'prioridade'=> 'media',
                            'descricao' => 'Entrar em contato para confirmar se o cliente recebeu corretamente o pedido e perguntar se está satisfeito.',
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Pós-venda: Pesquisa de Satisfação',
                'descricao'  => 'Envia pesquisa de satisfação 15 dias após entrega',
                'gatilho'    => 'apos_entrega',
                'delay_dias' => 15,
                'acoes'      => [
                    [
                        'tipo'  => 'criar_tarefa',
                        'dados' => [
                            'titulo'    => 'Enviar pesquisa de satisfação para {{cliente}}',
                            'tipo'      => 'pesquisa',
                            'prioridade'=> 'baixa',
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Reativação: Cliente 60 dias sem compra',
                'descricao'  => 'Alerta o vendedor quando cliente fica 60 dias sem comprar',
                'gatilho'    => 'dias_sem_compra',
                'delay_dias' => 60,
                'acoes'      => [
                    [
                        'tipo'  => 'criar_alerta',
                        'dados' => [
                            'tipo'      => 'sem_compra_60',
                            'prioridade'=> 'media',
                            'titulo'    => 'Cliente {{cliente}} há 60 dias sem comprar',
                        ]
                    ],
                    [
                        'tipo'  => 'criar_tarefa',
                        'dados' => [
                            'titulo'    => 'Reativar cliente {{cliente}} (60 dias sem compra)',
                            'tipo'      => 'contato',
                            'prioridade'=> 'alta',
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Reativação: Cliente 90 dias sem compra',
                'descricao'  => 'Cria campanha de recuperação após 90 dias sem compra',
                'gatilho'    => 'dias_sem_compra',
                'delay_dias' => 90,
                'acoes'      => [
                    [
                        'tipo'  => 'criar_alerta',
                        'dados' => [
                            'tipo'      => 'sem_compra_90',
                            'prioridade'=> 'alta',
                            'titulo'    => 'URGENTE: Cliente {{cliente}} há 90 dias sem comprar',
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Aniversário do Cliente',
                'descricao'  => 'Lembra o vendedor sobre o aniversário do cliente',
                'gatilho'    => 'aniversario',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'criar_tarefa',
                        'dados' => [
                            'titulo'    => 'Parabenizar {{cliente}} pelo aniversário 🎂',
                            'tipo'      => 'whatsapp',
                            'prioridade'=> 'alta',
                        ]
                    ],
                    [
                        'tipo'  => 'criar_alerta',
                        'dados' => [
                            'tipo'      => 'aniversario',
                            'prioridade'=> 'media',
                            'titulo'    => '🎂 Aniversário: {{cliente}} faz aniversário hoje!',
                        ]
                    ]
                ],
            ],
        ];

        foreach ($automacoes as $automacao) {
            DB::table('crm_automacoes')->updateOrInsert(
                ['nome' => $automacao['nome']],
                array_merge($automacao, [
                    'condicoes'  => null,
                    'acoes'      => json_encode($automacao['acoes']),
                    'ativa'      => true,
                    'criado_por' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // ─── 5. Pesquisa NPS Padrão ────────────────────────────────────
        DB::table('crm_satisfacao_pesquisas')->updateOrInsert(
            ['nome' => 'NPS Pós-entrega'],
            [
                'tipo'                         => 'nps',
                'disparo_dias_apos_entrega'    => 7,
                'disparo_automatico'           => true,
                'ativa'                        => true,
                'perguntas'                    => json_encode([
                    [
                        'id'       => 'nps',
                        'tipo'     => 'nps',
                        'pergunta' => 'Em uma escala de 0 a 10, qual a probabilidade de você recomendar nossa loja para um amigo ou familiar?',
                    ],
                    [
                        'id'       => 'comentario',
                        'tipo'     => 'texto',
                        'pergunta' => 'Quer nos contar mais sobre sua experiência?',
                        'opcional' => true,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ─── 6. Permissões CRM nos Perfis ─────────────────────────────
        $adminId     = DB::table('perfis_permissao')->where('nome', 'Administrador')->value('id');
        $gerenteId   = DB::table('perfis_permissao')->where('nome', 'Gerente')->value('id');
        $atendenteId = DB::table('perfis_permissao')->where('nome', 'Atendente')->value('id');
        $marketingId = DB::table('perfis_permissao')->where('nome', 'Marketing')->value('id');
        $suporteId   = DB::table('perfis_permissao')->where('nome', 'Suporte')->value('id');

        $acoes = ['view', 'create', 'edit', 'delete'];

        // Admin: tudo no CRM
        foreach ($acoes as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $adminId, 'modulo' => 'crm', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Gerente: tudo no CRM exceto delete
        foreach (['view', 'create', 'edit'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $gerenteId, 'modulo' => 'crm', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Atendente: view e create no CRM
        foreach (['view', 'create'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $atendenteId, 'modulo' => 'crm', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Marketing: view e create (campanhas, templates, segmentos)
        foreach (['view', 'create', 'edit'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $marketingId, 'modulo' => 'crm', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Suporte: view e create (ocorrências, contatos)
        foreach (['view', 'create'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $suporteId, 'modulo' => 'crm', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
