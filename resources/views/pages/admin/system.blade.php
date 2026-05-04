@extends('layouts.admin')

@section('title', 'Admin - Hệ thống')
@section('page-title', 'Hệ thống')
@section('page-description', 'Tổng hợp cấu hình môi trường, lưu trữ và số liệu nền của ứng dụng.')

@section('content')
<section class="admin-grid-2">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Môi trường</h2>
        <p class="card-description">Thông tin vận hành ứng dụng hiện tại.</p>
      </div>
    </div>
    <div class="card-content">
      @foreach($system as $label => $value)
        <div class="activity-item">
          <div style="flex:1;">
            <div class="admin-row-title">{{ $label }}</div>
            <div class="admin-row-meta">{{ $value }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Dữ liệu nền</h2>
        <p class="card-description">Các bảng liên kết quan trọng trong hệ thống học tập.</p>
      </div>
    </div>
    <div class="card-content">
      @foreach($totals as $label => $value)
        <div class="activity-item">
          <div style="flex:1;">
            <div class="admin-row-title">{{ $label }}</div>
            <div class="admin-row-meta">{{ number_format($value) }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
