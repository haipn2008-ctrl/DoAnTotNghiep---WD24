<script>
let editEditor = null;

document.addEventListener("DOMContentLoaded", function () {

    const editModal = document.getElementById("editContractModal");

    function initEditEditor(callback = null) {

        if (editEditor) {
            if (callback) callback();
            return;
        }

        const textarea = document.getElementById("editContractEditor");

        if (!textarea) return;

        ClassicEditor.create(textarea, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'underline', '|',
                'bulletedList', 'numberedList', '|',
                'insertTable', '|',
                'undo', 'redo'
            ]
        })
        .then(ed => {

            editEditor = ed;

            if(window.editContent){
                editEditor.setData(window.editContent);
            }

            console.log("Edit CKEditor Ready");

        })
        .catch(console.error);
    }

    // Khi mở modal
    if(editModal){

        editModal.addEventListener("shown.bs.modal",function(){

            initEditEditor();

            if(editEditor){
                editEditor.setData(window.editContent ?? "");
            }

        });

    }
    editModal.addEventListener("hidden.bs.modal", async function () {

        window.editContent = "";

        if (editEditor) {

            await editEditor.destroy();

            editEditor = null;

        }

        document.getElementById("editEditorWrapper").innerHTML = `
            <textarea
                id="editContractEditor"
                name="contract_content"></textarea>
        `;

    });

    // Preview
    const editEditorBtn = document.getElementById("editEditorBtn");
    const editPreviewBtn = document.getElementById("editPreviewBtn");

    const editEditorWrapper = document.getElementById("editEditorWrapper");
    const editPreviewWrapper = document.getElementById("editPreviewWrapper");
    const editPreviewContent = document.getElementById("editPreviewContent");

    if (editPreviewBtn) {

        editPreviewBtn.addEventListener("click", function () {

            if (!editEditor) {
                alert("CKEditor chưa sẵn sàng.");
                return;
            }

            editPreviewContent.innerHTML = editEditor.getData();

            editEditorWrapper.style.display = "none";
            editPreviewWrapper.style.display = "block";

            editEditorBtn.classList.remove("active");
            editPreviewBtn.classList.add("active");

        });

    }

    if (editEditorBtn) {

        editEditorBtn.addEventListener("click", function () {

            editPreviewWrapper.style.display = "none";
            editEditorWrapper.style.display = "block";

            editPreviewBtn.classList.remove("active");
            editEditorBtn.classList.add("active");

        });

    }

    // Submit
    const editForm = document.getElementById("editContractForm");

    if (editForm) {

        editForm.addEventListener("submit", function () {

            if (editEditor) {
                document.getElementById("editContractEditor").value = editEditor.getData();
            }

        });

    }

    // Click nút sửa
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".editContractBtn");

        if (!btn) return;

        document.getElementById("editContractForm").action =
            "/admin/contracts/" + btn.dataset.id;

        document.getElementById("editRoomSelect").value = btn.dataset.room;
        document.getElementById("editTenantSelect").value = btn.dataset.tenant;
        document.getElementById("editMonthlyRent").value = btn.dataset.rent;
        document.getElementById("editDeposit").value = btn.dataset.deposit;
        document.getElementById("editStartDate").value = btn.dataset.start;
        document.getElementById("editEndDate").value = btn.dataset.end;

        document.querySelector("#editContractForm textarea[name='note']").value =
            btn.dataset.note ?? "";

        window.editContent = btn.dataset.content ?? "";

        if (editEditor) {
            editEditor.setData(window.editContent);
        }

    });

});
</script>