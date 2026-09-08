@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm font-semibold text-error space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><i class="fas fa-circle-exclamation mt-0.5 text-xs"></i><span>{{ $message }}</span></li>
        @endforeach
    </ul>
@endif
