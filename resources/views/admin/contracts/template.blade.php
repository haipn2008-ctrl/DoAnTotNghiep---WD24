@extends('layouts.admin.index')

@section('title', 'Mẫu hợp đồng')
@section('page_title', 'Mẫu hợp đồng')

@section('content')
<div class="space-y-6">
    @include('admin.contracts.partials.workspace-nav')

    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-indigo-950 to-indigo-800 shadow-lg shadow-indigo-950/10">
        <div class="flex flex-col justify-between gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-center">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-indigo-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Phiên bản {{ $template->version }} đang áp dụng
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Quản lý mẫu hợp đồng</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100/80">Theo dõi các lần phát hành và kiểm tra nội dung mẫu đang dùng. Mỗi lần chỉnh sửa sẽ được lưu thành một phiên bản mới để bảo toàn lịch sử.</p>
            </div>
            <a data-contract-print href="{{ route('admin.contracts.template.print') }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-indigo-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-white/30">
                <i class="bx bx-printer text-lg" aria-hidden="true"></i>
                In mẫu hiện hành
            </a>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="text-lg font-bold text-slate-950">Các phiên bản đã phát hành</h3>
                <p class="mt-1 text-sm text-slate-500">Chọn một phiên bản để xem nội dung hoặc tạo bản cập nhật mới.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                <i class="bx bx-layer text-base" aria-hidden="true"></i>
                {{ $versions->count() }} phiên bản
            </span>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
            @foreach($versions as $version)
                <article class="group relative flex flex-col overflow-hidden rounded-xl border {{ $version->is_active ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-200 bg-white' }} p-5 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                    <div class="absolute inset-x-0 top-0 h-1 {{ $version->is_active ? 'bg-emerald-500' : 'bg-slate-200 group-hover:bg-indigo-500' }}"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Phiên bản</span>
                            <strong class="mt-1 block text-xl text-slate-950">{{ $version->version }}</strong>
                        </div>
                        @if($version->is_active)<span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Đang áp dụng</span>@endif
                    </div>
                    <p class="mt-3 line-clamp-2 min-h-10 text-sm leading-5 text-slate-600">{{ $version->name }}</p>
                    <div class="mt-4 flex items-center gap-2 border-t border-slate-200/80 pt-4 text-xs text-slate-500">
                        <i class="bx bx-calendar text-base" aria-hidden="true"></i>
                        {{ $version->effective_from?->format('H:i · d/m/Y') }}
                    </div>
                    <a href="{{ route('admin.contracts.template.show', $version) }}" class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-indigo-600 hover:bg-indigo-600 hover:text-white">
                        Xem chi tiết
                        <i class="bx bx-right-arrow-alt text-lg" aria-hidden="true"></i>
                    </a>
                </article>
            @endforeach
        </div>
    </section>

</div>
@endsection
