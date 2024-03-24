<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="mt-32">
    <section class="text-gray-600 body-font">
        <div class="container mx-auto flex px-5 py-10 md:flex-row flex-col items-center">
            <div class="lg:max-w-lg lg:w-1/3 md:w-1/3 w-3/6 mb-10 md:mb-0">
                <img class="object-cover object-center rounded" alt="hero" src="/images/top_image.png">
            </div>
            <div
                class="lg:flex-grow md:w-1/2 lg:pl-24 md:pl-16 flex flex-col md:items-start md:text-left items-center text-center">
                <h1 class="title-font sm:text-7xl text-3xl mb-4 font-light text-blue-900 tracking-widest">
                    CommuniCare
                </h1>
                <p class="leading-relaxed">
                    CommuniCareは職員間の効率的なコミュニケーションと利用者様の情報を一元管理することで介護施設運営の生産性を向上させることを目的としたプラットフォームです</p>
            </div>
        </div>
        <x-auth-links />
    </section>
</body>

</html>
