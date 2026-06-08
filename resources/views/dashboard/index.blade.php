@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <h2 class="mb-4">
        Dashboard
    </h2>

    <div class="row">

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5>Tổng phòng</h5>
                    <h2>20</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5>Đã thuê</h5>
                    <h2>15</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5>Phòng trống</h5>
                    <h2>5</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5>Doanh thu tháng</h5>
                    <h2>25M</h2>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
