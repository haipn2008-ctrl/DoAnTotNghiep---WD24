<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UtilityReading;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

    private function readingsForContracts(Collection $contracts): Builder
    {
        return UtilityReading::query()->where(function ($query) use ($contracts) {
            if ($contracts->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }

            foreach ($contracts as $contract) {
                $start = $contract->start_date;
                $end = $contract->actual_end_date ?? $contract->extend_end_date ?? $contract->end_date;

                $query->orWhere(function ($query) use ($contract, $start, $end) {
                    $query->where('room_id', $contract->room_id)
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
