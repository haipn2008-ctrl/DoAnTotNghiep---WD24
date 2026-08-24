@extends('layouts.admin.index')

@section('title', 'Yêu cầu trả phòng')
@section('page_title', 'Quản lý phòng trọ')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Yêu cầu trả phòng
            </h1>

        </div>

        <a href="{{ route('admin.contracts.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <span aria-hidden="true">←</span>
            Quản lý hợp đồng
        </a>
    </div>


    {{-- THÔNG BÁO --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ session('error') }}
        </div>
    @endif


    {{-- THỐNG KÊ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Tổng --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Tổng yêu cầu
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $terminationRequests->count() }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">
                    #
                </div>
            </div>
        </div>


        {{-- Chờ duyệt --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-700">
                        Chờ duyệt
                    </p>

                    <p class="mt-2 text-3xl font-bold text-amber-700">
                        {{ $terminationRequests->where('status', 'pending')->count() }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-xl text-amber-700">
                    ◷
                </div>
            </div>
        </div>


        {{-- Đã duyệt --}}
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">
                        Đã duyệt
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-700">
                        {{ $terminationRequests->where('status', 'approved')->count() }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xl font-bold text-emerald-700">
                    ✓
                </div>
            </div>
        </div>


        {{-- Từ chối --}}
        <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-700">
                        Từ chối
                    </p>

                    <p class="mt-2 text-3xl font-bold text-rose-700">
                        {{ $terminationRequests->where('status', 'rejected')->count() }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-xl font-bold text-rose-700">
                    ×
                </div>
            </div>
        </div>

    </div>


    {{-- DANH SÁCH --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- HEADER TABLE --}}
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">

            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Danh sách yêu cầu
                </h2>

            </div>

            <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600">
                {{ $terminationRequests->count() }} yêu cầu
            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            #
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Hợp đồng
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Khách thuê
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Ngày kết thúc HĐ
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Ngày muốn trả
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Lý do
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Trạng thái
                        </th>

                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Thao tác
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse($terminationRequests as $request)

                        <tr id="request-{{ $request->id }}" class="scroll-mt-24 transition hover:bg-slate-50/80 target:bg-indigo-50">

                            {{-- STT --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-500">
                                {{ $loop->iteration }}
                            </td>


                            {{-- HỢP ĐỒNG --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-600">
                                        HĐ
                                    </div>

                                    <div>

                                        <div class="font-semibold text-slate-900">
                                            {{ $request->contract->contract_code ?? '-' }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-500">
                                            Phòng {{ $request->contract->room->room_code ?? '-' }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- KHÁCH THUÊ --}}
                            <td class="px-5 py-4">

                                <div class="font-medium text-slate-900">
                                    {{ $request->contract->tenant->full_name ?? '-' }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $request->contract->tenant->phone ?? 'Chưa có SĐT' }}
                                </div>

                            </td>


                            {{-- NGÀY KẾT THÚC HỢP ĐỒNG --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">

                                {{ optional($request->contract->end_date)->format('d/m/Y') ?? '-' }}

                            </td>


                            {{-- NGÀY MUỐN TRẢ --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="font-bold text-rose-600">
                                    {{ optional($request->requested_end_date)->format('d/m/Y') ?? '-' }}
                                </div>

                                @if(
                                    $request->requested_end_date &&
                                    $request->contract?->end_date &&
                                    $request->requested_end_date->lt($request->contract->end_date)
                                )
                                    <div class="mt-1 text-xs font-medium text-amber-600">
                                        Trả trước hạn
                                    </div>
                                @endif

                            </td>


                            {{-- LÝ DO --}}
                            <td class="max-w-xs px-5 py-4 text-sm leading-6 text-slate-600">

                                {{ $request->reason ?: 'Không có lý do' }}

                            </td>


                            {{-- TRẠNG THÁI --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                @if($request->status === 'pending')

                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        Chờ duyệt
                                    </span>

                                @elseif($request->status === 'approved')

                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Đã duyệt
                                    </span>

                                @elseif($request->status === 'rejected')

                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        Từ chối
                                    </span>

                                @endif

                            </td>


                            {{-- THAO TÁC --}}
                            <td class="whitespace-nowrap px-5 py-4 text-center">

                                @if($request->status === 'pending')

                                    <div class="flex items-center justify-center gap-2">

                                        {{-- DUYỆT --}}
                                        <form method="POST"
                                              action="{{ route('admin.termination-requests.approve', $request) }}"
                                              onsubmit="return confirm('Bạn chắc chắn muốn duyệt yêu cầu trả phòng này?')">

                                            @csrf

                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                ✓ Duyệt
                                            </button>

                                        </form>


                                        {{-- TỪ CHỐI --}}
                                        <form method="POST"
                                              action="{{ route('admin.termination-requests.reject', $request) }}"
                                              onsubmit="return confirm('Bạn chắc chắn muốn từ chối yêu cầu trả phòng này?')">

                                            @csrf

                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                                × Từ chối
                                            </button>

                                        </form>

                                    </div>

                                @else

                                    <span class="text-sm font-medium text-slate-400">
                                        Đã xử lý
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8"
                                class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    □
                                </div>

                                <h3 class="mt-4 font-semibold text-slate-900">
                                    Chưa có yêu cầu trả phòng
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Yêu cầu trả phòng của khách thuê sẽ xuất hiện tại đây.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- FOOTER --}}
        <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 text-sm text-slate-500 sm:px-6">

            Hiển thị
            <span class="font-semibold text-slate-700">
                {{ $terminationRequests->count() }}
            </span>
            yêu cầu

        </div>

    </div>

</div>

@endsection
