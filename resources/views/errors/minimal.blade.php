@php
    $errorMessages = [
        401 => ['Chưa xác thực', 'Vui lòng đăng nhập để tiếp tục.'],
        403 => ['Không có quyền truy cập', 'Bạn không có quyền thực hiện thao tác này.'],
        404 => ['Không tìm thấy nội dung', 'Nội dung bạn yêu cầu không tồn tại hoặc đã được di chuyển.'],
        405 => ['Phương thức không được hỗ trợ', 'Yêu cầu này không được hệ thống hỗ trợ.'],
        408 => ['Yêu cầu hết thời gian', 'Yêu cầu mất quá nhiều thời gian xử lý. Vui lòng thử lại.'],
        409 => ['Dữ liệu đang xung đột', 'Không thể thực hiện thao tác do trạng thái dữ liệu hiện tại.'],
        419 => ['Phiên làm việc đã hết hạn', 'Vui lòng tải lại trang và thực hiện lại thao tác.'],
        422 => ['Dữ liệu không hợp lệ', 'Vui lòng kiểm tra lại thông tin đã nhập.'],
        429 => ['Quá nhiều yêu cầu', 'Bạn đã thao tác quá nhanh. Vui lòng chờ một lát rồi thử lại.'],
        500 => ['Hệ thống gặp sự cố', 'Đã xảy ra lỗi ngoài ý muốn. Vui lòng thử lại sau.'],
        503 => ['Hệ thống đang bảo trì', 'Dịch vụ tạm thời chưa sẵn sàng. Vui lòng quay lại sau.'],
    ];
    [$errorTitle, $defaultErrorMessage] = $errorMessages[$statusCode] ?? ['Không thể xử lý yêu cầu', 'Đã xảy ra lỗi. Vui lòng thử lại.'];
    $exceptionMessage = isset($exception) ? trim($exception->getMessage()) : '';
    $errorMessage = $exceptionMessage !== '' && preg_match('/[^\x00-\x7F]/u', $exceptionMessage)
        ? $exceptionMessage
        : $defaultErrorMessage;
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusCode }} | {{ $errorTitle }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-700 antialiased">
    <main class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-600">Lỗi {{ $statusCode }}</p>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">{{ $errorTitle }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $errorMessage }}</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <button type="button" onclick="window.history.back()" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quay lại</button>
            <a href="{{ url('/') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Về trang chủ</a>
        </div>
    </main>
</body>
</html>
