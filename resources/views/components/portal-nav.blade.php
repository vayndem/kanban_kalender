@props(['icon', 'title', 'subtitle' => null, 'wide' => false])

<nav class="sticky top-0 z-50 border-b border-base-300 bg-base-100/90 shadow-xs backdrop-blur-xl">
    <div class="{{ $wide ? 'max-w-7xl' : 'max-w-6xl' }} mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-3 py-2">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-field bg-gradient-to-br from-primary to-accent text-white shadow-xs">
                    <i class="fas {{ $icon }}"></i>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-black text-base-content">{{ $title }}</p>
                    @if ($subtitle)
                        <p class="truncate text-xs text-base-content/60">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                {{ $slot }}

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-xs text-error hover:bg-error/10">
                        <i class="fas fa-right-from-bracket"></i><span class="hidden sm:inline"> Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
