<script>
// =========================
// Lấy giá phòng + tiền cọc
// =========================
const roomSelect = document.getElementById("roomSelect");
const monthlyRent = document.getElementById("monthlyRent");
const deposit = document.getElementById("deposit");

const roomWarning = document.getElementById("roomWarning");
const submitBtn = document.getElementById("submitContract");

if (roomSelect) {

    function updateRoomInfo() {

        const option = roomSelect.options[roomSelect.selectedIndex];

        if (!option) return;

        const price = option.dataset.price || "";
        const status = option.dataset.status;

        // Giá thuê = giá phòng
        if (monthlyRent) {
            monthlyRent.value = price;
        }

        // Tiền cọc mặc định = đúng giá thuê
        if (deposit) {
            deposit.value = price;
        }

        // Kiểm tra trạng thái phòng
        if (roomWarning && submitBtn) {

            if (status === "occupied") {

                roomWarning.classList.remove("d-none");
                roomWarning.innerHTML = "⚠️ Phòng này đang có người thuê.";

                submitBtn.disabled = true;

            } else if (status === "maintenance") {

                roomWarning.classList.remove("d-none");
                roomWarning.innerHTML = "⚠️ Phòng này đang bảo trì.";

                submitBtn.disabled = true;

            } else {

                roomWarning.classList.add("d-none");
                roomWarning.innerHTML = "";

                submitBtn.disabled = false;
            }
        }
    }

    roomSelect.addEventListener("change", updateRoomInfo);

    // Nếu modal mở lại mà phòng đã được chọn
    updateRoomInfo();
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
// Upload + preview ảnh hợp đồng
// =========================
const uploadBox = document.getElementById("uploadBox");
const contractImage = document.getElementById("contractImage");
const previewImage = document.getElementById("previewImage");

if (uploadBox && contractImage) {

    uploadBox.addEventListener("click", function (e) {

        // Không mở file picker lần 2 nếu click trực tiếp vào input
        if (e.target === contractImage) return;

        contractImage.click();
    });

    contractImage.addEventListener("change", function () {

        const file = this.files && this.files[0];

        if (!file) {
            if (previewImage) {
                previewImage.src = "";
                previewImage.style.display = "none";
            }
            return;
        }

        if (!file.type || !file.type.startsWith("image/")) {

            alert("Vui lòng chọn file hình ảnh (PNG, JPG hoặc JPEG).");

            this.value = "";

            if (previewImage) {
                previewImage.src = "";
                previewImage.style.display = "none";
            }

            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {

            if (!previewImage) return;

            previewImage.src = e.target.result;
            previewImage.style.display = "block";
        };

        reader.onerror = function () {

            alert("Không thể đọc ảnh. Vui lòng chọn lại file.");

            contractImage.value = "";

            if (previewImage) {
                previewImage.src = "";
                previewImage.style.display = "none";
            }
        };

        reader.readAsDataURL(file);
    });
}
// =========================
// TẠO NỘI DUNG HỢP ĐỒNG
// =========================
const contractForm = document.getElementById("contractForm");
const contractContent = document.getElementById("contractContent");

if (contractForm && contractContent) {

    contractForm.addEventListener("submit", function () {

        let html = contractContent.value;

        const tenantSelect = document.getElementById("tenantSelect");
        const roomSelect = document.getElementById("roomSelect");

        const tenantOption =
            tenantSelect?.options[tenantSelect.selectedIndex];

        const roomOption =
            roomSelect?.options[roomSelect.selectedIndex];

        const startValue =
            document.getElementById("startDate")?.value || "";

        const endValue =
            document.getElementById("endDate")?.value || "";

        function dateParts(value) {
            if (!value) {
                return {
                    day: "",
                    month: "",
                    year: ""
                };
            }

            const [year, month, day] = value.split("-");

            return {
                day: day || "",
                month: month || "",
                year: year || ""
            };
        }

        function money(value) {
            if (!value) return "";

            return Number(value).toLocaleString("vi-VN");
        }

        const start = dateParts(startValue);
        const end = dateParts(endValue);

        const now = new Date();

        // Format ngày từ DB về dd/mm/yyyy
        function formatDate(value) {
            if (!value) return "";

            const date = new Date(value);

            if (isNaN(date.getTime())) {
                return value;
            }

            const day = String(date.getDate()).padStart(2, "0");
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const year = date.getFullYear();

            return `${day}/${month}/${year}`;
        }

        const data = {
            created_day: String(now.getDate()).padStart(2, "0"),
            created_month: String(now.getMonth() + 1).padStart(2, "0"),
            created_year: String(now.getFullYear()),

            house_address: "Cầu Giấy - Hà Nội",

            tenant_name:
                tenantOption?.dataset.name ||
                tenantOption?.textContent.trim() ||
                "",

            tenant_dob:
                formatDate(tenantOption?.dataset.dob),

            tenant_address:
                tenantOption?.dataset.address || "",

            tenant_cccd:
                tenantOption?.dataset.cccd || "",

            tenant_cccd_issue_date:
                formatDate(tenantOption?.dataset.cccdIssueDate),

            tenant_cccd_issue_place:
                tenantOption?.dataset.cccdIssuePlace || "",

            tenant_phone:
                tenantOption?.dataset.phone || "",

            room:
                roomOption?.textContent.trim() || "",

            price:
                money(document.getElementById("monthlyRent")?.value),

            deposit:
                money(document.getElementById("deposit")?.value),

            start_day: start.day,
            start_month: start.month,
            start_year: start.year,

            end_day: end.day,
            end_month: end.month,
            end_year: end.year
        };

        Object.entries(data).forEach(([key, value]) => {

            html = html.replace(
                new RegExp("\\{\\{\\s*" + key + "\\s*\\}\\}", "g"),
                value || ""
            );

        });

        contractContent.value = html;
    });
}
// =========================
// XEM CHI TIẾT HỢP ĐỒNG
// Event delegation + chống đăng ký event nhiều lần
// =========================
(function () {

    if (window.contractDetailViewHandlerInitialized) {
        return;
    }

    window.contractDetailViewHandlerInitialized = true;

    document.addEventListener("click", async function (e) {

        const btn = e.target.closest(".btn-view-contract");

        if (!btn) {
            return;
        }

        e.preventDefault();

        if (btn.dataset.loading === "1") {
            return;
        }

        const url = btn.dataset.url;

        const modalElement =
            document.getElementById("contractModal");

        const modalContent =
            document.getElementById("contractModalContent");

        if (!modalElement || !modalContent) {
            console.error(
                "Không tìm thấy #contractModal hoặc #contractModalContent"
            );
            return;
        }

        if (!url) {
            console.error("Nút xem hợp đồng không có data-url");
            return;
        }

        btn.dataset.loading = "1";

        modalContent.innerHTML = `
            <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-3">Đang tải hợp đồng...</div>
            </div>
        `;

        const contractModal =
            bootstrap.Modal.getOrCreateInstance(modalElement);

        contractModal.show();

        try {

            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "text/html"
                },
                credentials: "same-origin"
            });

            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }

            const html = await response.text();

            if (!html.trim()) {
                throw new Error("Server trả về nội dung rỗng");
            }

            modalContent.innerHTML = html;

            // Mỗi lần mở hợp đồng luôn quay về tab Thông tin.
            const infoTab =
                modalContent.querySelector("#infoTab");

            const contentTab =
                modalContent.querySelector("#contentTab");

            if (infoTab && contentTab) {

                infoTab.classList.add("show", "active");
                contentTab.classList.remove("show", "active");

                const tabButtons =
                    modalContent.querySelectorAll(
                        '[data-bs-toggle="pill"]'
                    );

                tabButtons.forEach(function (tabButton, index) {
                    tabButton.classList.toggle(
                        "active",
                        index === 0
                    );

                    tabButton.setAttribute(
                        "aria-selected",
                        index === 0 ? "true" : "false"
                    );
                });
            }

        } catch (error) {

            console.error(
                "Lỗi tải chi tiết hợp đồng:",
                error
            );

            modalContent.innerHTML = `
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        Có lỗi xảy ra
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger mb-0">
                        Không tải được chi tiết hợp đồng.
                    </div>
                </div>
            `;

        } finally {

            btn.dataset.loading = "0";

        }

    });

})();
// =========================
// GIA HẠN HỢP ĐỒNG
// =========================

