@extends('layouts.client.index')

@section('title', 'Đăng ký lịch rời phòng | Cổng khách thuê')
@section('page_title', 'Lịch rời phòng')

@section('content')
<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-950">
            Đăng ký lịch rời phòng
        </h1>

        <p class="mt-2 text-sm text-slate-500">Chọn ngày hết hạn nếu bạn rời đi đúng hạn, hoặc một ngày sớm hơn nếu muốn chấm dứt hợp đồng trước hạn.</p>

    </div>


    {{-- VALIDATION --}}
    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">

            <p class="text-sm font-bold">
                Không thể gửi yêu cầu
            </p>

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

            <h2 class="font-bold text-slate-900">
                Gửi lịch rời phòng dự kiến
            </h2>


        </div>


        <div class="p-6">

            @if($contracts->isEmpty())

                <div class="py-12 text-center">

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                        <svg class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="1.8">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6M7 3h7l5 5v13H7V3z"/>

                        </svg>

                    </div>

                    <h3 class="mt-4 font-bold text-slate-900">
                        Không có hợp đồng đang hiệu lực
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Bạn cần có hợp đồng đang có người ở hoặc vừa quá hạn để đăng ký lịch rời phòng.
                    </p>

                </div>

            @else

                <form method="POST"
                      action="{{ route('client.termination-requests.store') }}"
                      class="space-y-6">

                    @csrf


                    {{-- CHỌN HỢP ĐỒNG --}}
                    <div>

                        <label for="contract_id"
                               class="mb-2 block text-sm font-semibold text-slate-900">

                            Hợp đồng
                            <span class="text-red-500">*</span>

                        </label>

                        <select name="contract_id"
                                id="contract_id"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">

                            <option value="">
                                -- Chọn hợp đồng --
                            </option>

                            @foreach($contracts as $contract)

                                <option
                                    value="{{ $contract->id }}"

                                    data-end-date="{{ optional($contract->end_date)->format('Y-m-d') }}"

                                    {{ old('contract_id') == $contract->id ? 'selected' : '' }}>

                                    {{ $contract->contract_code
                                        ?? ('HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT)) }}

                                    - Phòng

                                    {{ $contract->room->room_code
                                        ?? $contract->room->room_number
                                        ?? $contract->room->name
                                        ?? 'Không có' }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- THÔNG TIN HỢP ĐỒNG --}}
                    <div id="contractInfo"
                         class="hidden rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4">

                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">
                            Ngày kết thúc hợp đồng hiện tại
                        </p>

                        <p id="currentEndDate"
                           class="mt-1 text-base font-bold text-indigo-950">
                            -
                        </p>

                    </div>


                    <div class="grid gap-6 md:grid-cols-2">


                        {{-- NGÀY TRẢ PHÒNG --}}
                        <div>

                            <label for="requested_end_date"
                                   class="mb-2 block text-sm font-semibold text-slate-900">

                                Ngày dự kiến trả phòng
                                <span class="text-red-500">*</span>

                            </label>

                            <input type="date"
                                   name="requested_end_date"
                                   id="requested_end_date"

                                   value="{{ old('requested_end_date') }}"

                                   min="{{ now()->format('Y-m-d') }}"

                                   required

                                   class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">

                            <p class="mt-2 text-xs text-slate-500">
                                Ngày bằng ngày hết hạn là rời đúng hạn; ngày sớm hơn là chấm dứt trước hạn.
                            </p>

                        </div>


                        {{-- LÝ DO --}}
                        <div>

                            <label for="reason"
                                   class="mb-2 block text-sm font-semibold text-slate-900">

                                Lý do trả phòng
                                <span class="text-red-500">*</span>

                            </label>

                            <textarea
                                name="reason"
                                id="reason"
                                rows="4"
                                maxlength="1000"
                                required

                                placeholder="Ví dụ: Tôi muốn chuyển chỗ ở..."

                                class="block w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">{{ old('reason') }}</textarea>

                        </div>

                    </div>


                    {{-- BUTTON --}}
                    
<div class="flex justify-end border-t border-slate-200 pt-5">

    <button
        type="submit"
        id="submitTerminationBtn"
        style="
            background-color: #dc2626;
            color: #ffffff;
            border: none;
            min-width: 190px;
        "
        class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-bold shadow-sm transition hover:opacity-90"
    >

        <svg xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             class="h-4 w-4">

            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 12 3 3l18 9-18 9 3-9Zm0 0h8"/>
        </svg>

        <span>Gửi lịch rời phòng</span>

    </button>

</div>
                </form>

            @endif

        </div>

    </div>



    {{-- ============================= --}}
    {{-- LỊCH SỬ YÊU CẦU --}}
    {{-- ============================= --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h2 class="font-bold text-slate-900">
                Lịch sử yêu cầu
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Theo dõi trạng thái các yêu cầu trả phòng đã gửi.
            </p>

        </div>


        @if($terminationRequests->isEmpty())

            <div class="px-6 py-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                    <svg class="h-6 w-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>

                    </svg>

                </div>

                <h3 class="mt-4 font-bold text-slate-900">
                    Chưa có yêu cầu trả phòng
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Các yêu cầu bạn gửi sẽ xuất hiện tại đây.
                </p>

            </div>

        @else

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Hợp đồng
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Ngày kết thúc HĐ
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Dự kiến trả
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Trạng thái
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100 bg-white">

                        @foreach($terminationRequests as $termination)

                            <tr class="transition hover:bg-slate-50">


                                {{-- HỢP ĐỒNG --}}
                                <td class="px-6 py-4">

                                    <p class="text-sm font-bold text-slate-900">

                                        {{ $termination->contract->contract_code
                                            ?? ('HD' . str_pad(
                                                $termination->contract_id,
                                                3,
                                                '0',
                                                STR_PAD_LEFT
                                            )) }}

                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">

                                        Phòng

                                        {{ $termination->contract->room->room_code
                                            ?? $termination->contract->room->room_number
                                            ?? $termination->contract->room->name
                                            ?? 'Không có' }}

                                    </p>


                                    @if($termination->reason)

                                        <p class="mt-2 max-w-md text-xs leading-5 text-slate-500">

                                            <span class="font-semibold text-slate-700">
                                                Lý do:
                                            </span>

                                            {{ $termination->reason }}

                                        </p>

                                    @endif

                                </td>


                                {{-- NGÀY KẾT THÚC HỢP ĐỒNG --}}
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">

                                    {{ optional($termination->contract->end_date)
                                        ->format('d/m/Y') ?? '-' }}

                                </td>


                                {{-- NGÀY TRẢ PHÒNG --}}
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-900">

                                    {{ optional($termination->requested_end_date)
                                        ->format('d/m/Y') ?? '-' }}

                                </td>


                                {{-- TRẠNG THÁI --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    @if($termination->status === 'pending')

                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                            Chờ duyệt
                                        </span>

                                    @elseif($termination->status === 'approved')

                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                            Đã duyệt
                                        </span>

                                    @elseif($termination->status === 'rejected')

                                        <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700">
                                            Từ chối
                                        </span>

                                    @else

                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                            Không xác định
                                        </span>

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



{{-- ============================= --}}
{{-- JAVASCRIPT --}}
{{-- ============================= --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const contractSelect =
        document.getElementById('contract_id');

    const contractInfo =
        document.getElementById('contractInfo');

    const currentEndDate =
        document.getElementById('currentEndDate');


    if (
        !contractSelect ||
        !contractInfo ||
        !currentEndDate
    ) {
        return;
    }


    function updateContractInfo() {

        const option =
            contractSelect.options[
                contractSelect.selectedIndex
            ];

        const endDate =
            option
                ? option.dataset.endDate
                : null;


        if (!endDate) {

            contractInfo.classList.add('hidden');

            currentEndDate.textContent = '-';

            return;
        }


        contractInfo.classList.remove('hidden');


        const date =
            new Date(endDate + 'T00:00:00');


        currentEndDate.textContent =
            date.toLocaleDateString('vi-VN');

    }


    contractSelect.addEventListener(
        'change',
        updateContractInfo
    );


    updateContractInfo();

});

</script>

@endpush
