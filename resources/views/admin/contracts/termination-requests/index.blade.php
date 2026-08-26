@extends('layouts.admin.index')

@section('title', 'Yêu cầu trả phòng')
@section('page_title', 'Quản lý phòng trọ')

@section('content')

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Yêu cầu trả phòng</h1>
        </div>

        <a href="{{ route('admin.contracts.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <span aria-hidden="true">←</span>
            Quản lý hợp đồng
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Tổng yêu cầu</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $terminationRequests->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">#</div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-700">Chờ duyệt</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ $terminationRequests->where('status', 'pending')->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-xl text-amber-700">◷</div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Đã duyệt</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $terminationRequests->where('status', 'approved')->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xl font-bold text-emerald-700">✓</div>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-700">Từ chối</p>
                    <p class="mt-2 text-3xl font-bold text-rose-700">{{ $terminationRequests->where('status', 'rejected')->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-xl font-bold text-rose-700">×</div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-base font-bold text-slate-900">Danh sách yêu cầu</h2>
            </div>
            <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600">
                {{ $terminationRequests->count() }} yêu cầu
            </div>
        </div>

        <div class="grid gap-4 bg-slate-50/70 p-4 lg:grid-cols-2 sm:p-6">
            @forelse($terminationRequests as $request)
                @php
                    $statusMeta = match ($request->status) {
                        'approved' => ['Đã duyệt', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                        'rejected' => ['Đã từ chối', 'border-rose-200 bg-rose-50 text-rose-700', 'bg-rose-500'],
                        default => ['Chờ duyệt', 'border-amber-200 bg-amber-50 text-amber-700', 'bg-amber-500'],
                    };
                    $isEarlyDeparture = $request->requested_end_date
                        && $request->contract?->end_date
                        && $request->requested_end_date->lt($request->contract->end_date);
                    $defaultCheckout = $request->requested_end_date?->isToday()
                        ? now()->addHour()
                        : $request->requested_end_date?->copy()->setTime(9, 0);
                @endphp

                <article id="request-{{ $request->id }}" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md target:ring-2 target:ring-indigo-300">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700">HĐ</span>
                            <div class="min-w-0">
                                <p class="break-words font-bold text-slate-950">{{ $request->contract->contract_code ?? '-' }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-500">Phòng {{ $request->contract->room->room_code ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusMeta[1] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta[2] }}"></span>{{ $statusMeta[0] }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Người thuê đại diện</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $request->contract->tenant->full_name ?? '-' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $request->contract->tenant->phone ?? 'Chưa có số điện thoại' }}</p>
                        </div>

                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Ngày đề nghị rời phòng</p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isEarlyDeparture ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $request->type_label }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center gap-2 text-sm">
                                <span class="font-medium text-slate-600">{{ optional($request->contract->end_date)->format('d/m/Y') ?? '-' }}</span>
                                <span class="text-indigo-400">→</span>
                                <span class="font-bold text-indigo-700">{{ optional($request->requested_end_date)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Hạn hợp đồng → ngày khách muốn rời phòng</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-100 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lý do trả phòng</p>
                        <p class="mt-1.5 text-sm leading-6 text-slate-700">{{ $request->reason ?: 'Không có lý do' }}</p>

                        @if($request->status === 'approved' && $request->scheduled_checkout_at)
                            <div class="mt-3 border-t border-slate-100 pt-3 text-sm text-emerald-700">
                                <span class="font-semibold">Lịch bàn giao:</span> {{ $request->scheduled_checkout_at->format('H:i d/m/Y') }}
                            </div>
                        @endif

                        @if($request->admin_note)
                            <p class="mt-2 border-t border-slate-100 pt-2 text-sm {{ $request->status === 'rejected' ? 'text-rose-700' : 'text-slate-600' }}">
                                <span class="font-semibold">Phản hồi quản lý:</span> {{ $request->admin_note }}
                            </p>
                        @endif
                    </div>

                    @if($request->status === 'pending')
                        <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                            <form method="POST" action="{{ route('admin.termination-requests.approve', $request) }}" onsubmit="return confirm('Bạn chắc chắn muốn duyệt yêu cầu trả phòng này?')" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                                @csrf
                                <p class="mb-3 text-sm font-semibold text-emerald-800">Xác nhận lịch rời phòng và bàn giao</p>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="block text-xs font-semibold text-slate-600">
                                        Ngày rời phòng
                                        <input type="date" name="approved_end_date" required value="{{ $request->requested_end_date?->format('Y-m-d') }}" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                    </label>
                                    <label class="block text-xs font-semibold text-slate-600">
                                        Thời gian bàn giao
                                        <input type="datetime-local" name="scheduled_checkout_at" required value="{{ $defaultCheckout?->format('Y-m-d\TH:i') }}" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                    </label>
                                </div>

                                <label class="mt-3 block text-xs font-semibold text-slate-600">
                                    Ghi chú lịch bàn giao <span class="font-normal text-slate-400">(không bắt buộc)</span>
                                    <input name="admin_note" maxlength="1000" placeholder="Ví dụ: Bàn giao chìa khóa và chốt chỉ số điện nước" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                </label>

                                <button type="submit" class="mt-3 h-11 w-full rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">✓ Duyệt trả phòng</button>
                            </form>

                            <form method="POST" action="{{ route('admin.termination-requests.reject', $request) }}" onsubmit="return confirm('Bạn chắc chắn muốn từ chối yêu cầu trả phòng này?')" class="flex min-w-0 gap-2">
                                @csrf
                                <input name="reject_reason" required minlength="3" maxlength="1000" placeholder="Nhập lý do từ chối" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                <button type="submit" class="h-11 shrink-0 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">Từ chối</button>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 border-t border-slate-100 pt-4 text-right text-sm font-medium text-slate-400">Yêu cầu đã được xử lý</div>
                    @endif
                </article>
            @empty
                <div class="py-14 text-center lg:col-span-2">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">□</div>
                    <h3 class="mt-4 font-semibold text-slate-900">Chưa có yêu cầu trả phòng</h3>
                    <p class="mt-1 text-sm text-slate-500">Yêu cầu trả phòng của khách thuê sẽ xuất hiện tại đây.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 text-sm text-slate-500 sm:px-6">
            Hiển thị <span class="font-semibold text-slate-700">{{ $terminationRequests->count() }}</span> yêu cầu
        </div>
    </div>
</div>

@endsection
