<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ToolGates')</title>
    <link rel="icon" type="image/png" href="/assets/images/logo.png"/>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @section('meta')
    @stop

    @yield('meta')
</head>
<body>
<div id="app">
    <main>
        @yield('content')
    </main>

</div>
</body>
</html>
