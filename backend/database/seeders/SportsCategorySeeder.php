<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaTipoProduto;
use Illuminate\Support\Str;

class SportsCategorySeeder extends Seeder
{
    public function run()
    {
        $tree = [
            'Camisetas' => [
                'Camisas de Time' => [
                    'Nacional' => [
                        'Corinthians', 'Flamengo', 'Palmeiras', 'São Paulo', 'Vasco', 'Grêmio', 'Atlético Mineiro', 
                        'Cruzeiro', 'Internacional', 'Fluminense', 'Botafogo', 'Santos', 'Bahia', 'Vitória', 
                        'Sport', 'Fortaleza', 'Ceará', 'Athletico Paranaense', 'Coritiba'
                    ],
                    'Internacional' => [
                        // Espanha
                        'Real Madrid', 'Barcelona', 'Atlético de Madrid', 'Sevilla',
                        // Inglaterra
                        'Manchester City', 'Manchester United', 'Arsenal', 'Liverpool', 'Chelsea', 'Tottenham', 'Newcastle',
                        // Itália
                        'Juventus', 'Milan', 'Inter de Milão', 'Napoli', 'Roma',
                        // Alemanha
                        'Bayern de Munique', 'Borussia Dortmund', 'Bayer Leverkusen',
                        // França
                        'PSG', 'Olympique de Marseille', 'Lyon',
                        // América do Sul
                        'Boca Juniors', 'River Plate', 'Peñarol', 'Nacional-URU', 'Colo-Colo', 'Olimpia', 'Independiente'
                    ],
                    'Seleção' => [
                        // Américas
                        'Brasil', 'Argentina', 'Uruguai', 'Chile', 'Colômbia', 'México', 'EUA',
                        // Europa
                        'França', 'Alemanha', 'Itália', 'Espanha', 'Inglaterra', 'Portugal', 'Holanda', 'Bélgica', 'Croácia',
                        // África / Ásia
                        'Marrocos', 'Nigéria', 'Senegal', 'Japão', 'Coreia do Sul'
                    ]
                ],
                'Camiseta Street' => [
                    'Oversized', 'Básica', 'Estampada', 'Longline', 'Regata', 'Moletom'
                ]
            ],
            'Calçados' => [
                'Chuteiras' => [
                    'Campo', 'Society', 'Futsal', 'Borracha'
                ],
                'Tênis' => [
                    'Corrida', 'Casual', 'Treino (Crossfit)', 'Basquete', 'Skate'
                ]
            ],
            'Acessórios' => [
                'Bolas', 'Mochilas', 'Bolsas Esportivas', 'Meiões', 'Caneleiras', 'Luvas de Goleiro', 'Bonés', 'Garrafas'
            ]
        ];

        $this->createCategories($tree, null);
    }

    private function createCategories($categories, $parentId)
    {
        foreach ($categories as $key => $value) {
            $name = is_string($key) ? $key : $value;
            
            $existing = CategoriaTipoProduto::where('nome', $name)->where('parent_id', $parentId)->first();
            
            if ($existing) {
                $category = $existing;
            } else {
                $baseSlug = Str::slug($name);
                $slug = $baseSlug;
                $counter = 1;
                while (CategoriaTipoProduto::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $category = CategoriaTipoProduto::create([
                    'nome' => $name,
                    'parent_id' => $parentId,
                    'slug' => $slug,
                    'ativo' => true,
                    'ordem' => 0
                ]);
            }

            if (is_array($value)) {
                $this->createCategories($value, $category->id);
            }
        }
    }
}
