<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Hiển thị danh sách hợp đồng
     */
    public function index(Request $request)
    {
        $query = Contract::with(['room', 'tenant']);

        // Tìm kiếm
        if ($request->keyword) {

            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {

                // Tìm theo HD001
                if (strtoupper(substr($keyword, 0, 2)) == 'HD') {

                    $id = (int) substr($keyword, 2);

                    $q->orWhere('id', $id);
                }

                // Tìm theo ID thường
                $q->orWhere('id', $keyword)

                    // Tìm theo tên người thuê
                    ->orWhereHas('tenant', function ($tenant) use ($keyword) {
                        $tenant->where(
                            'full_name',
                            'like',
                            '%' . $keyword . '%'
                        );
                    })

                    // Tìm theo mã phòng
                    ->orWhereHas('room', function ($room) use ($keyword) {
                        $room->where(
                            'room_code',
                            'like',
                            '%' . $keyword . '%'
                        );
                    });
            });
        }

        // Lọc trạng thái
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $contracts = $query
            ->latest()
            ->get();

        return view(
            'admin.contracts.index',
            compact('contracts')
        );
    }
    /**
     * Form tạo hợp đồng
     */
    public function create()
    {
        // chỉ lấy phòng đang trống
        $rooms = Room::where('status', 'available')->get();

        // lấy danh sách người thuê
        $tenants = Tenant::select('id', 'full_name as name')->get();

        return view('admin.contracts.create', compact('rooms', 'tenants'));
    }
    /**
     * Lưu hợp đồng
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'tenant_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $room = Room::find($request->room_id);

        if (!$room) {
            return back()->with('error', 'Không tìm thấy phòng');
        }

        // ❌ check phòng
        if ($room->status !== 'available') {
            return back()->with('error', 'Phòng đang có người thuê');
        }

        // ❌ check contract active (QUAN TRỌNG)
        $exists = Contract::where('room_id', $request->room_id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Phòng này đã có hợp đồng đang hoạt động');
        }

        // tạo hợp đồng
        Contract::create([
            'room_id' => $request->room_id,
            'tenant_id' => $request->tenant_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'deposit_amount' => $request->deposit_amount ?? 0,
            'status' => 'active'
        ]);

        // 🔥 UPDATE ĐÚNG: phòng phải thành RENTED
        $room->update([
            'status' => 'available'
        ]);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công');
    }
    /**
     * Chi tiết hợp đồng
     */
    public function show(Contract $contract)
    {
        return view(
            'admin.contracts.show',
            compact('contract')
        );
    }
    /**
     * Form sửa hợp đồng
     */
    public function edit(Contract $contract)
    {
        return view(
            'admin.contracts.edit',
            compact('contract')
        );
    }

    // Cập nhật thông tin người thuê
    public function update(Request $request, Contract $contract)
    {
        $request->validate([
            'full_name' => 'required|max:255',
            'cccd'      => 'required|max:255',
            'phone'     => 'required|max:255',
            'email'     => 'nullable|email',
            'address'   => 'nullable',
        ]);

        $contract->tenant->update([
            'full_name' => $request->full_name,
            'cccd'      => $request->cccd,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'address'   => $request->address,
        ]);
        return redirect()
            ->route('admin.contracts.show', $contract->id)
            ->with(
                'success',
                'Cập nhật thông tin người thuê thành công'
            );
    }

    public function end($id)
    {
        $contract = Contract::findOrFail($id);


        // kiểm tra hợp đồng
        if ($contract->status == 'ended') {
            return back()->with(
                'error',
                'Hợp đồng đã kết thúc'
            );
        }


        // cập nhật hợp đồng
        $contract->update([
            'status' => 'ended'
        ]);


        // trả phòng về trống
        $room = Room::find($contract->room_id);

        if ($room) {
            $room->update([
                'status' => 'empty'
            ]);
        }


        return back()->with(
            'success',
            'Kết thúc hợp đồng thành công'
        );
    }
    public function print($id)
    {
        $contract = Contract::with([
            'room',
            'tenant'
        ])->findOrFail($id);

        return view(
            'admin.contracts.print',
            compact('contract')
        );
    }
}
