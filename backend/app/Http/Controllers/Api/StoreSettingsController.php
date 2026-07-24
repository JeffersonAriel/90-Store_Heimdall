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
        $gateways = \App\Models\ApiConfiguracao::where('tipo', 'gateway')
            ->where('ativo', true)
            ->get(['slug', 'nome']);
        $banners = StoreBanner::where('is_active', true)
            ->where('type', 'vitrine')
            ->orderBy('order')
            ->get()
            ->map(function ($banner) {
                if (empty($banner->image_mobile_path)) {
                    $banner->image_mobile_path = $banner->image_path;
                }
                return $banner;
            });

        $megaMenuBanners = StoreBanner::where('is_active', true)
            ->where('type', 'megamenu')
            ->orderBy('order')
            ->get()
            ->map(function ($banner) {
                if (empty($banner->image_mobile_path)) {
                    $banner->image_mobile_path = $banner->image_path;
                }
                return $banner;
            });

        $benefits = StoreBenefit::where('is_active', true)
            ->orderBy('order')
            ->get();

        $tree = $this->buildCategoryTree(null);

        return response()->json([
            'banners'        => $banners,
            'megaMenuBanners'=> $megaMenuBanners,
            'benefits'       => $benefits,
            'categories'     => $tree,
            'paymentMethods' => $gateways,
            'whatsapp'       => env('STORE_WHATSAPP', null),
        ]);
    }

    private function buildCategoryTree($parentId = null)
    {
        $categories = CategoriaTipoProduto::where('parent_id', $parentId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get();

        $tree = [];
        foreach ($categories as $category) {
            $children = $this->buildCategoryTree($category->id);

            // Count products in this specific category
            $ownProductsCount = \App\Models\Produto::where('categoria_id', $category->id)
                ->where('ativo', true)
                ->count();

            // If it has active products or children with active products
            if (count($children) > 0 || $ownProductsCount > 0) {
                $tree[] = [
                    'id' => $category->id,
                    'nome' => $category->nome,
                    'slug' => $category->slug,
                    'banner_path' => $category->banner_path,
                    'children' => $children
                ];
            }
        }
        return $tree;
    }
}
