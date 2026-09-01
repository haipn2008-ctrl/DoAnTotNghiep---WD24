@extends('layouts.client.index')

@section('title', 'Hỗ trợ | Cổng khách thuê')
@section('page_title', 'Hỗ trợ')

@php
    $categories = ['repair' => 'Sửa chữa phòng', 'invoice' => 'Hóa đơn', 'utility' => 'Điện nước', 'contract' => 'Hợp đồng', 'other' => 'Vấn đề khác'];
    $statuses = ['new' => ['Mới gửi', 'bg-sky-50 text-sky-700'], 'in_progress' => ['Đang xử lý', 'bg-amber-50 text-amber-700'], 'resolved' => ['Đã hoàn thành', 'bg-emerald-50 text-emerald-700'], 'rejected' => ['Đã từ chối', 'bg-rose-50 text-rose-700']];
@endphp

@section('content')
    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        @if($canCreateSupport)
        <section class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold text-slate-950">Gửi yêu cầu hỗ trợ</h2>
            @if($errors->any())<div class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('client.support.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
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
                <button class="h-11 w-full rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">Gửi yêu cầu</button>
            </form>
        </section>
        @else
            <section class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mt-1 text-xl font-bold text-slate-950">Lịch sử yêu cầu</h2>
                <a href="{{ route('client.landlord-information') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-700">Xem thông tin liên hệ →</a>
            </section>
        @endif

        <section class="space-y-4">
            <div><h2 class="text-2xl font-bold text-slate-950">Yêu cầu của tôi</h2></div>
            @forelse($requests as $supportRequest)
                @php($status = $statuses[$supportRequest->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700'])
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start"><div><div class="flex flex-wrap items-center gap-2"><span class="text-xs font-semibold uppercase text-indigo-600">{{ $categories[$supportRequest->category] ?? $supportRequest->category }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span></div><h3 class="mt-2 text-lg font-bold text-slate-950">{{ $supportRequest->subject }}</h3><p class="mt-1 text-xs text-slate-500">Gửi lúc {{ $supportRequest->created_at->format('d/m/Y H:i') }}</p></div>@if($supportRequest->attachmentExists())<a href="{{ route('client.support.attachment', $supportRequest) }}" data-image-modal data-image-title="Ảnh đính kèm yêu cầu hỗ trợ" class="text-sm font-semibold text-indigo-700">Xem ảnh</a>@elseif($supportRequest->attachment)<span class="text-sm font-medium text-amber-700">Tệp đính kèm không còn tồn tại</span>@endif</div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $supportRequest->description }}</p>
                    @if($supportRequest->admin_response)<div class="mt-4 rounded-lg bg-emerald-50 p-4"><p class="text-xs font-semibold uppercase text-emerald-700">Phản hồi từ ban quản lý</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-emerald-900">{{ $supportRequest->admin_response }}</p><p class="mt-2 text-xs text-emerald-700">{{ $supportRequest->responded_at?->format('d/m/Y H:i') }}</p></div>@endif
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">Bạn chưa gửi yêu cầu hỗ trợ nào.</div>
            @endforelse
            @if($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
        </section>
    </div>
@endsection
