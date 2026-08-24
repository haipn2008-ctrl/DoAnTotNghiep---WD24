<style>
.refund-modal{
    position:fixed;
    inset:0;
    z-index:99999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:24px;
    background:rgba(15,23,42,.58);
    backdrop-filter:blur(3px);
}
.refund-modal.is-open{display:flex;}

.refund-modal-dialog{
    width:min(1280px,calc(100vw - 56px));
    height:min(820px,calc(100vh - 40px));
    max-height:calc(100vh - 40px);
    display:flex;
    flex-direction:column;
    overflow:hidden;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    box-shadow:0 25px 70px rgba(15,23,42,.28);
}

.refund-modal-body{
    flex:1 1 auto;
    min-height:0;
    overflow-y:auto;
    padding:22px;
    background:#f8fafc;
}
.refund-modal-body::-webkit-scrollbar{width:8px}
.refund-modal-body::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}

.refund-desktop-grid{
    display:grid;
    grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);
    gap:20px;
    align-items:start;
}

.refund-card{
    border:1px solid #e2e8f0;
    border-radius:14px;
    background:#fff;
    padding:18px;
    box-shadow:0 1px 2px rgba(15,23,42,.03);
}

.refund-info-grid{
    display:grid;
    grid-template-columns:1.15fr 1fr 1fr;
    gap:12px;
    margin-bottom:18px;
}

