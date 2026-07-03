<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = storage_path('app/installed.lock');
        $isInstallerRoute = $request->routeIs('installer.*');

        if (! File::exists($lockFile) && ! $isInstallerRoute) {
            return redirect()->route('installer.index');
        }

        if (File::exists($lockFile) && $isInstallerRoute) {
            return redirect('/');
        }

        return $next($request);
    }
}
