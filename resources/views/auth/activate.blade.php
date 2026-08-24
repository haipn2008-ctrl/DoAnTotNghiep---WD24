<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoàn thiện tài khoản | Quản lý phòng trọ</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-indigo-600">Thiết lập tài khoản lần đầu</p>
                    <h1 class="mt-2 text-2xl font-bold">
                        @switch($step)
                            @case('personal') Thông tin cá nhân @break
                            @case('identity') Giấy tờ tùy thân @break
                            @case('contact') Thông tin liên hệ @break
                            @default Tạo mật khẩu mới
                        @endswitch
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">Bước {{ $stepNumber }}/{{ $stepCount }} · {{ $user->email }}</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">{{ round(($stepNumber / $stepCount) * 100) }}%</span>
            </div>

            <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600" style="width: {{ ($stepNumber / $stepCount) * 100 }}%"></div></div>

            @if(session('error'))<div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="POST" action="{{ route('account.activation.step.store', $step) }}" class="mt-6 space-y-4">
                @csrf

                @if($step === 'personal')
                    <div><label for="name" class="mb-1.5 block text-sm font-semibold">Họ và tên</label><input id="name" name="name" value="{{ old('name', $profile['name']) }}" required maxlength="255" autofocus class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="date_of_birth" class="mb-1.5 block text-sm font-semibold">Ngày sinh</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $profile['date_of_birth']) }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label for="gender" class="mb-1.5 block text-sm font-semibold">Giới tính</label><select id="gender" name="gender" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="">Chọn giới tính</option><option value="male" @selected(old('gender', $profile['gender']) === 'male')>Nam</option><option value="female" @selected(old('gender', $profile['gender']) === 'female')>Nữ</option><option value="other" @selected(old('gender', $profile['gender']) === 'other')>Khác</option></select></div>
                    </div>
                @elseif($step === 'identity')
                    <div><label for="cccd" class="mb-1.5 block text-sm font-semibold">Số CCCD</label><input id="cccd" name="cccd" inputmode="numeric" value="{{ old('cccd', $profile['cccd']) }}" required minlength="12" maxlength="12" autofocus class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="cccd_issue_date" class="mb-1.5 block text-sm font-semibold">Ngày cấp</label><input id="cccd_issue_date" type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date', $profile['cccd_issue_date']) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label for="cccd_issue_place" class="mb-1.5 block text-sm font-semibold">Nơi cấp</label><input id="cccd_issue_place" name="cccd_issue_place" value="{{ old('cccd_issue_place', $profile['cccd_issue_place']) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    </div>
                @elseif($step === 'contact')
                    <div><label class="mb-1.5 block text-sm font-semibold">Email đăng nhập</label><input value="{{ $user->email }}" disabled class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-slate-500"></div>
                    <div><label for="phone" class="mb-1.5 block text-sm font-semibold">Số điện thoại</label><input id="phone" name="phone" inputmode="tel" value="{{ old('phone', $profile['phone']) }}" required maxlength="15" autofocus class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label for="address" class="mb-1.5 block text-sm font-semibold">Địa chỉ thường trú</label><textarea id="address" name="address" required maxlength="500" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('address', $profile['address']) }}</textarea></div>
                    <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-4 text-sm"><input type="checkbox" name="accept_terms" value="1" required class="mt-0.5 h-4 w-4"><span>Tôi xác nhận thông tin là chính xác và đồng ý với nội quy, điều khoản sử dụng cùng chính sách xử lý dữ liệu liên quan đến hợp đồng thuê.</span></label>
                @else
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Thông tin hồ sơ đã đầy đủ. Hãy tạo mật khẩu riêng để hoàn tất kích hoạt tài khoản.</div>
                    <div><label for="password" class="mb-1.5 block text-sm font-semibold">Mật khẩu mới</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" autofocus class="h-11 w-full rounded-lg border border-slate-200 px-3"><p class="mt-1 text-xs text-slate-500">Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</p></div>
                    <div><label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Nhập lại mật khẩu mới</label><input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                @endif

                <div class="flex items-center justify-between gap-3 pt-2">
                    @if($stepNumber > 1)<a href="{{ route('account.activation.step.show', ['personal', 'identity', 'contact', 'password'][$stepNumber - 2]) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Quay lại</a>@else<span></span>@endif
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $step === 'password' ? 'Hoàn tất kích hoạt' : 'Tiếp tục' }}</button>
                </div>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-sm font-medium text-slate-500">Đăng xuất</button></form>
        </div>
    </main>
</body>
</html>
