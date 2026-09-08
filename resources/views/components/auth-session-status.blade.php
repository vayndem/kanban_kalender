@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success text-sm font-semibold']) }}>
        <i class="fas fa-circle-check"></i>
        <span>{{ $status }}</span>
    </div>
@endif
