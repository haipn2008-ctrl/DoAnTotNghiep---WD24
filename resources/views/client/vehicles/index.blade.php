@extends('layouts.client.index')

@section('title', 'Phương tiện | Cổng khách thuê')
@section('page_title', 'Phương tiện của tôi')

@section('content')
    @php
        $statusLabels = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối'];
        $statusClasses = ['pending' => 'bg-amber-50 text-amber-700', 'approved' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-rose-50 text-rose-700'];
        $typeLabels = ['motorcycle' => 'Xe máy', 'electric_motorcycle' => 'Xe máy điện', 'bicycle' => 'Xe đạp'];
    @endphp

    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Tự quản lý thông tin</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Phương tiện của tôi</h2>
            <p class="mt-2 text-sm text-slate-500">Mỗi người đang ở được đăng ký tối đa một xe.</p>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        @if($contract)
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Đăng ký phương tiện mới</h3>
                <form method="POST" action="{{ route('client.vehicles.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5" data-vehicle-form>
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold">Chủ xe</label>
                    <select name="owner_tenant_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3">
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((int) old('owner_tenant_id', $tenant->id) === (int) $owner->id)>{{ $owner->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Loại xe</label>
                    <select name="vehicle_type" required class="h-11 w-full rounded-lg border border-slate-200 px-3" data-vehicle-type>
                        @foreach($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('vehicle_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="mb-1 block text-sm font-semibold">Tên xe</label><input name="vehicle_name" value="{{ old('vehicle_name') }}" maxlength="255" placeholder="Ví dụ: Honda Vision" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div data-license-wrap><label class="mb-1 block text-sm font-semibold">Biển số</label><input name="license_plate" value="{{ old('license_plate') }}" maxlength="50" class="h-11 w-full rounded-lg border border-slate-200 px-3" data-license-plate></div>
                <div class="flex items-end"><button class="h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white">Gửi đăng ký</button></div>
                <div class="md:col-span-2 xl:col-span-5">
                    <label class="mb-1 block text-sm font-semibold">Ảnh phương tiện <span class="font-normal text-slate-400">(không bắt buộc)</span></label>
                    <input type="file" name="vehicle_image" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" data-image-input>
                    <p class="mt-1 text-xs text-slate-500">Chấp nhận JPG, PNG hoặc WEBP, dung lượng tối đa 5 MB.</p>
                    <img class="mt-3 hidden h-32 w-48 rounded-lg object-cover ring-1 ring-slate-200" alt="Xem trước ảnh phương tiện" data-image-preview>
                </div>
                </form>
            </section>
        @else
            <section class="rounded-lg border border-sky-200 bg-sky-50 p-5 text-sky-900 shadow-sm">
                <h3 class="font-semibold">Chưa thể đăng ký phương tiện</h3>
                <p class="mt-2 text-sm leading-6">Tài khoản của bạn đã hoạt động, nhưng chưa có hợp đồng đã nhận phòng. Chức năng đăng ký phương tiện sẽ tự động mở sau khi ban quản lý xác nhận nhận phòng.</p>
            </section>
        @endif

        <section class="space-y-4">
            @forelse($vehicles as $vehicle)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-slate-950">{{ $vehicle->license_plate ?: 'Xe đạp không có biển số' }}</h3>
                            <p class="text-sm text-slate-500">{{ $typeLabels[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }} · {{ $vehicle->vehicle_name ?: 'Chưa ghi tên xe' }}</p>
                            <p class="mt-1 text-sm font-semibold text-indigo-700">Chủ xe: {{ $vehicle->tenant->full_name }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$vehicle->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$vehicle->status] ?? $vehicle->status }}</span>
                    </div>

                    @if($vehicle->review_note)
                        <p class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-700"><strong>Phản hồi:</strong> {{ $vehicle->review_note }}</p>
                    @endif

                    @if($vehicle->imageExists())
                        <a href="{{ route('client.vehicles.image', $vehicle) }}" data-image-modal data-image-title="Ảnh phương tiện {{ $vehicle->vehicle_name ?: '' }}" class="mt-4 inline-block">
                            <img src="{{ route('client.vehicles.image', $vehicle) }}" alt="Ảnh {{ $vehicle->vehicle_name ?: 'phương tiện' }}" class="h-36 w-52 rounded-lg object-cover ring-1 ring-slate-200">
                        </a>
                    @elseif($vehicle->vehicle_image)
                        <p class="mt-4 text-xs font-semibold text-amber-700">Ảnh phương tiện không còn tồn tại.</p>
                    @endif

                    @if($vehicle->status === \App\Models\Vehicle::STATUS_APPROVED)
                        <button type="button" class="mt-4 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white" data-change-vehicle="vehicle-edit-{{ $vehicle->id }}">
                            Đổi phương tiện
                        </button>
                        <p class="mt-2 text-xs text-slate-500">Thông tin mới sẽ được chuyển về trạng thái chờ quản trị viên duyệt lại.</p>
                    @endif

                    @if($vehicle->status !== \App\Models\Vehicle::STATUS_PENDING)
                        <form id="vehicle-edit-{{ $vehicle->id }}" method="POST" action="{{ route('client.vehicles.update', $vehicle) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5 {{ $vehicle->status === \App\Models\Vehicle::STATUS_APPROVED ? 'hidden' : '' }}" data-vehicle-form>
                            @csrf
                            @method('PUT')
                            <select name="vehicle_type" required class="h-10 rounded-lg border border-slate-200 px-3" data-vehicle-type>
                                @foreach($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($vehicle->vehicle_type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="vehicle_name" value="{{ $vehicle->vehicle_name }}" maxlength="255" class="h-10 rounded-lg border border-slate-200 px-3" placeholder="Tên xe">
                            <div data-license-wrap><input name="license_plate" value="{{ $vehicle->license_plate }}" maxlength="50" class="h-10 w-full rounded-lg border border-slate-200 px-3" placeholder="Biển số" data-license-plate></div>
                            <button class="h-10 rounded-lg border border-indigo-200 text-sm font-semibold text-indigo-700">Lưu và gửi duyệt lại</button>
                            <div class="md:col-span-2 xl:col-span-5">
                                <label class="mb-1 block text-sm font-semibold">Thay ảnh phương tiện</label>
                                <input type="file" name="vehicle_image" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" data-image-input>
                                <p class="mt-1 text-xs text-slate-500">Để trống nếu muốn giữ ảnh hiện tại.</p>
                                <img class="mt-3 hidden h-32 w-48 rounded-lg object-cover ring-1 ring-slate-200" alt="Xem trước ảnh mới" data-image-preview>
                            </div>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.vehicles.destroy', $vehicle) }}" class="mt-3 text-right" onsubmit="const reason = prompt('Nhập lý do hủy/gỡ phương tiện (ít nhất 10 ký tự):'); if (!reason || reason.trim().length < 10) { alert('Lý do phải có ít nhất 10 ký tự.'); return false; } this.elements.removal_reason.value = reason.trim(); return true;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="removal_reason">
                        @if($vehicle->status === \App\Models\Vehicle::STATUS_PENDING)
                            <button class="rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hủy yêu cầu</button>
                        @else
                            <button class="text-sm font-semibold text-rose-600">{{ $vehicle->status === \App\Models\Vehicle::STATUS_APPROVED ? 'Gỡ phương tiện' : 'Hủy yêu cầu' }}</button>
                        @endif
                    </form>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Bạn chưa đăng ký phương tiện nào.</div>
            @endforelse
        </section>

        @if($archivedVehicles->isNotEmpty())
            <section class="space-y-3">
                <div>
                    <h3 class="font-semibold text-slate-950">Phương tiện đã hủy hoặc đã gỡ</h3>
                    <p class="mt-1 text-sm text-slate-500">Đăng ký lại sẽ chuyển phương tiện về trạng thái chờ quản trị viên duyệt.</p>
                </div>
                @foreach($archivedVehicles as $vehicle)
                    <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $vehicle->display_license_plate ?: 'Xe đạp không có biển số' }}</p>
                            <p class="text-sm text-slate-500">{{ $typeLabels[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }} · Chủ xe: {{ $vehicle->tenant->full_name }}</p>
                            @if($vehicle->removal_reason)<p class="mt-1 text-xs text-slate-500">Lý do: {{ $vehicle->removal_reason }}</p>@endif
                        </div>
                        <form method="POST" action="{{ route('client.vehicles.restore', $vehicle) }}" onsubmit="const reason = prompt('Nhập lý do đăng ký lại phương tiện (ít nhất 10 ký tự):'); if (!reason || reason.trim().length < 10) { alert('Lý do phải có ít nhất 10 ký tự.'); return false; } this.elements.restoration_reason.value = reason.trim(); return confirm('Xác nhận gửi lại phương tiện để duyệt?');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="restoration_reason">
                            <button class="rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Đăng ký lại</button>
                        </form>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-vehicle-form]').forEach(function (form) {
            const type = form.querySelector('[data-vehicle-type]');
            const licenseWrap = form.querySelector('[data-license-wrap]');
            const license = form.querySelector('[data-license-plate]');

            function syncLicenseField() {
                const isBicycle = type.value === 'bicycle';
                licenseWrap.classList.toggle('hidden', isBicycle);
                license.disabled = isBicycle;
                license.required = !isBicycle;
            }

            type.addEventListener('change', syncLicenseField);
            syncLicenseField();

            const imageInput = form.querySelector('[data-image-input]');
            const imagePreview = form.querySelector('[data-image-preview]');
            imageInput.addEventListener('change', function () {
                const file = imageInput.files[0];
                if (!file) {
                    imagePreview.removeAttribute('src');
                    imagePreview.classList.add('hidden');
                    return;
                }

                imagePreview.src = URL.createObjectURL(file);
                imagePreview.classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-change-vehicle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = document.getElementById(button.dataset.changeVehicle);
                form.classList.remove('hidden');
                button.classList.add('hidden');
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    </script>
@endpush
