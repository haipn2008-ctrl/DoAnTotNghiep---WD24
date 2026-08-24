<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Rules\AdultDateOfBirth;
use App\Services\ContractIdentityDocumentService;
use App\Services\ContractTenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractTenantController extends Controller
{
    public function __construct(
        private readonly ContractTenantService $members,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function store(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'identity_number' => ['required', 'digits:12'],
            'identity_front' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ], [
            'identity_number.required' => 'Vui lòng nhập số CCCD của người thuê.',
            'identity_number.required_with' => 'Vui lòng nhập số CCCD trước khi tải ảnh căn cước.',
            'identity_front.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'identity_front.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
            'identity_back.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'identity_back.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
            'phone.required' => 'Vui lòng nhập số điện thoại người thuê.',
            'phone.regex' => 'Số điện thoại người thuê phải gồm từ 10 đến 15 chữ số.',
        ]);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($contract, $request, $data, &$storedPaths): void {
                $member = $this->members->declareByTenant($contract, $request->user(), $data);
                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->identityDocuments->storePair(
                        $member,
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

        return back()->with('success', 'Đã gửi khai báo người thuê để admin duyệt.');
    }

    public function withdraw(Request $request, Contract $contract, ContractTenant $member)
    {
        abort_unless((int) $member->contract_id === (int) $contract->id, 404);
        $this->members->withdrawByTenant($member, $request->user());

        return back()->with('success', 'Đã rút khai báo đang chờ duyệt.');
    }
}
