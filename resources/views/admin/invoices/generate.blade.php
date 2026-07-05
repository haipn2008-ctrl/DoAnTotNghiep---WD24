@extends('layouts.admin.index')

@section('title', 'Sinh hóa đơn')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Sinh hóa đơn từ hợp đồng</h4>
            </div>
            <div class="col-sm-6">
                <form action="{{ route('admin.invoices.generate') }}" method="GET"
                    class="d-flex justify-content-end align-items-center">
                    <label class="me-2 mb-0">Kỳ hóa đơn:</label>
                    <select name="month" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>

        <div id="invoiceAlert" class="alert d-none" role="alert"></div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Hợp đồng đang hiệu lực - Tháng {{ $month }}/{{ $year }}</h5>
                        <span class="badge bg-primary">{{ $contracts->count() }} hợp đồng</span>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Phòng</th>
                                        <th>Khách thuê</th>
                                        <th>Hợp đồng</th>
                                        <th class="text-end">Tiền phòng</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contracts as $contract)
                                        @php
                                            $hasInvoice = in_array($contract->room_id, $issuedRoomIds);
                                        @endphp
                                        <tr id="contract-row-{{ $contract->id }}">
                                            <td class="fw-bold">
                                                {{ $contract->room->room_code ?? 'N/A' }}
                                                <br>
                                                <small class="text-muted">Số người: {{ $contract->number_of_people }}</small>
                                            </td>
                                            <td>
                                                {{ $contract->tenant->full_name ?? 'N/A' }}
                                                <br>
                                                <small class="text-muted">{{ $contract->tenant->phone ?? '' }}</small>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ $contract->contract_code }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format($contract->monthly_rent, 0, ',', '.') }} VND
                                            </td>
                                            <td class="text-center invoice-status">
                                                @if($hasInvoice)
                                                    <span class="badge bg-success">Đã có hóa đơn</span>
                                                @else
                                                    <span class="badge bg-warning">Chờ phát hành</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-primary btn-sm preview-invoice-btn"
                                                    data-contract-id="{{ $contract->id }}"
                                                    data-preview-url="{{ route('admin.invoices.preview', $contract) }}"
                                                    data-issue-url="{{ route('admin.invoices.issue', $contract) }}"
                                                    {{ $hasInvoice ? 'disabled' : '' }}>
                                                    <i class="mdi mdi-file-eye-outline me-1"></i>
                                                    Tạo hóa đơn tháng {{ $month }}
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                Không có hợp đồng active trong kỳ {{ $month }}/{{ $year }}.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Bản nháp hóa đơn</h5>
                        <small class="text-muted" id="previewMeta"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Phòng</small>
                            <span class="fw-bold" id="previewRoom"></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Khách thuê</small>
                            <span class="fw-bold" id="previewTenant"></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Hạn thanh toán</small>
                            <span class="fw-bold" id="previewDueDate"></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Khoản thu</th>
                                    <th class="text-center">Chỉ số cũ</th>
                                    <th class="text-center">Chỉ số mới</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="previewLines"></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Tổng cộng</th>
                                    <th class="text-end text-success fs-5" id="previewTotal"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success" id="issueInvoiceBtn">
                        <span class="default-label">
                            <i class="mdi mdi-check-circle-outline me-1"></i>
                            Xác nhận & Phát hành hóa đơn
                        </span>
                        <span class="loading-label d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Đang phát hành...
                        </span>
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
            const modal = new bootstrap.Modal(modalElement);
            const alertBox = document.getElementById('invoiceAlert');
            const issueButton = document.getElementById('issueInvoiceBtn');
            let currentIssueUrl = null;
            let currentContractId = null;

            const formatter = new Intl.NumberFormat('vi-VN');

            function showAlert(type, message) {
                alertBox.className = `alert alert-${type}`;
                alertBox.textContent = message;
                alertBox.classList.remove('d-none');
            }

            function money(value) {
                return `${formatter.format(Math.round(Number(value) || 0))} VND`;
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
                            <td>
                                <span class="fw-bold">${line.name}</span>
                                ${line.note ? `<br><small class="text-muted">${line.note}</small>` : ''}
                            </td>
                            <td class="text-center">${line.old_index ?? '-'}</td>
                            <td class="text-center">${line.new_index ?? '-'}</td>
                            <td class="text-center">
                                ${formatter.format(Number(line.quantity) || 0)} ${line.unit ?? ''}
                                ${indexText ? `<br><small class="text-muted">${indexText}</small>` : ''}
                            </td>
                            <td class="text-end">${money(line.unit_price)}</td>
                            <td class="text-end fw-bold">${money(line.amount)}</td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('previewLines').innerHTML = rows;
            }

            function setIssueLoading(isLoading) {
                issueButton.disabled = isLoading;
                issueButton.querySelector('.default-label').classList.toggle('d-none', isLoading);
                issueButton.querySelector('.loading-label').classList.toggle('d-none', !isLoading);
            }

            document.querySelectorAll('.preview-invoice-btn').forEach(function(button) {
                button.addEventListener('click', async function() {
                    alertBox.classList.add('d-none');
                    button.disabled = true;
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang tính...';

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
                        modal.show();
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

                    modal.hide();
                    showAlert('success', data.message);

                    const row = document.getElementById(`contract-row-${currentContractId}`);
                    if (row) {
                        row.querySelector('.invoice-status').innerHTML =
                            '<span class="badge bg-success">Đã có hóa đơn</span>';
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
