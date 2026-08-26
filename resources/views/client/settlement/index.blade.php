@extends('layouts.client.index')

@section('title', 'Quyết toán | Cổng khách thuê')
@section('page_title', 'Quyết toán')

@section('content')
    <div class="space-y-6">
        <section class="rounded-xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <p class="text-sm font-semibold text-violet-700">Cổng quyết toán sau trả phòng</p>
                    <h2 class="mt-1 text-2xl font-bold text-violet-950">Theo dõi toàn bộ nghĩa vụ tài chính tại một nơi</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-violet-800">
                        Xem bảng quyết toán, thanh toán khoản còn thiếu, cung cấp thông tin nhận tiền hoàn và tải chứng từ của từng hợp đồng.
                    </p>
                </div>
                <a href="{{ route('client.support.index') }}"
                   class="inline-flex h-11 items-center justify-center rounded-lg bg-violet-700 px-4 text-sm font-semibold text-white hover:bg-violet-800">
                    Yêu cầu hỗ trợ quyết toán
                </a>
            </div>
        </section>

        @forelse($contracts as $contract)
            @php
                $statement = $contract->settlementStatement;
                $netAmount = (float) ($statement?->net_amount ?? 0);
                $openInvoices = $contract->invoices->filter(fn ($invoice) => in_array($invoice->status, ['unpaid', 'partial'], true));
                $remainingDebt = $openInvoices->sum(fn ($invoice) => max(0, $invoice->payable_amount - (float) ($invoice->paid_amount ?? 0)));
                $isCompleted = $contract->status === \App\Models\Contract::STATUS_COMPLETED;
            @endphp

            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-col justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold text-slate-950">{{ $contract->contract_code }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isCompleted ? 'bg-emerald-50 text-emerald-700' : 'bg-violet-50 text-violet-700' }}">
                                {{ $isCompleted ? 'Đã hoàn tất' : 'Đang quyết toán' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Phòng {{ $contract->room->room_code ?? '-' }}
                            @if($contract->actual_move_out_at)
                                · Trả phòng ngày {{ $contract->actual_move_out_at->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('client.contracts.show', $contract) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">
                        Xem chi tiết và biên bản →
                    </a>
                </header>

                @if($statement)
                    <div class="grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="bg-white p-5"><p class="text-xs font-medium text-slate-500">Phí cuối kỳ</p><p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $statement->final_charge_amount, 0, ',', '.') }}đ</p></div>
                        <div class="bg-white p-5"><p class="text-xs font-medium text-slate-500">Công nợ trước bù cọc</p><p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $statement->previous_outstanding_amount, 0, ',', '.') }}đ</p></div>
                        <div class="bg-white p-5"><p class="text-xs font-medium text-slate-500">Tiền cọc ghi nhận</p><p class="mt-2 text-xl font-bold text-indigo-700">{{ number_format((float) $statement->deposit_credit, 0, ',', '.') }}đ</p></div>
                        <div class="bg-white p-5">
                            <p class="text-xs font-medium text-slate-500">Kết quả quyết toán</p>
                            <p class="mt-2 text-xl font-bold {{ $netAmount > 0 ? 'text-rose-700' : ($netAmount < 0 ? 'text-emerald-700' : 'text-slate-950') }}">
                                {{ number_format(abs($netAmount), 0, ',', '.') }}đ
                            </p>
                            <p class="mt-1 text-xs font-semibold {{ $netAmount > 0 ? 'text-rose-600' : ($netAmount < 0 ? 'text-emerald-600' : 'text-slate-500') }}">
                                {{ $netAmount > 0 ? 'Cần thanh toán' : ($netAmount < 0 ? 'Sẽ được hoàn' : 'Đã cân bằng') }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4 p-5">
                        @if($isCompleted)
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                Quyết toán đã hoàn tất. Bạn vẫn có thể xem hợp đồng, hóa đơn và chứng từ đã phát hành.
                            </div>
                        @elseif($remainingDebt > 0)
                            <div class="flex flex-col justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center">
                                <div><p class="font-semibold text-rose-900">Còn {{ number_format($remainingDebt, 0, ',', '.') }}đ cần thanh toán</p><p class="mt-1 text-sm text-rose-700">Chọn hóa đơn để gửi xác nhận và chứng từ thanh toán.</p></div>
                                <a href="{{ route('client.invoices.index', ['status' => 'unpaid']) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-rose-700 px-4 text-sm font-semibold text-white">Thanh toán hóa đơn</a>
                            </div>
                        @elseif($netAmount < 0 || (float) $contract->deposit_refund_amount > 0)
                            <div class="flex flex-col justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center">
                                <div><p class="font-semibold text-emerald-900">Có khoản tiền được hoàn</p><p class="mt-1 text-sm text-emerald-700">Cung cấp tài khoản nhận tiền hoặc theo dõi chứng từ chuyển khoản.</p></div>
                                <a href="{{ route('client.deposit-refunds.index', $contract) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white">Thông tin nhận tiền</a>
                            </div>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                Không còn khoản phải thanh toán. Ban quản lý đang kiểm tra chứng từ để hoàn tất quyết toán.
                            </div>
                        @endif

                        @if($openInvoices->isNotEmpty())
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Hóa đơn còn mở</h4>
                                <div class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200">
                                    @foreach($openInvoices as $invoice)
                                        <a href="{{ route('client.invoices.show', $invoice) }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-slate-50">
                                            <span class="font-medium text-slate-700">{{ $invoice->invoice_code }}</span>
                                            <span class="font-bold text-rose-700">{{ number_format(max(0, $invoice->payable_amount - (float) ($invoice->paid_amount ?? 0)), 0, ',', '.') }}đ</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-5">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Ban quản lý đang lập bảng quyết toán cho hợp đồng này. Bạn sẽ nhận được thông báo khi số liệu hoàn tất.
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <section class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="font-semibold text-slate-950">Chưa có hợp đồng cần quyết toán</h3>
                <p class="mt-2 text-sm text-slate-500">Các hợp đồng đã trả phòng hoặc đã hoàn tất sẽ xuất hiện tại đây.</p>
                <a href="{{ route('client.contracts.index') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-700">Xem lịch sử hợp đồng →</a>
            </section>
        @endforelse
    </div>
@endsection
