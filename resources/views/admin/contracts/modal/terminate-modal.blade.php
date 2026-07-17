<div class="modal fade"
     id="terminateContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog">

        <form
            id="terminateContractForm"
            method="POST"
            class="modal-content">

            @csrf

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title">
                    <i class="bi bi-slash-circle me-2"></i>
                    Kết thúc hợp đồng
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="alert alert-warning">

                    <strong>Chú ý!</strong>

                    <ul class="mb-0 mt-2">
                        <li>Trạng thái hợp đồng sẽ chuyển sang <b>Đã kết thúc</b>.</li>
                        <li>Phòng sẽ chuyển về <b>Trống</b>.</li>
                        <li>Số người trong phòng sẽ về <b>0</b>.</li>
                    </ul>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Ngày trả phòng
                    </label>

                    <input
                        type="date"
                        name="actual_end_date"
                        id="actual_end_date"
                        class="form-control"
                        required>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Lý do kết thúc
                    </label>

                    <input
                        type="text"
                        name="termination_reason"
                        class="form-control"
                        required>

                </div>

                <div>

                    <label class="form-label">
                        Ghi chú
                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        name="termination_note"></textarea>

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
                    class="btn btn-danger"
                    type="submit">

                    Xác nhận kết thúc

                </button>

            </div>

        </form>

    </div>

</div>