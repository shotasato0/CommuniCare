<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia><?php echo e(config('app.name', 'CommuniCare')); ?></title>

    <!-- ファビコンの設定 -->
    <link rel="icon" type="image/x-icon" href="<?php echo e(url('favicon.ico')); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Scripts -->
    <script>
        window.Ziggy = <?php echo json_encode(new \Tighten\Ziggy\Ziggy(), 15, 512) ?>;
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
</head>

<body class="font-sans antialiased">
    <div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div>
</body>

</html>
<?php /**PATH /var/www/html/resources/views/app.blade.php ENDPATH**/ ?>