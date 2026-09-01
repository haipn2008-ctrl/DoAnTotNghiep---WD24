@extends('layouts.admin.index')

@section('title', 'Chi tiết phòng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết phòng')

@php
    $statusOptions = [
        'available' => ['label' => 'Trống', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'occupied' => ['label' => 'Đang thuê', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'maintenance' => ['label' => 'Bảo trì', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'retired' => ['label' => 'Ngừng khai thác', 'class' => 'bg-slate-100 text-slate-600 ring-slate-200', 'dot' => 'bg-slate-400'],
    ];
    $status = $statusOptions[$room->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
    $conditionLabels = [
        'normal' => 'Sử dụng bình thường', 'good' => 'Sử dụng bình thường',
        'worn' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng',
    ];
    $evidenceLabels = [
        'baseline' => 'Trước khi bàn giao phòng', 'handover' => 'Trước khi bàn giao (dữ liệu cũ)',
        'checkout' => 'Sau khi nhận lại phòng', 'maintenance' => 'Bảo trì (dữ liệu cũ)',
        'general' => 'Khác', 'legacy' => 'Ảnh dữ liệu cũ',
    ];
    $roomAssets = $room->amenities->where('category', \App\Models\Amenity::CATEGORY_ASSET);
    $totalAssetQuantity = $roomAssets->sum(fn ($asset) => (int) ($asset->pivot->quantity ?? 1));
@endphp

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Không thể lưu ảnh hiện trạng. Vui lòng kiểm tra:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Phòng {{ $room->room_code }}</h2>
                <p class="mt-1 text-sm text-slate-500">Tổng quan thông tin, người ở và toàn bộ tài sản trong phòng.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($room->status !== \App\Models\Room::STATUS_RETIRED)
                <a href="{{ route('admin.rooms.edit', $room) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>
                @endif
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid lg:grid-cols-[minmax(300px,0.8fr)_minmax(0,1.7fr)]">
            <div class="relative min-h-72 overflow-hidden bg-slate-100">
                @if ($room->thumbnail && ! str_starts_with($room->thumbnail, 'room-evidence/'))
                    <img src="{{ route('admin.rooms.thumbnail', $room) }}" alt="Phòng {{ $room->room_code }}" class="absolute inset-0 h-full w-full object-cover">
                @else
                    <div class="flex h-full min-h-72 w-full flex-col items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/80 shadow-sm"><i class="bx bx-image text-4xl"></i></span>
                        <p class="mt-3 text-sm font-medium">Chưa có ảnh đại diện</p>
                    </div>
                @endif
                    <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold shadow-sm ring-1 {{ $status['class'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
            </div>

            <div class="p-5 sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Thông tin phòng</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-950">Không gian &amp; giá thuê</h3>
                    </div>
                    <span class="rounded-lg bg-indigo-50 px-3 py-2 font-mono text-sm font-bold text-indigo-700">{{ $room->room_code }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 py-5 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm"><i class="bx bx-building-house text-xl"></i></span>
                        <p class="mt-3 text-xs font-medium text-slate-500">Vị trí</p>
                        <p class="mt-1 font-bold text-slate-950">Tầng {{ $room->floor }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm"><i class="bx bx-wallet text-xl"></i></span>
                        <p class="mt-3 text-xs font-medium text-slate-500">Giá thuê/tháng</p>
                        <p class="mt-1 font-bold text-slate-950">{{ number_format($room->price, 0, ',', '.') }}đ</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-sky-600 shadow-sm"><i class="bx bx-area text-xl"></i></span>
                        <p class="mt-3 text-xs font-medium text-slate-500">Diện tích</p>
                        <p class="mt-1 font-bold text-slate-950">{{ $room->area }} m²</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-violet-600 shadow-sm"><i class="bx bx-group text-xl"></i></span>
                        <p class="mt-3 text-xs font-medium text-slate-500">Sức chứa</p>
                        <p class="mt-1 font-bold text-slate-950">{{ $room->current_people }}/{{ $room->max_people ?? 4 }} người</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mô tả</p>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ $room->description ?: 'Chưa có mô tả cho phòng này.' }}</p>
                </div>
            </div>
            </div>
        </section>

        <nav aria-label="Điều hướng nhanh" class="grid grid-cols-2 gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:grid-cols-4">
            <a href="#nguoi-dang-o" class="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-group text-xl"></i>Người đang ở</a>
            <a href="#phuong-tien" class="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-cycling text-xl"></i>Phương tiện</a>
            <a href="#tai-san" class="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-package text-xl"></i>Tài sản ({{ $totalAssetQuantity }})</a>
            <a href="#nhat-ky" class="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-images text-xl"></i>Ảnh hiện trạng</a>
        </nav>

        <section id="nguoi-dang-o" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Người đang ở</h3>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
                    {{ $occupancyContract?->number_of_people ?? 0 }} người
                </span>
            </div>

            @if ($occupancyContract)
                <div class="grid gap-4 p-5 lg:grid-cols-[280px_1fr]">
                    @php
                        $representative = $occupancyContract->representative ?: $occupancyContract->tenant;
                    @endphp
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Người thuê đại diện · Tài khoản liên hệ</p>
                        @if ($representative)
                            <a href="{{ route('admin.tenants.show', $representative) }}" class="mt-2 block font-bold text-slate-950 hover:text-indigo-700 hover:underline">
                                {{ $representative->full_name }}
                            </a>
                            <p class="mt-1 text-sm text-slate-600">{{ $representative->phone ?: 'Chưa có số điện thoại' }}</p>
                            <a href="{{ route('admin.tenants.show', $representative) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-700">
                                Xem hồ sơ khách thuê <i class="bx bx-right-arrow-alt text-base"></i>
                            </a>
                        @else
                            <p class="mt-2 text-sm text-rose-700">Hợp đồng chưa xác định người đại diện.</p>
                        @endif
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">Danh sách người thuê trong phòng</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($members as $member)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $member->full_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $member->phone ?: 'Chưa có số điện thoại' }}</span>
                                    </span>
                                    @if ($member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE)
                                        <span class="shrink-0 rounded-full bg-indigo-100 px-2 py-1 text-[11px] font-semibold text-indigo-700">Người thuê đại diện · Có tài khoản</span>
                                    @elseif ($member->tenant)
                                        <span class="flex shrink-0 flex-col items-end gap-1"><a href="{{ route('admin.tenants.show', $member->tenant) }}" class="text-xs font-semibold text-indigo-700">Xem hồ sơ</a><span class="text-[11px] text-slate-400">Không cấp tài khoản</span></span>
                                    @else
                                        <span class="text-xs text-slate-400">Không cần tài khoản</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($unidentifiedMembers > 0)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Còn {{ $unidentifiedMembers }} người chỉ có số lượng nhưng chưa có hồ sơ danh tính. Đây có thể là dữ liệu hợp đồng cũ; quản trị viên cần bổ sung khi hợp đồng còn là bản nháp hoặc trong lần cập nhật hồ sơ phù hợp.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="p-5">
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                        Phòng hiện không có hợp đồng đang ở. Trạng thái nhân sự mong đợi là 0 người.
                    </div>
                </div>
            @endif
        </section>

        <section id="phuong-tien" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Phương tiện đang gửi</h3>
                    <p class="text-sm text-slate-500">Chỉ hiển thị phương tiện đã được duyệt của người đang ở trong phòng.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">{{ $approvedVehicles->count() }} xe</span>
                    <a href="{{ route('admin.vehicles.index', ['status' => 'approved', 'room_id' => $room->id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Quản lý</a>
                </div>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($approvedVehicles as $vehicle)
                    <article class="flex gap-3 rounded-lg border border-slate-200 p-3">
                        @if($vehicle->imageExists())
                            <a href="{{ route('admin.vehicles.image', $vehicle) }}" data-image-modal data-image-title="Ảnh {{ $vehicle->vehicle_name ?: 'phương tiện' }}" class="shrink-0">
                                <img src="{{ route('admin.vehicles.image', $vehicle) }}" alt="Ảnh phương tiện" class="h-16 w-24 rounded-lg object-cover ring-1 ring-slate-200">
                            </a>
                        @elseif($vehicle->vehicle_image)
                            <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg bg-amber-50 px-2 text-center text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Ảnh không còn tồn tại</span>
                        @else
                            <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><i class="bx bx-cycling text-2xl"></i></span>
                        @endif
                        <div class="min-w-0">
                            <p class="font-bold text-slate-950">{{ $vehicle->display_license_plate ?: 'Không có biển số' }}</p>
                            <p class="mt-1 truncate text-sm text-slate-600">{{ ['motorcycle' => 'Xe máy', 'electric_motorcycle' => 'Xe máy điện', 'bicycle' => 'Xe đạp'][$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->vehicle_name ?: 'Chưa ghi tên' }}</p>
                            <a href="{{ route('admin.tenants.show', $vehicle->tenant) }}" class="mt-1 block truncate text-xs font-semibold text-indigo-700 hover:underline">{{ $vehicle->tenant?->full_name ?? 'Không xác định' }}</a>
                        </div>
                    </article>
                @empty
                    <div class="sm:col-span-2 xl:col-span-3 rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">Phòng chưa có phương tiện nào được duyệt.</div>
                @endforelse
            </div>
        </section>

        <section id="tai-san" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-5 sm:px-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-950">Tài sản có trong phòng</h3>
                    <p class="mt-1 text-sm text-slate-500">Danh sách đầy đủ thiết bị và vật dụng được bàn giao cùng phòng.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-bold text-indigo-700">{{ $roomAssets->count() }} loại</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700">{{ $totalAssetQuantity }} món</span>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($roomAssets as $asset)
                        @php $isDamaged = $asset->pivot->condition === 'damaged'; @endphp
                        <article class="flex min-h-44 flex-col rounded-xl border {{ $isDamaged ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-white' }} p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                @if($asset->pivot->image_path)
                                    <a href="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" data-image-modal data-image-title="{{ $asset->name }} · Phòng {{ $room->room_code }}" class="block overflow-hidden rounded-xl">
                                        <img src="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" alt="{{ $asset->name }}" loading="lazy" class="h-20 w-28 object-cover ring-1 ring-slate-200 transition hover:scale-105">
                                    </a>
                                @else
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isDamaged ? 'bg-rose-100 text-rose-600' : 'bg-indigo-50 text-indigo-600' }}"><i class="bx bx-package text-2xl"></i></span>
                                @endif
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">SL: {{ $asset->pivot->quantity }}</span>
                            </div>
                            <h4 class="mt-3 text-base font-bold text-slate-950">{{ $asset->name }}</h4>
                            <p class="mt-1 inline-flex items-center gap-1.5 text-sm font-medium {{ $isDamaged ? 'text-rose-700' : 'text-emerald-700' }}"><i class="bx {{ $isDamaged ? 'bx-error-circle' : 'bx-check-circle' }} text-lg"></i>{{ $conditionLabels[$asset->pivot->condition] ?? 'Không xác định' }}</p>
                            <p class="mt-auto border-t border-slate-200/80 pt-3 text-sm text-slate-500"><span class="font-medium text-slate-700">Ghi chú:</span> {{ $asset->pivot->note ?: 'Không có' }}</p>
                        </article>
                    @empty
                        <div class="sm:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm"><i class="bx bx-package text-2xl"></i></span>
                            <p class="mt-3 font-semibold text-slate-700">Chưa khai báo tài sản trong phòng</p>
                            <p class="mt-1 text-sm text-slate-500">Bấm “Cập nhật” để bổ sung thiết bị và vật dụng bàn giao.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="nhat-ky" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Nhật ký ảnh hiện trạng</h3>
                </div>
                <button type="button" data-room-evidence-toggle aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>Thêm nhật ký
                </button>
            </div>

            <form action="{{ route('admin.rooms.evidence.store', $room) }}" method="POST" enctype="multipart/form-data" data-room-evidence-form class="{{ $errors->any() ? '' : 'hidden' }} grid gap-4 border-b border-slate-200 bg-slate-50 p-5 md:grid-cols-2">
                @csrf
                <div>
                    <label for="evidence_type" class="mb-1 block text-sm font-semibold text-slate-700">Loại ảnh</label>
                    <select id="evidence_type" name="evidence_type" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                        @foreach (['baseline' => 'Trước khi bàn giao phòng', 'checkout' => 'Sau khi nhận lại phòng'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('evidence_type', 'baseline') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="contract_id" class="mb-1 block text-sm font-semibold text-slate-700">Hợp đồng liên quan</label>
                    <select id="contract_id" name="contract_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                        <option value="">Không gắn hợp đồng</option>
                        @foreach ($room->contracts->sortByDesc('id') as $contract)
                            <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>{{ $contract->contract_code }} — {{ $contract->status }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Bắt buộc chọn hợp đồng đối với ảnh sau khi nhận lại phòng.</p>
                </div>
                <div class="md:col-span-2">
                    <label for="evidence_images" class="mb-1 block text-sm font-semibold text-slate-700">Chọn ảnh (tối đa 15 ảnh)</label>
                    <input id="evidence_images" type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp" data-preview-target="evidence-images-preview" data-max-files="15" class="js-image-preview-input block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">
                    <div id="evidence-images-preview" class="mt-3 hidden rounded-lg border border-slate-200 bg-white p-3">
                        <p data-preview-count class="mb-2 text-xs font-semibold text-slate-600"></p>
                        <div data-preview-grid class="flex flex-wrap gap-3"></div>
                        <p data-preview-error class="mt-2 hidden text-xs font-semibold text-rose-600"></p>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="caption" class="mb-1 block text-sm font-semibold text-slate-700">Ghi chú chung</label>
                    <input id="caption" type="text" name="caption" maxlength="1000" value="{{ old('caption') }}" placeholder="Ví dụ: Vết xước cạnh tủ đã có trước khi bàn giao" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" data-room-evidence-cancel class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
                    <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"><i class="bx bx-cloud-upload text-lg"></i>Lưu nhật ký</button>
                </div>
            </form>

            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($room->images as $image)
                    <article class="overflow-hidden rounded-lg border border-slate-200">
                        <a href="{{ route('admin.rooms.evidence.image', [$room, $image]) }}" data-image-modal data-image-title="Ảnh hiện trạng phòng">
                            <img src="{{ route('admin.rooms.evidence.image', [$room, $image]) }}" alt="{{ $evidenceLabels[$image->evidence_type] ?? 'Ảnh phòng' }}" class="h-48 w-full bg-slate-100 object-cover">
                        </a>
                        <div class="space-y-1.5 p-3 text-xs text-slate-500">
                            <p class="font-semibold text-slate-900">{{ $evidenceLabels[$image->evidence_type] ?? $image->evidence_type }}</p>
                            <p>Ghi nhận: {{ $image->taken_at?->format('d/m/Y H:i') ?? 'Không rõ thời điểm' }} · {{ $image->uploader?->name ?? 'Dữ liệu chuyển đổi' }}</p>
                            @if ($image->contract)<p>Hợp đồng: <a href="{{ route('admin.contracts.show', $image->contract) }}" class="font-semibold text-indigo-600">{{ $image->contract->contract_code }}</a></p>@endif
                            @if ($image->caption)<p class="leading-5 text-slate-700">{{ $image->caption }}</p>@endif
                            <p class="truncate font-mono" title="{{ $image->sha256 }}">SHA-256: {{ $image->sha256 ? substr($image->sha256, 0, 16).'…' : 'ảnh cũ chưa có mã' }}</p>
                        </div>
                    </article>
                @empty
                    <div class="sm:col-span-2 xl:col-span-3 rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">Chưa có nhật ký.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
