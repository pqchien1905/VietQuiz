@php
  $promotion = $promotion ?? null;
@endphp

<div class="promotion-modal-grid">
  <div class="form-group">
    <label class="label">Mã</label>
    <input class="input" name="code" value="{{ old('code', $promotion?->code) }}" placeholder="WELCOME20" required>
  </div>
  <div class="form-group">
    <label class="label">Tên</label>
    <input class="input" name="name" value="{{ old('name', $promotion?->name) }}" placeholder="Ưu đãi học viên mới" required>
  </div>
  <div class="form-group">
    <label class="label">Đối tượng VIP</label>
    <select class="input select" name="audience">
      @foreach($vipAudienceLabels as $value => $label)
        <option value="{{ $value }}" @selected(old('audience', $promotion?->audience ?? 'all') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label">Gói VIP</label>
    <select class="input select" name="vip_plan">
      <option value="">Không giới hạn VIP</option>
      @foreach($vipPlanLabels as $value => $label)
        <option value="{{ $value }}" @selected(old('vip_plan', $promotion?->vip_plan) === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label">Loại giảm</label>
    <select class="input select" name="discount_type">
      @foreach($discountTypes as $value => $label)
        <option value="{{ $value }}" @selected(old('discount_type', $promotion?->discount_type ?? 'percentage') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label">Giá trị</label>
    <input class="input" name="discount_value" type="number" step="0.01" min="0" value="{{ old('discount_value', $promotion?->discount_value ?? 10) }}" required>
  </div>
  <div class="form-group">
    <label class="label">Giới hạn lượt dùng</label>
    <input class="input" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $promotion?->usage_limit) }}">
  </div>
  <div class="form-group">
    <label class="label">Đã dùng</label>
    <input class="input" name="used_count" type="number" min="0" value="{{ old('used_count', $promotion?->used_count ?? 0) }}">
  </div>
  <div class="form-group">
    <label class="label">Trạng thái</label>
    <select class="input select" name="status">
      @foreach($statusLabels as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $promotion?->status ?? 'active') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group">
    <label class="label">Bắt đầu</label>
    <input class="input" name="starts_at" type="datetime-local" value="{{ old('starts_at', $promotion?->starts_at?->format('Y-m-d\TH:i')) }}">
  </div>
  <div class="form-group">
    <label class="label">Kết thúc</label>
    <input class="input" name="ends_at" type="datetime-local" value="{{ old('ends_at', $promotion?->ends_at?->format('Y-m-d\TH:i')) }}">
  </div>
  <div class="form-group full">
    <label class="label">Mô tả</label>
    <textarea class="input" name="description" rows="3">{{ old('description', $promotion?->description) }}</textarea>
  </div>
</div>
