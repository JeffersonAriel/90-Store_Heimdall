<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PerfilPermissaoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Perfis Base ──────────────────────────────────────────
        $perfis = [
            [
                'nome'      => 'Administrador',
                'descricao' => 'Acesso total ao sistema, incluindo módulo de Segurança e Logs',
                'is_admin'  => true,
            ],
            [
                'nome'      => 'Gerente',
                'descricao' => 'Acesso a produtos, pedidos, financeiro e marketing',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Atendente',
                'descricao' => 'Acesso a pedidos e clientes',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Estoque',
                'descricao' => 'Acesso a produtos, fornecedores e estoque',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Financeiro',
                'descricao' => 'Acesso ao controle financeiro e relatórios de faturamento',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Marketing',
                'descricao' => 'Acesso a cupons de desconto, banners e benefícios',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Logística',
                'descricao' => 'Acesso ao estoque, regras de frete e controle de pedidos',
                'is_admin'  => false,
            ],
            [
                'nome'      => 'Suporte',
                'descricao' => 'Acesso aos dados de clientes e pedidos de venda',
                'is_admin'  => false,
            ],
        ];

        foreach ($perfis as $perfil) {
            DB::table('perfis_permissao')->updateOrInsert(
                ['nome' => $perfil['nome']],
                array_merge($perfil, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // ─── Permissões por Perfil ────────────────────────────────
        $adminId      = DB::table('perfis_permissao')->where('nome', 'Administrador')->value('id');
        $gerenteId    = DB::table('perfis_permissao')->where('nome', 'Gerente')->value('id');
        $atendenteId  = DB::table('perfis_permissao')->where('nome', 'Atendente')->value('id');
        $estoqueId    = DB::table('perfis_permissao')->where('nome', 'Estoque')->value('id');
        $financeiroId = DB::table('perfis_permissao')->where('nome', 'Financeiro')->value('id');
        $marketingId  = DB::table('perfis_permissao')->where('nome', 'Marketing')->value('id');
        $logisticaId  = DB::table('perfis_permissao')->where('nome', 'Logística')->value('id');
        $suporteId    = DB::table('perfis_permissao')->where('nome', 'Suporte')->value('id');

        $modulos = ['produtos', 'fornecedores', 'categorias', 'pedidos', 'estoque',
                    'financeiro', 'frete', 'api_config', 'funcionarios', 'marketing',
                    'importacao', 'seguranca', 'clientes'];
        $acoes   = ['view', 'create', 'edit', 'delete'];

        // Admin: tudo
        foreach ($modulos as $modulo) {
            foreach ($acoes as $acao) {
                DB::table('permissoes_modulo')->updateOrInsert(
                    ['perfil_id' => $adminId, 'modulo' => $modulo, 'acao' => $acao],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Gerente: tudo exceto funcionários e segurança (view/create/edit)
        $modulosGerente = ['produtos', 'fornecedores', 'categorias', 'pedidos', 'estoque',
                           'financeiro', 'frete', 'marketing', 'importacao', 'clientes'];
        foreach ($modulosGerente as $modulo) {
            foreach (['view', 'create', 'edit'] as $acao) {
                DB::table('permissoes_modulo')->updateOrInsert(
                    ['perfil_id' => $gerenteId, 'modulo' => $modulo, 'acao' => $acao],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Atendente: pedidos (view/edit) + clientes (view)
        foreach (['view', 'edit'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $atendenteId, 'modulo' => 'pedidos', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        DB::table('permissoes_modulo')->updateOrInsert(
            ['perfil_id' => $atendenteId, 'modulo' => 'clientes', 'acao' => 'view'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Estoque: produtos, fornecedores, estoque, categorias, importacao
        $modulosEstoque = ['produtos', 'fornecedores', 'estoque', 'categorias', 'importacao'];
        foreach ($modulosEstoque as $modulo) {
            foreach (['view', 'create', 'edit'] as $acao) {
                DB::table('permissoes_modulo')->updateOrInsert(
                    ['perfil_id' => $estoqueId, 'modulo' => $modulo, 'acao' => $acao],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Financeiro: financeiro e pedidos (view)
        foreach ($acoes as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $financeiroId, 'modulo' => 'financeiro', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        DB::table('permissoes_modulo')->updateOrInsert(
            ['perfil_id' => $financeiroId, 'modulo' => 'pedidos', 'acao' => 'view'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Marketing: marketing, categorias (view) e produtos (view)
        foreach ($acoes as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $marketingId, 'modulo' => 'marketing', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        DB::table('permissoes_modulo')->updateOrInsert(
            ['perfil_id' => $marketingId, 'modulo' => 'categorias', 'acao' => 'view'],
            ['created_at' => now(), 'updated_at' => now()]
        );
        DB::table('permissoes_modulo')->updateOrInsert(
            ['perfil_id' => $marketingId, 'modulo' => 'produtos', 'acao' => 'view'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Logística: estoque (view/edit), frete (view/edit) e pedidos (view/edit)
        foreach (['view', 'edit'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $logisticaId, 'modulo' => 'estoque', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $logisticaId, 'modulo' => 'frete', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $logisticaId, 'modulo' => 'pedidos', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Suporte: clientes (view/edit) e pedidos (view/edit)
        foreach (['view', 'edit'] as $acao) {
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $suporteId, 'modulo' => 'clientes', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('permissoes_modulo')->updateOrInsert(
                ['perfil_id' => $suporteId, 'modulo' => 'pedidos', 'acao' => $acao],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
