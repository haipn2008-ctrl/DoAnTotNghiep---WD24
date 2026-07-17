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
            id="roomSelect"
            name="room_id"
            class="form-select"
            required>

            <option value="">
                Chọn phòng...
            </option>

            @foreach($rooms as $room)

            <option
                value="{{ $room->id }}"
                data-price="{{ $room->price }}"
                data-status="{{ $room->status }}">

                {{ $room->room_code }}

                @if($room->status=='occupied')
                    🔴 Đang thuê
                @elseif($room->status=='maintenance')
                    🟡 Bảo trì
                @endif

            </option>

            @endforeach

        </select>

        <small
            id="roomWarning"
            class="text-danger fw-bold mt-2 d-none">
        </small>

    </div>

    {{-- Khách thuê --}}
    <div class="col-md-6 mb-4">

        <label class="form-label">
            Khách thuê
            <span class="text-danger">*</span>
        </label>

        <select
            id="tenantSelect"
            name="tenant_id"
            class="form-select"
            required>

            <option value="">
                Chọn khách thuê...
            </option>

            @foreach($tenants as $tenant)

                <option
                    value="{{ $tenant->id }}"
                    data-name="{{ $tenant->name }}">

                    {{ $tenant->name }}

                </option>

            @endforeach

        </select>

    </div>

</div>
<div class="mb-4">

<label class="form-label">

Thời hạn hợp đồng

</label>

<div class="duration-group">

<label>

<input
type="radio"
name="duration"
value="3">

3 tháng

</label>

<label>

<input
type="radio"
name="duration"
value="6">

6 tháng

</label>

<label>

<input
type="radio"
name="duration"
value="9">

9 tháng

</label>

<label>

<input
type="radio"
name="duration"
value="12"
checked>

12 tháng

</label>

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
id="startDate"
class="form-control"
required>

</div>

<div class="col-md-6 mb-4">

<label class="form-label">

Ngày kết thúc

</label>

<input
type="date"
name="end_date"
id="endDate"
class="form-control"
readonly>

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
id="monthlyRent"
class="form-control"
readonly>

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
id="deposit"
class="form-control">

<span class="input-group-text">

VNĐ

</span>

</div>

</div>

</div>
{{-- =========================
    GHI CHÚ
========================= --}}

<div class="mb-4">

    <label class="form-label">

        Ghi chú

    </label>

    <textarea

        class="form-control"

        rows="4"

        name="note"

        placeholder="Nhập ghi chú nếu có..."></textarea>

</div>
{{-- =========================
    UPLOAD ẢNH
========================= --}}

<div class="mb-4">

<label class="form-label">

Hình ảnh hợp đồng

</label>

<div

class="upload-box"

id="uploadBox">

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

id="contractImage"

name="contract_image">

<img

id="previewImage"

src=""

alt="preview">

</div>

</div>
<div class="d-flex

justify-content-end

gap-3

mt-4">

<button

type="button"

class="btn btn-light btn-cancel"

data-bs-dismiss="modal">

Hủy

</button>

<button
    id="submitContract"
    type="submit"
    class="btn btn-success btn-save">

    <i class="bi bi-file-earmark-plus"></i>

    Tạo hợp đồng

</button>

</div>

</div>

</div>

</div>
