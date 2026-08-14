<div class="grid gap-4 md:grid-cols-2">
    <div><label class="mb-1.5 block text-sm font-semibold">Mã ngân hàng VietQR</label><input name="bank_id" value="{{ old('bank_id', $setting->bank_id) }}" placeholder="Ví dụ: MB, VCB, ACB" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
    <div><label class="mb-1.5 block text-sm font-semibold">Số tài khoản</label><input name="bank_account_no" value="{{ old('bank_account_no', $setting->bank_account_no) }}" inputmode="numeric" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
    <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold">Tên chủ tài khoản</label><input name="bank_account_name" value="{{ old('bank_account_name', $setting->bank_account_name) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
</div>
