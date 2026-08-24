@php
    $defaultMembers = isset($contract)
        ? $contract->currentMembers
            ->where('role', \App\Models\ContractTenant::ROLE_TENANT)
            ->whereIn('status', [\App\Models\ContractTenant::STATUS_PENDING, \App\Models\ContractTenant::STATUS_APPROVED])
            ->map(fn ($member) => [
                'id' => $member->id,
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
        <div>
            <p class="text-sm font-semibold text-slate-800">Người thuê</p>
        </div>
        <div class="flex items-center gap-2">
            <span data-member-count class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">0 người</span>
            <button type="button" data-add-member class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">+ Thêm người</button>
        </div>
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
