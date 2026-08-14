<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\TemporaryResidence;
use Illuminate\Http\Request;

class TemporaryResidenceController extends Controller
{
    /**
     * Danh sách đăng ký tạm trú.
     */
    public function index(Request $request)
    {
        $query = TemporaryResidence::with([
            'tenant',
            'contract.room',
        ]);

        // Tìm kiếm theo họ tên, số điện thoại hoặc CCCD
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('cccd', 'like', "%{$search}%");
                });
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc từ ngày bắt đầu
        if ($request->filled('start_date')) {
            $query->whereDate(
                'start_date',
                '>=',
                $request->start_date
            );
        }

        // Lọc đến ngày bắt đầu
        if ($request->filled('end_date')) {
            $query->whereDate(
                'start_date',
                '<=',
                $request->end_date
            );
        }

        // Lấy danh sách đăng ký tạm trú
        $temporaryResidences = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.temporary_residences.index',
            compact('temporaryResidences')
        );
    }

    /**
     * Hiển thị form đăng ký tạm trú.
     */
    public function create()
    {
        // Lấy danh sách khách thuê
        $tenants = Tenant::orderBy('full_name')->get();

        // Lấy các hợp đồng phù hợp để đăng ký tạm trú
        $contracts = Contract::with([
            'tenant',
            'room',
        ])
            ->whereIn('status', ['active', 'pending'])
            ->latest()
            ->get();

        return view(
            'admin.temporary_residences.create',
            compact('tenants', 'contracts')
        );
    }

    /**
     * Lưu đăng ký tạm trú.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'exists:tenants,id',
            ],

            'contract_id' => [
                'required',
                'exists:contracts,id',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                'in:pending,active,expired,cancelled',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'tenant_id.required' => 'Vui lòng chọn khách thuê.',
            'tenant_id.exists' => 'Khách thuê không tồn tại.',

            'contract_id.required' => 'Vui lòng chọn hợp đồng.',
            'contract_id.exists' => 'Hợp đồng không tồn tại.',

            'start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',

            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' =>
            'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',

            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        /*
    |--------------------------------------------------------------------------
    | 1. Kiểm tra hợp đồng có tồn tại và thuộc đúng khách thuê
    |--------------------------------------------------------------------------
    */

        $contract = Contract::findOrFail($validated['contract_id']);

        if ((int) $contract->tenant_id !== (int) $validated['tenant_id']) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không thuộc về khách thuê đã chọn.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | 2. Kiểm tra trạng thái hợp đồng
    |--------------------------------------------------------------------------
    |
    | Chỉ cho phép đăng ký tạm trú với hợp đồng đang hoạt động
    | hoặc đang chờ xử lý.
    |
    */

        if (!in_array($contract->status, ['active', 'pending'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không ở trạng thái phù hợp để đăng ký tạm trú.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | 3. Kiểm tra hợp đồng đã có đăng ký tạm trú chưa
    |--------------------------------------------------------------------------
    |
    | Một hợp đồng chỉ có một đăng ký tạm trú đang chờ hoặc đang hoạt động.
    |
    */

        $exists = TemporaryResidence::where(
            'contract_id',
            $validated['contract_id']
        )
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng này đã có đăng ký tạm trú đang chờ hoặc đang hoạt động.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | 4. Tạo đăng ký tạm trú
    |--------------------------------------------------------------------------
    */

        TemporaryResidence::create($validated);

        /*
    |--------------------------------------------------------------------------
    | 5. Quay lại danh sách
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route('admin.temporary_residences.index')
            ->with(
                'success',
                'Đăng ký tạm trú đã được tạo thành công.'
            );
    }
    public function show(TemporaryResidence $temporaryResidence)
    {
        $temporaryResidence->load([
            'tenant',
            'contract.room',
        ]);

        return view(
            'admin.temporary_residences.show',
            compact('temporaryResidence')
        );
    }

    /**
     * Hiển thị form chỉnh sửa.
     */
    public function edit(TemporaryResidence $temporaryResidence)
    {
        $tenants = Tenant::orderBy('name')->get();

        $contracts = Contract::with([
            'tenant',
            'room',
        ])
            ->where(function ($query) use ($temporaryResidence) {
                $query->whereIn('status', ['active', 'pending'])
                    ->orWhere(
                        'id',
                        $temporaryResidence->contract_id
                    );
            })
            ->latest()
            ->get();

        return view(
            'admin.temporary_residences.edit',
            compact(
                'temporaryResidence',
                'tenants',
                'contracts'
            )
        );
    }

    /**
     * Cập nhật đăng ký tạm trú.
     */
    public function update(
        Request $request,
        TemporaryResidence $temporaryResidence
    ) {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'exists:tenants,id',
            ],

            'contract_id' => [
                'required',
                'exists:contracts,id',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                'in:pending,active,expired,cancelled',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'tenant_id.required' => 'Vui lòng chọn khách thuê.',
            'tenant_id.exists' => 'Khách thuê không tồn tại.',

            'contract_id.required' => 'Vui lòng chọn hợp đồng.',
            'contract_id.exists' => 'Hợp đồng không tồn tại.',

            'start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',

            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' =>
            'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',

            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',

            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        /*
         * Kiểm tra hợp đồng có thuộc khách thuê đã chọn hay không.
         */
        $contract = Contract::findOrFail($validated['contract_id']);

        if ((int) $contract->tenant_id !== (int) $validated['tenant_id']) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không thuộc về khách thuê đã chọn.',
                ]);
        }

        /*
         * Không cho phép một hợp đồng có nhiều đăng ký
         * tạm trú đang chờ hoặc đang hoạt động.
         *
         * Bỏ qua chính bản ghi đang chỉnh sửa.
         */
        $exists = TemporaryResidence::where(
            'contract_id',
            $validated['contract_id']
        )
            ->whereIn('status', ['pending', 'active'])
            ->where(
                'id',
                '!=',
                $temporaryResidence->id
            )
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng này đã có đăng ký tạm trú đang hoạt động.',
                ]);
        }

        /*
         * Kiểm tra trạng thái hợp đồng.
         */
        if (
            !in_array($contract->status, ['active', 'pending'])
            && $contract->id != $temporaryResidence->contract_id
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Chỉ được đăng ký tạm trú cho hợp đồng đang chờ hoặc đang hoạt động.',
                ]);
        }

        /*
         * Cập nhật dữ liệu.
         */
        $temporaryResidence->update($validated);

        return redirect()
            ->route('admin.temporary_residences.index')
            ->with(
                'success',
                'Thông tin tạm trú đã được cập nhật thành công.'
            );
    }

    /**
     * Xóa đăng ký tạm trú.
     */
    public function destroy(TemporaryResidence $temporaryResidence)
    {
        $temporaryResidence->delete();

        return redirect()
            ->route('admin.temporary_residences.index')
            ->with(
                'success',
                'Đăng ký tạm trú đã được xóa thành công.'
            );
    }
}
