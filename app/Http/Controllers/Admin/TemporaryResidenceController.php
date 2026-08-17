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

        /*
        |--------------------------------------------------------------------------
        | Tìm kiếm
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'cccd',
                            'like',
                            "%{$search}%"
                        );
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc trạng thái
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày bắt đầu từ
        |--------------------------------------------------------------------------
        */
        if ($request->filled('start_date')) {
            $query->whereDate(
                'start_date',
                '>=',
                $request->input('start_date')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lọc ngày bắt đầu đến
        |--------------------------------------------------------------------------
        */
        if ($request->filled('end_date')) {
            $query->whereDate(
                'start_date',
                '<=',
                $request->input('end_date')
            );
        }

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
        /*
        |--------------------------------------------------------------------------
        | Danh sách khách thuê
        |--------------------------------------------------------------------------
        */
        $tenants = Tenant::orderBy('full_name')->get();

        /*
        |--------------------------------------------------------------------------
        | Danh sách hợp đồng có thể đăng ký tạm trú
        |--------------------------------------------------------------------------
        */
        $contracts = Contract::with([
            'tenant',
            'room',
        ])
            ->whereIn(
                'status',
                ['active', 'pending']
            )
            ->latest()
            ->get();

        return view(
            'admin.temporary_residences.create',
            compact(
                'tenants',
                'contracts'
            )
        );
    }

    /**
     * Lưu đăng ký tạm trú.
     */
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
            'tenant_id.required' =>
            'Vui lòng chọn khách thuê.',

            'tenant_id.exists' =>
            'Khách thuê không tồn tại.',

            'contract_id.required' =>
            'Vui lòng chọn hợp đồng.',

            'contract_id.exists' =>
            'Hợp đồng không tồn tại.',

            'status.required' =>
            'Vui lòng chọn trạng thái.',

            'status.in' =>
            'Trạng thái không hợp lệ.',

            'note.max' =>
            'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        /*
    |--------------------------------------------------------------------------
    | Lấy hợp đồng
    |--------------------------------------------------------------------------
    */
        $contract = Contract::with([
            'tenant',
            'room',
        ])->findOrFail(
            $validated['contract_id']
        );

        /*
    |--------------------------------------------------------------------------
    | Kiểm tra hợp đồng thuộc đúng khách thuê
    |--------------------------------------------------------------------------
    */
        if (
            (int) $contract->tenant_id
            !==
            (int) $validated['tenant_id']
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không thuộc về khách thuê đã chọn.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Kiểm tra trạng thái hợp đồng
    |--------------------------------------------------------------------------
    */
        if (
            !in_array(
                $contract->status,
                ['active', 'pending'],
                true
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không ở trạng thái phù hợp để đăng ký tạm trú.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Kiểm tra hợp đồng đã có đăng ký tạm trú hay chưa
    |--------------------------------------------------------------------------
    */
        $exists = TemporaryResidence::where(
            'contract_id',
            $validated['contract_id']
        )
            ->whereIn(
                'status',
                ['pending', 'active']
            )
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
    | THỜI GIAN TẠM TRÚ LẤY TRỰC TIẾP TỪ HỢP ĐỒNG
    |--------------------------------------------------------------------------
    */
        if (!$contract->start_date) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng chưa có ngày bắt đầu nên không thể đăng ký tạm trú.',
                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Gán thời gian tạm trú theo thời gian thuê trong hợp đồng
    |--------------------------------------------------------------------------
    */
        $validated['start_date'] = $contract->start_date;
        $validated['end_date'] = $contract->end_date;

        /*
    |--------------------------------------------------------------------------
    | Tạo đăng ký tạm trú
    |--------------------------------------------------------------------------
    */
        TemporaryResidence::create($validated);

        return redirect()
            ->route('admin.temporary_residences.index')
            ->with(
                'success',
                'Đăng ký tạm trú đã được tạo thành công. Thời gian tạm trú được lấy theo thời gian thuê trong hợp đồng.'
            );
    }

    /**
     * Xem chi tiết đăng ký tạm trú.
     */
    public function show(
        TemporaryResidence $temporaryResidence
    ) {
        $temporaryResidence->load([
            'tenant.document',
            'tenant.vehicles',
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
    public function edit(
        TemporaryResidence $temporaryResidence
    ) {
        /*
        |--------------------------------------------------------------------------
        | Lưu ý:
        | tenants dùng full_name, không phải name
        |--------------------------------------------------------------------------
        */
        $tenants = Tenant::orderBy('full_name')->get();

        /*
        |--------------------------------------------------------------------------
        | Lấy hợp đồng đang active/pending
        | và giữ lại hợp đồng hiện tại nếu nó đã đổi trạng thái
        |--------------------------------------------------------------------------
        */
        $contracts = Contract::with([
            'tenant',
            'room',
        ])
            ->where(function ($query) use ($temporaryResidence) {
                $query
                    ->whereIn(
                        'status',
                        ['active', 'pending']
                    )
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
            'tenant_id.required' =>
            'Vui lòng chọn khách thuê.',

            'tenant_id.exists' =>
            'Khách thuê không tồn tại.',

            'contract_id.required' =>
            'Vui lòng chọn hợp đồng.',

            'contract_id.exists' =>
            'Hợp đồng không tồn tại.',

            'start_date.required' =>
            'Vui lòng nhập ngày bắt đầu.',

            'start_date.date' =>
            'Ngày bắt đầu không hợp lệ.',

            'end_date.date' =>
            'Ngày kết thúc không hợp lệ.',

            'end_date.after_or_equal' =>
            'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',

            'status.required' =>
            'Vui lòng chọn trạng thái.',

            'status.in' =>
            'Trạng thái không hợp lệ.',

            'note.max' =>
            'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra hợp đồng
        |--------------------------------------------------------------------------
        */
        $contract = Contract::findOrFail(
            $validated['contract_id']
        );

        if (
            (int) $contract->tenant_id
            !==
            (int) $validated['tenant_id']
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Hợp đồng không thuộc về khách thuê đã chọn.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Không cho phép trùng đăng ký active/pending
        |--------------------------------------------------------------------------
        */
        $exists = TemporaryResidence::where(
            'contract_id',
            $validated['contract_id']
        )
            ->whereIn(
                'status',
                ['pending', 'active']
            )
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
        |--------------------------------------------------------------------------
        | Kiểm tra trạng thái hợp đồng
        |--------------------------------------------------------------------------
        */
        if (
            !in_array(
                $contract->status,
                ['active', 'pending'],
                true
            )
            &&
            $contract->id != $temporaryResidence->contract_id
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                    'Chỉ được đăng ký tạm trú cho hợp đồng đang chờ hoặc đang hoạt động.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cập nhật
        |--------------------------------------------------------------------------
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
    public function destroy(
        TemporaryResidence $temporaryResidence
    ) {
        $temporaryResidence->delete();

        return redirect()
            ->route('admin.temporary_residences.index')
            ->with(
                'success',
                'Đăng ký tạm trú đã được xóa thành công.'
            );
    }
    public function sign(
        Request $request,
        TemporaryResidence $temporaryResidence
    ) {
        $validated = $request->validate([
            'signature' => [
                'required',
                'string',
            ],
        ], [
            'signature.required' => 'Vui lòng ký tên trước khi lưu.',
        ]);

        $temporaryResidence->update([
            'signature' => $validated['signature'],
            'signed_at' => now(),
        ]);

        return back()->with(
            'success',
            'Chữ ký đã được lưu thành công.'
        );
    }
    public function pdf(TemporaryResidence $temporaryResidence)
    {
        $temporaryResidence->load([
            'tenant.document',
            'tenant.vehicles',
            'contract.room',
        ]);

        return view(
            'admin.temporary_residences.pdf',
            compact('temporaryResidence')
        );
    }
}
