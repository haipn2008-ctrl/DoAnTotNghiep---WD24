<div class="modal fade"
     id="extendContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <form
            id="extendContractForm"
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
                            id="extend_contract_code"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Phòng
                        </label>

                        <input
                            id="extend_room"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Khách thuê
                        </label>

                        <input
                            id="extend_tenant"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Ngày kết thúc hiện tại
                        </label>

                        <input
                            id="extend_current_end_date"
                            class="form-control"
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

                    <div class="col-md-12 mt-3">
                        <label class="form-check">
                            <input type="checkbox" name="confirm_extend" value="1" required class="form-check-input">
                            <span class="form-check-label fw-semibold">Xác nhận thông tin dùng để lập phụ lục gia hạn</span>
                        </label>
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

                    Lập phụ lục gia hạn

                </button>

            </div>

        </form>

    </div>

</div>
