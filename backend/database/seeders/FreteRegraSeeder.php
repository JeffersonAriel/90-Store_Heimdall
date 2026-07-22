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
                'lat_origem'         => -23.5044,
                'lng_origem'         => -46.4600,
                'cep_origem'         => '08010-000',
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
