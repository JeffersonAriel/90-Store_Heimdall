<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreBanner;
use App\Models\StoreBenefit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreSettingsAdminController extends Controller
{
    // BANNERS
    public function bannersIndex()
    {
        $banners = StoreBanner::with('category')->orderBy('order')->get();
        $categories = \App\Models\CategoriaTipoProduto::whereNull('parent_id')->get();
        return Inertia::render('Marketing/Banners', [
            'banners' => $banners,
            'categories' => $categories
        ]);
    }

    public function bannersStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'image_path' => 'required|string',
            'image_mobile_path' => 'nullable|string',
            'video_path' => 'nullable|string',
            'link_url' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
            'type' => 'required|string|in:vitrine,megamenu',
            'aspect_ratio' => 'required|string|in:16:9,4:3,1:1,9:16,4:5',
            'category_id' => 'nullable|exists:categorias_tipo_produto,id',
        ]);
        StoreBanner::create($validated);
        return redirect()->back()->with('success', 'Banner criado com sucesso!');
    }

    public function bannersUpdate(Request $request, $id)
    {
        $banner = StoreBanner::findOrFail($id);
        $validated = $request->validate([
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'image_path' => 'required|string',
            'image_mobile_path' => 'nullable|string',
            'video_path' => 'nullable|string',
            'link_url' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
            'type' => 'required|string|in:vitrine,megamenu',
            'aspect_ratio' => 'required|string|in:16:9,4:3,1:1,9:16,4:5',
            'category_id' => 'nullable|exists:categorias_tipo_produto,id',
        ]);
        $banner->update($validated);
        return redirect()->back()->with('success', 'Banner atualizado com sucesso!');
    }

    public function bannersDestroy($id)
    {
        StoreBanner::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Banner removido!');
    }

    // BENEFITS
    public function benefitsIndex()
    {
        $benefits = StoreBenefit::orderBy('order')->get();
        return Inertia::render('Marketing/Benefits', [
            'benefits' => $benefits
        ]);
    }

    public function benefitsStore(Request $request)
    {
        $validated = $request->validate([
            'icon' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        StoreBenefit::create($validated);
        return redirect()->back()->with('success', 'Benefício criado com sucesso!');
    }

    public function benefitsUpdate(Request $request, $id)
    {
        $benefit = StoreBenefit::findOrFail($id);
        $validated = $request->validate([
            'icon' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        $benefit->update($validated);
        return redirect()->back()->with('success', 'Benefício atualizado!');
    }

    public function benefitsDestroy($id)
    {
        StoreBenefit::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Benefício removido!');
    }
}
