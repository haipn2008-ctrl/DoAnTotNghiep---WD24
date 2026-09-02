@extends('layouts.client.index')

@section('title', 'Hỗ trợ | Cổng khách thuê')
@section('page_title', 'Hỗ trợ')

@php
    $categories = ['repair' => 'Sửa chữa phòng', 'invoice' => 'Hóa đơn', 'utility' => 'Điện nước', 'contract' => 'Hợp đồng', 'other' => 'Vấn đề khác'];
    $statuses = ['new' => ['Mới gửi', 'bg-sky-50 text-sky-700'], 'in_progress' => ['Đang xử lý', 'bg-amber-50 text-amber-700'], 'resolved' => ['Đã hoàn thành', 'bg-emerald-50 text-emerald-700'], 'rejected' => ['Đã từ chối', 'bg-rose-50 text-rose-700']];
@endphp

@section('content')
    <div class="mx-auto grid max-w-7xl gap-6 xl:grid-cols-[400px_1fr]">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 xl:col-span-2 sm:px-8"><div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div><div class="relative flex items-center gap-4"><span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5h14v10H9l-4 3v-13Z" /><path stroke-linecap="round" d="M9 9h6M9 12h4" /></svg></span><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">Cổng khách thuê</p><h1 class="mt-1 text-2xl font-bold sm:text-3xl">Trung tâm hỗ trợ</h1><p class="mt-2 text-sm text-indigo-100">Theo dõi yêu cầu và liên hệ ban quản lý khi cần hỗ trợ.</p></div></div></section>
        @if($canCreateSupport)
        <section class="h-fit overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-indigo-600 to-violet-700 px-5 py-5 text-white"><p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-100">Trung tâm hỗ trợ</p><h2 class="mt-1 text-xl font-bold">Gửi yêu cầu hỗ trợ</h2></div>
            @if($errors->any())<div class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('client.support.store') }}" enctype="multipart/form-data" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Hợp đồng cần hỗ trợ</label>
                    <select name="contract_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3">
                        @foreach($eligibleContracts as $contract)
                            <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>
                                {{ $contract->contract_code }} · Phòng {{ $contract->room->room_code ?? '-' }} · {{ $contract->status === \App\Models\Contract::STATUS_SETTLING ? 'Đang quyết toán' : 'Đang thuê' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Loại vấn đề</label><select name="category" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="">Chọn loại vấn đề</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Tiêu đề</label><input name="subject" value="{{ old('subject') }}" maxlength="255" required placeholder="Mô tả ngắn vấn đề" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nội dung chi tiết</label><textarea name="description" rows="5" maxlength="5000" required class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('description') }}</textarea></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh đính kèm (nếu có)</label><input type="file" name="attachment" accept="image/*" capture="environment" class="block w-full rounded-lg border border-slate-200 p-2 text-sm"></div>
                <button class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4 12 16-8-5.5 16-3-6.5L4 12Z" /></svg>Gửi yêu cầu</button>
            </form>
        </section>
        @else
            <section class="h-fit overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-br from-indigo-50 to-violet-50 p-5"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5h14v10H9l-4 3v-13Z" /></svg></span><h2 class="mt-4 text-xl font-bold text-slate-950">Lịch sử yêu cầu</h2><p class="mt-1 text-sm leading-6 text-slate-500">Tài khoản hiện chỉ có quyền theo dõi các yêu cầu đã gửi.</p></div>
                <div class="p-5"><a href="{{ route('client.landlord-information') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Thông tin liên hệ<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg></a></div>
            </section>
        @endif

        <section class="space-y-4">
            <div><h2 class="text-2xl font-bold text-slate-950">Yêu cầu của tôi</h2></div>
            @forelse($requests as $supportRequest)
                @php($status = $statuses[$supportRequest->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700'])
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start"><div><div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold uppercase text-indigo-600">{{ $categories[$supportRequest->category] ?? $supportRequest->category }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span></div><h3 class="mt-2 text-lg font-bold text-slate-950">{{ $supportRequest->subject }}</h3><p class="mt-1 text-xs text-slate-500">Gửi lúc {{ $supportRequest->created_at->format('d/m/Y H:i') }}</p></div>@if($supportRequest->attachmentExists())<a href="{{ route('client.support.attachment', $supportRequest) }}" data-image-modal data-image-title="Ảnh đính kèm yêu cầu hỗ trợ" class="text-sm font-semibold text-indigo-700">Xem ảnh</a>@elseif($supportRequest->attachment)<span class="text-sm font-medium text-amber-700">Tệp đính kèm không còn tồn tại</span>@endif</div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $supportRequest->description }}</p>
                    @if($supportRequest->admin_response)<div class="mt-4 rounded-lg bg-emerald-50 p-4"><p class="text-xs font-semibold uppercase text-emerald-700">Phản hồi từ ban quản lý</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-emerald-900">{{ $supportRequest->admin_response }}</p><p class="mt-2 text-xs text-emerald-700">{{ $supportRequest->responded_at?->format('d/m/Y H:i') }}</p></div>@endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5h14v10H9l-4 3v-13Z" /></svg></span><p class="mt-3 text-sm font-semibold text-slate-700">Bạn chưa gửi yêu cầu hỗ trợ nào</p></div>
            @endforelse
            @if($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
        </section>
    </div>
@endsection
