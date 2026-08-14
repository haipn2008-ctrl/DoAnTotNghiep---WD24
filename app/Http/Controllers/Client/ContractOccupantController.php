<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Services\ContractOccupantService;
use App\Services\ContractIdentityDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContractOccupantController extends Controller
{
    public function __construct(
        private readonly ContractOccupantService $occupants,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function store(Request $request, Contract $contract)
    {
        $isMinor = false;
        try {
            $isMinor = filled($request->input('date_of_birth'))
                && Carbon::parse($request->input('date_of_birth'))->age < 14;
        } catch (\Throwable) {
            // Quy tắc date bên dưới sẽ trả thông báo định dạng phù hợp.
        }
        $requiresIdentityImages = ! $isMinor || filled($request->input('identity_number'));
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identity_number' => [Rule::requiredIf(! $isMinor), 'nullable', 'required_with:identity_front,identity_back', 'digits:12'],
            'identity_front' => [Rule::requiredIf($requiresIdentityImages), 'nullable', 'required_with:identity_back', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => [Rule::requiredIf($requiresIdentityImages), 'nullable', 'required_with:identity_front', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'identity_number.required' => 'Người từ đủ 14 tuổi cần nhập số CCCD.',
            'identity_number.required_with' => 'Vui lòng nhập số CCCD trước khi tải ảnh căn cước.',
            'identity_front.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'identity_front.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
            'identity_back.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'identity_back.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
        ]);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($contract, $request, $data, &$storedPaths): void {
                $occupant = $this->occupants->declareByTenant($contract, $request->user(), $data);
                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->identityDocuments->storePair(
                        $occupant,
                        $data['identity_front'],
                        $data['identity_back'],
                        $request->user(),
                        $storedPaths,
                    );
                }
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
