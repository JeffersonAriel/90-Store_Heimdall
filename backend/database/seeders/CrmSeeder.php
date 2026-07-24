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

        // ─── 4. Automações Padrão (Voltadas Diretamente ao Cliente) ──────
        $automacoes = [
            // Status de Pedido
            [
                'nome'       => 'Status: Pedido Recebido',
                'descricao'  => 'Envia e-mail automático ao cliente assim que o pedido é registrado',
                'gatilho'    => 'status_pendente',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Recebemos o seu pedido {{pedido}}, {{cliente}}! 🛍️',
                            'mensagem'  => "Olá {{cliente}}!\n\nSeu pedido {{pedido}} no valor de {{valor}} foi registrado em nosso sistema com sucesso.\n\nEstamos aguardando a confirmação do pagamento para dar início ao processamento.\n\nObrigado por escolher a 90 Store!",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Status: Pagamento Aprovado',
                'descricao'  => 'Envia e-mail de confirmação assim que o pagamento é aprovado',
                'gatilho'    => 'pagamento_aprovado',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Pagamento Aprovado! Pedido {{pedido}} 🟢',
                            'mensagem'  => "Oba, {{cliente}}!\n\nSeu pagamento referente ao pedido {{pedido}} (Total: {{valor}}) foi confirmado com sucesso!\n\nNossa equipe já foi notificada e já iniciou o processo de separação dos seus produtos com carinho.\n\nEm breve te avisaremos sobre o envio!\n\nAbraços,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Status: Em Separação',
                'descricao'  => 'Notifica o cliente que os itens estão em embalagem e conferência',
                'gatilho'    => 'status_em_separacao',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Seu pedido {{pedido}} está sendo embalado! 📦',
                            'mensagem'  => "Olá {{cliente}}!\n\nPassando para avisar que seu pedido {{pedido}} deu entrada no setor de embalagem e controle de qualidade.\n\nEstamos conferindo cada item para garantir que tudo chegue impecável para você!\n\nAtenciosamente,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Status: Pedido Enviado (Em Trânsito)',
                'descricao'  => 'Notifica o despacho do pedido e informações de transporte',
                'gatilho'    => 'status_enviado',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Seu pedido {{pedido}} foi enviado! 🚚💨',
                            'mensagem'  => "Notícia boa, {{cliente}}!\n\nSeu pedido {{pedido}} foi coletado pela transportadora e já está a caminho do seu endereço.\n\nAcompanhe as atualizações da entrega diretamente em nosso site.\n\nCom carinho,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Pós-venda: Confirmação de Entrega',
                'descricao'  => 'Envia e-mail automático ao cliente 5 dias após a entrega para saber como foi o recebimento',
                'gatilho'    => 'apos_entrega',
                'delay_dias' => 5,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Seu pedido chegou certinho, {{cliente}}? 📦',
                            'mensagem'  => "Olá {{cliente}}!\n\nPassando para confirmar se seu último pedido chegou perfeitamente e se deu tudo certo com a entrega.\n\nSua satisfação é a nossa prioridade! Caso precise de qualquer suporte ou tenha dúvidas sobre o produto, estamos à inteira disposição.\n\nAbraços,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Pós-venda: Pesquisa de Satisfação',
                'descricao'  => 'Envia pesquisa de avaliação ao cliente 15 dias após a entrega do produto',
                'gatilho'    => 'apos_entrega',
                'delay_dias' => 15,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Como está sendo sua experiência com a 90 Store, {{cliente}}? ⭐',
                            'mensagem'  => "Olá {{cliente}}!\n\nEsperamos que esteja aproveitando muito sua compra!\n\nSua opinião vale muito para nós. Como você avalia sua experiência de compra conosco?\n\nSe puder responder a este e-mail ou deixar sua avaliação, ficaremos imensamente gratos!\n\nAtenciosamente,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Status: Pedido Cancelado',
                'descricao'  => 'Informa o cancelamento do pedido e coloca equipe de suporte à disposição',
                'gatilho'    => 'status_cancelado',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Atualização sobre o seu pedido {{pedido}}',
                            'mensagem'  => "Olá {{cliente}}.\n\nInformamos que o seu pedido {{pedido}} no valor de {{valor}} foi cancelado.\n\nSe você teve qualquer problema com a opção de pagamento ou deseja refazer a compra, nossa equipe de suporte está de prontidão para ajudar!\n\nAtenciosamente,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            // Marketing & Vendas
            [
                'nome'       => 'Boas-Vindas: Primeira Compra',
                'descricao'  => 'Envia e-mail de boas-vindas especial para clientes em seu primeiro pedido',
                'gatilho'    => 'primeira_compra',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Seja bem-vindo(a) à 90 Store, {{cliente}}! 💙',
                            'mensagem'  => "É uma honra ter você conosco, {{cliente}}!\n\nQueremos agradecer imensamente por confiar em nossa marca e realizar seu primeiro pedido.\n\nPreparamos tudo com o mais alto padrão de qualidade para superar suas expectativas!\n\nCom carinho,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Recuperação: Carrinho Abandonado',
                'descricao'  => 'Lembrete amigável enviando e-mail quando o cliente deixa produtos no carrinho',
                'gatilho'    => 'carrinho_abandonado',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Você esqueceu algo incrível no seu carrinho, {{cliente}}! 🛒✨',
                            'mensagem'  => "Olá {{cliente}}!\n\nNotamos que você deixou produtos guardados em seu carrinho de compras na 90 Store.\n\nComo nossos estoques são limitados, garantimos a reserva por tempo limitado. Volte ao site e garanta os seus favoritos com segurança!\n\nUm grande abraço,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Reativação: Cliente 60 dias sem compra',
                'descricao'  => 'Envia e-mail de saudades com mensagem personalizada quando o cliente fica 60 dias sem comprar',
                'gatilho'    => 'dias_sem_compra',
                'delay_dias' => 60,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Sentimos sua falta, {{cliente}}! 🎁',
                            'mensagem'  => "Olá {{cliente}}!\n\nFaz um tempinho que não nos falamos! Queremos te ver por aqui novamente.\n\nPreparamos novidades incríveis e reposições exclusivas em nossa loja.\n\nDê uma olhadinha no site e aproveite para garantir seus favoritos!\n\nCom carinho,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Reativação: Cliente 90 dias sem compra',
                'descricao'  => 'Envia e-mail de recuperação especial para clientes inativos há 90 dias',
                'gatilho'    => 'dias_sem_compra',
                'delay_dias' => 90,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => '{{cliente}}, temos um presente especial para o seu retorno! 🌟',
                            'mensagem'  => "Olá {{cliente}}!\n\nQue saudades de você na 90 Store! Para celebrar a sua volta, preparamos uma condição especial exclusiva para o seu próximo pedido.\n\nAcesse nosso site e confira os lançamentos que acabaram de chegar!\n\nUm grande abraço,\nEquipe 90 Store",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Aniversário do Cliente',
                'descricao'  => 'Envia e-mail de felicitações automático no dia do aniversário do cliente',
                'gatilho'    => 'aniversario',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Feliz Aniversário, {{cliente}}! 🎂🎉',
                            'mensagem'  => "Parabéns {{cliente}}!\n\nNeste dia tão especial, toda a equipe da 90 Store deseja a você muita saúde, felicidades e realizações!\n\nComo nosso cliente especial, queremos celebrar com você. Aproveite o seu dia!\n\nCom carinho,\nEquipe 90 Store 🥳",
                        ]
                    ]
                ],
            ],
            [
                'nome'       => 'Fidelidade: Cliente VIP 90 Mais',
                'descricao'  => 'Envia mensagem especial quando o cliente é promovido à categoria VIP',
                'gatilho'    => 'vip_upgrade',
                'delay_dias' => 0,
                'acoes'      => [
                    [
                        'tipo'  => 'enviar_email',
                        'dados' => [
                            'assunto'   => 'Você agora é Cliente VIP 90 Mais, {{cliente}}! 👑',
                            'mensagem'  => "Parabéns, {{cliente}}!\n\nPela sua preferência e relacionamento conosco, você acaba de conquistar o status VIP 90 Store ★ 90 Mais!\n\nAproveite benefícios exclusivos, suporte prioritário e condições diferenciadas em nossa loja.\n\nMuito obrigado pela confiança!\n\nEquipe 90 Store",
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
