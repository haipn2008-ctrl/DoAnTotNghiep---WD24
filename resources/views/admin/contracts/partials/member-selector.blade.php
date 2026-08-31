@php
    $defaultMembers = isset($contract)
        ? $contract->currentMembers
            ->where('role', \App\Models\ContractTenant::ROLE_TENANT)
            ->whereIn('status', [\App\Models\ContractTenant::STATUS_PENDING, \App\Models\ContractTenant::STATUS_APPROVED])
            ->map(fn ($member) => [
                'id' => $member->id,
                'tenant_id' => $member->tenant_id,
                'full_name' => $member->full_name,
                'date_of_birth' => $member->date_of_birth?->toDateString(),
                'gender' => $member->tenant?->gender,
                'identity_number' => $member->identity_number,
                'cccd_issue_date' => $member->tenant?->cccd_issue_date?->toDateString(),
                'cccd_issue_place' => $member->tenant?->cccd_issue_place,
                'identity_front_path' => $member->identity_front_path,
                'identity_back_path' => $member->identity_back_path,
                'phone' => $member->phone,
                'email' => $member->tenant?->email,
                'address' => $member->address,
            ])->values()->all()
        : [];
    $selectedMembers = old('members', $defaultMembers);
@endphp

<div data-contract-tenants class="md:col-span-2 rounded-lg border border-slate-200">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-slate-800">Người thuê</p>
            <span data-member-count class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">0 người</span>
        </div>
        <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
            <select data-existing-member-tenant class="h-9 min-w-56 max-w-sm rounded-lg border border-slate-200 bg-white px-3 text-xs outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                <option value="">Chọn khách có sẵn</option>
                @foreach($availableMemberTenants as $tenant)
                    <option value="{{ $tenant->id }}"
                        data-full-name="{{ $tenant->full_name }}"
                        data-date-of-birth="{{ $tenant->date_of_birth?->toDateString() }}"
                        data-gender="{{ $tenant->gender }}"
                        data-cccd="{{ $tenant->cccd }}"
                        data-cccd-issue-date="{{ $tenant->cccd_issue_date?->toDateString() }}"
                        data-cccd-issue-place="{{ $tenant->cccd_issue_place }}"
                        data-phone="{{ $tenant->phone }}"
                        data-email="{{ $tenant->email }}"
                        data-address="{{ $tenant->address }}"
                        data-identity-front-url="{{ $tenant->document?->hasImage('front') ? route('admin.tenants.identity-document', [$tenant, 'front']) : '' }}"
                        data-identity-back-url="{{ $tenant->document?->hasImage('back') ? route('admin.tenants.identity-document', [$tenant, 'back']) : '' }}">
                        {{ $tenant->full_name }} — CCCD {{ $tenant->cccd }}
                    </option>
                @endforeach
            </select>
            <button type="button" data-add-existing-member class="h-9 rounded-lg border border-indigo-200 bg-white px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Thêm khách có sẵn</button>
            <button type="button" data-add-member class="h-9 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700">Thêm khách mới</button>
        </div>
        <p data-existing-member-error class="hidden w-full text-right text-xs font-semibold text-rose-600"></p>
    </div>

    <div data-member-list class="space-y-3 p-4">
        @foreach($selectedMembers as $index => $member)
            @include('admin.contracts.partials.member-row', ['index' => $index, 'member' => $member])
        @endforeach
        <p data-empty-members class="{{ count($selectedMembers) ? 'hidden' : '' }} rounded-lg border border-dashed border-slate-200 px-4 py-5 text-center text-sm text-slate-500">Chưa khai báo người thuê.</p>
        <p data-member-limit class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"></p>
    </div>

    <template data-member-template>
        @include('admin.contracts.partials.member-row', ['index' => '__INDEX__', 'member' => []])
    </template>
</div>

@error('members') <p class="md:col-span-2 text-sm text-rose-600">{{ $message }}</p> @enderror
@error('members.*') <p class="md:col-span-2 text-sm text-rose-600">{{ $message }}</p> @enderror
