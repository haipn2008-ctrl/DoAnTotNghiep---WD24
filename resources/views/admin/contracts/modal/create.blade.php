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

                    <div class="row">

                        @include('admin.contracts.partials.form-left')

                        @include('admin.contracts.partials.editor-right')

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>