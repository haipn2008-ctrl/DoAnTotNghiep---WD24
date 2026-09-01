@extends('layouts.admin.index')

@section('title', 'Chi tiết mẫu hợp đồng')
@section('page_title', 'Chi tiết mẫu hợp đồng')

@section('content')
@php
    $labels = [
        'deposit_payment' => 'Thanh toán tiền cọc',
        'monthly_payment' => 'Thanh toán hằng tháng',
        'landlord_obligations' => 'Quyền và nghĩa vụ Bên A',
        'tenant_obligations' => 'Quyền và nghĩa vụ Bên B',
        'early_termination' => 'Chấm dứt trước thời hạn',
        'settlement' => 'Quyết toán khi kết thúc',
        'commitment' => 'Cam kết của hai bên',
        'effectiveness' => 'Hiệu lực và sửa đổi hợp đồng',
        'dispute_resolution' => 'Giải quyết tranh chấp',
        'copies' => 'Số bản và xác nhận nội dung',
    ];
    $showEditForm = $errors->any();
@endphp

<div class="space-y-6">
    @include('admin.contracts.partials.workspace-nav')

    <div class="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:flex-row lg:items-center">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-slate-950">Chi tiết phiên bản {{ $template->version }}</h2>
                @if($template->is_active)<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Đang áp dụng</span>@endif
            </div>
            <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600"><span>{{ $template->name }}</span><span class="hidden text-slate-300 sm:inline">•</span><span class="inline-flex items-center gap-1.5"><i class="bx bx-calendar" aria-hidden="true"></i>Phát hành {{ $template->effective_from?->format('H:i d/m/Y') }}</span></p>
        </div>
        <button type="button" id="open-template-editor" aria-controls="template-editor" aria-expanded="{{ $showEditForm ? 'true' : 'false' }}" class="template-primary-action">
            <i class="bx bx-edit-alt" aria-hidden="true"></i>
            <span>Sửa phiên bản này</span>
        </button>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="font-bold text-slate-950">Bản xem trước</h3>
                <p class="mt-1 text-sm text-slate-500">Nội dung của phiên bản {{ $template->version }} được thu nhỏ để tiện kiểm tra.</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">Khổ A4 · bản thu nhỏ</span>
        </div>
        <div class="template-preview-stage">
            <div class="template-mini-preview">
                @include('admin.contracts.contract-template-content')
            </div>
        </div>
    </section>

    <section id="template-editor" class="{{ $showEditForm ? '' : 'hidden' }} overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm" tabindex="-1">
        <form method="POST" action="{{ route('admin.contracts.template.store') }}" data-confirm="Một phiên bản mẫu mới sẽ được phát hành từ nội dung đang chỉnh." data-confirm-label="Phát hành phiên bản">
            @csrf
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                    <h3 class="font-bold text-slate-950">Chỉnh sửa từ phiên bản {{ $template->version }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Khi lưu, hệ thống tạo phiên bản mới và không sửa đè lịch sử.</p>
                </div>
                <button type="button" id="close-template-editor" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-xl text-slate-500 hover:bg-slate-100" aria-label="Đóng form sửa">×</button>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tên mẫu</label>
                    <input name="name" maxlength="255" required value="{{ old('name', $template->name) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 xl:grid-cols-2">
                    @foreach($labels as $key => $label)
                        <div class="{{ in_array($key, ['landlord_obligations', 'tenant_obligations', 'effectiveness'], true) ? 'xl:col-span-2' : '' }}">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="clause-{{ $key }}">{{ $label }}</label>
                            <textarea id="clause-{{ $key }}" name="clauses[{{ $key }}]" rows="4" maxlength="5000" required class="w-full rounded-lg border border-slate-200 p-3 leading-6 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old("clauses.{$key}", $template->clause($key)) }}</textarea>
                            @if($key === 'monthly_payment')<p class="mt-1 text-xs text-slate-500">Có thể dùng <code>:invoice_day</code> để tự điền ngày lập hóa đơn.</p>@endif
                            @error("clauses.{$key}")<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row">
                <button type="button" id="cancel-template-editor" class="template-secondary-action">Hủy</button>
                <button class="template-primary-action">
                    <i class="bx bx-save" aria-hidden="true"></i>
                    <span>Lưu thành phiên bản mới</span>
                </button>
            </div>
        </form>
    </section>
</div>

<style>
    .template-primary-action,
    .template-secondary-action {
        display: inline-flex;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.25;
        cursor: pointer;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    .template-primary-action {
        border: 1px solid #4338ca;
        background: #4338ca;
        color: #fff;
        box-shadow: 0 4px 12px rgba(67, 56, 202, .24);
    }
    .template-primary-action:hover {
        border-color: #3730a3;
        background: #3730a3;
        color: #fff;
        box-shadow: 0 6px 16px rgba(67, 56, 202, .30);
        transform: translateY(-1px);
    }
    .template-primary-action:focus-visible {
        outline: 3px solid rgba(99, 102, 241, .28);
        outline-offset: 3px;
    }
    .template-primary-action i { font-size: 18px; }
    .template-secondary-action {
        border: 1px solid #94a3b8;
        background: #fff;
        color: #334155;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
    }
    .template-secondary-action:hover {
        border-color: #64748b;
        background: #f1f5f9;
        color: #0f172a;
    }
    .template-secondary-action:focus-visible {
        outline: 3px solid rgba(100, 116, 139, .22);
        outline-offset: 3px;
    }
    .template-preview-stage {
        display: flex;
        justify-content: center;
        overflow-x: auto;
        padding: 36px 24px 44px;
        background: #eef2f7;
    }
    .template-mini-preview {
        width: min(100%, 650px);
        min-height: 919px;
        box-sizing: border-box;
        padding: 44px 48px 52px;
        background: #fff;
        border: 1px solid #dbe2ea;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .14);
    }
    .template-mini-preview .contract-document { font-size: 13px; line-height: 1.5; }
    .template-mini-preview .contract-document p { margin: 6px 0; }
    .template-mini-preview .contract-document .national { font-size: 16px; }
    .template-mini-preview .contract-document .slogan { font-size: 14px; margin-top: 2px; }
    .template-mini-preview .contract-document .title { font-size: 19px; margin: 22px 0 2px; }
    .template-mini-preview .contract-document .code { margin-bottom: 18px; }
    .template-mini-preview .contract-document .section-title { font-size: 15px; margin: 18px 0 7px; }
    .template-mini-preview .contract-document th,
    .template-mini-preview .contract-document td { padding: 5px 6px; }
    .template-mini-preview .contract-document .no-border td { padding: 2px 0; }
    .template-mini-preview .contract-document .label { width: 155px; }
    .template-mini-preview .contract-document .line { min-width: 90px; }
    .template-mini-preview .contract-document .line.long { min-width: 210px; }
    .template-mini-preview .contract-document .line.short { min-width: 48px; }
    .template-mini-preview .contract-document .signature-space { height: 76px; }

    @media (max-width: 720px) {
        .template-preview-stage { justify-content: flex-start; padding: 16px; }
        .template-mini-preview {
            width: 650px;
            min-width: 650px;
            padding: 40px 44px 48px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('template-editor');
    const openButton = document.getElementById('open-template-editor');
    const closeButtons = [
        document.getElementById('close-template-editor'),
        document.getElementById('cancel-template-editor'),
    ];

    const openEditor = () => {
        editor?.classList.remove('hidden');
        openButton?.setAttribute('aria-expanded', 'true');
        editor?.focus({ preventScroll: true });
        editor?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    const closeEditor = () => {
        editor?.classList.add('hidden');
        openButton?.setAttribute('aria-expanded', 'false');
        openButton?.focus();
    };

    openButton?.addEventListener('click', openEditor);
    closeButtons.forEach(button => button?.addEventListener('click', closeEditor));

    @if($showEditForm)
        editor?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    @endif
});
</script>
@endsection
