<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Services\ContractOccupantService;
use App\Services\ContractIdentityDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractOccupantController extends Controller
{
    public function __construct(
        private readonly ContractOccupantService $occupants,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function store(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identity_number' => ['required', 'digits:12'],
            'identity_front' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($contract, $request, $data, &$storedPaths): void {
                $occupant = $this->occupants->declareByTenant($contract, $request->user(), $data);
                $this->identityDocuments->storePair(
                    $occupant,
                    $data['identity_front'],
                    $data['identity_back'],
                    $request->user(),
                    $storedPaths,
                );
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Đã gửi khai báo người ở để admin duyệt.');
    }

    public function withdraw(Request $request, Contract $contract, ContractOccupant $occupant)
    {
        abort_unless((int) $occupant->contract_id === (int) $contract->id, 404);
        $this->occupants->withdrawByTenant($occupant, $request->user());

        return back()->with('success', 'Đã rút khai báo đang chờ duyệt.');
    }
}
