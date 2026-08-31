@extends('layouts.admin.index')

@section('title', 'Thêm người vào phòng | Quản lý phòng trọ')
@section('page_title', 'Thêm người vào phòng')

@section('content')
    @php($selectedSource = old('source', 'existing'))
    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Hợp đồng {{ $contract->contract_code }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Thêm người vào phòng {{ $contract->room->room_code }}</h2>
                <p class="mt-2 text-sm text-slate-500">Phòng đang có {{ $contract->currentMembers->count() }}/{{ $contract->room->max_people }} người. Người được thêm sẽ vào trạng thái đang ở ngay.</p>
            </div>
            <a href="{{ route('admin.contracts.show', $contract).'#contract-tenants' }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Quay lại hợp đồng</a>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">
            Sau khi lưu, hệ thống sẽ chuyển sang màn hình ghi mốc điện nước đúng ngày người này vào ở. Mốc chỉ dùng để đối chiếu và không tự chia tiền.
        </div>

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Chưa thể thêm người thuê</p>
                <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.contract-tenants.store', $contract) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-950">1. Chọn nguồn hồ sơ</h3>
                    <p class="mt-1 text-sm text-slate-500">Không tạo lại hồ sơ nếu khách đã tồn tại trên hệ thống.</p>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <span class="flex items-start gap-3">
                            <input type="radio" name="source" value="existing" class="mt-1 h-4 w-4 text-indigo-600" @checked($selectedSource === 'existing')>
                            <span><strong class="block text-slate-950">Khách có sẵn</strong><span class="mt-1 block text-sm text-slate-500">Chọn hồ sơ hiện không thuộc phòng hoặc danh sách chờ nào.</span></span>
                        </span>
                    </label>
                    <label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <span class="flex items-start gap-3">
                            <input type="radio" name="source" value="new" class="mt-1 h-4 w-4 text-indigo-600" @checked($selectedSource === 'new')>
                            <span><strong class="block text-slate-950">Người hoàn toàn mới</strong><span class="mt-1 block text-sm text-slate-500">Tạo hồ sơ khách thuê mới và lưu lại để dùng về sau.</span></span>
                        </span>
                    </label>
                </div>
            </section>

            <section data-source-panel="existing" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-950">2. Chọn khách có sẵn</h3>
                </div>
                <div class="space-y-4 p-5">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">Khách thuê đủ điều kiện</span>
                        <select name="tenant_id" data-existing-tenant class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Chọn khách thuê</option>
                            @foreach($availableTenants as $tenant)
                                <option value="{{ $tenant->id }}" data-has-documents="{{ $tenant->document?->hasCompleteImagePair() ? '1' : '0' }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                    {{ $tenant->full_name }} — CCCD {{ $tenant->cccd }} — {{ $tenant->phone }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if($availableTenants->isEmpty())
                        <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">Không có khách sẵn nào đủ điều kiện. Hãy chọn “Người hoàn toàn mới”.</p>
                    @else
                        <p class="text-xs leading-5 text-slate-500">Khách đang ở hoặc đang nằm trong danh sách chờ của phòng khác không xuất hiện tại đây.</p>
                    @endif
                </div>
            </section>

            <section data-source-panel="new" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-950">2. Nhập hồ sơ người mới</h3>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</span><input name="full_name" value="{{ old('full_name') }}" maxlength="150" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ today()->subYears(18)->toDateString() }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Giới tính</span><select name="gender" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"><option value="">Chọn giới tính</option><option value="male" @selected(old('gender') === 'male')>Nam</option><option value="female" @selected(old('gender') === 'female')>Nữ</option><option value="other" @selected(old('gender') === 'other')>Khác</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Số CCCD</span><input name="identity_number" value="{{ old('identity_number') }}" inputmode="numeric" minlength="12" maxlength="12" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD</span><input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date') }}" max="{{ today()->toDateString() }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD</span><input name="cccd_issue_place" value="{{ old('cccd_issue_place') }}" maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</span><input name="phone" value="{{ old('phone') }}" inputmode="numeric" minlength="10" maxlength="15" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Email (không bắt buộc)</span><input type="email" name="email" value="{{ old('email') }}" maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></label>
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ thường trú</span><textarea name="address" maxlength="500" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ old('address') }}</textarea></label>
                </div>
            </section>

            <section data-identity-panel class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-950">Ảnh CCCD</h3>
                    <p data-identity-help class="mt-1 text-sm text-slate-500">Người mới phải có đủ ảnh hai mặt CCCD.</p>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Mặt trước</span><input type="file" name="identity_front" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5"></label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Mặt sau</span><input type="file" name="identity_back" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5"></label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="block max-w-md"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày giờ bắt đầu ở</span><input type="datetime-local" name="actual_move_in_at" value="{{ old('actual_move_in_at', now()->format('Y-m-d\TH:i')) }}" min="{{ $contract->actual_move_in_at->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-semibold"></label>
                <p class="mt-2 text-xs text-slate-500">Thời điểm này được lưu vào lịch sử cư trú và dùng làm ngày ghi mốc điện nước tiếp theo.</p>
            </section>

            <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <a href="{{ route('admin.contracts.show', $contract).'#contract-tenants' }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700">Hủy</a>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"><i class="bx bx-user-plus text-lg"></i>Thêm người và ghi mốc điện nước</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sourceInputs = [...document.querySelectorAll('input[name="source"]')];
            const panels = [...document.querySelectorAll('[data-source-panel]')];
            const tenantSelect = document.querySelector('[data-existing-tenant]');
            const identityPanel = document.querySelector('[data-identity-panel]');
            const identityHelp = document.querySelector('[data-identity-help]');
            const identityInputs = [...identityPanel.querySelectorAll('input[type="file"]')];

            const sync = () => {
                const source = sourceInputs.find(input => input.checked)?.value || 'existing';
                panels.forEach(panel => panel.classList.toggle('hidden', panel.dataset.sourcePanel !== source));
                const selectedOption = tenantSelect.options[tenantSelect.selectedIndex];
                const needsExistingDocuments = source === 'existing' && selectedOption?.value && selectedOption.dataset.hasDocuments !== '1';
                const showIdentity = source === 'new' || needsExistingDocuments;
                identityPanel.classList.toggle('hidden', !showIdentity);
                identityInputs.forEach(input => input.required = showIdentity);
                identityHelp.textContent = source === 'new'
                    ? 'Người mới phải có đủ ảnh hai mặt CCCD.'
                    : 'Hồ sơ được chọn chưa có đủ ảnh CCCD. Vui lòng bổ sung hai mặt.';
                tenantSelect.required = source === 'existing';
            };

            sourceInputs.forEach(input => input.addEventListener('change', sync));
            tenantSelect.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
