<div class="col-lg-6">

    <div class="card shadow-sm border-0 h-100 contract-editor-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom">

            <label class="form-label fw-semibold mb-2">
                Mẫu hợp đồng
            </label>

            <select
                class="form-select"
                id="templateSelect"
                name="template_id">

                <option value="">
                    Soạn thảo mới
                </option>

                @foreach($templates ?? [] as $template)

                    <option value="{{ $template->id }}">
                        {{ $template->name }}
                    </option>

                @endforeach

            </select>

        </div>

        {{-- Body --}}
        <div class="card-body p-0 d-flex flex-column">

            {{-- Tiêu đề --}}
            <div class="px-3 py-2 border-bottom">

                <label class="form-label fw-semibold mb-0">
                    Nội dung hợp đồng
                </label>

            </div>

            {{-- Toolbar --}}
            <div class="editor-toolbar">

                <div class="btn-group">

                    <button
                        type="button"
                        class="btn btn-success active"
                        id="editorBtn">

                        Soạn thảo

                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-success"
                        id="previewBtn">

                        Xem trước

                    </button>

                </div>

            </div>

            {{-- CKEditor --}}
            <div id="editorWrapper" class="flex-grow-1">

                <textarea
                    id="contractEditor"
                    name="contract_content">

{!! old('contract_content', '

<h2 style="text-align:center">
HỢP ĐỒNG THUÊ PHÒNG
</h2>

<p>Hôm nay, chúng tôi gồm:</p>

<p><strong>Bên cho thuê:</strong></p>

<p><strong>Bên thuê:</strong></p>

<p><strong>Phòng:</strong> {{room}}</p>

<p><strong>Thời hạn:</strong> từ {{start_date}} đến {{end_date}}</p>

<p><strong>Giá thuê:</strong> {{price}} / tháng</p>

<p><strong>Tiền cọc:</strong> {{deposit}}</p>

<p>Các bên cam kết thực hiện đúng các điều khoản đã thỏa thuận.</p>

') !!}

                </textarea>

            </div>

            {{-- Preview --}}
            <div
                id="previewWrapper"
                class="flex-grow-1"
                style="display:none">

                <div
                    id="previewContent"
                    class="contract-preview">

                </div>

            </div>

        </div>

    </div>

</div>