document.addEventListener('click', function (e) {

    const btn = e.target.closest('.extendContractBtn');

    if (!btn) return;

    const id = btn.dataset.id;
    const code = btn.dataset.code;
    const room = btn.dataset.room;
    const tenant = btn.dataset.tenant;
    const endDate = btn.dataset.end;
    const action = btn.dataset.action;

    console.log('Gia hạn contract:', {
        id,
        code,
        room,
        tenant,
        endDate,
        action
    });

    // =========================
    // FORM ACTION
    // =========================

    const form = document.getElementById('extendContractForm');

    if (form) {
        form.action = action;
    }


    // =========================
    // MÃ HỢP ĐỒNG
    // =========================

    const codeInput =
        document.getElementById('extend_contract_code');

    if (codeInput) {
        codeInput.value = code || '';
    }


    // =========================
    // PHÒNG
    // =========================

    const roomInput =
        document.getElementById('extend_room');

    if (roomInput) {
        roomInput.value = room || '';
    }


    // =========================
    // KHÁCH THUÊ
    // =========================

    const tenantInput =
        document.getElementById('extend_tenant');

    if (tenantInput) {
        tenantInput.value = tenant || '';
    }


    // =========================
    // NGÀY KẾT THÚC HIỆN TẠI
    // =========================

    const currentEndInput =
        document.getElementById('extend_current_end_date');

    if (currentEndInput) {

        if (endDate) {

            const parts = endDate.split('-');

            currentEndInput.value =
                `${parts[2]}/${parts[1]}/${parts[0]}`;

        } else {

            currentEndInput.value = '';

        }
    }


    // =========================
    // NGÀY KẾT THÚC MỚI
    // =========================

    const newEndInput =
        document.getElementById('new_end_date');

    const submitBtn =
        document.getElementById('btnSubmitExtend');

    const error =
        document.getElementById('extendError');


    if (newEndInput) {

        // reset dữ liệu cũ
        newEndInput.value = '';

        // ngày mới phải sau ngày hiện tại
        if (endDate) {

            const nextDay = new Date(endDate + 'T00:00:00');

            nextDay.setDate(nextDay.getDate() + 1);

            const year = nextDay.getFullYear();

            const month =
                String(nextDay.getMonth() + 1)
                    .padStart(2, '0');

            const day =
                String(nextDay.getDate())
                    .padStart(2, '0');

            newEndInput.min =
                `${year}-${month}-${day}`;
        }
    }


    if (submitBtn) {
        submitBtn.disabled = true;
    }


    if (error) {
        error.classList.add('d-none');
    }

});


