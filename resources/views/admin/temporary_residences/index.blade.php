@extends('layouts.admin.index')

@section('title', 'Giấy tạm trú')
@section('page_title', 'Giấy tạm trú')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div><h2 class="text-2xl font-bold text-slate-950">Giấy tạm trú của người thuê</h2><p class="mt-1 text-sm text-slate-500">Mỗi người đang ở hiển thị một dòng; lịch sử giấy được lưu trong trang chi tiết.</p></div>
        <a href="{{ route('admin.temporary_residences.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700"><i class="bx bx-upload mr-2 text-lg"></i>Cập nhật giấy tạm trú</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Người đang thuê</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $summary['total'] }}</p></div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"><p class="text-sm text-emerald-700">Đã có hồ sơ còn hiệu lực</p><p class="mt-2 text-3xl font-bold text-emerald-800">{{ $summary['documented'] }}</p></div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-sm text-amber-700">Chưa có/Cần cập nhật lại</p><p class="mt-2 text-3xl font-bold text-amber-800">{{ $summary['missing'] }}</p></div>
    </div>

    <form class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_240px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Tên, CCCD, phòng hoặc mã hồ sơ" class="h-11 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500">
        <select name="status" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
            <option value="">Tất cả người đang thuê</option>
            @foreach(['missing' => 'Chưa có/Cần cập nhật lại', 'active' => 'Đã cập nhật minh chứng', 'pending' => 'Chờ bổ sung', 'expired' => 'Giấy gần nhất đã hết hạn', 'cancelled' => 'Giấy gần nhất đã hủy'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white">Tìm kiếm</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Người thuê</th><th class="px-5 py-3">Phòng/Hợp đồng</th><th class="px-5 py-3">Giấy hiện tại</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Minh chứng</th><th class="px-5 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        @php($residence = $member->activeTemporaryResidence ?? $member->latestTemporaryResidence)
                        <tr>
                            <td class="px-5 py-4"><p class="font-bold text-slate-950">{{ $member->full_name }}</p><p class="mt-1 text-xs text-slate-500">CCCD {{ $member->identity_number ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $member->role_label }}</p></td>
                            <td class="px-5 py-4"><p class="font-semibold">{{ $member->contract?->room?->room_code ?? '—' }}</p><p class="text-xs text-slate-500">{{ $member->contract?->contract_code }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($residence)
                                    <p>{{ $residence->start_date?->format('d/m/Y') }}<span class="mx-1 text-slate-400">→</span>{{ $residence->end_date?->format('d/m/Y') ?? 'Không thời hạn' }}</p>
                                    @if($residence->reference_number)<p class="mt-1 text-xs font-semibold text-indigo-700">Mã {{ $residence->reference_number }}</p>@endif
                                @else
                                    <span class="text-slate-400">Chưa có giấy</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($member->activeTemporaryResidence)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $residence->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $residence->status_label }}</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $residence ? $residence->status_label : 'Chưa cập nhật' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">@if($residence?->evidenceExists())<a href="{{ route('admin.temporary_residences.evidence', $residence) }}" data-image-modal data-media-type="{{ $residence->evidenceIsPdf() ? 'pdf' : 'image' }}" data-image-title="Minh chứng giấy tạm trú {{ $residence->reference_number ?: '#'.$residence->id }}" class="font-semibold text-indigo-700">Xem tệp</a>@elseif($residence?->evidence_path)<span class="font-medium text-amber-700">Tệp không còn tồn tại</span>@else<span class="text-slate-400">Chưa có</span>@endif</td>
                            <td class="px-5 py-4 text-right">
                                @if($member->activeTemporaryResidence)
                                    <a href="{{ route('admin.temporary_residences.show', $residence) }}{{ $residence->evidence_path ? '' : '#evidence-upload' }}" class="font-semibold text-indigo-700">{{ $residence->evidence_path ? 'Chi tiết' : 'Bổ sung minh chứng' }}</a>
                                @else
                                    <a href="{{ route('admin.temporary_residences.create', ['member' => $member->id]) }}" class="inline-flex rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">Cập nhật giấy</a>
                                    @if($residence)<a href="{{ route('admin.temporary_residences.show', $residence) }}" class="ml-2 text-xs font-semibold text-slate-600">Lịch sử</a>@endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Không tìm thấy người đang thuê phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($members->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $members->links() }}</div>@endif
    </div>
</div>
@endsection
