<div class="modal fade"
     id="editContractModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-xl contract-modal">

        <div class="modal-content">

            @include('admin.contracts.partials.style')

            <div class="modal-header contract-header d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="mb-1 fw-bold text-white">

                        <i class="bi bi-pencil-square me-2"></i>

                        Chỉnh sửa hợp đồng

                    </h3>

                    <p class="mb-0 text-white-50">

                        Điều chỉnh thông tin hợp đồng

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
                    id="editContractForm"
                    method="POST">

                    @csrf
                    @method('PUT')

                    <div class="row">

                        @include('admin.contracts.partials.edit-left')

                        @include('admin.contracts.partials.edit-right')

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>