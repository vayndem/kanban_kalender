@props(['icon' => 'fa-user', 'tone' => 'text-base-content/40'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-btn bg-base-200/70 px-2.5 py-1.5 text-xs transition hover:bg-base-200']) }}>
    <i class="fas {{ $icon }} text-[10px] {{ $tone }}"></i>
    {{ $slot }}
</div>
