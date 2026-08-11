@php
    $defaultOccupants = isset($contract)
        ? $contract->occupants
            ->where('role', \App\Models\ContractOccupant::ROLE_OCCUPANT)
            ->whereIn('status', [\App\Models\ContractOccupant::STATUS_PENDING, \App\Models\ContractOccupant::STATUS_APPROVED])
            ->map(fn ($occupant) => [
                'id' => $occupant->id,
                'full_name' => $occupant->full_name,
                'date_of_birth' => $occupant->date_of_birth?->toDateString(),
                'identity_number' => $occupant->identity_number,
                'identity_front_path' => $occupant->identity_front_path,
                'identity_back_path' => $occupant->identity_back_path,
                'phone' => $occupant->phone,
            ])->values()->all()
        : [];
    $selectedOccupants = old('occupants', $defaultOccupants);
@endphp

<div data-contract-occupants class="md:col-span-2 rounded-lg border border-slate-200">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div>
            <p class="text-sm font-semibold text-slate-800">Người ở</p>
            <p class="text-xs text-slate-500">Danh sách người thực tế cư trú. Không cần tạo tài khoản và không phụ thuộc người đứng tên hợp đồng.</p>
        </div>
        <div class="flex items-center gap-2">
            <span data-occupant-count class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">0 người</span>
            <button type="button" data-add-occupant class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">+ Thêm người</button>
        </div>
    </div>

    <div data-occupant-list class="space-y-3 p-4">
        @foreach($selectedOccupants as $index => $occupant)
            @include('admin.contracts.partials.occupant-row', ['index' => $index, 'occupant' => $occupant])
        @endforeach
        <p data-empty-occupants class="{{ count($selectedOccupants) ? 'hidden' : '' }} rounded-lg border border-dashed border-slate-200 px-4 py-5 text-center text-sm text-slate-500">Chưa khai báo người ở.</p>
        <p data-occupant-limit class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"></p>
    </div>

    <template data-occupant-template>
        @include('admin.contracts.partials.occupant-row', ['index' => '__INDEX__', 'occupant' => []])
    </template>
</div>

@error('occupants') <p class="md:col-span-2 text-sm text-rose-600">{{ $message }}</p> @enderror
@error('occupants.*') <p class="md:col-span-2 text-sm text-rose-600">{{ $message }}</p> @enderror
