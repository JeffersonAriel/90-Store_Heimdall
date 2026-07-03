<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'HEIMDALL ERP') }}</title>

        <!-- SEO Base -->
        <meta name="description" content="{{ $page['props']['seo']['description'] ?? 'HEIMDALL ERP + 90+ Store' }}">
        <meta name="robots" content="{{ $page['props']['seo']['robots'] ?? 'index, follow' }}">

        <!-- Open Graph -->
        <meta property="og:title" content="{{ $page['props']['seo']['og_title'] ?? config('app.name') }}">
        <meta property="og:description" content="{{ $page['props']['seo']['og_description'] ?? '' }}">
        <meta property="og:image" content="{{ $page['props']['seo']['og_image'] ?? '' }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="pt_BR">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @inertiaHead

        @vite(['resources/js/erp.ts', 'resources/js/store.ts', 'resources/js/installer.ts', 'resources/css/erp.css', 'resources/css/store.css'])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
