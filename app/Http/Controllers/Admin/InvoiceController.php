<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\UtilityReading;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['contract.room', 'contract.tenant', 'payments']);

        if ($request->filled('month')) {
            $month = (int) $request->month;
            $query->where(function ($q) use ($month) {
                $q->where('month', $month)
                  ->orWhereRaw('MONTH(invoice_date) = ?', [$month]);
            });
        }

        if ($request->filled('year')) {
            $year = (int) $request->year;
            $query->where(function ($q) use ($year) {
                $q->where('year', $year)
                  ->orWhereRaw('YEAR(invoice_date) = ?', [$year]);
            });
        }

        if ($request->filled('room_id')) {
            $query->whereHas('contract.room', function ($q) use ($request) {
                $q->where('id', $request->room_id);
            });
        }

        if ($request->filled('tenant_id')) {
            $query->whereHas('contract.tenant', function ($q) use ($request) {
                $q->where('id', $request->tenant_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest('invoice_date')->paginate(15);
        $rooms = Room::orderBy('room_code')->get();
        $contracts = Contract::with(['room', 'tenant'])->get();

        return view('admin.invoices.index', compact('invoices', 'rooms', 'contracts'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['contract.room', 'contract.tenant', 'payments']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['contract.room', 'contract.tenant']);

        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'required|in:unpaid,partial,paid',
            'note' => 'nullable|string',
        ]);

        $invoice->update($data);

        return redirect()->route('admin.invoices.index')->with('success', 'Cập nhật hóa đơn thành công');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->effective_status !== 'unpaid') {
            return back()->with('error', 'Chỉ có thể xóa hóa đơn chưa thanh toán');
        }

        $invoice->payments()->delete();
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Xóa hóa đơn thành công');
    }

    public function generateForm()
    {
        $contracts = Contract::with(['room', 'tenant'])->where('status', 'active')->get();
        $months = range(1, 12);
        $years = [now()->year, now()->year + 1];

        return view('admin.invoices.generate', compact('contracts', 'months', 'years'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'contract_id' => 'nullable|exists:contracts,id',
        ]);

        $contracts = $request->filled('contract_id')
            ? Contract::where('id', $request->contract_id)->where('status', 'active')->get()
            : Contract::where('status', 'active')->get();

        $setting = Setting::first();
        $created = 0;

        foreach ($contracts as $contract) {
            $exists = Invoice::where('contract_id', $contract->id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->exists();

            if ($exists) {
                continue;
            }

            $utilityReading = UtilityReading::where('room_id', $contract->room_id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->where('status', 'confirmed')
                ->first();

            $roomFee = (float) $contract->monthly_rent;
            $electricityFee = 0;
            $waterFee = 0;
            $internetFee = $setting?->internet_fee ?? 0;
            $serviceFee = $setting?->service_fee ?? 0;

            if ($utilityReading) {
                $electricityFee = ($utilityReading->electricity_usage ?? 0) * ($setting?->electric_price ?? 0);
                $waterFee = ($utilityReading->water_usage ?? 0) * ($setting?->water_price ?? 0);
            }

            $totalAmount = $roomFee + $electricityFee + $waterFee + $internetFee + $serviceFee;

            Invoice::create([
                'contract_id' => $contract->id,
                'utility_reading_id' => $utilityReading?->id,
                'month' => $request->month,
                'year' => $request->year,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'room_fee' => $roomFee,
                'electricity_fee' => $electricityFee,
                'water_fee' => $waterFee,
                'internet_fee' => $internetFee,
                'service_fee' => $serviceFee,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
            ]);

            $created++;
        }

        return redirect()->route('admin.invoices.index', ['month' => $request->month, 'year' => $request->year])
            ->with('success', 'Đã sinh ' . $created . ' hóa đơn cho kỳ ' . $request->month . '/' . $request->year);
    }

    public function payments(Request $request)
    {
        $invoices = Invoice::with(['contract.room', 'contract.tenant'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest('invoice_date')
            ->paginate(15);

        return view('admin.invoices.payments', compact('invoices'));
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,qr',
            'transaction_code' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => $data['amount_paid'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_code' => $data['transaction_code'] ?? null,
            'status' => 'success',
            'confirmed_by' => auth()->id(),
            'note' => $data['note'] ?? null,
        ]);

        $paidAmount = $invoice->payments()->sum('amount_paid');
        $invoice->total_amount = (float) $invoice->total_amount;
        $invoice->status = $paidAmount >= $invoice->total_amount ? 'paid' : 'partial';
        $invoice->save();

        return redirect()->route('admin.invoices.show', $invoice->id)->with('success', 'Đã ghi nhận thanh toán thành công');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['contract.room', 'contract.tenant', 'payments']);

        return view('admin.invoices.print', compact('invoice'));
    }
}
