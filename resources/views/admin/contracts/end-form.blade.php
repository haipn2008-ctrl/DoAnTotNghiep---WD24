@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h4 class="mb-0 fw-bold">
                <i class="fas fa-file-signature text-danger me-2"></i>
                Xác nhận kết thúc hợp đồng
            </h4>

        </div>

        <div class="card-body">

            <div class="alert alert-warning border-0 shadow-sm">

                <h5 class="fw-bold mb-3">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Lưu ý trước khi kết thúc hợp đồng
                </h5>

                <ul class="mb-0">

                    <li>Đảm bảo người thuê đã thanh toán đầy đủ tiền phòng và các khoản phát sinh.</li>

                    <li>Đã chốt chỉ số điện, nước và lập hóa đơn cuối cùng (nếu có).</li>

                    <li>Xác nhận người thuê đã bàn giao chìa khóa và tài sản trong phòng.</li>

                    <li>Sau khi xác nhận, hợp đồng sẽ chuyển sang trạng thái <strong>Đã kết thúc</strong>.</li>

                    <li>Phòng sẽ tự động chuyển sang trạng thái <strong>Trống</strong> và có thể cho thuê lại.</li>

                    <li>Thao tác này không thể hoàn tác.</li>

                </ul>

            </div>

            <div class="row">

                {{-- Thông tin hợp đồng --}}
                <div class="col-md-5">

                    <div class="card border-0 shadow-sm bg-light">

                        <div class="card-header bg-primary text-white">

                            <h5 class="mb-0">
                                Thông tin hợp đồng
                            </h5>

                        </div>

                        <div class="card-body">

                            <table class="table table-borderless mb-0">

                                <tr>
                                    <th width="40%">Mã hợp đồng</th>
                                    <td>{{ $contract->contract_code }}</td>
                                </tr>

                                <tr>
                                    <th>Người thuê</th>
                                    <td>{{ $contract->tenant->full_name }}</td>
                                </tr>

                                <tr>
                                    <th>Phòng</th>
                                    <td>{{ $contract->room->room_code }}</td>
                                </tr>

                                <tr>
                                    <th>Ngày bắt đầu</th>
                                    <td>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</td>
                                </tr>

                                <tr>
                                    <th>Ngày kết thúc</th>
                                    <td class="text-danger fw-bold">
                                        {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                                    </td>
                                </tr>

                            </table>

                        </div>

                    </div>

                </div>

                {{-- Form --}}
                <div class="col-md-7">

                    <form action="{{ route('admin.contracts.end',$contract->id) }}"
                          method="POST">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Ngày trả phòng thực tế
                            </label>

                            <input type="date"
                                   name="actual_end_date"
                                   class="form-control"
                                   value="{{ date('Y-m-d') }}"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label fw-bold">
                                <i class="fas fa-list me-1"></i>
                                Lý do kết thúc
                            </label>

                            <select name="termination_reason"
                                    class="form-select">

                                <option value="expired">
                                    📅 Hết hạn hợp đồng
                                </option>

                                <option value="early">
                                    🚪 Khách trả phòng trước hạn
                                </option>

                                <option value="violation">
                                    ⚠️ Vi phạm hợp đồng
                                </option>

                                <option value="other">
                                    📝 Lý do khác
                                </option>

                            </select>

                        </div>

                        <div class="mb-4">

                            <label class="form-label fw-bold">
                                <i class="fas fa-comment-alt me-1"></i>
                                Ghi chú
                            </label>

                            <textarea
                                name="termination_note"
                                rows="5"
                                class="form-control"
                                placeholder="Nhập ghi chú nếu có..."></textarea>

                        </div>
                        <div class="form-check mb-4">

                            <input class="form-check-input"
                                type="checkbox"
                                id="confirmEnd">

                            <label class="form-check-label fw-semibold" for="confirmEnd">

                                Tôi xác nhận người thuê đã hoàn tất việc trả phòng và đồng ý kết thúc hợp đồng.

                            </label>

                        </div>
                        <div class="d-flex justify-content-end">

                            <a href="{{ route('admin.contracts.end.list') }}"
                               class="btn btn-secondary me-2">

                                <i class="fas fa-arrow-left"></i>

                                Quay lại

                            </a>

                            <button type="submit"
                                id="btnEnd"
                                class="btn btn-danger"
                                disabled
                                onclick="return confirm('Bạn chắc chắn muốn kết thúc hợp đồng này?')">

                                <i class="fas fa-check-circle"></i>

                                Xác nhận kết thúc

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>
<script>

document.getElementById('confirmEnd').addEventListener('change', function () {

    document.getElementById('btnEnd').disabled = !this.checked;

});

</script>
@endsection