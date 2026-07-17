@php
    $isEdit = isset($user);
    $action = $isEdit ? route('admin.users.update', $user) : route('admin.users.store');
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-semibold">Vui lòng kiểm tra lại thông tin.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="rounded-lg border border-slate-200 bg-white shadow-sm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Thông tin tài khoản</h3>
        <p class="text-sm text-slate-500">Thiết lập thông tin đăng nhập và vai trò hệ thống.</p>
    </div>

    <div class="grid gap-5 p-5 md:grid-cols-2">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $isEdit ? 'Mật khẩu mới' : 'Mật khẩu' }}</label>
            <input id="password" type="password" name="password" {{ $isEdit ? '' : 'required' }} class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @if ($isEdit)
                <p class="mt-1 text-xs text-slate-500">Để trống nếu không đổi mật khẩu.</p>
            @endif
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Xác nhận mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }} class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
        </div>

        <div class="md:col-span-2">
            <label for="role_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Vai trò</label>
            <select id="role_id" name="role_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->role_name }}</option>
                @endforeach
            </select>
            @error('role_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i class="bx bx-save text-lg"></i>
            {{ $isEdit ? 'Cập nhật' : 'Thêm tài khoản' }}
        </button>
    </div>
</form>
