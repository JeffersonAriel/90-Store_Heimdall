<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FreteRegraSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('fretes_regras')->updateOrInsert(
            ['nome' => 'Regra Principal'],
            [
                'tipo'               => 'nacional',
                'valor_minimo_gratis'=> 0.00,
                'raio_km_local'      => 50,
                'lat_origem'         => -23.5350,
                'lng_origem'         => -46.4520,
                'cep_origem'         => '08230-600',
                'logradouro_origem'  => 'Rua Nicolau Campanella',
                'numero_origem'      => '25',
                'bairro_origem'      => 'Vila Verde',
                'cidade_origem'      => 'São Paulo',
                'estado_origem'      => 'SP',
                'servicos_locais_json' => json_encode([
                    ['nome' => 'Uber Flash', 'api_slug' => 'uber_flash'],
                    ['nome' => '99 Entregas', 'api_slug' => '99entregas'],
                ]),
                'ativo'              => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }
}
