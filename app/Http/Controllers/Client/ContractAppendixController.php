<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ContractAppendix;
use App\Services\AdminNotificationService;
use App\Services\ContractAppendixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractAppendixController extends Controller
{
    public function __construct(private readonly ContractAppendixService $appendices) {}

    public function show(Request $request, ContractAppendix $appendix)
    {
        $appendix = $this->owned($request, $appendix)->load(['contract.room', 'contract.tenant', 'sender']);

        return view('client.contracts.appendices.show', compact('appendix'));
    }

    public function accept(Request $request, ContractAppendix $appendix)
    {
        $request->validate(['confirmation' => ['accepted']]);
        $appendix = $this->appendices->accept($this->owned($request, $appendix), $request->user());
        app(AdminNotificationService::class)->appendixResponded($appendix, true);

        $message = $appendix->isRoomTransfer()
            ? 'Bạn đã đồng ý phụ lục. Phòng thuê chưa thay đổi; phụ lục đang chờ hai bên ký và hoàn tất bàn giao.'
            : 'Bạn đã chấp nhận phụ lục. Nội dung phụ lục đã được ghi nhận.';

        return redirect()->route('client.contract-appendices.show', $appendix)->with('success', $message);
    }

    public function reject(Request $request, ContractAppendix $appendix)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $appendix = $this->appendices->reject(
            $this->owned($request, $appendix),
            $request->user(),
            $data['rejection_reason']
        );
        app(AdminNotificationService::class)->appendixResponded($appendix, false);

        return redirect()->route('client.contract-appendices.show', $appendix)
            ->with('success', 'Đã gửi lý do từ chối cho ban quản lý xem xét.');
    }

    public function signedEvidence(Request $request, ContractAppendix $appendix, int $index)
    {
        $appendix = $this->owned($request, $appendix);
        $path = $appendix->signed_evidence_paths[$index] ?? null;
        abort_unless(
            is_string($path)
            && str_starts_with($path, 'contract-appendices/signed/')
            && Storage::disk('local')->exists($path),
            404
        );

        return Storage::disk('local')->response($path, 'phu-luc-da-ky-'.($index + 1).'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function owned(Request $request, ContractAppendix $appendix): ContractAppendix
    {
        return ContractAppendix::query()
            ->whereKey($appendix->id)
            ->where('status', '!=', ContractAppendix::STATUS_DRAFT)
            ->whereHas('contract', fn ($query) => $query->managedBy($request->user()))
            ->firstOrFail();
    }
}
