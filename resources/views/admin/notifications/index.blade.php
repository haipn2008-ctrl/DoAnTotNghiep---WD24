@extends('layouts.admin.index')

@section('title', 'Thông báo quản trị')
@section('page_title', 'Thông báo cần xử lý')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-bold text-slate-950">Trung tâm thông báo</h2>
                <p class="mt-1 text-sm text-slate-500">Yêu cầu cần duyệt chỉ tự đóng khi nghiệp vụ đã được xử lý; thông báo khách gỡ phương tiện sẽ đóng sau khi admin mở xem.</p>
            </div>
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="bx bx-file text-lg"></i> Danh sách hợp đồng
            </a>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach(['open' => 'Cần xử lý', 'resolved' => 'Đã xử lý', 'all' => 'Tất cả'] as $key => $label)
                <a href="{{ route('admin.notifications.index', ['status' => $key]) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }} ({{ $counts[$key] }})
                </a>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    <a href="{{ route('admin.notifications.open', $notification) }}" class="flex gap-4 p-5 transition hover:bg-slate-50">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->resolved_at ? 'bg-slate-100 text-slate-500' : 'bg-rose-50 text-rose-600' }}">
                            <i class="bx {{ $notification->resolved_at ? 'bx-check' : 'bx-bell' }} text-xl"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <strong class="text-slate-900">{{ $notification->title }}</strong>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $notification->type_label }}</span>
                                @if(!$notification->resolved_at)<span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">Cần xử lý</span>@endif
                            </span>
                            <span class="mt-1 block text-sm text-slate-600">{{ $notification->message }}</span>
                            <span class="mt-2 block text-xs text-slate-400">
                                @if($notification->vehicle_id)
                                    {{ $notification->tenant?->full_name ?? 'Khách thuê' }} · Phương tiện
                                @else
                                    {{ $notification->contract?->contract_code }} · Phòng {{ $notification->contract?->room?->room_code ?? '—' }} · {{ $notification->contract?->tenant?->full_name ?? '—' }}
                                @endif
                                · {{ $notification->detected_at?->format('d/m/Y H:i') }}
                                @if($notification->resolved_at) · Đã xử lý {{ $notification->resolved_at->format('d/m/Y H:i') }} @endif
                            </span>
                        </span>
                        <i class="bx bx-chevron-right self-center text-2xl text-slate-300"></i>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><i class="bx bx-check-shield text-3xl"></i></span>
                        <h3 class="mt-4 font-semibold text-slate-900">Không có thông báo trong nhóm này</h3>
                        <p class="mt-1 text-sm text-slate-500">Các yêu cầu của khách thuê và cảnh báo vận hành sẽ xuất hiện tại đây.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{ $notifications->links() }}
    </div>
@endsection
