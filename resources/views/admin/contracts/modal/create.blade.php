<div class="modal fade"
     id="createContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-xl contract-modal">

        <div class="modal-content">

            @include('admin.contracts.partials.style')

            <div class="modal-header contract-header d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="mb-1 fw-bold text-white">

                        <i class="bi bi-file-earmark-plus me-2"></i>

                        Tạo hợp đồng mới

                    </h3>

                    <p class="mb-0 text-white-50">

                        Điền thông tin hợp đồng thuê phòng

                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="contract-body">

                <form
                    action="{{ route('admin.contracts.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    id="contractForm">

                    @csrf
                    <textarea
                        id="contractContent"
                        name="contract_content"
                        hidden
                    >@include('admin.contracts.partials.contract-template')</textarea>
                    <div class="row">

                        @include('admin.contracts.partials.form-left')

                    </div>

                    {{-- Xác nhận đã kiểm tra thông tin hợp đồng với bản cứng --}}
                    <div class="mt-4 p-3 border rounded bg-light">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="confirm_contract_accuracy"
                                id="confirmContractAccuracy"
                                value="1"
                                required
                                {{ old('confirm_contract_accuracy') ? 'checked' : '' }}
                            >
                            <label class="form-check-label fw-semibold" for="confirmContractAccuracy">
                                Tôi đã kiểm tra kỹ thông tin hợp đồng và xác nhận thông tin đã khớp với bản cứng.
                            </label>
                        </div>

                        @error('confirm_contract_accuracy')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>