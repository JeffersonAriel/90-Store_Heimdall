<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreBanner;
use App\Models\StoreBenefit;
use App\Models\CategoriaTipoProduto;
use Illuminate\Http\Request;

class StoreSettingsController extends Controller
{
    public function index()
    {
        $banners = StoreBanner::where('is_active', true)
            ->orderBy('order')
            ->get();

        $benefits = StoreBenefit::where('is_active', true)
            ->orderBy('order')
            ->get();

        // Construir a árvore de categorias apenas com produtos
        $allCategories = CategoriaTipoProduto::where('ativo', true)
            ->with(['children' => function($q) {
                $q->where('ativo', true);
            }])
            ->get();

        $tree = [];
        
        foreach ($allCategories->whereNull('parent_id') as $parent) {
            $children = [];
            
            foreach ($parent->children as $child) {
                // Conta os produtos dessa categoria filha
                $productsCount = \App\Models\Produto::where('categoria_id', $child->id)
                    ->where('ativo', true)
                    ->count();
                    
                if ($productsCount > 0) {
                    $children[] = [
                        'id' => $child->id,
                        'nome' => $child->nome,
                        'slug' => $child->slug,
                    ];
                }
            }

            // Conta os produtos da própria categoria pai (caso ela não tenha filhas mas tenha produtos diretamente)
            $ownProductsCount = \App\Models\Produto::where('categoria_id', $parent->id)
                ->where('ativo', true)
                ->count();

            if (count($children) > 0 || $ownProductsCount > 0) {
                $tree[] = [
                    'id' => $parent->id,
                    'nome' => $parent->nome,
                    'slug' => $parent->slug,
                    'children' => $children
                ];
            }
        }

        return response()->json([
            'banners' => $banners,
            'benefits' => $benefits,
            'categories' => $tree
        ]);
    }
}
