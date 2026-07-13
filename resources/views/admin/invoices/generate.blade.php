@extends('layouts.admin.index')

@section('title', 'Sinh hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Sinh hóa đơn')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hóa đơn</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Sinh hóa đơn từ hợp đồng</h2>
            </div>

            <form action="{{ route('admin.invoices.generate') }}" method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="month" class="mb-1.5 block text-sm font-semibold text-slate-700">Tháng</label>
                    <select id="month" name="month" onchange="this.form.submit()" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="year" class="mb-1.5 block text-sm font-semibold text-slate-700">Năm</label>
                    <select id="year" name="year" onchange="this.form.submit()" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" @selected($year == $y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </form>
        </div>

        <div id="invoiceAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-medium"></div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-950">Hợp đồng đang hiệu lực</h3>
                    <p class="text-sm text-slate-500">Kỳ hóa đơn tháng {{ $month }}/{{ $year }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200">
                    {{ $contracts->count() }} hợp đồng
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3">Khách thuê</th>
                            <th class="px-5 py-3">Hợp đồng</th>
                            <th class="px-5 py-3 text-right">Tiền phòng</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($contracts as $contract)
                            @php($hasInvoice = in_array($contract->room_id, $issuedRoomIds))
                            <tr id="contract-row-{{ $contract->id }}" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $contract->room->room_code ?? 'N/A' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Số người: {{ $contract->number_of_people }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-900">{{ $contract->tenant->full_name ?? 'N/A' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $contract->tenant->phone ?? '' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $contract->contract_code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}
                                        -
                                        {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">
                                    {{ number_format($contract->monthly_rent, 0, ',', '.') }}đ
                                </td>
                                <td class="invoice-status px-5 py-4">
                                    @if ($hasInvoice)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đã có hóa đơn</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Chờ phát hành</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        <button type="button"
                                            class="preview-invoice-btn inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                            data-contract-id="{{ $contract->id }}"
                                            data-preview-url="{{ route('admin.invoices.preview', $contract) }}"
                                            data-issue-url="{{ route('admin.invoices.issue', $contract) }}"
                                            {{ $hasInvoice ? 'disabled' : '' }}>
                                            <i class="bx bx-file-find text-lg"></i>
                                            Tạo hóa đơn tháng {{ $month }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                    Không có hợp đồng đang hiệu lực trong kỳ {{ $month }}/{{ $year }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="invoicePreviewModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-close-preview></div>
        <div class="relative z-10 flex min-h-full items-center justify-center p-4">
            <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Bản nháp hóa đơn</h3>
                        <p class="mt-1 text-sm text-slate-500" id="previewMeta"></p>
                    </div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" data-close-preview>
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <div class="max-h-[calc(90vh-140px)] overflow-y-auto p-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">Phòng</p>
                            <p class="mt-2 font-semibold text-slate-950" id="previewRoom"></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">Khách thuê</p>
                            <p class="mt-2 font-semibold text-slate-950" id="previewTenant"></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">Hạn thanh toán</p>
                            <p class="mt-2 font-semibold text-slate-950" id="previewDueDate"></p>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Khoản thu</th>
                                    <th class="px-4 py-3 text-center">Chỉ số cũ</th>
                                    <th class="px-4 py-3 text-center">Chỉ số mới</th>
                                    <th class="px-4 py-3 text-center">Số lượng</th>
                                    <th class="px-4 py-3 text-right">Đơn giá</th>
                                    <th class="px-4 py-3 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="previewLines" class="divide-y divide-slate-100"></tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <th colspan="5" class="px-4 py-3 text-right text-slate-700">Tổng cộng</th>
                                    <th class="px-4 py-3 text-right text-lg font-bold text-emerald-700" id="previewTotal"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-close-preview>Đóng</button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" id="issueInvoiceBtn">
                        <span class="default-label inline-flex items-center gap-2">
                            <i class="bx bx-check-circle text-lg"></i>
                            Xác nhận và phát hành
                        </span>
                        <span class="loading-label hidden">Đang phát hành...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const month = {{ (int) $month }};
            const year = {{ (int) $year }};
            const csrfToken = '{{ csrf_token() }}';
            const modalElement = document.getElementById('invoicePreviewModal');
            const alertBox = document.getElementById('invoiceAlert');
            const issueButton = document.getElementById('issueInvoiceBtn');
            let currentIssueUrl = null;
            let currentContractId = null;

            const formatter = new Intl.NumberFormat('vi-VN');

            function openModal() {
                modalElement.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modalElement.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function showAlert(type, message) {
                const styles = {
                    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    danger: 'border-rose-200 bg-rose-50 text-rose-700',
                    warning: 'border-amber-200 bg-amber-50 text-amber-700',
                };

                alertBox.className = `rounded-lg border px-4 py-3 text-sm font-medium ${styles[type] || styles.warning}`;
                alertBox.textContent = message;
                alertBox.classList.remove('hidden');
            }

            function money(value) {
                return `${formatter.format(Math.round(Number(value) || 0))}đ`;
            }

            function renderPreview(data) {
                document.getElementById('previewMeta').textContent =
                    `${data.contract_code} - Tháng ${data.month}/${data.year} - Ngày lập ${data.invoice_date}`;
                document.getElementById('previewRoom').textContent = data.room_code;
                document.getElementById('previewTenant').textContent = data.tenant_name;
                document.getElementById('previewDueDate').textContent = data.due_date;
                document.getElementById('previewTotal').textContent = money(data.total_amount);

                const rows = data.lines.map(function(line) {
                    const indexText = line.old_index !== null && line.new_index !== null
                        ? `${line.old_index} -> ${line.new_index}`
                        : '';

                    return `
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-950">${line.name}</span>
                                ${line.note ? `<p class="mt-1 text-xs text-slate-500">${line.note}</p>` : ''}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">${line.old_index ?? '-'}</td>
                            <td class="px-4 py-3 text-center text-slate-600">${line.new_index ?? '-'}</td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                ${formatter.format(Number(line.quantity) || 0)} ${line.unit ?? ''}
                                ${indexText ? `<p class="mt-1 text-xs text-slate-500">${indexText}</p>` : ''}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">${money(line.unit_price)}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-950">${money(line.amount)}</td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('previewLines').innerHTML = rows;
            }

            function setIssueLoading(isLoading) {
                issueButton.disabled = isLoading;
                issueButton.querySelector('.default-label').classList.toggle('hidden', isLoading);
                issueButton.querySelector('.loading-label').classList.toggle('hidden', !isLoading);
            }

            document.querySelectorAll('[data-close-preview]').forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('.preview-invoice-btn').forEach(function(button) {
                button.addEventListener('click', async function() {
                    alertBox.classList.add('hidden');
                    button.disabled = true;
                    const originalHtml = button.innerHTML;
                    button.innerHTML = 'Đang tính...';

                    try {
                        const url = `${button.dataset.previewUrl}?month=${month}&year=${year}`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Không thể tạo bản nháp hóa đơn.');
                        }

                        currentIssueUrl = button.dataset.issueUrl;
                        currentContractId = button.dataset.contractId;
                        renderPreview(data);
                        setIssueLoading(false);
                        openModal();
                    } catch (error) {
                        showAlert('danger', error.message);
                    } finally {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                });
            });

            issueButton.addEventListener('click', async function() {
                if (!currentIssueUrl) {
                    return;
                }

                setIssueLoading(true);

                try {
                    const response = await fetch(currentIssueUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ month, year }),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Phát hành hóa đơn thất bại.');
                    }

                    closeModal();
                    showAlert('success', data.message);

                    const row = document.getElementById(`contract-row-${currentContractId}`);
                    if (row) {
                        row.querySelector('.invoice-status').innerHTML =
                            '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đã có hóa đơn</span>';
                        row.querySelector('.preview-invoice-btn').disabled = true;
                    }
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    setIssueLoading(false);
                }
            });
        });
    </script>
@endpush
