<div class="col-lg-6">

    <div class="card h-100">

        <div class="card-body">

            <div class="row">

                {{-- Phòng --}}
                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Phòng <span class="text-danger">*</span>
                    </label>

                    <select
                        id="editRoomSelect"
                        class="form-select"
                        disabled>

                        @foreach($rooms as $room)

                            <option
                                value="{{ $room->id }}"
                                data-price="{{ $room->price }}"
                                data-room="{{ $room->room_code }}">

                                {{ $room->room_code }}

                            </option>

                        @endforeach

                    </select>

                    <input
                        type="hidden"
                        id="editRoomId"
                        name="room_id">

                </div>

                {{-- Khách thuê --}}
                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Khách thuê <span class="text-danger">*</span>
                    </label>

                    <select
                        id="editTenantSelect"
                        class="form-select"
                        disabled>

                        @foreach($tenants as $tenant)

                            <option value="{{ $tenant->id }}">
                                {{ $tenant->name }}
                            </option>

                        @endforeach

                    </select>

                    <input
                        type="hidden"
                        id="editTenantId"
                        name="tenant_id">

                </div>

            </div>

            {{-- Thời hạn --}}
            <div class="mb-4">

                <label class="form-label">
                    Thời hạn hợp đồng
                </label>

                <div class="duration-group">

                    @foreach([3,6,9,12] as $month)

                        <label>

                            <input
                                type="radio"
                                name="duration"
                                value="{{ $month }}"
                                class="durationRadio">

                            {{ $month }} tháng

                        </label>

                    @endforeach

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Ngày bắt đầu
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        id="editStartDate"
                        class="form-control"
                        value=""
                        required>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Ngày kết thúc
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        id="editEndDate"
                        class="form-control"
                        value=""
                        required>

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Giá thuê
                    </label>

                    <div class="input-group">

                        <input
                            type="number"
                            name="monthly_rent"
                            id="editMonthlyRent"
                            class="form-control"
                            value=""
                            required>

                        <span class="input-group-text">
                            VNĐ
                        </span>

                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="form-label">
                        Tiền cọc
                    </label>

                    <div class="input-group">

                        <input
                            type="number"
                            name="deposit_amount"
                            id="editDeposit"
                            class="form-control"
                            value=""
                            required>

                        <span class="input-group-text">
                            VNĐ
                        </span>

                    </div>

                </div>

            </div>

            {{-- Ghi chú --}}
            <div class="mb-4">

                <label class="form-label">
                    Ghi chú
                </label>

                <textarea
                    class="form-control"
                    rows="4"
                    name="note"></textarea>

            </div>

            {{-- Upload --}}
            <div class="mb-4">

                <label class="form-label">
                    Hình ảnh hợp đồng
                </label>

                <div
                    class="upload-box"
                    id="editUploadBox">

                    <i class="bi bi-cloud-arrow-up"></i>

                    <h6 class="mt-3">
                        Nhấn hoặc kéo ảnh vào đây
                    </h6>

                    <small class="text-muted">
                        PNG • JPG • JPEG
                    </small>

                    <input
                        hidden
                        type="file"
                        accept="image/*"
                        id="editContractImage"
                        name="contract_image">

                    <img
                        id="editPreviewImage"
                        src=""
                        style="display:none"
                        alt="preview">

                </div>

            </div>

            {{-- Lý do chỉnh sửa --}}
            <div class="mb-4">

                <label class="form-label">
                    Lý do chỉnh sửa
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    rows="4"
                    class="form-control"
                    name="reason"
                    required
                    placeholder="Nhập lý do chỉnh sửa..."></textarea>

            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Hủy

                </button>

                <button
                    type="submit"
                    class="btn btn-success btn-save">

                    <i class="bi bi-check-circle"></i>

                    Lưu thay đổi

                </button>

            </div>

        </div>

    </div>

</div>