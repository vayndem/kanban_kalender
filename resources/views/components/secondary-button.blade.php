<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-ghost border border-base-300']) }}>
    {{ $slot }}
</button>
