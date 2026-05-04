@extends('layouts.admin')

@section('title', 'Admin - Thùng rác')
@section('page-title', 'Thùng rác')
@section('page-description', 'Khôi phục dữ liệu đã xóa mềm trong các module quản trị chính.')

@section('content')
<section class="stats-grid stats-grid-4">
  @foreach($summary as $label => $value)
    <div class="stat-card"><div class="stat-card__label">{{ $label }}</div><div class="stat-card__value">{{ number_format($value) }}</div></div>
  @endforeach
</section>

<section class="card">
  <div class="card-header"><form method="GET" class="admin-toolbar"><div class="form-group"><label class="label">Loại dữ liệu</label><select class="input select" name="type"><option value="all">Tất cả</option>@foreach(array_keys($summary) as $key)<option value="{{ $key }}" @selected($type === $key)>{{ \App\Support\AdminLabels::trashType($key) }}</option>@endforeach</select></div><button class="btn btn-primary">Lọc</button><a class="btn btn-outline" href="{{ route('admin.trash') }}">Đặt lại</a></form></div>
  <div class="table-wrapper" style="border:none;border-radius:0;"><table><thead><tr><th>Loại</th><th>Dữ liệu</th><th>ID</th><th>Xóa lúc</th><th></th></tr></thead><tbody>
    @forelse($items as $item)
      <tr><td><span class="badge badge-outline">{{ $item['label'] }}</span></td><td><div class="admin-row-title">{{ \Illuminate\Support\Str::limit($item['title'], 120) }}</div></td><td>#{{ $item['id'] }}</td><td>{{ $item['deleted_at']?->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('admin.trash.restore', [$item['type'], $item['id']]) }}">@csrf<button class="btn btn-outline-primary btn-sm">Khôi phục</button></form></td></tr>
    @empty <tr><td colspan="5" class="empty-state">Thùng rác trống.</td></tr> @endforelse
  </tbody></table></div>
</section>
@endsection
