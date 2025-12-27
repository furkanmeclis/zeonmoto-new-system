<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $metaTitle = session('meta_title', config('app.name', 'Laravel'));
        $metaDescription = session('meta_description');
        $metaImage = session('meta_image', asset('logo.png'));
        $metaUrl = session('meta_url', url()->current());
        $metaType = session('meta_type', 'website');
        $structuredData = session('structured_data');
    @endphp

    <title inertia>{{ $metaTitle }}</title>

    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $metaType }}">
    <meta property="og:url" content="{{ $metaUrl }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:image" content="{{ $metaImage }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $metaUrl }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    @if ($metaDescription)
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    <meta name="twitter:image" content="{{ $metaImage }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $metaUrl }}">

    <!-- Google Shopping Structured Data -->
    @if ($structuredData)
        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif

    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
