@extends('layouts.admin.index')

@php($editing = isset($appendix))
@section('title', $editing ? 'Sửa phụ lục' : 'Lập phụ lục')
@section('page_title', $editing ? 'Sửa phụ lục hợp đồng' : 'Lập phụ lục hợp đồng')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div>
        <a href="{{ $editing ? route('admin.contract-appendices.show', $appendix) : route('admin.contracts.show', $contract) }}" class="text-sm font-semibold text-indigo-700">← Quay lại</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $editing ? 'Chỉnh sửa '.$appendix->code : 'Lập phụ lục cho '.$contract->contract_code }}</h2>
        <p class="mt-1 text-sm text-slate-600">Phòng {{ $contract->room?->room_code }} · Khách thuê đại diện {{ $contract->tenant?->full_name }}</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
        Phụ lục chỉ có hiệu lực sau khi gửi và được khách thuê đại diện chấp nhận. Nội dung hợp đồng gốc không bị sửa đổi.
    </div>

    <form method="POST" action="{{ $editing ? route('admin.contract-appendices.update', $appendix) : route('admin.contracts.appendices.store', $contract) }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="space-y-5 p-6">
            @if($editing && $appendix->parent?->rejection_reason)
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                    <strong>Lý do khách từ chối bản trước:</strong>
                    <p class="mt-1 whitespace-pre-line">{{ $appendix->parent->rejection_reason }}</p>
                </div>
            @endif
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tiêu đề phụ lục *</label>
                <select name="title" required data-appendix-title class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">-- Chọn điều khoản cần sửa đổi/bổ sung --</option>
                    @foreach($clauseOptions as $clauseTitle)
                        <option value="{{ $clauseTitle }}" data-price-fields="{{ implode(',', \App\Models\ContractAppendix::priceFieldsForTitle($clauseTitle)) }}" @selected(old('title', $appendix->title ?? '') === $clauseTitle)>{{ $clauseTitle }}</option>
                    @endforeach
                </select>
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <section data-price-adjustment-panel class="hidden rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                <h3 class="font-bold text-indigo-950">Đơn giá áp dụng cho hợp đồng</h3>
                <p class="mt-1 text-xs leading-5 text-indigo-800">Nhập đơn giá mới. Nếu ngày hiệu lực không phải ngày đầu tháng, hệ thống áp dụng từ kỳ dịch vụ kế tiếp; hóa đơn đã phát hành không thay đổi.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach(\App\Models\ContractAppendix::PRICE_FIELD_LABELS as $field => $label)
                        @php($storedChange = data_get($appendix ?? null, 'price_adjustments.'.$field))
                        @php($currentPrice = data_get($storedChange, 'old', $currentRates->{$field} ?? 0))
                        <div data-price-field="{{ $field }}" class="hidden rounded-lg border border-indigo-100 bg-white p-3">
                            <label class="block text-sm font-semibold text-slate-700">{{ $label }} mới ({{ \App\Models\ContractAppendix::PRICE_FIELD_UNITS[$field] }})</label>
                            <p class="mt-1 text-xs text-slate-500">Đang áp dụng: <strong>{{ number_format((float) $currentPrice, 0, ',', '.') }} {{ \App\Models\ContractAppendix::PRICE_FIELD_UNITS[$field] }}</strong></p>
                            <input data-price-input type="number" min="0" max="99999999.99" step="0.01" name="price_adjustments[{{ $field }}]" value="{{ old('price_adjustments.'.$field, data_get($storedChange, 'new')) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            @error('price_adjustments.'.$field)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                @error('price_adjustments')<p class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
            </section>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Căn cứ/Lý do lập phụ lục</label>
                <textarea name="legal_basis" rows="3" maxlength="2000" placeholder="Nêu chính sách, thỏa thuận hoặc căn cứ cần bổ sung" class="w-full rounded-lg border border-slate-200 p-3 leading-6 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('legal_basis', $appendix->legal_basis ?? '') }}</textarea>
                @error('legal_basis')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nội dung điều chỉnh/bổ sung *</label>
                <textarea name="content" data-appendix-content rows="12" required minlength="20" maxlength="30000" placeholder="Chọn tiêu đề phụ lục để lấy sẵn nội dung đang áp dụng, sau đó chỉnh sửa phần cần thay đổi" class="w-full rounded-lg border border-slate-200 p-3 leading-7 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('content', $appendix->content ?? '') }}</textarea>
                @error('content')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="max-w-sm">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày bắt đầu áp dụng *</label>
                <input type="date" name="effective_from" required value="{{ old('effective_from', isset($appendix) ? $appendix->effective_from?->toDateString() : today()->toDateString()) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @error('effective_from')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ $editing ? route('admin.contract-appendices.show', $appendix) : route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Hủy</a>
            <button class="h-11 rounded-lg bg-indigo-700 px-5 text-sm font-bold text-white hover:bg-indigo-800">{{ $editing ? 'Lưu bản nháp' : 'Tạo bản nháp' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const title = document.querySelector('[data-appendix-title]');
    const panel = document.querySelector('[data-price-adjustment-panel]');
    const content = document.querySelector('[data-appendix-content]');
    const contentDefaults = @json($contentDefaults);
    if (!title || !panel || !content) return;
    let lastAutoContent = '';

    const syncPriceFields = () => {
        const fields = (title.selectedOptions[0]?.dataset.priceFields || '').split(',').filter(Boolean);
        panel.classList.toggle('hidden', fields.length === 0);
        panel.querySelectorAll('[data-price-field]').forEach(card => {
            const active = fields.includes(card.dataset.priceField);
            const input = card.querySelector('[data-price-input]');
            card.classList.toggle('hidden', !active);
            if (input) {
                input.disabled = !active;
                input.required = active && fields.length === 1;
            }
        });
    };

    const syncContent = () => {
        const defaultContent = contentDefaults[title.value] || '';
        if (!content.value.trim() || content.value === lastAutoContent) {
            content.value = defaultContent;
        }
        lastAutoContent = defaultContent;
    };

    title.addEventListener('change', () => {
        syncPriceFields();
        syncContent();
    });
    syncPriceFields();
    syncContent();
});
</script>
@endpush
