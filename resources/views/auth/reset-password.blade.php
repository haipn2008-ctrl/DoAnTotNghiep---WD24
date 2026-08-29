<x-auth-layout
    title="Đặt lại mật khẩu"
    eyebrow="Bảo mật tài khoản"
    description="Tạo mật khẩu mới có ít nhất 8 ký tự cho tài khoản của bạn."
>
    @if ($errors->any())
        <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label for="code" class="mb-1.5 block text-sm font-semibold text-slate-700">Mã xác thực 6 số</label>
            <input id="code" type="text" inputmode="numeric" name="code" value="{{ old('code') }}" required maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code"
                class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu mới</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Nhập lại mật khẩu mới</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
        </div>
        <label class="flex w-fit cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
            <input id="toggle-password-visibility" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Hiển thị mật khẩu
        </label>
        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
            Đặt lại mật khẩu
        </button>
    </form>
    <script>
        document.getElementById('toggle-password-visibility')?.addEventListener('change', function () {
            const type = this.checked ? 'text' : 'password';
            document.getElementById('password').type = type;
            document.getElementById('password_confirmation').type = type;
        });
    </script>
</x-auth-layout>
