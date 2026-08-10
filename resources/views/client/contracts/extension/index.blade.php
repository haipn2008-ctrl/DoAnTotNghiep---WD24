@extends('layouts.client.index')

@section('title', 'Yêu cầu gia hạn | Cổng khách thuê')
@section('page_title', 'Yêu cầu gia hạn')

@section('content')
<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">HỢP ĐỒNG</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">Yêu cầu gia hạn hợp đồng</h1>
        <p class="mt-2 text-sm text-slate-500">Gửi yêu cầu gia hạn thời gian thuê phòng của bạn.</p>
    </div>

    {{-- THÔNG BÁO --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
            <p class="text-sm font-bold">Không thể gửi yêu cầu</p>
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-bold text-slate-900">Gửi yêu cầu gia hạn</h2>
            <p class="mt-1 text-xs text-slate-500">Chọn hợp đồng và thời gian bạn muốn tiếp tục thuê.</p>
        </div>

        <div class="p-6">
            @if($contracts->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7V3z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-bold text-slate-900">Không có hợp đồng đang hiệu lực</h3>
                    <p class="mt-1 text-sm text-slate-500">Bạn cần có hợp đồng đang hiệu lực để gửi yêu cầu gia hạn.</p>
                </div>
            @else
                <form method="POST" action="{{ route('client.extension-requests.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="contract_id" class="mb-2 block text-sm font-semibold text-slate-900">
                            Hợp đồng <span class="text-red-500">*</span>
                        </label>
                        <select name="contract_id" id="contract_id" required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <option value="">-- Chọn hợp đồng --</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}"
                                        data-end-date="{{ optional($contract->end_date)->format('Y-m-d') }}"
                                        {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                    {{ $contract->contract_code ?? ('HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT)) }}
                                    - Phòng {{ $contract->room->room_code ?? $contract->room->room_number ?? $contract->room->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="contractInfo" class="hidden rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Ngày kết thúc hiện tại</p>
                        <p id="currentEndDate" class="mt-1 text-base font-bold text-indigo-950">-</p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="requested_end_date" class="mb-2 block text-sm font-semibold text-slate-900">
                                Gia hạn đến ngày <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   name="requested_end_date"
                                   id="requested_end_date"
                                   value="{{ old('requested_end_date') }}"
                                   required
                                   class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <p class="mt-2 text-xs text-slate-500">Ngày mới phải sau ngày kết thúc hiện tại của hợp đồng.</p>
                        </div>

                        <div>
                            <label for="reason" class="mb-2 block text-sm font-semibold text-slate-900">Lý do gia hạn</label>
                            <textarea name="reason"
                                      id="reason"
                                      rows="4"
                                      maxlength="1000"
                                      placeholder="Ví dụ: Tôi muốn tiếp tục thuê phòng trong thời gian tới..."
                                      class="block w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">{{ old('reason') }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-5">
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3 3l18 9-18 9 3-9Zm0 0h8"/>
                            </svg>
                            Gửi yêu cầu gia hạn
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- LỊCH SỬ --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-bold text-slate-900">Lịch sử yêu cầu</h2>
            <p class="mt-1 text-xs text-slate-500">Theo dõi trạng thái các yêu cầu gia hạn đã gửi.</p>
        </div>

        @if($extensionRequests->isEmpty())
            <div class="px-6 py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h3 class="mt-4 font-bold text-slate-900">Chưa có yêu cầu gia hạn</h3>
                <p class="mt-1 text-sm text-slate-500">Các yêu cầu bạn gửi sẽ xuất hiện tại đây.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Hợp đồng</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ngày kết thúc cũ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Đề nghị đến</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($extensionRequests as $extension)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $extension->contract->contract_code ?? ('HD' . str_pad($extension->contract_id, 3, '0', STR_PAD_LEFT)) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Phòng {{ $extension->contract->room->room_code ?? $extension->contract->room->room_number ?? $extension->contract->room->name ?? 'N/A' }}
                                    </p>
                                    @if($extension->reason)
                                        <p class="mt-2 max-w-md text-xs leading-5 text-slate-500">
                                            <span class="font-semibold text-slate-700">Lý do:</span> {{ $extension->reason }}
                                        </p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ $extension->current_end_date ? \Carbon\Carbon::parse($extension->current_end_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-900">
                                    {{ $extension->requested_end_date ? \Carbon\Carbon::parse($extension->requested_end_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($extension->status === 'pending')
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Chờ duyệt</span>
                                    @elseif($extension->status === 'approved')
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Đã duyệt</span>
                                    @elseif($extension->status === 'rejected')
                                        <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Từ chối</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $extension->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const contractSelect = document.getElementById('contract_id');
    const contractInfo = document.getElementById('contractInfo');
    const currentEndDate = document.getElementById('currentEndDate');
    const requestedEndDate = document.getElementById('requested_end_date');

    if (!contractSelect || !contractInfo || !currentEndDate || !requestedEndDate) return;

    function updateContractInfo() {
        const option = contractSelect.options[contractSelect.selectedIndex];
        const endDate = option ? option.dataset.endDate : null;

        if (!endDate) {
            contractInfo.classList.add('hidden');
            currentEndDate.textContent = '-';
            requestedEndDate.removeAttribute('min');
            return;
        }

        contractInfo.classList.remove('hidden');

        const date = new Date(endDate + 'T00:00:00');
        currentEndDate.textContent = date.toLocaleDateString('vi-VN');

        date.setDate(date.getDate() + 1);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        requestedEndDate.min = `${year}-${month}-${day}`;
    }

    contractSelect.addEventListener('change', updateContractInfo);
    updateContractInfo();
});
</script>
@endpush
