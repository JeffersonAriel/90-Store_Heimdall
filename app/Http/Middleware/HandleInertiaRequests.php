<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Setting;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define os dados compartilhados globalmente com todas as páginas Inertia.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'initials' => $user->initials,
                    'role' => $user->roles->first()?->name,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_super_admin' => $user->isSuperAdmin(),
                    'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                ] : null,
            ],

            // Flash messages
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info'    => fn() => $request->session()->get('info'),
            ],

            // Configurações da loja/sistema para o frontend
            'settings' => [
                'store_name' => Setting::get('store.name', '90+ Store'),
                'currency_symbol' => Setting::get('store.currency_symbol', 'R$'),
                'cashback_enabled' => (bool) Setting::get('features.cashback', true),
                'wishlist_enabled' => (bool) Setting::get('features.wishlist', true),
                'blog_enabled' => (bool) Setting::get('features.blog', true),
                'ai_enabled' => (bool) Setting::get('features.ai', true),
            ],

            // Timezone e locale
            'locale' => app()->getLocale(),
            'timezone' => config('app.timezone'),

            // SEO defaults (podem ser sobrescritos por cada página)
            'seo' => [
                'title' => Setting::get('seo.meta_title', '90+ Store'),
                'description' => Setting::get('seo.meta_desc', ''),
                'og_image' => Setting::get('seo.og_image', ''),
            ],
        ]);
    }
}