// =========================
// VALIDATE NGÀY GIA HẠN
// =========================

document.addEventListener('change', function (e) {

    if (e.target.id !== 'new_end_date') {
        return;
    }

    const input = e.target;

    const submitBtn =
        document.getElementById('btnSubmitExtend');

    const error =
        document.getElementById('extendError');


    if (!input.value) {

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        return;
    }


    if (input.min && input.value < input.min) {

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        if (error) {
            error.classList.remove('d-none');
        }

        return;
    }


    if (submitBtn) {
        submitBtn.disabled = false;
    }

    if (error) {
        error.classList.add('d-none');
    }

});

// Dùng event delegation để vẫn hoạt động nếu modal được render lại
document.addEventListener('change', function (e) {
    if (e.target.id === 'depositProcessType') {
        updateDepositPreview();
    }
});

document.addEventListener('input', function (e) {
    if (e.target.id === 'deductionAmount') {
        updateDepositPreview();
    }
});


// =========================
// KẾT THÚC HỢP ĐỒNG
// =========================
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.terminateBtn');

    if (!btn) return;

    const action = btn.dataset.action;

    console.log('Terminate contract:', {
        id: btn.dataset.id,
        action: action
    });

    const form = document.getElementById('terminateContractForm');

    if (!form) {
        console.error('Không tìm thấy terminateContractForm');
        return;
    }

    // Gán đúng route Laravel
    form.action = action;

    // Reset ngày trả phòng
    const dateInput = document.getElementById('actual_end_date');

    if (dateInput) {
        dateInput.value = '';

        // Không cho chọn trước ngày bắt đầu
        if (btn.dataset.start) {
            dateInput.min = btn.dataset.start;
        }
    }

});
</script>