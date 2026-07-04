<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegrasPontosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $regras = [
            [
                'tipo'                   => 'compra',
                'multiplicador'          => 1.0, // 1 ponto por R$ gasto
                'valor_por_ponto'        => 0.01, // R$ 0,01 por ponto = R$100 em pontos = R$1,00 de desconto
                'minimo_pontos_resgate'  => 500,
                'ativo'                  => true,
            ],
            [
                'tipo'                   => 'indicacao',
                'multiplicador'          => 0.0, // N/A (recompensa fixa definida na indicação)
                'valor_por_ponto'        => 0.01,
                'minimo_pontos_resgate'  => 1,
                'ativo'                  => true,
            ],
            [
                'tipo'                   => 'aniversario',
                'multiplicador'          => 2.0, // Dobra pontos nas compras do mês de aniversário
                'valor_por_ponto'        => 0.01,
                'minimo_pontos_resgate'  => 1,
                'ativo'                  => false, // Desativado por padrão — ativar no Heimdall
            ],
        ];

        foreach ($regras as $regra) {
            DB::table('regras_pontos')->updateOrInsert(
                ['tipo' => $regra['tipo']],
                array_merge($regra, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
