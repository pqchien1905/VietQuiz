{{-- Dashboard layout for teacher/student pages --}}
@extends('layouts.app')

@php
    $role = $role ?? auth()->user()->role ?? 'teacher';
@endphp

@push('styles')
<style>
  .activity-bar { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; flex: 1; }
  .activity-bar-inner { width: 100%; max-width: 2rem; border-radius: 4px 4px 0 0; background: linear-gradient(to top, var(--primary), color-mix(in srgb, var(--primary) 60%, var(--info))); transition: height var(--transition-slow); }
  .activity-bar-label { font-size: 0.65rem; color: var(--muted-foreground); }
  .chart-bars { display: flex; align-items: flex-end; gap: 0.375rem; height: 100px; padding-bottom: 0.25rem; }
  .quick-action { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--card); cursor: pointer; transition: all var(--transition-fast); text-decoration: none; color: var(--foreground); }
  .quick-action:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); border-color: var(--primary); }
  .quick-action-icon { width: 2.5rem; height: 2.5rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .task-item { display:flex; align-items:flex-start; gap:0.75rem; padding:0.875rem 0; border-top:1px solid var(--border); position:relative; }
  .task-item::before { content:''; position:absolute; left:-1.5rem; right:-1.5rem; top:0; border-top:1px solid var(--border); }
  .task-item:first-child::before { display:none; }
  .task-icon { width:2.25rem; height:2.25rem; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .activity-item { display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0; border-top:1px solid var(--border); position:relative; }
  .activity-item::before { content:''; position:absolute; left:-1.5rem; right:-1.5rem; top:0; border-top:1px solid var(--border); }
  .activity-item:first-child::before { display:none; }
</style>
@endpush

@section('body')
<div class="app-shell">
  @include('components.sidebar', ['role' => $role])

  <div class="main-container">
    @include('components.header', ['role' => $role])

    <main class="main-content" id="main-content">
      @yield('content')
    </main>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Page initialization
  document.body.classList.add('page-enter');
</script>
@endpush
