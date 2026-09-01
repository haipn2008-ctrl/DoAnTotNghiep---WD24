@extends('layouts.client.index')

@section('title', 'Thông báo | Cổng khách thuê')
@section('page_title', 'Thông báo')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-600">Trung tâm cập nhật</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Thông báo của tôi</h2>
            </div>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('client.notifications.read-all') }}">
                    @csrf
                    <button class="h-11 rounded-xl border border-indigo-100 bg-white px-4 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_12px_32px_rgba(15,23,42,.06)]">
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    @php($data = $notification->data)
                    <a href="{{ route('client.notifications.open', $notification->id) }}" class="flex gap-4 p-5 transition hover:bg-indigo-50/40 {{ $notification->read_at ? '' : 'bg-indigo-50/70' }}">
                        <span class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-700' }}">
                            <x-bell-icon class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <strong class="text-slate-950">{{ $data['title'] ?? 'Thông báo' }}</strong>
                                @unless($notification->read_at)<span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white">Mới</span>@endunless
                            </span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</span>
                            <span class="mt-2 block text-xs text-slate-500">
                                @if(!empty($data['invoice_code'])){{ $data['invoice_code'] }}@endif
                                @if(!empty($data['contract_code'])){{ $data['contract_code'] }}@endif
                                @if(!empty($data['remaining_amount'])) · Còn nợ {{ number_format($data['remaining_amount'], 0, ',', '.') }}đ @endif
                                · {{ $notification->created_at?->format('H:i d/m/Y') }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="p-12 text-center">
                        <x-bell-icon class="mx-auto h-10 w-10 text-slate-300" />
                        <h3 class="mt-3 font-semibold text-slate-900">Chưa có thông báo</h3>
                    </div>
                @endforelse
            </div>
        </section>

        {{ $notifications->links() }}
    </div>
@endsection
