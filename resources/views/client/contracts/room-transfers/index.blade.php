@extends('layouts.client.index')

@section('title', 'Yêu cầu đổi phòng | Cổng khách thuê')
@section('page_title', 'Yêu cầu đổi phòng')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 sm:px-8">
            <div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-20 right-28 h-40 w-40 rounded-full bg-white/5"></div>
            <div class="relative flex items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 backdrop-blur-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">Hợp đồng</p>
                    <h1 class="mt-1 text-2xl font-bold sm:text-3xl">Yêu cầu đổi phòng</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100">Chọn hợp đồng, phòng mới và thời gian bạn muốn chuyển.</p>
                </div>
            </div>
        </section>

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại thông tin:</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('client.room-transfers.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">Thông tin chuyển phòng</h2>
                <p class="mt-0.5 text-sm text-slate-500">Các trường có dấu <span class="text-rose-500">*</span> là bắt buộc.</p>
            </div>

            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label for="contract_id" class="mb-2 block text-sm font-semibold text-slate-700">Hợp đồng đang thuê <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="contract_id" name="contract_id" required class="h-12 w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 pr-10 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            @forelse($contracts as $contract)
                                <option value="{{ $contract->id }}" @selected(old('contract_id') == $contract->id)>{{ $contract->contract_code }} · Phòng {{ $contract->room?->room_code }}</option>
                            @empty
                                <option value="">Không có hợp đồng phù hợp</option>
                            @endforelse
                        </select>
                        <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                    </div>
                </div>

                <div>
                    <label for="new_room_id" class="mb-2 block text-sm font-semibold text-slate-700">Phòng muốn chuyển đến <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="new_room_id" name="new_room_id" required class="h-12 w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 pr-10 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Chọn phòng còn trống</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('new_room_id') == $room->id)>Phòng {{ $room->room_code }} · {{ number_format((float) $room->price, 0, ',', '.') }}đ/tháng · Tối đa {{ $room->max_people }} người</option>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                    </div>
                </div>

                <div>
                    <label for="requested_transfer_date" class="mb-2 block text-sm font-semibold text-slate-700">Ngày mong muốn chuyển <span class="text-rose-500">*</span></label>
                    <input id="requested_transfer_date" type="date" name="requested_transfer_date" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" value="{{ old('requested_transfer_date', today()->toDateString()) }}" required class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <p class="mt-1.5 text-xs text-slate-500">Có thể chọn ngày trong vòng 30 ngày tới.</p>
                </div>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold text-slate-700">Lý do chuyển phòng <span class="text-rose-500">*</span></label>
                    <textarea id="reason" name="reason" required minlength="3" maxlength="2000" rows="4" placeholder="Nhập lý do bạn muốn chuyển phòng..." class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                <button type="submit" class="inline-flex min-w-40 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" @disabled($contracts->isEmpty() || $rooms->isEmpty())>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4 12 16-8-5.5 16-3-6.5L4 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 13.5 20 4" /></svg>
                    Gửi yêu cầu
                </button>
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="font-bold text-slate-900">Lịch sử đổi phòng</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Theo dõi kết quả các yêu cầu đã gửi.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $roomTransfers->count() }} yêu cầu</span>
            </div>

            @forelse($roomTransfers as $transfer)
                @php
                    $transferStatus = match($transfer->status) {
                        'pending' => ['Chờ duyệt', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
                        'pending_appendix' => ['Đang hoàn tất phụ lục', 'bg-violet-50 text-violet-700', 'bg-violet-500'],
                        'completed' => ['Đã chuyển', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                        default => ['Bị từ chối', 'bg-rose-50 text-rose-700', 'bg-rose-500'],
                    };
                @endphp
                <article class="border-b border-slate-100 p-5 last:border-0 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" /></svg>
                            </span>
                            <div>
                                <p class="font-bold text-slate-900">Phòng {{ $transfer->oldRoom?->room_code ?? '-' }} <span class="mx-1 text-slate-400">→</span> Phòng {{ $transfer->newRoom?->room_code ?? '-' }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $transfer->reason }}</p>
                                @if($transfer->admin_reason)
                                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><span class="font-semibold text-slate-700">Phản hồi:</span> {{ $transfer->admin_reason }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $transferStatus[1] }}"><span class="h-1.5 w-1.5 rounded-full {{ $transferStatus[2] }}"></span>{{ $transferStatus[0] }}</span>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9v15h-9v-15Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M10 8h4M10 11.5h4M10 15h2" /></svg>
                    </span>
                    <p class="mt-3 font-semibold text-slate-800">Chưa có yêu cầu đổi phòng</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
