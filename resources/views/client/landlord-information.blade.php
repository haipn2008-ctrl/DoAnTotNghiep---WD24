@extends('layouts.client.index')

@section('title', 'Thông tin chủ trọ | Cổng khách thuê')
@section('page_title', 'Thông tin chủ trọ')

@section('content')
    @php($phoneLink = preg_replace('/[^0-9+]/', '', (string) $setting->landlord_phone))

    <div class="mx-auto max-w-4xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 sm:px-8"><div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div><div class="relative flex items-center gap-4"><span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 4.5h11v15h-11v-15ZM9 8h6m-6 3h6m-6 3h3" /></svg></span><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">Liên hệ</p><h2 class="mt-1 text-2xl font-bold sm:text-3xl">Thông tin chủ trọ</h2><p class="mt-2 text-sm text-indigo-100">Thông tin liên hệ chính thức của đơn vị quản lý nơi ở.</p></div></div></section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-5">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-xl font-bold text-indigo-700">
                    {{ mb_strtoupper(mb_substr($setting->landlord_name ?: 'C', 0, 1)) }}
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Chủ trọ</p>
                    <h3 class="mt-0.5 text-lg font-bold text-slate-950">{{ $setting->landlord_name ?: 'Chưa thiết lập' }}</h3>
                </div>
            </div>

            <dl class="divide-y divide-slate-100 px-5">
                <div class="grid gap-1 py-4 sm:grid-cols-[160px_1fr]">
                    <dt class="text-sm font-medium text-slate-500">Số điện thoại</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                        @if(filled($setting->landlord_phone))
                            <a href="tel:{{ $phoneLink }}" class="text-indigo-700 hover:text-indigo-800">{{ $setting->landlord_phone }}</a>
                        @else
                            Chưa thiết lập
                        @endif
                    </dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-[160px_1fr]">
                    <dt class="text-sm font-medium text-slate-500">Địa chỉ chủ trọ</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $setting->landlord_address ?: 'Chưa thiết lập' }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-[160px_1fr]">
                    <dt class="text-sm font-medium text-slate-500">Nhà trọ</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $setting->property_name ?: 'Nhà trọ' }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-[160px_1fr]">
                    <dt class="text-sm font-medium text-slate-500">Địa chỉ nhà trọ</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $setting->property_address ?: 'Chưa thiết lập' }}</dd>
                </div>
            </dl>

            @if(auth()->user()?->isActive())
                <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <a href="{{ route('client.support.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 hover:text-indigo-800">
                        Gửi yêu cầu hỗ trợ <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </section>
    </div>
@endsection
