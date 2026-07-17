<div class="modal fade"
     id="returnDepositModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog">

        <form
            id="returnDepositForm"
            method="POST"
            class="modal-content">

            @csrf

            <div class="modal-header bg-info text-white">

                <h5 class="modal-title">
                    <i class="bi bi-cash-coin me-2"></i>
                    Hoàn tiền cọc
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="alert alert-info">

                    Sau khi xác nhận:

                    <ul class="mb-0 mt-2">

                        <li>Tiền cọc sẽ được đánh dấu là <strong>Đã hoàn</strong>.</li>

                        <li>Không thể hoàn tác thao tác này.</li>

                    </ul>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Lý do hoàn cọc
                    </label>

                    <input
                        type="text"
                        name="return_reason"
                        class="form-control"
                        placeholder="Ví dụ: Khách trả phòng đúng hạn"
                        required>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Ghi chú
                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        name="return_note"></textarea>

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
                    class="btn btn-info">

                    <i class="bi bi-check-circle me-1"></i>

                    Xác nhận hoàn cọc

                </button>

            </div>

        </form>

    </div>

</div>