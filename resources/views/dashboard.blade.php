@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">
            Ringkasan data aplikasi restoran.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted">Jumlah Product</div>
                <div class="display-5 fw-semibold">
                    {{ $jumlahProduk }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted">Jumlah Menu</div>
                <div class="display-5 fw-semibold">
                    {{ $jumlahMenu }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted">Total Penjualan</div>
                <div class="display-5 fw-semibold">
                    {{ $totalPenjualan }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted">Total Profit</div>
                <div class="display-5 fw-semibold">
                    {{ $totalProfit }}
                </div>
            </div>
        </div>
</div>
@endsection
