@php
    $isEdit = isset($tenant);
    $action = $isEdit ? route('admin.tenants.update', $tenant) : route('admin.tenants.store');
    $selectedUser = old('user_id', $tenant->user_id ?? '');
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

<form action="{{ $action }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Thông tin khách thuê</h3>
        <p class="text-sm text-slate-500">Thông tin định danh, liên hệ và tài khoản đăng nhập.</p>
    </div>

    <div class="grid gap-5 p-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="user_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Tài khoản đăng nhập</label>
            <select id="user_id" name="user_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                <option value="">Chọn tài khoản</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) $selectedUser === (string) $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
            @error('user_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="full_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label>
            <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $tenant->full_name ?? '') }}" placeholder="Nguyễn Văn A" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('full_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="date_of_birth" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh</label>
            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $tenant->date_of_birth ?? '') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('date_of_birth') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="cccd" class="mb-1.5 block text-sm font-semibold text-slate-700">CCCD</label>
            <input id="cccd" type="text" name="cccd" value="{{ old('cccd', $tenant->cccd ?? '') }}" placeholder="012345678901" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('cccd') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="cccd_issue_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD</label>
            <input id="cccd_issue_date" type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date', $tenant->cccd_issue_date ?? '') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('cccd_issue_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label for="cccd_issue_place" class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD</label>
            <input id="cccd_issue_place" type="text" name="cccd_issue_place" value="{{ old('cccd_issue_place', $tenant->cccd_issue_place ?? '') }}" placeholder="Cục Cảnh sát QLHC về TTXH" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('cccd_issue_place') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone', $tenant->phone ?? '') }}" placeholder="0366xxxxxx" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $tenant->email ?? '') }}" placeholder="example@gmail.com" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ</label>
            <textarea id="address" name="address" rows="3" placeholder="Nhập địa chỉ" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('address', $tenant->address ?? '') }}</textarea>
            @error('address') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
        <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i class="bx bx-save text-lg"></i>
            {{ $isEdit ? 'Cập nhật' : 'Thêm khách thuê' }}
        </button>
    </div>
</form>
