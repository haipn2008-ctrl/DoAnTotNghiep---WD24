@extends('layouts.client.index')

@section('title', 'Thành viên trong phòng | Cổng khách thuê')
@section('page_title', 'Thành viên trong phòng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Quản lý nơi ở</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Thành viên trong phòng</h2>
                <p class="mt-2 text-sm text-slate-500">
                    @if($room)
                        Những người đã được xác nhận đang ở phòng {{ $room->room_code }}.
                    @else
                        Danh sách sẽ xuất hiện khi tài khoản có phòng đang thuê.
                    @endif
                </p>
            </div>

            @if($room)
                <a href="{{ route('client.room.show', ['contract' => $contract->id]).'#room-'.$contract->id }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="bx bx-building-house text-lg"></i>
                    Xem phòng {{ $room->room_code }}
                </a>
            @endif
        </div>

        @if($room)
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-indigo-50/60 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                            <i class="bx bx-group text-2xl"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-950">Danh sách đang cư trú</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Chọn một thành viên để xem và cập nhật hồ sơ.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm">
                        {{ $members->count() }} thành viên
                    </span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3 sm:p-6">
                    @forelse($members as $member)
                        @php($isRepresentative = $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE)
                        @php($isCurrentUser = $member->tenant_id === auth()->user()?->tenant?->id)
                        <a href="{{ route('client.room.members.show', ['member' => $member, 'contract' => $contract->id]) }}" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100/70 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            <span class="absolute inset-x-0 top-0 h-1 {{ $isRepresentative ? 'bg-indigo-500' : 'bg-slate-200 group-hover:bg-indigo-300' }}"></span>
                            <span class="flex items-start gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-100 to-violet-100 text-lg font-bold uppercase text-indigo-700 ring-4 ring-indigo-50">
                                    {{ mb_substr(trim($member->full_name), 0, 1) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span class="block truncate font-bold text-slate-950">{{ $member->full_name }}</span>
                                        @if($isCurrentUser)<span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Bạn</span>@endif
                                    </span>
                                    <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $isRepresentative ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $member->role_label }}
                                    </span>
                                </span>
                                <i class="bx bx-chevron-right text-2xl text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-600"></i>
                            </span>

                            <span class="mt-5 grid gap-2 border-t border-slate-100 pt-4 text-sm text-slate-500">
                                <span class="flex items-center gap-2">
                                    <i class="bx bx-phone text-base text-slate-400"></i>
                                    {{ $member->phone ?: 'Chưa cập nhật số điện thoại' }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <i class="bx bx-calendar-check text-base text-slate-400"></i>
                                    @if($member->actual_move_in_at)
                                        Ở từ {{ $member->actual_move_in_at->format('d/m/Y') }}
                                    @else
                                        Đang cư trú
                                    @endif
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center sm:col-span-2 xl:col-span-3">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                <i class="bx bx-user-x text-3xl"></i>
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-800">Chưa có thành viên</h3>
                            <p class="mt-1 text-sm text-slate-500">Thành viên sẽ xuất hiện sau khi được xác nhận vào ở.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="bx bx-building-house text-3xl"></i>
                </span>
                <h3 class="mt-4 font-semibold text-slate-950">Chưa có phòng đang thuê</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Danh sách thành viên sẽ xuất hiện khi tài khoản có hợp đồng đang hiệu lực.</p>
            </div>
        @endif
    </div>
@endsection
