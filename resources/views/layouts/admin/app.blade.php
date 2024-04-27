<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    @vite(['resources/sass/admin/app.scss', 'resources/js/admin/bootstrap.js','resources/css/admin/app.css'])
</head>
<body class="bg-gray-200">


@include('layouts.admin.header')

<div class="app">
    @include('layouts.admin.aside')

    <div class="content">
        @yield('content')
    </div>
</div>

</body>
</html>