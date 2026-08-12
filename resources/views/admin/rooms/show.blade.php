@extends('layouts.admin.index')

@section('title', 'Chi tiết phòng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết phòng')

@php
    $statusOptions = [
        'available' => ['label' => 'Trống', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'occupied' => ['label' => 'Đang thuê', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'maintenance' => ['label' => 'Bảo trì', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
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

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Phòng {{ $room->room_code }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Chi tiết phòng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.rooms.edit', $room) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @if ($room->thumbnail)
                    <img src="{{ asset('storage/' . $room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="h-72 w-full object-cover">
                @else
                    <div class="flex h-72 w-full items-center justify-center bg-slate-100 text-slate-400">
                        <i class="bx bx-image text-5xl"></i>
                    </div>
                @endif

                <div class="space-y-4 p-5">
                    <div>
                        <p class="text-sm text-slate-500">Mã phòng</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ $room->room_code }}</p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin phòng</h3>
                    <p class="text-sm text-slate-500">Thông tin giá thuê, sức chứa và mô tả.</p>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Tầng</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">Tầng {{ $room->floor }}</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Giá thuê</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format($room->price, 0, ',', '.') }}đ</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Diện tích</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $room->area }} m²</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Số người hiện tại</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $room->current_people }}/{{ $room->max_people ?? 4 }} người</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-sm font-semibold text-slate-700">Mô tả</p>
                        <p class="mt-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-600">
                            {{ $room->description ?: 'Chưa có mô tả cho phòng này.' }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Người đang ở</h3>
                    <p class="text-sm text-slate-500">Danh sách lấy từ hợp đồng đang hoạt động hoặc đã quá hạn nhưng chưa trả phòng.</p>
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
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Người đại diện thuê</p>
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
                        <p class="mb-2 text-sm font-semibold text-slate-700">Thành viên trong phòng</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($occupants as $occupant)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $occupant->full_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $occupant->phone ?: 'Chưa có số điện thoại' }}</span>
                                    </span>
                                    @if ($occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE)
                                        <span class="shrink-0 rounded-full bg-indigo-100 px-2 py-1 text-[11px] font-semibold text-indigo-700">Đại diện</span>
                                    @elseif ($occupant->tenant)
                                        <a href="{{ route('admin.tenants.show', $occupant->tenant) }}" class="text-xs font-semibold text-indigo-700">Xem hồ sơ</a>
                                    @else
                                        <span class="text-xs text-slate-400">Không cần tài khoản</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($unidentifiedOccupants > 0)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Còn {{ $unidentifiedOccupants }} người chỉ có số lượng nhưng chưa có hồ sơ danh tính. Đây có thể là dữ liệu hợp đồng cũ; quản trị viên cần bổ sung khi hợp đồng còn là bản nháp hoặc trong lần cập nhật hồ sơ phù hợp.
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

        @php
            $roomAssets = $room->amenities->where('category', \App\Models\Amenity::CATEGORY_ASSET);
        @endphp
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Tài sản bàn giao</h3>
                <p class="text-sm text-slate-500">Số lượng và tình trạng tài sản để đối chiếu khi bàn giao, trả phòng.</p>
            </div>

            <div class="p-5">
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><i class="bx bx-package"></i></span>
                    <p class="text-sm font-semibold text-slate-700">{{ $roomAssets->count() }} loại tài sản</p>
                </div>
                <div class="space-y-3">
                    @forelse ($roomAssets as $asset)
                        <div class="grid gap-2 rounded-lg border border-slate-200 px-4 py-3 sm:grid-cols-[1fr_120px_220px] sm:items-center">
                            <p class="font-semibold text-slate-900">{{ $asset->name }}</p>
                            <p class="text-sm text-slate-600">Số lượng: {{ $asset->pivot->quantity }}</p>
                            <p class="text-sm text-slate-600">{{ $conditionLabels[$asset->pivot->condition] ?? 'Không xác định' }}</p>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">Chưa khai báo tài sản bàn giao.</div>
                    @endforelse
                    </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Nhật ký ảnh hiện trạng</h3>
                    <p class="text-sm text-slate-500">Chỉ dùng hai mốc trước bàn giao và sau khi nhận lại phòng. Thời điểm ghi nhận do máy chủ tự khóa, quản trị viên không thể sửa.</p>
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
                        <a href="{{ asset('storage/'.$image->path) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $evidenceLabels[$image->evidence_type] ?? 'Ảnh phòng' }}" class="h-48 w-full bg-slate-100 object-cover">
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
