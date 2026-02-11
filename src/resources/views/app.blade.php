<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'CommuniCare') }}</title>

    <!-- ファビコンの設定 -->
    <link rel="icon" type="image/x-icon" href="{{ url('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Scripts -->
    <script>
        window.Ziggy = @json(new \Tighten\Ziggy\Ziggy());
    </script>
    @vite(['resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div id="app" data-page="{{ json_encode($page) }}"></div>
</body>

</html>
