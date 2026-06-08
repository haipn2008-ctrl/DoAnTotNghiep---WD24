<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý phòng trọ</title>

    <link rel="stylesheet" href="{{ asset('coreui/css/style.css') }}">
</head>

<body>

    @include('layouts.sidebar')

    <div class="wrapper d-flex flex-column min-vh-100 bg-light">

        @include('layouts.navbar')

        <div class="body flex-grow-1 px-3 py-3">
            @yield('content')
        </div>

    </div>

    <script src="{{ asset('coreui/js/main.js') }}"></script>

</body>

</html>
