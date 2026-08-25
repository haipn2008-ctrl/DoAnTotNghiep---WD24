@extends('layouts.client.index')

@section('title', 'Thông báo | Cổng khách thuê')
@section('page_title', 'Thông báo')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Trao đổi trong hệ thống</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Thông báo của tôi</h2>
                <p class="mt-2 text-sm text-slate-500">Các nhắc nhở và thông tin do ban quản lý gửi đến tài khoản của bạn.</p>
            </div>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('client.notifications.read-all') }}">
                    @csrf
                    <button class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    @php($data = $notification->data)
                    <a href="{{ route('client.notifications.open', $notification->id) }}" class="flex gap-4 p-5 transition hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-indigo-50/60' }}">
                        <span class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-700' }}">
                            <i class="bx bx-bell text-xl"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <strong class="text-slate-950">{{ $data['title'] ?? 'Thông báo' }}</strong>
                                @unless($notification->read_at)<span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white">Mới</span>@endunless
                            </span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</span>
                            <span class="mt-2 block text-xs text-slate-500">
                                @if(!empty($data['invoice_code'])){{ $data['invoice_code'] }}@endif
                                @if(!empty($data['remaining_amount'])) · Còn nợ {{ number_format($data['remaining_amount'], 0, ',', '.') }}đ @endif
                                · {{ $notification->created_at?->format('H:i d/m/Y') }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="p-12 text-center">
                        <i class="bx bx-bell-off text-4xl text-slate-300"></i>
                        <h3 class="mt-3 font-semibold text-slate-900">Chưa có thông báo</h3>
                        <p class="mt-1 text-sm text-slate-500">Thông báo mới từ ban quản lý sẽ xuất hiện tại đây.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{ $notifications->links() }}
    </div>
@endsection
