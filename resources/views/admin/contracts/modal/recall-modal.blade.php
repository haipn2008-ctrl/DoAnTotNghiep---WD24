<div class="modal fade"
     id="recallContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <form id="recallContractForm"
                  method="POST">

                @csrf

                {{-- HEADER --}}
                <div class="modal-header border-0 bg-danger text-white px-4 py-3">

                    <div>
                        <h5 class="modal-title fw-bold mb-1">

                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Thu hồi hợp đồng

                        </h5>

                        <small class="text-white-50">
                            Hợp đồng
                            <strong id="recallContractCode"></strong>
                        </small>
                    </div>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                {{-- BODY --}}
                <div class="modal-body p-4">

                    <div class="alert alert-warning border-0 rounded-3 mb-4">

                        <div class="d-flex">

                            <i class="bi bi-exclamation-triangle-fill
                                      fs-4 me-3 text-warning"></i>

                            <div>

                                <div class="fw-bold mb-1">
                                    Hợp đồng sẽ được chuyển về Bản nháp
                                </div>

                                <small>
                                    Khách thuê sẽ tạm thời không thể ký
                                    hợp đồng. Sau khi chỉnh sửa, bạn cần
                                    gửi hợp đồng cho khách thuê ký lại.
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="mb-2">

                        <label for="recallReason"
                               class="form-label fw-semibold">

                            Lý do thu hồi
                            <span class="text-danger">*</span>

                        </label>

                        <textarea
                            class="form-control"
                            id="recallReason"
                            name="reason"
                            rows="4"
                            maxlength="500"
                            required
                            placeholder="Ví dụ: Sai thông tin khách thuê, cần điều chỉnh giá thuê..."></textarea>

                        <div class="d-flex justify-content-between mt-2">

                            <small id="recallReasonError"
                                   class="text-danger d-none">

                                Lý do phải có ít nhất 5 ký tự.

                            </small>

                            <small id="recallReasonCount"
                                   class="text-muted ms-auto">
                                0/500
                            </small>

                        </div>

                    </div>

                </div>


                {{-- FOOTER --}}
                <div class="modal-footer bg-light border-0 px-4 py-3">

                    <button type="button"
                            class="btn btn-light border"
                            data-bs-dismiss="modal">

                        <i class="bi bi-x-circle me-1"></i>
                        Hủy

                    </button>

                    <button type="submit"
                            id="btnSubmitRecall"
                            class="btn btn-danger"
                            disabled>

                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Xác nhận thu hồi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>