<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Room;
use App\Models\SupportRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category' => 'nullable|string|in:' . implode(',', array_keys(Expense::categories())),
            'room_id' => 'nullable|integer|exists:rooms,id',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Expense::with(['room', 'supportRequest', 'creator'])
            ->when($filters['category'] ?? null, fn ($q, $cat) => $q->where('category', $cat))
            ->when($filters['room_id'] ?? null, fn ($q, $room) => $q->where('room_id', $room))
            ->when($filters['month'] ?? null, fn ($q, $m) => $q->whereMonth('expense_date', $m))
            ->when($filters['year'] ?? null, fn ($q, $y) => $q->whereYear('expense_date', $y))
            ->when($filters['search'] ?? null, function ($q, $keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('expense_code', 'like', "%{$keyword}%")
                        ->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('payer_name', 'like', "%{$keyword}%")
                        ->orWhere('notes', 'like', "%{$keyword}%");
                });
            });

        $expenses = (clone $query)->orderByDesc('expense_date')->orderByDesc('id')->paginate(15)->withQueryString();

        // Thống kê nhanh
        $now = now();
        $thisMonthExpenses = Expense::whereYear('expense_date', $now->year)->whereMonth('expense_date', $now->month)->sum('amount');
        $thisYearExpenses = Expense::whereYear('expense_date', $now->year)->sum('amount');
        $utilityExpensesThisMonth = Expense::whereYear('expense_date', $now->year)
            ->whereMonth('expense_date', $now->month)
            ->whereIn('category', [Expense::CATEGORY_ELECTRICITY, Expense::CATEGORY_WATER])
            ->sum('amount');
        $maintenanceExpensesThisMonth = Expense::whereYear('expense_date', $now->year)
            ->whereMonth('expense_date', $now->month)
            ->where('category', Expense::CATEGORY_MAINTENANCE)
            ->sum('amount');

        $filteredTotal = (clone $query)->sum('amount');

        $categories = Expense::categories();
        $categoryBadges = Expense::categoryBadges();
        $rooms = Room::orderBy('room_code')->get(['id', 'room_code']);

        return view('admin.expenses.index', compact(
            'expenses',
            'thisMonthExpenses',
            'thisYearExpenses',
            'utilityExpensesThisMonth',
            'maintenanceExpensesThisMonth',
            'filteredTotal',
            'categories',
            'categoryBadges',
            'rooms',
            'filters'
        ));
    }

    public function create(Request $request): View
    {
        $defaultRoomId = $request->query('room_id');
        $defaultSupportRequestId = $request->query('support_request_id');
        $defaultCategory = $request->query('category', Expense::CATEGORY_OTHER);

        if ($defaultSupportRequestId) {
            $supportReq = SupportRequest::with('contract.room')->find($defaultSupportRequestId);
            if ($supportReq) {
                $defaultRoomId = $defaultRoomId ?: $supportReq->contract?->room_id;
                if ($supportReq->category === 'repair') {
                    $defaultCategory = Expense::CATEGORY_MAINTENANCE;
                }
            }
        }

        $categories = Expense::categories();
        $paymentMethods = Expense::paymentMethods();
        $rooms = Room::orderBy('room_code')->get(['id', 'room_code']);

        return view('admin.expenses.create', compact(
            'categories',
            'paymentMethods',
            'rooms',
            'defaultRoomId',
            'defaultSupportRequestId',
            'defaultCategory'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(Expense::categories())),
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000|max:999999999999',
            'expense_date' => 'required|date',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'support_request_id' => 'nullable|integer|exists:support_requests,id',
            'payer_name' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Expense::paymentMethods())),
            'notes' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'category.required' => 'Vui lòng chọn danh mục chi phí.',
            'title.required' => 'Vui lòng nhập tên khoản chi.',
            'amount.required' => 'Vui lòng nhập số tiền chi.',
            'amount.min' => 'Số tiền chi tối thiểu là 1.000đ.',
            'expense_date.required' => 'Vui lòng chọn ngày chi tiền.',
            'receipt_image.max' => 'Ảnh chứng từ tối đa 5MB.',
        ]);

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image')->store('expenses', 'local');
        }

        $data['expense_code'] = Expense::generateExpenseCode($data['expense_date']);
        $data['created_by'] = Auth::id();

        $expense = Expense::create($data);

        return redirect()->route('admin.expenses.index')
            ->with('success', "Đã tạo phiếu chi {$expense->expense_code} thành công.");
    }

    public function edit(Expense $expense): View
    {
        $categories = Expense::categories();
        $paymentMethods = Expense::paymentMethods();
        $rooms = Room::orderBy('room_code')->get(['id', 'room_code']);

        return view('admin.expenses.edit', compact(
            'expense',
            'categories',
            'paymentMethods',
            'rooms'
        ));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(Expense::categories())),
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000|max:999999999999',
            'expense_date' => 'required|date',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'support_request_id' => 'nullable|integer|exists:support_requests,id',
            'payer_name' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Expense::paymentMethods())),
            'notes' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_receipt' => 'nullable|boolean',
        ], [
            'category.required' => 'Vui lòng chọn danh mục chi phí.',
            'title.required' => 'Vui lòng nhập tên khoản chi.',
            'amount.required' => 'Vui lòng nhập số tiền chi.',
            'amount.min' => 'Số tiền chi tối thiểu là 1.000đ.',
            'expense_date.required' => 'Vui lòng chọn ngày chi tiền.',
            'receipt_image.max' => 'Ảnh chứng từ tối đa 5MB.',
        ]);

        if ($request->boolean('remove_receipt')) {
            if ($expense->receipt_image && Storage::disk('local')->exists($expense->receipt_image)) {
                Storage::disk('local')->delete($expense->receipt_image);
            }
            $data['receipt_image'] = null;
        }

        if ($request->hasFile('receipt_image')) {
            if ($expense->receipt_image && Storage::disk('local')->exists($expense->receipt_image)) {
                Storage::disk('local')->delete($expense->receipt_image);
            }
            $data['receipt_image'] = $request->file('receipt_image')->store('expenses', 'local');
        }

        unset($data['remove_receipt']);
        $expense->update($data);

        return redirect()->route('admin.expenses.index')
            ->with('success', "Cập nhật phiếu chi {$expense->expense_code} thành công.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $code = $expense->expense_code;
        if ($expense->receipt_image && Storage::disk('local')->exists($expense->receipt_image)) {
            Storage::disk('local')->delete($expense->receipt_image);
        }

        $expense->delete();

        return redirect()->route('admin.expenses.index')
            ->with('success', "Đã xóa phiếu chi {$code}.");
    }

    public function receipt(Expense $expense): StreamedResponse|BinaryFileResponse
    {
        abort_unless($expense->receipt_image && Storage::disk('local')->exists($expense->receipt_image), 404);

        return Storage::disk('local')->response($expense->receipt_image);
    }
}

