<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-base-300 bg-base-100/90 shadow-xs backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-10 w-10" />
                        <span class="hidden lg:block"><strong class="block text-sm font-black tracking-tight text-base-content">E-ling Admin</strong><small class="block text-[11px] font-bold uppercase tracking-[.16em] text-primary">Course Manager</small></span>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('jadwal.kalender')" :active="request()->routeIs('jadwal.kalender')">
                        {{ __('Kanban') }}
                    </x-nav-link>

                    <x-nav-link :href="url('/')" :active="request()->is('/')">
                        {{ __('Halaman Depan') }}
                    </x-nav-link>

                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-field border border-base-300 bg-base-200 px-3 py-2 text-sm font-bold text-base-content transition hover:border-primary/50 hover:text-primary">
                            <div class="flex h-7 w-7 items-center justify-center rounded-field brand-chip text-xs font-black">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</div>
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" :aria-expanded="open" aria-label="Buka menu navigasi"
                    class="btn btn-square btn-ghost text-base-content/60 hover:text-base-content">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('jadwal.kalender')" :active="request()->routeIs('jadwal.kalender')">
                {{ __('Kanban') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="url('/')" :active="request()->is('/')">
                {{ __('Halaman Depan') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-base-300">
            <div class="flex items-center gap-3 px-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-field brand-chip text-sm font-black">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="min-w-0">
                    <div class="font-bold text-base text-base-content break-words">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-base-content/60 break-words">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
