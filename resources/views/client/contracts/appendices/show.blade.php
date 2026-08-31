@extends('layouts.client.index')

@section('title', 'Phụ lục '.$appendix->code)
@section('page_title', 'Phụ lục hợp đồng')

@section('content')
@php
    $pending = $appendix->status === \App\Models\ContractAppendix::STATUS_PENDING_TENANT;
    $colors = ['pending_tenant'=>'bg-amber-100 text-amber-800','pending_signature'=>'bg-sky-100 text-sky-800','accepted'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-rose-100 text-rose-800','superseded'=>'bg-violet-100 text-violet-800'];
@endphp
<div class="mx-auto max-w-5xl space-y-5">
    <div>
        <a href="{{ route('client.contracts.show', $appendix->contract) }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng {{ $appendix->contract->contract_code }}</a>
        <div class="mt-2 flex flex-wrap items-center gap-3"><h2 class="text-2xl font-bold text-slate-950">{{ $appendix->code }}</h2><span class="rounded-full px-3 py-1 text-xs font-bold {{ $colors[$appendix->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $appendix->status_label }}</span></div>
        <p class="mt-1 text-sm text-slate-600">Được gửi lúc {{ $appendix->sent_at?->format('H:i d/m/Y') }}</p>
    </div>

    @if($pending)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"><strong>Phụ lục đang chờ bạn xác nhận.</strong> Hãy đọc toàn bộ nội dung trước khi chấp nhận hoặc nêu rõ lý do nếu từ chối.</div>
    @elseif($appendix->rejection_reason)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-950"><strong>Lý do bạn đã từ chối:</strong><p class="mt-1 whitespace-pre-line">{{ $appendix->rejection_reason }}</p></div>
    @endif

    @if($appendix->status === \App\Models\ContractAppendix::STATUS_PENDING_SIGNATURE)
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-950"><strong>Phụ lục gia hạn đang chờ ký trực tiếp.</strong> Ban quản lý sẽ in phụ lục để hai bên ký. Thời hạn hợp đồng chỉ thay đổi sau khi minh chứng bản ký được tải lên hệ thống.</div>
    @endif

    <article class="mx-auto max-w-3xl bg-white px-8 py-10 shadow-lg ring-1 ring-slate-200 sm:px-14 sm:py-14">
        @include('shared.contract-appendix-document', ['appendix' => $appendix])
    </article>

    @if(filled($appendix->signed_evidence_paths))
        <section class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2"><div><h3 class="font-bold text-slate-950">Ảnh bản cứng đã ký</h3><p class="mt-1 text-sm text-slate-500">Minh chứng phụ lục được hai bên ký và ban quản lý lưu trên hệ thống.</p></div><span class="text-xs font-semibold text-emerald-700">Tải lên {{ $appendix->signed_evidence_uploaded_at?->format('H:i d/m/Y') }}</span></div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($appendix->signed_evidence_paths as $index => $path)
                    <a href="{{ route('client.contract-appendices.signed-evidence', [$appendix, $index]) }}" data-image-modal data-image-title="Bản cứng phụ lục {{ $appendix->code }} - Trang {{ $index + 1 }}" class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50 hover:border-indigo-300">
                        <img src="{{ route('client.contract-appendices.signed-evidence', [$appendix, $index]) }}" alt="Bản cứng phụ lục trang {{ $index + 1 }}" class="h-44 w-full object-cover">
                        <span class="block px-3 py-2 text-center text-xs font-semibold text-indigo-700">Xem trang {{ $index + 1 }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($pending)
        <section class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('client.contract-appendices.accept', $appendix) }}" onsubmit="return confirm('Bạn xác nhận đã đọc và đồng ý toàn bộ nội dung phụ lục?')" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                @csrf
                <h3 class="font-bold text-emerald-950">Chấp nhận phụ lục</h3>
                <label class="mt-4 flex items-start gap-3 text-sm leading-6 text-emerald-950"><input type="checkbox" name="confirmation" value="1" required class="mt-1 rounded border-emerald-300 text-emerald-700"><span>Tôi là người thuê đại diện, đã đọc và đồng ý toàn bộ nội dung phụ lục.</span></label>
                @error('confirmation')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <button class="mt-4 h-11 w-full rounded-lg bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Chấp nhận phụ lục</button>
            </form>
            <form method="POST" action="{{ route('client.contract-appendices.reject', $appendix) }}" class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                @csrf
                <h3 class="font-bold text-rose-950">Từ chối và yêu cầu chỉnh sửa</h3>
                <label class="mt-3 block text-sm font-semibold text-rose-950">Lý do từ chối *</label>
                <textarea name="rejection_reason" required minlength="10" maxlength="2000" rows="4" placeholder="Nêu rõ điều khoản chưa phù hợp để ban quản lý xem xét sửa" class="mt-1.5 w-full rounded-lg border border-rose-200 bg-white p-3 text-sm leading-6 outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-100">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <button class="mt-4 h-11 w-full rounded-lg bg-rose-700 px-4 text-sm font-bold text-white hover:bg-rose-800">Gửi lý do từ chối</button>
            </form>
        </section>
    @endif
</div>
@endsection
