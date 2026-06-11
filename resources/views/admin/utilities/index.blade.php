@extends('layouts.admin.index')

@section('title', 'Quản lý điện / nước')

@section('content')
      <div class="container-fluid">
         <div class="row mb-4">
            <div class="col-sm-6">
               <h4 class="mb-sm-0 font-size-18">Kiểm tra chỉ số Điện/Nước</h4>
            </div>
            <div class="col-sm-6">
               <form action="{{ route('admin.utilities.index') }}" method="GET" class="d-flex justify-content-end align-items-center">
                  <label class="me-2 mb-0">Kỳ:</label>
                  <select name="month" class="form-select form-select-sm w-auto me-2">
                     @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                     @endfor
                  </select>
                  <select name="year" class="form-select form-select-sm w-auto me-2">
                     @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                     @endfor
                  </select>
                  <button type="submit" class="btn btn-sm btn-primary">Lọc</button>
               </form>
            </div>
         </div>

         <div class="row">
            <div class="col-xl-4 col-md-4">
               <div class="card card-h-100">
                  <div class="card-body">
                     <span class="text-muted mb-3 lh-1 d-block text-truncate">Tổng điện tiêu thụ</span>
                     <h4 class="mb-1 text-primary">
                        {{ number_format($totalElectricity) }} <span class="font-size-16 text-muted">kWh</span>
                     </h4>
                  </div>
               </div>
            </div>

            <div class="col-xl-4 col-md-4">
               <div class="card card-h-100">
                  <div class="card-body">
                     <span class="text-muted mb-3 lh-1 d-block text-truncate">Tổng nước tiêu thụ</span>
                     <h4 class="mb-1 text-info">
                        {{ number_format($totalWater) }} <span class="font-size-16 text-muted">Khối</span>
                     </h4>
                  </div>
               </div>
            </div>

            <div class="col-xl-4 col-md-4">
               <div class="card card-h-100">
                  <div class="card-body">
                     <span class="text-muted mb-3 lh-1 d-block text-truncate">Tiến độ chốt số</span>
                     <h4 class="mb-1 {{ $roomsRead < $totalRooms ? 'text-warning' : 'text-success' }}">
                        {{ $roomsRead }} / {{ $totalRooms }} <span class="font-size-16 text-muted">Phòng</span>
                     </h4>
                  </div>
               </div>
            </div>
         </div>

         <div class="row mt-3">
            <div class="col-12">
               <div class="card">
                  <div class="card-header bg-white">
                     <h5 class="card-title mb-0">Chi tiết các phòng đã nhập</h5>
                  </div>
                  <div class="card-body px-0 pt-0">
                     <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-hover mb-0">
                           <thead class="table-light">
                              <tr>
                                 <th>Tên Phòng</th>
                                 <th class="text-center">Số Điện Cũ</th>
                                 <th class="text-center">Số Điện Mới</th>
                                 <th class="text-center text-primary">Dùng (kWh)</th>
                                 <th class="text-center">Số Nước Cũ</th>
                                 <th class="text-center">Số Nước Mới</th>
                                 <th class="text-center text-info">Dùng (Khối)</th>
                              </tr>
                           </thead>
                           <tbody>
                              @forelse($readings as $item)
                                 @php
                                     $dienDung = $item->electricity_new - $item->electricity_old;
                                     $nuocDung = $item->water_new - $item->water_old;
                                 @endphp
                                 <tr>
                                    <td class="fw-bold">{{ $item->room->room_code ?? 'Phòng trống' }}</td>
                                    <td class="text-center">{{ $item->electricity_old }}</td>
                                    <td class="text-center fw-bold">{{ $item->electricity_new }}</td>
                                    <td class="text-center text-primary fw-bold">+ {{ $dienDung }}</td>
                                    <td class="text-center">{{ $item->water_old }}</td>
                                    <td class="text-center fw-bold">{{ $item->water_new }}</td>
                                    <td class="text-center text-info fw-bold">+ {{ $nuocDung }}</td>
                                 </tr>
                              @empty
                                 <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                       Chưa có dữ liệu chốt số cho tháng {{ $month }}/{{ $year }}. 
                                       <br>
                                       <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-primary mt-2">Nhập số ngay</a>
                                    </td>
                                 </tr>
                              @endforelse
                           </tbody>
                        </table>
                     </div>
                  </div>
               </div>
            </div>
         </div>

      </div> 
@endsection
