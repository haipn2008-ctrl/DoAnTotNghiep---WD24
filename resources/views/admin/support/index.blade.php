@extends('layouts.admin.index')

@section('title', 'Yêu cầu hỗ trợ | Quản lý phòng trọ')
@section('page_title', 'Yêu cầu hỗ trợ')

@php
    $categories = ['repair' => 'Sửa chữa phòng', 'invoice' => 'Hóa đơn', 'utility' => 'Điện nước', 'contract' => 'Hợp đồng', 'other' => 'Khác'];
    $statuses = ['new' => ['Mới gửi', 'bg-sky-50 text-sky-700'], 'in_progress' => ['Đang xử lý', 'bg-amber-50 text-amber-700'], 'resolved' => ['Hoàn thành', 'bg-emerald-50 text-emerald-700'], 'rejected' => ['Từ chối', 'bg-rose-50 text-rose-700']];
@endphp

@section('content')
    <div class="space-y-5">
        <div><p class="text-sm font-medium text-slate-500">Trao đổi với khách thuê</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Yêu cầu hỗ trợ</h2></div>
        @if($errors->any())<div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <form method="GET" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
            <select name="category" class="h-11 rounded-lg border border-slate-200 px-3"><option value="">Tất cả loại</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select>
            <select name="status" class="h-11 rounded-lg border border-slate-200 px-3"><option value="">Tất cả trạng thái</option>@foreach($statuses as $value => $meta)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta[0] }}</option>@endforeach</select>
            <button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white">Lọc</button>
        </form>
        <div class="space-y-4">
            @forelse($requests as $supportRequest)
                @php($status = $statuses[$supportRequest->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700'])
                <article id="request-{{ $supportRequest->id }}" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-5 shadow-sm target:border-indigo-300 target:ring-2 target:ring-indigo-100">
                    <div class="grid gap-5 lg:grid-cols-[1fr_420px]">
                        <div><div class="flex flex-wrap gap-2"><span class="text-xs font-semibold uppercase text-indigo-600">{{ $categories[$supportRequest->category] ?? $supportRequest->category }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span></div><h3 class="mt-2 text-lg font-bold text-slate-950">{{ $supportRequest->subject }}</h3><p class="mt-1 text-xs text-slate-500">{{ $supportRequest->tenant?->full_name ?? $supportRequest->user?->name ?? 'Tài khoản không còn hoạt động' }} · Phòng {{ $supportRequest->contract?->room?->room_code ?? '-' }} · {{ $supportRequest->created_at->format('d/m/Y H:i') }}</p><p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $supportRequest->description }}</p>@if($supportRequest->attachmentExists())<a href="{{ route('admin.support.attachment', $supportRequest) }}" data-image-modal data-image-title="Ảnh đính kèm yêu cầu hỗ trợ" class="mt-3 inline-block text-sm font-semibold text-indigo-700">Xem ảnh đính kèm</a>@elseif($supportRequest->attachment)<span class="mt-3 inline-block text-sm font-medium text-amber-700">Tệp đính kèm không còn tồn tại</span>@endif</div>
                        <form method="POST" action="{{ route('admin.support.update', $supportRequest) }}" class="space-y-3 rounded-lg bg-slate-50 p-4">@csrf @method('PUT')<div><label class="mb-1 block text-sm font-semibold">Trạng thái</label><select name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3">@foreach($statuses as $value => $meta)<option value="{{ $value }}" @selected($supportRequest->status === $value)>{{ $meta[0] }}</option>@endforeach</select></div><div><label class="mb-1 block text-sm font-semibold">Phản hồi cho khách</label><textarea name="admin_response" rows="4" maxlength="5000" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ $supportRequest->admin_response }}</textarea></div><button class="h-10 w-full rounded-lg bg-indigo-600 text-sm font-semibold text-white">Cập nhật yêu cầu</button></form>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-12 text-center text-slate-500">Không có yêu cầu phù hợp.</div>
            @endforelse
        </div>
        @if($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
    </div>
@endsection
