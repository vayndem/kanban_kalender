<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h2 class="text-lg font-black tracking-tight text-base-content">Masuk ke akun Anda</h2>
        <p class="mt-1 text-sm text-base-content/60">Gunakan email dan password yang diberikan admin.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ lihatPassword: false }" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative">
                <i class="fas fa-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/60"></i>
                <x-text-input id="email" class="block w-full pl-10" type="email" name="email" :value="old('email')"
                    placeholder="nama@email.com" required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative">
                <i class="fas fa-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/60"></i>
                <x-text-input id="password" class="block w-full pl-10 pr-11" ::type="lihatPassword ? 'text' : 'password'"
                    type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
                <button type="button" @click="lihatPassword = !lihatPassword"
                    :aria-label="lihatPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-field p-2 text-base-content/60 transition hover:bg-base-200 hover:text-base-content">
                    <i class="fas" :class="lihatPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" class="checkbox checkbox-primary checkbox-sm" name="remember">
                <span class="text-sm font-semibold text-base-content/70">{{ __('Ingat saya') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('password.request') }}">
                    {{ __('Lupa password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            <i class="fas fa-right-to-bracket mr-2"></i> {{ __('Masuk') }}
        </x-primary-button>
    </form>
</x-guest-layout>
