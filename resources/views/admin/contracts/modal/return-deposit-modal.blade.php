<div class="modal fade" id="returnDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="depositProcessForm" method="POST">
                @csrf
                <div class="modal-header bg-info-subtle">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Xử lý tiền cọc</h5>
                        <small class="text-muted" id="depositContractCode"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border d-flex justify-content-between">
                        <span>Tiền cọc ban đầu</span>
                        <strong class="text-warning" id="depositOriginalText">0 VNĐ</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức xử lý <span class="text-danger">*</span></label>
                        <select class="form-select" name="deposit_process_type" id="depositProcessType" required>
                            <option value="full_refund">Hoàn cọc toàn bộ</option>
                            <option value="partial_refund">Khấu trừ một phần</option>
                            <option value="no_refund">Không hoàn cọc</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="deductionGroup">
                        <label class="form-label fw-semibold">Số tiền khấu trừ</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="deduction_amount" id="deductionAmount" min="0" step="1000" value="0">
                            <span class="input-group-text">VNĐ</span>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="border rounded p-3"><small class="text-muted">Khấu trừ</small><div class="fw-bold text-danger" id="deductionPreview">0 VNĐ</div></div></div>
                        <div class="col-6"><div class="border rounded p-3"><small class="text-muted">Thực hoàn</small><div class="fw-bold text-success" id="refundPreview">0 VNĐ</div></div></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="return_reason" maxlength="255" placeholder="VD: Khách trả phòng trước hạn" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea class="form-control" name="return_note" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Sau khi xác nhận, hợp đồng chuyển sang <strong>Hoàn tất</strong> và không thể xử lý cọc lần hai.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info"><i class="bi bi-check-circle me-1"></i>Xác nhận xử lý</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.returnDepositBtn');
    if (!btn) return;
    const form = document.getElementById('depositProcessForm');
    form.action = btn.dataset.action;
    form.dataset.deposit = btn.dataset.deposit || 0;
    document.getElementById('depositContractCode').textContent = 'Hợp đồng: ' + (btn.dataset.code || '');
    document.getElementById('depositOriginalText').textContent = money(form.dataset.deposit);
    document.getElementById('depositProcessType').value = 'full_refund';
    document.getElementById('deductionAmount').value = 0;
    updateDepositPreview();
});
document.getElementById('depositProcessType')?.addEventListener('change', updateDepositPreview);
document.getElementById('deductionAmount')?.addEventListener('input', updateDepositPreview);
function money(value) { return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' VNĐ'; }
function updateDepositPreview() {
    const form = document.getElementById('depositProcessForm');
    if (!form) return;
    const deposit = Number(form.dataset.deposit || 0);
    const type = document.getElementById('depositProcessType').value;
    const group = document.getElementById('deductionGroup');
    const input = document.getElementById('deductionAmount');
    group.classList.toggle('d-none', type !== 'partial_refund');
    input.required = type === 'partial_refund';
    let deduction = 0;
    if (type === 'partial_refund') deduction = Math.min(Math.max(Number(input.value || 0), 0), deposit);
    if (type === 'no_refund') deduction = deposit;
    document.getElementById('deductionPreview').textContent = money(deduction);
    document.getElementById('refundPreview').textContent = money(deposit - deduction);
}
</script>
