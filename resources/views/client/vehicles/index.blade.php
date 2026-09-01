@extends('layouts.client.index')

@section('title', 'Phương tiện | Cổng khách thuê')
@section('page_title', 'Phương tiện của tôi')

@section('content')
    @php
        $statusLabels = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối'];
        $statusClasses = ['pending' => 'bg-amber-50 text-amber-700', 'approved' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-rose-50 text-rose-700'];
        $typeLabels = ['motorcycle' => 'Xe máy', 'electric_motorcycle' => 'Xe máy điện', 'bicycle' => 'Xe đạp'];
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white shadow-lg shadow-indigo-200/50">
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div><p class="text-sm font-semibold text-indigo-100">QUẢN LÝ LƯU TRÚ</p><h2 class="mt-1 text-2xl font-bold">Phương tiện của tôi</h2><p class="mt-1 text-sm text-indigo-100">Khai báo và theo dõi phương tiện đang gửi tại khu trọ.</p></div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20"><i class="bx bx-cycling text-3xl"></i></div>
            </div>
            <span class="absolute -bottom-14 -right-8 h-36 w-36 rounded-full bg-white/10"></span>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        @if($contract)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"><i class="bx bx-check-shield text-xl"></i></span>
                        <div><h3 class="font-bold text-slate-950">Xác nhận tình trạng phương tiện</h3><p class="text-sm text-slate-500">Mỗi người đang ở cần xác nhận để ban quản lý không nhầm giữa chưa khai báo và không có xe.</p></div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($owners as $owner)
                        @php($declaration = $declarations->get($owner->id))
                        @php($declarationStatus = $declaration?->vehicle_declaration_status ?? \App\Models\ContractTenant::VEHICLE_UNDECLARED)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[minmax(220px,1fr)_auto] lg:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $owner->full_name }}</p><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $declarationStatus === 'has_vehicle' ? 'bg-indigo-50 text-indigo-700' : ($declarationStatus === 'no_vehicle' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700') }}">{{ $declaration?->vehicle_declaration_label ?? 'Chưa khai báo' }}</span></div>
                                <p class="mt-1 text-sm text-slate-500">Phòng {{ $contract->room?->room_code }} · {{ $contract->contract_code }}</p>
                            </div>
                            <form method="POST" action="{{ route('client.vehicles.declare') }}" class="grid grid-cols-3 gap-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="owner_tenant_id" value="{{ $owner->id }}">
                                <button name="declaration_status" value="no_vehicle" class="flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-emerald-200 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"><i class="bx bx-walk text-lg"></i><span>Không có xe</span></button>
                                <button name="declaration_status" value="later" class="flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-amber-200 px-3 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50"><i class="bx bx-time-five text-lg"></i><span>Bổ sung sau</span></button>
                                <button name="declaration_status" value="has_vehicle" class="flex min-h-11 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"><i class="bx bx-cycling text-lg"></i><span>Có phương tiện</span></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            @php($canRegisterVehicle = $declarations->contains(fn ($member) => $member->vehicle_declaration_status === \App\Models\ContractTenant::VEHICLE_HAS))
            @if($canRegisterVehicle)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700"><i class="bx bx-plus-circle text-xl"></i></span><div><h3 class="font-bold text-slate-950">Đăng ký phương tiện mới</h3><p class="text-sm text-slate-500">Điền thông tin chính xác để ban quản lý nhận diện và duyệt xe.</p></div></div></div>
                <form method="POST" action="{{ route('client.vehicles.store') }}" enctype="multipart/form-data" class="grid gap-5 p-6 md:grid-cols-2" data-vehicle-form>
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
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-semibold">Ảnh phương tiện <span class="font-normal text-slate-400">(không bắt buộc)</span></label>
                    <input type="file" name="vehicle_image" accept="image/jpeg,image/png,image/webp" class="block w-full cursor-pointer rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 p-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:font-semibold file:text-white" data-image-input>
                    <p class="mt-1 text-xs text-slate-500">Chấp nhận JPG, PNG hoặc WEBP, dung lượng tối đa 5 MB.</p>
                    <img class="mt-3 hidden h-32 w-48 rounded-lg object-cover ring-1 ring-slate-200" alt="Xem trước ảnh phương tiện" data-image-preview>
                </div>
                <div class="flex justify-end border-t border-slate-100 pt-5 md:col-span-2"><button class="inline-flex h-11 items-center gap-2 rounded-xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"><i class="bx bx-send text-lg"></i>Gửi đăng ký</button></div>
                </form>
            </section>
            @else
                <section class="rounded-xl border border-dashed border-indigo-200 bg-indigo-50/50 p-5 text-sm text-indigo-800">
                    Chọn <strong>Có phương tiện</strong> ở phía trên để mở biểu mẫu đăng ký xe.
                </section>
            @endif
        @else
            <section class="rounded-lg border border-sky-200 bg-sky-50 p-5 text-sky-900 shadow-sm">
                <h3 class="font-semibold">Chưa thể đăng ký phương tiện</h3>
                <p class="mt-2 text-sm leading-6">Tài khoản của bạn đã hoạt động, nhưng chưa có hợp đồng đã nhận phòng. Chức năng đăng ký phương tiện sẽ tự động mở sau khi ban quản lý xác nhận nhận phòng.</p>
            </section>
        @endif

        <section class="space-y-4">
            @forelse($vehicles as $vehicle)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="grid md:grid-cols-[240px_minmax(0,1fr)]">
                        <div class="min-h-48 bg-slate-100">
                            @if($vehicle->imageExists())
                                <a href="{{ route('client.vehicles.image', $vehicle) }}" data-image-modal data-image-title="Ảnh phương tiện {{ $vehicle->vehicle_name ?: '' }}" class="block h-full">
                                    <img src="{{ route('client.vehicles.image', $vehicle) }}" alt="Ảnh {{ $vehicle->vehicle_name ?: 'phương tiện' }}" class="h-full min-h-48 w-full object-cover">
                                </a>
                            @else
                                <div class="flex h-full min-h-48 flex-col items-center justify-center text-slate-400"><i class="bx bx-cycling text-5xl"></i><span class="mt-2 text-sm">Chưa có ảnh phương tiện</span></div>
                            @endif
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Biển số phương tiện</p><h3 class="mt-1 text-xl font-bold text-slate-950">{{ $vehicle->license_plate ?: 'Xe đạp không có biển số' }}</h3><p class="mt-1 text-sm text-slate-500">{{ $typeLabels[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }} · {{ $vehicle->vehicle_name ?: 'Chưa ghi tên xe' }}</p></div>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$vehicle->status] ?? 'bg-slate-100 text-slate-600' }}"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $statusLabels[$vehicle->status] ?? $vehicle->status }}</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Chủ phương tiện</p><p class="mt-1 font-semibold text-slate-900">{{ $vehicle->tenant->full_name }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Hình thức gửi</p><p class="mt-1 font-semibold text-emerald-700">Miễn phí</p></div></div>

                            @if($vehicle->review_note && $vehicle->status === \App\Models\Vehicle::STATUS_REJECTED)
                                <p class="mt-4 rounded-xl border border-rose-100 bg-rose-50 p-3 text-sm text-rose-700"><strong>Lý do từ chối:</strong> {{ $vehicle->review_note }}</p>
                            @elseif($vehicle->review_note)
                                <p class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-3 text-sm text-slate-600"><i class="bx bx-info-circle mr-1"></i>{{ $vehicle->review_note }}</p>
                            @endif

                    @if($vehicle->status === \App\Models\Vehicle::STATUS_APPROVED)
                        <span class="sr-only">Thông tin mới sẽ được chuyển về trạng thái chờ quản trị viên duyệt lại.</span>
                        <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100" data-change-vehicle="vehicle-edit-{{ $vehicle->id }}">
                            <i class="bx bx-refresh text-lg"></i>Đổi phương tiện
                        </button>
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
                                <img class="mt-3 hidden h-32 w-48 rounded-lg object-cover ring-1 ring-slate-200" alt="Xem trước ảnh mới" data-image-preview>
                            </div>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.vehicles.destroy', $vehicle) }}" class="mt-3 text-right" data-confirm="{{ $vehicle->status === \App\Models\Vehicle::STATUS_APPROVED ? 'Phương tiện sẽ được gỡ khỏi danh sách đang gửi và vẫn được lưu trong lịch sử.' : 'Yêu cầu đăng ký phương tiện này sẽ được hủy.' }}" data-confirm-label="{{ $vehicle->status === \App\Models\Vehicle::STATUS_APPROVED ? 'Gỡ phương tiện' : 'Hủy yêu cầu' }}" data-reason-input="removal_reason" data-reason-placeholder="Nhập lý do hủy hoặc gỡ phương tiện...">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="removal_reason">
                        @if($vehicle->status === \App\Models\Vehicle::STATUS_PENDING)
                            <button class="rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hủy yêu cầu</button>
                        @else
                            <button class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50"><i class="bx bx-trash text-lg"></i>{{ $vehicle->status === \App\Models\Vehicle::STATUS_APPROVED ? 'Gỡ phương tiện' : 'Hủy yêu cầu' }}</button>
                        @endif
                    </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Bạn chưa đăng ký phương tiện nào.</div>
            @endforelse
        </section>

        @if($archivedVehicles->isNotEmpty())
            <section class="space-y-3">
                <div>
                    <h3 class="font-semibold text-slate-950">Phương tiện đã hủy hoặc đã gỡ</h3>
                </div>
                @foreach($archivedVehicles as $vehicle)
                    <article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $vehicle->display_license_plate ?: 'Xe đạp không có biển số' }}</p>
                            <p class="text-sm text-slate-500">{{ $typeLabels[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }} · Chủ xe: {{ $vehicle->tenant->full_name }}</p>
                            @if($vehicle->removal_reason)<p class="mt-1 text-xs text-slate-500">Lý do: {{ $vehicle->removal_reason }}</p>@endif
                        </div>
                        <form method="POST" action="{{ route('client.vehicles.restore', $vehicle) }}" data-confirm="Phương tiện sẽ được gửi lại cho ban quản lý xét duyệt." data-confirm-label="Gửi đăng ký lại" data-reason-input="restoration_reason" data-reason-placeholder="Nhập lý do đăng ký lại phương tiện...">
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
