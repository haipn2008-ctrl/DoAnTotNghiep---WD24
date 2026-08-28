@extends('layouts.admin.index')

@section('title', 'Mẫu hợp đồng')
@section('page_title', 'Mẫu hợp đồng')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <a href="{{ route('admin.contracts.index') }}" class="text-sm font-semibold text-indigo-700">← Quản lý hợp đồng</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">Quản lý mẫu hợp đồng</h2>
            <p class="mt-1 text-sm text-slate-600">Mẫu hiện hành: phiên bản {{ $template->version }}. Muốn chỉnh sửa, hãy mở chi tiết một phiên bản để tạo phiên bản mới từ nội dung đó.</p>
        </div>
        <a target="_blank" href="{{ route('admin.contracts.template.print') }}" class="inline-flex h-11 items-center justify-center rounded-lg bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">In bản mẫu hiện hành</a>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="font-bold text-slate-950">Lịch sử phiên bản</h3>
            <p class="mt-1 text-sm text-slate-500">Các phiên bản đã phát hành không bị sửa đè.</p>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3 sm:p-6">
            @foreach($versions as $version)
                <article class="flex flex-col rounded-xl border {{ $version->is_active ? 'border-emerald-300 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <strong class="text-base text-slate-950">Phiên bản {{ $version->version }}</strong>
                            <p class="mt-1 text-sm text-slate-600">{{ $version->name }}</p>
                        </div>
                        @if($version->is_active)<span class="shrink-0 rounded-full bg-emerald-700 px-2.5 py-1 text-xs font-bold text-white">Đang áp dụng</span>@endif
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Phát hành lúc {{ $version->effective_from?->format('H:i d/m/Y') }}</p>
                    <a href="{{ route('admin.contracts.template.show', $version) }}" class="mt-4 inline-flex h-10 items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Xem chi tiết</a>
                </article>
            @endforeach
        </div>
    </section>

</div>
@endsection
