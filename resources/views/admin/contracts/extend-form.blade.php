@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h4 class="mb-0 fw-bold">
                <i class="fas fa-calendar-plus text-primary me-2"></i>
                Xác nhận gia hạn hợp đồng
            </h4>

        </div>

        <div class="card-body">

            {{-- Lưu ý --}}
            <div class="alert alert-info border-0 shadow-sm">

                <h5 class="fw-bold mb-3">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Lưu ý khi gia hạn hợp đồng
                </h5>

                <ul class="mb-0">

                    <li>Đảm bảo người thuê có nhu cầu tiếp tục thuê phòng.</li>

                    <li>Kiểm tra người thuê đã thanh toán đầy đủ các khoản còn nợ.</li>

                    <li>Ngày kết thúc mới phải lớn hơn ngày kết thúc hiện tại.</li>

                    <li>Sau khi xác nhận, thời hạn hợp đồng sẽ được cập nhật.</li>

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
                                    <th width="45%">Mã hợp đồng</th>
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
                                    <th>Ngày kết thúc hiện tại</th>
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

                    <form action="{{ route('admin.contracts.extend',$contract->id) }}"
                          method="POST">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label fw-bold">

                                <i class="fas fa-calendar-alt me-1"></i>

                                Ngày kết thúc mới

                            </label>

                            <input type="date"
                                   name="new_end_date"
                                   class="form-control"
                                   value="{{ \Carbon\Carbon::parse($contract->end_date)->addMonth()->format('Y-m-d') }}"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label fw-bold">

                                <i class="fas fa-list me-1"></i>

                                Lý do gia hạn

                            </label>

                            <select name="extend_reason"
                                    class="form-select">

                                <option value="tenant_request">
                                    Người thuê có nhu cầu tiếp tục thuê
                                </option>

                                <option value="renew_contract">
                                    Gia hạn theo hợp đồng
                                </option>

                                <option value="agreement">
                                    Hai bên thỏa thuận
                                </option>

                                <option value="other">
                                    Khác
                                </option>

                            </select>

                        </div>

                        <div class="mb-4">

                            <label class="form-label fw-bold">

                                <i class="fas fa-comment-alt me-1"></i>

                                Ghi chú

                            </label>

                            <textarea
                                name="extend_note"
                                rows="5"
                                class="form-control"
                                placeholder="Nhập ghi chú..."></textarea>

                        </div>

                        <div class="form-check mb-4">

                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirmExtend">

                            <label class="form-check-label fw-semibold"
                                   for="confirmExtend">

                                Tôi xác nhận hai bên đã thống nhất gia hạn hợp đồng.

                            </label>

                        </div>

                        <div class="d-flex justify-content-end">

                            <a href="{{ route('admin.contracts.extend.list') }}"
                               class="btn btn-secondary me-2">

                                <i class="fas fa-arrow-left"></i>

                                Quay lại

                            </a>

                            <button type="submit"
                                    id="btnExtend"
                                    class="btn btn-primary"
                                    disabled>

                                <i class="fas fa-check-circle"></i>

                                Xác nhận gia hạn

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

document.getElementById('confirmExtend').addEventListener('change', function () {

    document.getElementById('btnExtend').disabled = !this.checked;

});

</script>

@endsection