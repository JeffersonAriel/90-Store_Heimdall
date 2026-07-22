<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaTipoProduto;
use Illuminate\Support\Str;

class FullCategoryStructureSeeder extends Seeder
{
    public function run()
    {
        $tree = [
            'Futebol' => [
                'Chuteiras' => [
                    'Campo',
                    'Society',
                    'Futsal',
                    'Infantil',
                    'Profissional',
                ],
                'Camisas' => [
                    'Brasileirão',
                    'Europeus',
                    'Seleções',
                    'Retrô',
                    'Treino',
                ],
                'Calções',
                'Meiões',
                'Jaquetas',
                'Agasalhos',
                'Bolas',
                'Caneleiras',
                'Luvas de Goleiro',
                'Mochilas',
                'Bolsas',
                'Acessórios',
            ],
            'Basquete' => [
                'Camisas NBA',
                'Regatas',
                'Shorts',
                'Tênis',
                'Bolas',
                'Bonés',
                'Mochilas',
                'Acessórios',
            ],
            'NFL (Futebol Americano)' => [
                'Jerseys',
                'Camisetas',
                'Moletons',
                'Bonés',
                'Shorts',
                'Jaquetas',
                'Mochilas',
                'Acessórios',
            ],
            'Corrida' => [
                'Tênis',
                'Camisetas',
                'Shorts',
                'Leggings',
                'Jaquetas',
                'Mochilas',
                'Relógios',
                'Acessórios',
            ],
            'Academia' => [
                'Camisetas',
                'Regatas',
                'Shorts',
                'Calças',
                'Tênis',
                'Mochilas',
                'Garrafas',
                'Luvas',
                'Acessórios',
            ],
            'Vôlei',
            'Futsal',
            'Casual' => [
                'Camisetas',
                'Moletons',
                'Jaquetas',
                'Calças',
                'Bermudas',
                'Bonés',
                'Mochilas',
                'Tênis',
            ],
            'Infantil',
            'Feminino',
            'Outlet' => [
                'Até 30%',
                'Até 50%',
                'Até 70%',
                'Últimas Unidades',
            ],
        ];

        $this->createCategories($tree, null);
    }

    private function createCategories($categories, $parentId)
    {
        $ordem = 1;
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
                    'ordem' => $ordem
                ]);
            }

            $ordem++;

            if (is_array($value)) {
                $this->createCategories($value, $category->id);
            }
        }
    }
}
