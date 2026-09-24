<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trio Blog</title>
    <!-- Render CSS dan JS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>