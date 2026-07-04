<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─── Categorias Raiz ──────────────────────────────────────
        $categorias = [

            // ── VESTUÁRIO ──────────────────────────────────────────
            [
                'nome'   => 'Camisetas',
                'slug'   => 'camisetas',
                'icone'  => 'shirt',
                'ordem'  => 1,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => [
                            'Time Nacional', 'Time Internacional', 'Seleção', 'Retrô',
                            'Street', 'Feminina', 'Infantil', 'Goleiro',
                        ],
                    ],
                    [
                        'nome'  => 'Temporada',
                        'tipo'  => 'text',
                        'obrigatorio' => false,
                        'opcoes' => [],
                    ],
                    [
                        'nome'  => 'Manga',
                        'tipo'  => 'select',
                        'obrigatorio' => false,
                        'opcoes' => ['Manga Curta', 'Manga Longa', 'Regata'],
                    ],
                ],
            ],

            [
                'nome'   => 'Calças e Shorts',
                'slug'   => 'calcas-shorts',
                'icone'  => 'pants',
                'ordem'  => 2,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Calça de Moletom', 'Calça de Treino', 'Short de Futebol',
                                     'Short de Corrida', 'Short de Academia', 'Legging'],
                    ],
                ],
            ],

            [
                'nome'   => 'Agasalhos e Jaquetas',
                'slug'   => 'agasalhos-jaquetas',
                'icone'  => 'jacket',
                'ordem'  => 3,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Agasalho Completo', 'Jaqueta Corta-Vento', 'Moletom', 'Blusa de Frio'],
                    ],
                ],
            ],

            // ── CALÇADOS ───────────────────────────────────────────
            [
                'nome'   => 'Calçados',
                'slug'   => 'calcados',
                'icone'  => 'shoe',
                'ordem'  => 4,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Chuteira Society', 'Chuteira Campo', 'Chuteira Futsal',
                                     'Tênis de Corrida', 'Tênis de Treino', 'Tênis Casual Esportivo',
                                     'Tênis de Quadra'],
                    ],
                    [
                        'nome'  => 'Solado',
                        'tipo'  => 'select',
                        'obrigatorio' => false,
                        'opcoes' => ['SG (Solo Macio)', 'FG (Solo Firme)', 'AG (Solo Artificial)',
                                     'TF (Grama Sintética)', 'IN (Indoor)'],
                    ],
                ],
            ],

            // ── EQUIPAMENTOS ───────────────────────────────────────
            [
                'nome'   => 'Bolas',
                'slug'   => 'bolas',
                'icone'  => 'ball',
                'ordem'  => 5,
                'atributos' => [
                    [
                        'nome'  => 'Modalidade',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Futebol', 'Futsal', 'Society', 'Vôlei', 'Basquete',
                                     'Handebol', 'Rugby', 'Tênis'],
                    ],
                    [
                        'nome'  => 'Tamanho Oficial',
                        'tipo'  => 'select',
                        'obrigatorio' => false,
                        'opcoes' => ['Tamanho 3', 'Tamanho 4', 'Tamanho 5'],
                    ],
                ],
            ],

            [
                'nome'   => 'Meias e Acessórios',
                'slug'   => 'meias-acessorios',
                'icone'  => 'accessories',
                'ordem'  => 6,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Meia de Futebol', 'Meia Esportiva', 'Luva de Goleiro',
                                     'Caneleira', 'Joelheira', 'Tornozeleira', 'Cotoveleira',
                                     'Munhequeira', 'Boné', 'Viseira'],
                    ],
                ],
            ],

            [
                'nome'   => 'Equipamentos de Treino',
                'slug'   => 'equipamentos-treino',
                'icone'  => 'equipment',
                'ordem'  => 7,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Corda de Pular', 'Cone de Treino', 'Escada de Agilidade',
                                     'Barra Fixa', 'Elástico de Treino', 'Bola de Ritmo',
                                     'Coletes de Treino', 'Redes'],
                    ],
                ],
            ],

            // ── SUPLEMENTOS ────────────────────────────────────────
            [
                'nome'   => 'Suplementos Esportivos',
                'slug'   => 'suplementos',
                'icone'  => 'supplement',
                'ordem'  => 8,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Whey Protein', 'Creatina', 'BCAA', 'Pré-treino',
                                     'Hipercalórico', 'Glutamina', 'Cafeína', 'Termogênico',
                                     'Vitaminas e Minerais', 'Barra de Proteína'],
                    ],
                    [
                        'nome'  => 'Sabor',
                        'tipo'  => 'text',
                        'obrigatorio' => false,
                        'opcoes' => [],
                    ],
                ],
            ],

            // ── LINHA FEMININA ─────────────────────────────────────
            [
                'nome'   => 'Linha Feminina',
                'slug'   => 'linha-feminina',
                'icone'  => 'female',
                'ordem'  => 9,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Top Esportivo', 'Legging', 'Calça de Moletom', 'Shorts Feminino',
                                     'Jaqueta Feminina', 'Camiseta Feminina'],
                    ],
                ],
            ],

            // ── LINHA INFANTIL ─────────────────────────────────────
            [
                'nome'   => 'Linha Infantil',
                'slug'   => 'linha-infantil',
                'icone'  => 'child',
                'ordem'  => 10,
                'atributos' => [
                    [
                        'nome'  => 'Tipo',
                        'tipo'  => 'select',
                        'obrigatorio' => true,
                        'opcoes' => ['Camiseta Infantil', 'Kit Infantil (camiseta + short)',
                                     'Chuteira Infantil', 'Bola Infantil'],
                    ],
                    [
                        'nome'  => 'Faixa Etária',
                        'tipo'  => 'select',
                        'obrigatorio' => false,
                        'opcoes' => ['2-4 anos', '4-6 anos', '6-8 anos', '8-10 anos',
                                     '10-12 anos', '12-14 anos'],
                    ],
                ],
            ],

        ]; // fim $categorias

        foreach ($categorias as $ordem => $catData) {
            $atributosData = $catData['atributos'] ?? [];
            unset($catData['atributos']);

            $catId = DB::table('categorias_tipo_produto')->insertGetId(array_merge($catData, [
                'parent_id'  => null,
                'ativo'      => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            // Insere atributos da categoria
            foreach ($atributosData as $atribOrdem => $atribData) {
                $opcoes = $atribData['opcoes'] ?? [];
                unset($atribData['opcoes']);

                $atribId = DB::table('atributos_categoria')->insertGetId(array_merge($atribData, [
                    'categoria_id' => $catId,
                    'ordem'        => $atribOrdem,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]));

                // Insere opções do atributo
                foreach ($opcoes as $opcOrdem => $opcaoValor) {
                    DB::table('opcoes_atributo')->insert([
                        'atributo_id' => $atribId,
                        'valor'       => $opcaoValor,
                        'ordem'       => $opcOrdem,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        }
    }
}
