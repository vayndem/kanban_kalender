@props([
    'title',
    'icon',
    'count' => null,
    'tone' => 'info',
    'hint' => null,
    'scroll' => 'max-h-52',
])

@php
    $tones = [
        'error' => ['bar' => 'bg-error', 'chip' => 'bg-error text-error-content', 'text' => 'text-error', 'wash' => 'bg-error/10'],
        'warning' => ['bar' => 'bg-warning', 'chip' => 'bg-warning text-warning-content', 'text' => 'text-warning', 'wash' => 'bg-warning/10'],
        'accent' => ['bar' => 'bg-accent', 'chip' => 'bg-accent text-accent-content', 'text' => 'text-accent', 'wash' => 'bg-accent/10'],
        'info' => ['bar' => 'bg-info', 'chip' => 'bg-info text-info-content', 'text' => 'text-info', 'wash' => 'bg-info/10'],
        'secondary' => ['bar' => 'bg-secondary', 'chip' => 'bg-secondary text-secondary-content', 'text' => 'text-secondary', 'wash' => 'bg-secondary/10'],
        'neutral' => ['bar' => 'bg-neutral', 'chip' => 'bg-neutral text-neutral-content', 'text' => 'text-neutral', 'wash' => 'bg-neutral/10'],
    ];
    $t = $tones[$tone] ?? $tones['info'];
@endphp

<div class="app-card-hover flex flex-col overflow-hidden">
    <div class="h-1 {{ $t['bar'] }}"></div>

    <div class="flex items-center gap-3 border-b border-base-300 {{ $t['wash'] }} px-4 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-btn {{ $t['chip'] }} shadow-sm">
            <i class="fas {{ $icon }}"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-black leading-snug {{ $t['text'] }}">{{ $title }}</p>
            @isset($count)
                <p class="text-lg font-black leading-tight text-base-content">{{ $count }}</p>
            @endisset
        </div>
    </div>

    @if ($hint)
        <p class="border-b border-base-300 px-4 py-2 text-[11px] text-base-content/50">{{ $hint }}</p>
    @endif

    <div class="{{ $scroll }} flex-1 space-y-1.5 overflow-y-auto p-3">
        {{ $slot }}
    </div>
</div>
