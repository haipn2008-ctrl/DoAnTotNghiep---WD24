<div class="col-lg-6">

    <div class="card shadow-sm border-0 h-100 contract-editor-card">

        <div class="card-header bg-white border-bottom">

            <label class="form-label fw-semibold mb-2">
                Mẫu hợp đồng
            </label>

            <select class="form-select"
                    id="editTemplateSelect"
                    name="template_id">

                <option value="">Giữ mẫu hiện tại</option>

                @foreach($templates ?? [] as $template)
                    <option value="{{ $template->id }}"
                        {{ (isset($contract) && $contract->template_id == $template->id) ? 'selected' : '' }}>
                        {{ $template->name }}
                    </option>
                @endforeach

            </select>

        </div>

        <div class="card-body p-0 d-flex flex-column">

            <div class="px-3 py-2 border-bottom">
                <label class="form-label fw-semibold mb-0">
                    Nội dung hợp đồng
                </label>
            </div>

            <div class="editor-toolbar">

                <div class="btn-group">

                    <button type="button"
                            class="btn btn-success active"
                            id="editEditorBtn">
                        Soạn thảo
                    </button>

                    <button type="button"
                            class="btn btn-outline-success"
                            id="editPreviewBtn">
                        Xem trước
                    </button>

                </div>

            </div>

            <div id="editEditorWrapper" class="flex-grow-1">

                <textarea id="editContractEditor"
                          name="contract_content">{!! old('contract_content', $contract->contract_content ?? '') !!}</textarea>

            </div>

            <div id="editPreviewWrapper"
                 class="flex-grow-1"
                 style="display:none;">

                <div id="editPreviewContent"
                     class="contract-preview">
                </div>

            </div>

        </div>

    </div>

</div>