.refund-mini{
    border:1px solid #e2e8f0;
    border-radius:11px;
    background:#fff;
    padding:12px;
}
.refund-mini.deposit{
    border-color:#fde68a;
    background:#fffbeb;
}
.refund-mini .label{
    color:#64748b;
    font-size:11px;
    font-weight:600;
}
.refund-mini .value{
    margin-top:4px;
    color:#0f172a;
    font-size:14px;
    font-weight:700;
}
.refund-mini.deposit .value{color:#d97706;font-size:17px}

.refund-bank{
    overflow:hidden;
    border:1px solid #e2e8f0;
    border-radius:10px;
}
.refund-bank-row{
    display:flex;
    justify-content:space-between;
    gap:14px;
    padding:9px 11px;
    border-bottom:1px solid #f1f5f9;
    font-size:13px;
}
.refund-bank-row:last-child{border-bottom:0}
.refund-bank-row span{color:#64748b}
.refund-bank-row strong{text-align:right;color:#0f172a}

.refund-qr-box{
    margin-top:12px;
    padding:10px;
    border:1px solid #e2e8f0;
    border-radius:11px;
    background:#f8fafc;
    text-align:center;
}
.refund-qr-box p{
    margin:0 0 7px;
    color:#94a3b8;
    font-size:10px;
    font-weight:700;
    letter-spacing:.06em;
    text-transform:uppercase;
}
.refund-qr{
    width:180px!important;
    height:180px!important;
    max-width:180px!important;
    max-height:180px!important;
    object-fit:contain;
    display:block;
    margin:auto;
    padding:4px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    background:#fff;
}

.refund-file{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-top:12px;
    padding:10px 12px;
    border:1px dashed #cbd5e1;
    border-radius:10px;
    background:#f8fafc;
    font-size:12px;
}
.refund-file a{
    color:#4f46e5;
    font-weight:700;
    text-decoration:none;
}
.refund-file a:hover{text-decoration:underline}

.refund-heading{
    margin:0;
    color:#0f172a;
    font-size:17px;
    font-weight:700;
}
.refund-desc{
    margin:4px 0 16px;
    color:#64748b;
    font-size:12px;
}

.refund-field{margin-bottom:14px}
.refund-field label{
    display:block;
    margin-bottom:6px;
    color:#334155;
    font-size:12px;
    font-weight:700;
}
.refund-field label span{color:#ef4444}
.refund-input,
.refund-select,
.refund-textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #cbd5e1;
    border-radius:9px;
    background:#fff;
    padding:10px 12px;
    color:#0f172a;
    font-size:13px;
    outline:none;
}
.refund-input:focus,
.refund-select:focus,
.refund-textarea:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 3px rgba(99,102,241,.10);
}
.refund-textarea{min-height:90px;resize:vertical}

.refund-money-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin:14px 0;
}
.refund-money{
    padding:12px;
    border-radius:11px;
}
.refund-money.deduction{
    border:1px solid #fecaca;
    background:#fef2f2;
}
.refund-money.transfer{
    border:1px solid #a7f3d0;
    background:#ecfdf5;
}
.refund-money span{display:block;font-size:11px;font-weight:600}
.refund-money strong{display:block;margin-top:3px;font-size:17px}
.refund-money.deduction span,.refund-money.deduction strong{color:#dc2626}
.refund-money.transfer span,.refund-money.transfer strong{color:#059669}


.refund-upload{
    display:block;
    width:100%;
    box-sizing:border-box;
    padding:11px 12px;
    border:1px dashed #94a3b8;
    border-radius:10px;
    background:#f8fafc;
    color:#334155;
    font-size:13px;
    cursor:pointer;
}
.refund-upload:hover{
    border-color:#6366f1;
    background:#eef2ff;
}
.refund-upload-hint{
    margin-top:5px;
    color:#64748b;
    font-size:11px;
    line-height:1.45;
}
.refund-upload-preview{
    display:none;
    margin-top:9px;
    width:180px;
    height:120px;
    max-width:100%;
    object-fit:cover;
    border:1px solid #cbd5e1;
    border-radius:8px;
}

.refund-upload-wrap{
    display:block;
}

.refund-upload-title{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:7px;
    color:#334155;
    font-size:12px;
    font-weight:700;
}

.refund-upload-title i{
    color:#4f46e5;
    font-size:16px;
}

.refund-note{
    display:flex;
    gap:8px;
    padding:10px 12px;
    border:1px solid #fde68a;
    border-radius:9px;
    background:#fffbeb;
    color:#92400e;
    font-size:11px;
    line-height:1.5;
}

.refund-footer{
    flex:0 0 auto;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:14px 18px;
    border-top:1px solid #e2e8f0;
    background:#fff;
    position:sticky;
    bottom:0;
    z-index:20;
    box-shadow:0 -6px 18px rgba(15,23,42,.06);
}

.refund-footer .refund-btn{
    min-height:42px;
    padding:10px 16px;
    white-space:nowrap;
}

.refund-modal-dialog > form{
    min-height:0;
}

.refund-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:0;
    border-radius:9px;
    padding:9px 14px;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
}
.refund-btn.cancel{
    border:1px solid #cbd5e1;
    background:#fff;
    color:#475569;
}
.refund-btn.cancel:hover{background:#f8fafc}
.refund-btn.primary{background:#4f46e5;color:#fff}
.refund-btn.primary:hover{background:#4338ca}
.refund-btn.success{background:#059669;color:#fff}
.refund-btn.success:hover{background:#047857}

@media(max-width:1000px){
    .refund-modal{padding:10px}
    .refund-modal-dialog{
        width:calc(100vw - 20px);
        height:calc(100vh - 20px);
    }
    .refund-desktop-grid{grid-template-columns:1fr}
    .refund-info-grid{grid-template-columns:1fr}
}
</style>

@foreach($contracts as $contract)
    @php
        $deposit = (float)($contract->deposit_amount ?? 0);
        $approvedRefund = (float)($contract->deposit_refund_amount ?? 0);
        $refundRequested = $contract->isRefundRequested();
        $refundApproved = $contract->isRefundApproved();
        $refundRejected = $contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REJECTED;
    @endphp

    {{-- MỖI HỢP ĐỒNG CHỈ CÓ 1 MODAL --}}
    <div id="refundModal{{ $contract->id }}" class="refund-modal" aria-hidden="true">
        <div class="refund-modal-dialog">

            @if($refundRequested)
                {{-- ================= DUYỆT HOÀN CỌC ================= --}}
                <form method="POST"
                      enctype="multipart/form-data"
                      action="{{ route('admin.deposit-refunds.approve', $contract) }}"
                      class="flex min-h-0 flex-1 flex-col">
                    @csrf

                    <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <i class="bx bx-wallet text-xl"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Duyệt hoàn cọc</div>
                                <div class="text-lg font-bold text-slate-900">{{ $contract->contract_code }}</div>
                            </div>
                        </div>
                        <button type="button"
                                data-refund-close="refundModal{{ $contract->id }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <i class="bx bx-x text-xl"></i>
                        </button>
                    </div>

                    <div class="refund-modal-body">
                        <div class="refund-info-grid">
                            <div class="refund-mini deposit">
                                <div class="label">Tiền cọc ban đầu</div>
                                <div class="value">{{ number_format($deposit,0,',','.') }} VNĐ</div>
                            </div>
                            <div class="refund-mini">
                                <div class="label">Khách thuê</div>
                                <div class="value truncate">{{ $contract->tenant->full_name ?? '-' }}</div>
                            </div>
                            <div class="refund-mini">
                                <div class="label">Phòng</div>
                                <div class="value" style="color:#4f46e5">{{ $contract->room->room_code ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="refund-desktop-grid">
                            <div>
                                <section class="refund-card">
                                    <div class="mb-3 flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <i class="bx bx-credit-card text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-900">Thông tin nhận tiền</h4>
                                            <p class="text-xs text-slate-500">Khách cung cấp</p>
                                        </div>
                                    </div>

                                    <div class="refund-bank">
                                        <div class="refund-bank-row">
                                            <span>Ngân hàng</span>
                                            <strong>{{ $contract->deposit_bank_name ?? '-' }}</strong>
                                        </div>
                                        <div class="refund-bank-row">
                                            <span>Số tài khoản</span>
                                            <strong>{{ $contract->deposit_bank_account_number ?? '-' }}</strong>
                                        </div>
                                        <div class="refund-bank-row">
                                            <span>Chủ tài khoản</span>
                                            <strong>{{ $contract->deposit_bank_account_name ?? '-' }}</strong>
                                        </div>
                                    </div>

                                    @if($contract->deposit_qr_image)
                                        <div class="refund-qr-box">
                                            <p>QR ngân hàng</p>
                                            <img src="{{ route('admin.deposit-refunds.qr',$contract) }}"
                                                 alt="QR ngân hàng"
                                                 class="refund-qr">
                                        </div>
                                    @endif

                                </section>
                            </div>

                            <div>
                                <section class="refund-card h-full">
                                    <h4 class="refund-heading">Phương án hoàn cọc</h4>
                                    <p class="refund-desc">Xác định số tiền khấu trừ trước khi duyệt.</p>

                                    <div class="refund-field">
                                        <label>Phương thức xử lý <span>*</span></label>
                                        <select name="deposit_process_type"
                                                class="refund-type refund-select"
                                                data-id="{{ $contract->id }}"
                                                data-deposit="{{ $deposit }}">
                                            <option value="full_refund">Hoàn toàn bộ</option>
                                            <option value="partial_refund">Khấu trừ một phần</option>
                                            <option value="no_refund">Không hoàn cọc</option>
                                        </select>
                                    </div>

                                    <div class="refund-field deduction-box-{{ $contract->id }}" style="display:none">
                                        <label>Số tiền khấu trừ</label>
                                        <input type="number"
                                               name="deduction_amount"
                                               value="0"
                                               min="0"
                                               max="{{ $deposit }}"
                                               step="1000"
                                               data-id="{{ $contract->id }}"
                                               class="deduction-input refund-input">
                                    </div>

                                    <div class="refund-field damage-proof-field-{{ $contract->id }}" style="display:none">
                                        <label>Ảnh minh chứng hư hỏng / thiệt hại <span>*</span></label>
                                        <input type="file"
                                               name="damage_proof"
                                               accept="image/png,image/jpeg,image/webp"
                                               class="refund-upload damage-proof-input damage-proof-input-{{ $contract->id }}"
                                               data-id="{{ $contract->id }}">
                                        <div class="refund-upload-hint">
                                            Chỉ bắt buộc khi có khấu trừ. Có thể tải ảnh phòng, thiết bị hoặc tài sản bị hư hỏng.
                                        </div>
                                        <img class="refund-upload-preview damage-proof-preview-{{ $contract->id }}"
                                             alt="Ảnh minh chứng hư hỏng">
                                    </div>

                                    <div class="refund-money-grid">
                                        <div class="refund-money deduction">
                                            <span>Khấu trừ</span>
                                            <strong class="deduction-preview-{{ $contract->id }}">0 VNĐ</strong>
                                        </div>
                                        <div class="refund-money transfer">
                                            <span>Số tiền sẽ chuyển</span>
                                            <strong class="refund-preview-{{ $contract->id }}">{{ number_format($deposit,0,',','.') }} VNĐ</strong>
                                        </div>
                                    </div>

                                    <div class="refund-field">
                                        <label>Lý do <span>*</span></label>
                                        <input type="text"
                                               name="return_reason"
                                               maxlength="255"
                                               required
                                               class="refund-input"
                                               placeholder="Ví dụ: Trừ tiền điện nước còn thiếu...">
                                    </div>

                                    <div class="refund-field">
                                        <label>Ghi chú</label>
                                        <textarea name="return_note"
                                                  class="refund-textarea"
                                                  placeholder="Ghi chú thêm nếu cần..."></textarea>
                                    </div>

                                    <div class="refund-note">
                                        <i class="bx bx-info-circle"></i>
                                        <span>Kiểm tra khoản hoàn, khấu trừ và minh chứng trước khi duyệt.</span>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="refund-footer">
                        <button type="button"
                                data-refund-close="refundModal{{ $contract->id }}"
                                class="refund-btn cancel">
                            <i class="bx bx-x"></i> Hủy
                        </button>
                        <button type="submit" class="refund-btn primary">
                            <i class="bx bx-check-circle"></i> Duyệt số tiền hoàn
                        </button>
                    </div>
                </form>

            @elseif($refundApproved)
                {{-- ================= XÁC NHẬN ĐÃ CHUYỂN ================= --}}
                <form method="POST"
                      enctype="multipart/form-data"
                      action="{{ route('admin.deposit-refunds.complete',$contract) }}"
                      class="flex min-h-0 flex-1 flex-col">
                    @csrf

                    <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="bx bx-transfer-alt text-xl"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Xác nhận chuyển khoản</div>
                                <div class="text-lg font-bold text-slate-900">{{ $contract->contract_code }}</div>
                            </div>
                        </div>
                        <button type="button"
                                data-refund-close="refundModal{{ $contract->id }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <i class="bx bx-x text-xl"></i>
                        </button>
                    </div>

                    <div class="refund-modal-body">
                        <div class="refund-info-grid">
                            <div class="refund-mini deposit" style="border-color:#a7f3d0;background:#ecfdf5">
                                <div class="label" style="color:#059669">Số tiền cần chuyển</div>
                                <div class="value" style="color:#047857;font-size:17px">
                                    {{ number_format($approvedRefund,0,',','.') }} VNĐ
                                </div>
                            </div>
                            <div class="refund-mini">
                                <div class="label">Khách thuê</div>
                                <div class="value truncate">{{ $contract->tenant->full_name ?? '-' }}</div>
                            </div>
                            <div class="refund-mini">
                                <div class="label">Phòng</div>
                                <div class="value" style="color:#4f46e5">{{ $contract->room->room_code ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="refund-desktop-grid">
                            <div>
                                <section class="refund-card">
                                    <div class="mb-3 flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <i class="bx bx-credit-card text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-900">Thông tin nhận tiền</h4>
                                            <p class="text-xs text-slate-500">Kiểm tra trước khi chuyển</p>
                                        </div>
                                    </div>

                                    <div class="refund-bank">
                                        <div class="refund-bank-row"><span>Ngân hàng</span><strong>{{ $contract->deposit_bank_name ?? '-' }}</strong></div>
                                        <div class="refund-bank-row"><span>Số tài khoản</span><strong>{{ $contract->deposit_bank_account_number ?? '-' }}</strong></div>
                                        <div class="refund-bank-row"><span>Chủ tài khoản</span><strong>{{ $contract->deposit_bank_account_name ?? '-' }}</strong></div>
                                    </div>

                                    @if($contract->deposit_qr_image)
                                        <div class="refund-qr-box">
                                            <p>QR ngân hàng</p>
                                            <img src="{{ route('admin.deposit-refunds.qr',$contract) }}" alt="QR ngân hàng" class="refund-qr">
                                        </div>
                                    @endif
                                </section>
                            </div>

                            <div>
                                <section class="refund-card h-full">
                                    <h4 class="refund-heading">Xác nhận đã chuyển khoản</h4>
                                    <p class="refund-desc">Nhập số tiền thực tế và tải bằng chứng giao dịch.</p>

                                    <div class="refund-field">
                                        <label>Số tiền thực chuyển <span>*</span></label>
                                        <input type="number"
                                               name="transfer_amount"
                                               value="{{ $approvedRefund }}"
                                               min="0"
                                               step="1000"
                                               required
                                               class="refund-input">
                                    </div>

                                    <div class="refund-field">
                                        <label>Ảnh bằng chứng chuyển khoản <span>*</span></label>
                                        <input type="file"
                                               name="transfer_proof"
                                               accept="image/png,image/jpeg,image/webp"
                                               required
                                               class="refund-upload transfer-proof-input transfer-proof-input-{{ $contract->id }}"
                                               data-id="{{ $contract->id }}">

                                        <div class="refund-upload-hint">
                                            Tải ảnh biên lai hoặc ảnh giao dịch đã chuyển khoản thành công.
                                        </div>

                                        <img class="refund-upload-preview transfer-proof-preview-{{ $contract->id }}"
                                             alt="Ảnh bằng chứng chuyển khoản">
                                    </div>

                                    <div class="refund-field">
                                        <label>Ghi chú</label>
                                        <textarea name="transfer_note"
                                                  class="refund-textarea"
                                                  placeholder="Ghi chú giao dịch..."></textarea>
                                    </div>

                                    <div class="refund-note" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46">
                                        <i class="bx bx-check-circle"></i>
                                        <span>Sau khi xác nhận, yêu cầu sẽ chuyển sang trạng thái <strong>Đã hoàn tất</strong>.</span>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="refund-footer">
                        <button type="button"
                                data-refund-close="refundModal{{ $contract->id }}"
                                class="refund-btn cancel">
                            <i class="bx bx-x"></i> Hủy
                        </button>
                        <button type="submit" class="refund-btn success">
                            <i class="bx bx-check-double"></i> Xác nhận đã chuyển khoản
                        </button>
                    </div>
                </form>

            @else
                <div class="flex flex-1 items-center justify-center p-10">
                    <div class="text-center">
                        <i class="bx bx-check-circle text-5xl text-emerald-500"></i>
                        <h3 class="mt-3 font-bold text-slate-900">Yêu cầu đã được xử lý</h3>
                        <p class="mt-1 text-sm text-slate-500">Không còn thao tác nào cho yêu cầu này.</p>
                        <button type="button"
                                data-refund-close="refundModal{{ $contract->id }}"
                                class="refund-btn cancel mt-4">
                            Đóng
                        </button>
                    </div>
                </div>
                        @endif

        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- MODAL TỪ CHỐI HOÀN CỌC --}}
    {{-- ===================================================== --}}

    <div
        id="rejectRefundModal{{ $contract->id }}"
        class="refund-modal"
        aria-hidden="true"
    >
        <div class="refund-modal-dialog">

            <form
                method="POST"
                action="{{ route('admin.deposit-refunds.reject', $contract) }}"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                {{-- HEADER --}}
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-5 py-4">

                    <div class="flex items-center gap-3">

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <i class="bx bx-x-circle text-xl"></i>
                        </div>

                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-red-500">
                                Từ chối hoàn cọc
                            </div>

                            <div class="text-lg font-bold text-slate-900">
                                {{ $contract->contract_code }}
                            </div>
                        </div>

                    </div>

                    <button
                        type="button"
                        data-refund-close="rejectRefundModal{{ $contract->id }}"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >
                        <i class="bx bx-x text-xl"></i>
                    </button>

                </div>


                {{-- BODY --}}
                <div class="refund-modal-body">

                    {{-- THÔNG TIN HỢP ĐỒNG --}}
                    <div class="refund-info-grid">

                        <div class="refund-mini">
                            <div class="label">
                                Hợp đồng
                            </div>

                            <div class="value">
                                {{ $contract->contract_code }}
                            </div>
                        </div>

                        <div class="refund-mini">
                            <div class="label">
                                Khách thuê
                            </div>

                            <div class="value truncate">
                                {{ $contract->tenant->full_name ?? '-' }}
                            </div>
                        </div>

                        <div class="refund-mini">
                            <div class="label">
                                Phòng
                            </div>

                            <div
                                class="value"
                                style="color:#4f46e5"
                            >
                                {{ $contract->room->room_code ?? '-' }}
                            </div>
                        </div>

                    </div>


                    {{-- CẢNH BÁO --}}
                    <div class="refund-note"
                         style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">

                        <i class="bx bx-error-circle"></i>

                        <span>
                            Bạn đang từ chối yêu cầu hoàn cọc của khách thuê.
                            Vui lòng nhập lý do trước khi xác nhận.
                        </span>

                    </div>


                    {{-- LÝ DO --}}
                    <section class="refund-card mt-4">

                        <div class="mb-3 flex items-center gap-3">

                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                <i class="bx bx-message-square-error text-lg"></i>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-900">
                                    Lý do từ chối
                                </h4>

                                <p class="text-xs text-slate-500">
                                    Lý do này sẽ được lưu lại trong hợp đồng.
                                </p>
                            </div>

                        </div>


                        <div class="refund-field">

                            <label>
                                Lý do
                                <span>*</span>
                            </label>

                            <textarea
                                name="reason"
                                rows="5"
                                maxlength="1000"
                                required
                                class="refund-textarea"
                                placeholder="Nhập lý do từ chối yêu cầu hoàn cọc..."
                            ></textarea>

                        </div>

                    </section>

                </div>


                {{-- FOOTER --}}
                <div class="refund-footer">

                    <button
                        type="button"
                        data-refund-close="rejectRefundModal{{ $contract->id }}"
                        class="refund-btn cancel"
                    >
                        <i class="bx bx-x"></i>
                        Hủy
                    </button>

                    <button
                        type="submit"
                        class="refund-btn"
                        style="background:#dc2626;color:#fff;"
                    >
                        <i class="bx bx-x-circle"></i>
                        Xác nhận từ chối
                    </button>

                </div>

            </form>

        </div>
    </div>


@endforeach

<script>
(function () {
    function openRefundModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        document.querySelectorAll('.refund-modal').forEach(function (item) {
            item.classList.remove('is-open');
            item.setAttribute('aria-hidden', 'true');
        });

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const body = modal.querySelector('.refund-modal-body');
        if (body) body.scrollTop = 0;
    }

    function closeRefundModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.refund-modal.is-open')) {
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('click', function (event) {
        const openButton = event.target.closest('[data-refund-open]');
        if (openButton) {
            event.preventDefault();
            openRefundModal(openButton.dataset.refundOpen);
            return;
        }

        const closeButton = event.target.closest('[data-refund-close]');
        if (closeButton) {
            event.preventDefault();
            closeRefundModal(document.getElementById(closeButton.dataset.refundClose));
            return;
        }

        const modal = event.target.closest('.refund-modal');
        if (modal && event.target === modal) {
            closeRefundModal(modal);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        const modal = document.querySelector('.refund-modal.is-open');
        closeRefundModal(modal);
    });

    function updateRefundPreview(id) {
        const type = document.querySelector('.refund-type[data-id="' + id + '"]');
        const input = document.querySelector('.deduction-input[data-id="' + id + '"]');
        const deductionPreview = document.querySelector('.deduction-preview-' + id);
        const refundPreview = document.querySelector('.refund-preview-' + id);
        const deductionBox = document.querySelector('.deduction-box-' + id);

        if (!type || !deductionPreview || !refundPreview) return;

        const deposit = Number(type.dataset.deposit || 0);
        let deduction = 0;

        if (type.value === 'partial_refund') {
            if (deductionBox) deductionBox.style.display = '';
            deduction = Math.max(0, Number(input ? input.value : 0));
            deduction = Math.min(deduction, deposit);
        } else {
            if (deductionBox) deductionBox.style.display = 'none';
        }

        if (type.value === 'no_refund') {
            deduction = deposit;
        }

        if (input) input.value = Math.round(deduction);

        const refund = Math.max(deposit - deduction, 0);
        const format = new Intl.NumberFormat('vi-VN');

        deductionPreview.textContent = format.format(deduction) + ' VNĐ';
        refundPreview.textContent = format.format(refund) + ' VNĐ';
    }


    function updateDamageProof(id) {
        const type = document.querySelector('.refund-type[data-id="' + id + '"]');
        const box = document.querySelector('.damage-proof-field-' + id);
        const input = document.querySelector('.damage-proof-input-' + id);
        if (!type || !box || !input) return;

        const needsProof = type.value === 'partial_refund' || type.value === 'no_refund';
        box.style.display = needsProof ? '' : 'none';
        input.required = needsProof;

        if (!needsProof) {
            input.value = '';
            const preview = document.querySelector('.damage-proof-preview-' + id);
            if (preview) {
                preview.removeAttribute('src');
                preview.style.display = 'none';
            }
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('.refund-type')) {
            updateRefundPreview(event.target.dataset.id);
            updateDamageProof(event.target.dataset.id);
        }
    });


    document.addEventListener('change', function (event) {
        const input = event.target;

        const isDamage = input.classList.contains('damage-proof-input');
        const isTransfer = input.classList.contains('transfer-proof-input');

        if (!isDamage && !isTransfer) return;

        const id = input.dataset.id;
        const previewSelector = isDamage
            ? '.damage-proof-preview-' + id
            : '.transfer-proof-preview-' + id;

        const preview = document.querySelector(previewSelector);
        const file = input.files && input.files[0];

        if (!preview) return;

        if (!file) {
            preview.removeAttribute('src');
            preview.style.display = 'none';
            return;
        }

        if (!file.type.startsWith('image/')) {
            input.value = '';
            preview.removeAttribute('src');
            preview.style.display = 'none';
            return;
        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
        }

        const objectUrl = URL.createObjectURL(file);
        preview.dataset.objectUrl = objectUrl;
        preview.src = objectUrl;
        preview.style.display = 'block';
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('.deduction-input')) {
            updateRefundPreview(event.target.dataset.id);
        }
    });

    document.querySelectorAll('.refund-type').forEach(function (select) {
        updateRefundPreview(select.dataset.id);
        updateDamageProof(select.dataset.id);
    });
})();
</script>
