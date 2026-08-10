<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UtilityReading;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UtilityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'year' => 'nullable|integer|between:2000,2100',
        ]);

        $contracts = $request->user()->tenant?->contracts()->with('room')->get() ?? collect();
        $query = $this->readingsForContracts($contracts)
            ->with(['room', 'invoice.details']);

        $query->when($filters['year'] ?? null, fn ($query, $year) => $query->where('year', $year));

        $readings = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('room_id')
            ->paginate(12)
            ->withQueryString();

        $years = $this->readingsForContracts($contracts)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('client.utilities.index', compact('readings', 'years'));
    }

    public function image(Request $request, int $reading, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['electricity', 'water'], true), 404);
        $contracts = $request->user()->tenant?->contracts()->get() ?? collect();
        $reading = $this->readingsForContracts($contracts)->findOrFail($reading);
        $path = $reading->{$type.'_image'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function readingsForContracts(Collection $contracts): Builder
    {
        return UtilityReading::query()->where(function ($query) use ($contracts) {
            if ($contracts->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('contract_id', $contracts->pluck('id'));

            // Tương thích dữ liệu cũ chưa gắn contract_id. Chỉ dùng phòng và thời gian
            // cho các bản ghi legacy, không để lịch sử người thuê trước lọt vào hợp đồng mới.
            foreach ($contracts as $contract) {
                $start = $contract->start_date;
                $end = $contract->actual_end_date ?? $contract->extend_end_date ?? $contract->end_date;

                $query->orWhere(function ($query) use ($contract, $start, $end) {
                    $query->whereNull('contract_id')
                        ->where('room_id', $contract->room_id)
                        ->where(function ($query) use ($start) {
                            $query->where('year', '>', $start->year)
                                ->orWhere(fn ($query) => $query->where('year', $start->year)->where('month', '>=', $start->month));
                        })
                        ->where(function ($query) use ($end) {
                            $query->where('year', '<', $end->year)
                                ->orWhere(fn ($query) => $query->where('year', $end->year)->where('month', '<=', $end->month));
                        });
                });
            }
        });
    }
}
