<div class="modal fade"
     id="extendContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <form action="{{ route('admin.contracts.extend',$contract) }}"
              method="POST"
              class="modal-content border-0 shadow">

            @csrf

            <div class="modal-header bg-warning text-dark">

                <h4 class="fw-bold mb-0">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Gia hạn hợp đồng
                </h4>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Mã hợp đồng
                        </label>

                        <input
                            class="form-control"
                            value="{{ $contract->contract_code }}"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Phòng
                        </label>

                        <input
                            class="form-control"
                            value="{{ $contract->room->room_code }}"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Khách thuê
                        </label>

                        <input
                            class="form-control"
                            value="{{ $contract->tenant->full_name }}"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Ngày kết thúc hiện tại
                        </label>

                        <input
                            class="form-control"
                            value="{{ optional($contract->end_date)->format('d/m/Y') }}"
                            readonly>

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label fw-bold">
                            Ngày kết thúc mới
                        </label>

                        <input
                            type="date"
                            name="new_end_date"
                            id="new_end_date"
                            class="form-control"
                            min="{{ optional($contract->end_date)->format('Y-m-d') }}"
                            required>

                        <small
                            id="extendError"
                            class="text-danger d-none">

                            Ngày mới phải lớn hơn ngày kết thúc hiện tại.

                        </small>

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Lý do gia hạn
                        </label>

                        <input
                            type="text"
                            name="extend_reason"
                            class="form-control"
                            placeholder="Ví dụ: Gia hạn thêm 12 tháng"
                            required>

                    </div>

                    <div class="col-md-12">

                        <label class="form-label">
                            Ghi chú
                        </label>

                        <textarea
                            class="form-control"
                            rows="4"
                            name="extend_note"></textarea>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">

                    Hủy

                </button>

                <button
                    type="submit"
                    class="btn btn-warning"
                    id="btnSubmitExtend">

                    <i class="bi bi-check-circle me-1"></i>

                    Gia hạn hợp đồng

                </button>

            </div>

        </form>

    </div>

</div>