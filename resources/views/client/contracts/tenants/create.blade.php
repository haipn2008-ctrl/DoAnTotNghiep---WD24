@extends('layouts.client.index')

@section('title', 'Khai báo người thuê | Cổng khách thuê')
@section('page_title', 'Khai báo người thuê')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('client.contracts.show', $contract) }}" class="text-sm font-semibold text-indigo-700">← Quay lại hợp đồng</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">Thêm người thuê vào phòng {{ $contract->room->room_code }}</h2>
            <p class="mt-1 text-sm text-slate-500">Hợp đồng {{ $contract->contract_code }} · {{ $contract->currentMembers->count() }}/{{ $contract->room->max_people }} người</p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><p class="font-semibold">Vui lòng kiểm tra lại thông tin:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm leading-6 text-indigo-900">
        <p class="font-bold">Quyền và trách nhiệm trong hợp đồng</p>
        <p class="mt-1">Người được thêm là <strong>người thuê trong phòng</strong> và có tên trong danh sách của hợp đồng. Người này <strong>không được cấp tài khoản riêng</strong>; người thuê đại diện tiếp tục là đầu mối duy nhất gửi yêu cầu và nhận thông báo từ quản lý.</p>
    </div>

    <form method="POST" action="{{ route('client.contracts.members.store', $contract) }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Thông tin người thuê</h3></div>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên *</label><input name="full_name" value="{{ old('full_name') }}" required maxlength="150" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh *</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Giới tính *</label><select name="gender" required class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><option value="">Chọn giới tính</option><option value="male" @selected(old('gender') === 'male')>Nam</option><option value="female" @selected(old('gender') === 'female')>Nữ</option><option value="other" @selected(old('gender') === 'other')>Khác</option></select></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số CCCD *</label><input name="identity_number" value="{{ old('identity_number') }}" required inputmode="numeric" minlength="12" maxlength="12" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD *</label><input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date') }}" max="{{ today()->toDateString() }}" required class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD *</label><input name="cccd_issue_place" value="{{ old('cccd_issue_place') }}" required maxlength="255" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại *</label><input name="phone" value="{{ old('phone') }}" required minlength="10" maxlength="15" inputmode="tel" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label><input type="email" name="email" value="{{ old('email') }}" maxlength="255" placeholder="Không bắt buộc" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ thường trú *</label><input name="address" value="{{ old('address') }}" required maxlength="500" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
        </div>
        <div class="grid gap-4 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh mặt trước CCCD *</label><input type="file" name="identity_front" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh mặt sau CCCD *</label><input type="file" name="identity_back" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700"></div>
        </div>
        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end">
            <a href="{{ route('client.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
            <button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Gửi hồ sơ để duyệt</button>
        </div>
    </form>
</div>
@endsection
