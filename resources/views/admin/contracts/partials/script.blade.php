<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
// =========================
// Lấy giá phòng
// =========================
const roomSelect = document.getElementById("roomSelect");
const monthlyRent = document.getElementById("monthlyRent");

const roomWarning = document.getElementById("roomWarning");
const submitBtn = document.getElementById("submitContract");

if (roomSelect && monthlyRent) {

    roomSelect.addEventListener("change", function () {

        const option = this.options[this.selectedIndex];

        monthlyRent.value = option.dataset.price || "";

        const status = option.dataset.status;

        if (status === "occupied") {

            roomWarning.classList.remove("d-none");
            roomWarning.innerHTML = "⚠️ Phòng này đang có người thuê.";

            submitBtn.disabled = true;

        }
        else if (status === "maintenance") {

            roomWarning.classList.remove("d-none");
            roomWarning.innerHTML = "⚠️ Phòng này đang bảo trì.";

            submitBtn.disabled = true;

        }
        else {

            roomWarning.classList.add("d-none");
            roomWarning.innerHTML = "";

            submitBtn.disabled = false;

        }

    });

}

// =========================
// Tính ngày kết thúc
// =========================
const startDate = document.getElementById("startDate");
const endDate = document.getElementById("endDate");
const radios = document.querySelectorAll("input[name='duration']");

function calculateEndDate() {

    if (!startDate || !endDate) return;

    if (!startDate.value) return;

    const checked = document.querySelector("input[name='duration']:checked");

    if (!checked) return;

    const d = new Date(startDate.value);

    d.setMonth(d.getMonth() + parseInt(checked.value));

    endDate.value = d.toISOString().split("T")[0];

}

if (startDate) {

    startDate.addEventListener("change", calculateEndDate);

}

radios.forEach(r => {

    r.addEventListener("change", calculateEndDate);

});

// =========================
// Upload ảnh
// =========================
const uploadBox = document.getElementById("uploadBox");
const contractImage = document.getElementById("contractImage");
const previewImage = document.getElementById("previewImage");

if (uploadBox && contractImage) {

    uploadBox.addEventListener("click", function () {

        contractImage.click();

    });

    contractImage.addEventListener("change", function () {

        if (!this.files.length) return;

        const reader = new FileReader();

        reader.onload = function (e) {

            previewImage.src = e.target.result;

            previewImage.style.display = "block";

        };

        reader.readAsDataURL(this.files[0]);

    });

}
let editor = null;

document.addEventListener("DOMContentLoaded", function () {

    function initEditor() {
        console.count("CREATE");
        if (editor) return;

        const textarea = document.getElementById("contractEditor");
        if (!textarea) {
            console.error("Không tìm thấy #contractEditor");
            return;
        }

        ClassicEditor.create(textarea,{
            toolbar: [
                'heading',
                '|',
                'bold',
                'italic',
                '|',
                'bulletedList',
                'numberedList',
                '|',
                'undo',
                'redo'
            ]
        }).then(ed=>{
            editor = ed;
            console.log("CKEditor Ready");
        }).catch(error => {
            console.error("CKEditor Error:", error);
            console.log("ClassicEditor:", ClassicEditor);
        });
    }

    const createModal = document.getElementById("createContractModal");

    if (createModal) {

        createModal.addEventListener("shown.bs.modal", async function () {

            await initEditor();

        });

        createModal.addEventListener("hidden.bs.modal", async function () {

            if (editor) {

                await editor.destroy();

                editor = null;

            }

            document.getElementById("contractForm").reset();

            document.getElementById("editorWrapper").innerHTML = `
                <textarea id="contractEditor" name="contract_content"></textarea>
            `;

        });

    }

    const contractForm=document.getElementById("contractForm");
    if(contractForm){
        contractForm.addEventListener("submit",function(){
            if(editor){
                document.getElementById("contractEditor").value=editor.getData();
            }
        });
    }

    const editorBtn=document.getElementById("editorBtn");
    const previewBtn=document.getElementById("previewBtn");
    const editorWrapper=document.getElementById("editorWrapper");
    const previewWrapper=document.getElementById("previewWrapper");
    const previewContent=document.getElementById("previewContent");

    if(previewBtn){
        previewBtn.addEventListener("click",function(){
            if(!editor) return;
            editorWrapper.style.display="none";
            previewWrapper.style.display="block";
            previewContent.innerHTML=editor.getData();
            editorBtn.classList.remove("active");
            previewBtn.classList.add("active");
        });
    }

    if(editorBtn){
        editorBtn.addEventListener("click",function(){
            previewWrapper.style.display="none";
            editorWrapper.style.display="block";
            previewBtn.classList.remove("active");
            editorBtn.classList.add("active");
        });
    }
});
document.addEventListener("DOMContentLoaded",function(){

    $(document).on("click",".btn-view-contract",function(){

        let url=$(this).data("url");

        $("#contractModalContent").html(`
            <div class="text-center p-5">

                <div class="spinner-border text-primary"></div>

            </div>
        `);

        $("#contractModal").modal("show");

        $("#contractModalContent").load(url,function(response,status){

            if(status==="error"){

                $("#contractModalContent").html(`

                    <div class="alert alert-danger">

                        Không tải được hợp đồng.

                    </div>

                `);

            }

        });

    });

    // =========================
// Validate ngày gia hạn
// =========================

document.addEventListener("DOMContentLoaded", function () {

    const extendModal = document.getElementById("extendContractModal");

    if (!extendModal) return;

    extendModal.addEventListener("shown.bs.modal", function () {

        const endDateInput = document.getElementById("new_end_date");
        const submitBtn = document.getElementById("btnSubmitExtend");
        const error = document.getElementById("extendError");

        if (!endDateInput) return;

        // Lấy ngày kết thúc hiện tại từ Blade
        const currentEndDate = "{{ optional($contract->end_date)->format('Y-m-d') }}";

        // Không cho chọn ngày nhỏ hơn ngày hiện tại
        endDateInput.min = currentEndDate;

        function validateExtendDate() {

            if (!endDateInput.value) {

                submitBtn.disabled = true;
                error.classList.add("d-none");
                return;

            }

            if (endDateInput.value <= currentEndDate) {

                submitBtn.disabled = true;

                error.classList.remove("d-none");

            } else {

                submitBtn.disabled = false;

                error.classList.add("d-none");

            }

        }

        endDateInput.addEventListener("change", validateExtendDate);

        validateExtendDate();

    });

});

// =========================
// Hoàn tiền cọc
// =========================

$(document).on('click', '.returnDepositBtn', function () {

    let id = $(this).data('id');

    $('#returnDepositForm').attr(
        'action',
        '/admin/contracts/' + id + '/return-deposit'
    );

});

// =========================
// Kết thúc hợp đồng
// =========================

$(document).on('click', '.terminateBtn', function () {

    let id = $(this).data('id');

    $('#terminateContractForm').attr(
        'action',
        '/admin/contracts/' + id + '/terminate'
    );

    $('#actual_end_date').val($(this).data('end'));

});

});
</script>
