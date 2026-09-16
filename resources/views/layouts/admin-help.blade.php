@php
    $helpContent = App\Support\PusatBantuan::untuk(
        request()->route()?->getName(),
        request()->string('tab')->toString()
    );
@endphp

<div x-data="{ open: false, content: @js($helpContent) }">
    <button
        type="button"
        @click="open = true"
        class="group fixed bottom-5 right-5 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-primary to-accent text-white shadow-xl shadow-success/20 transition hover:scale-105 hover:shadow-2xl hover:shadow-success/20 focus:outline-hidden focus:ring-4 focus:ring-primary dark:focus:ring-primary"
        aria-label="Buka pusat bantuan"
    >
        <span class="text-2xl font-black">?</span>
    </button>

    <template x-if="open">
    <div
        x-transition.opacity
        class="fixed inset-0 z-[210] flex items-end justify-end bg-base-200/45 p-4 backdrop-blur-xs sm:items-center sm:justify-center"
    >
        <div @click="open = false" class="absolute inset-0"></div>

        <div
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-xl overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-2xl"
        >
            <div class="bg-gradient-to-r from-primary to-accent px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-white/75">Pusat Bantuan</p>
                        <h3 class="mt-2 text-xl font-black" x-text="content.title"></h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/85" x-text="content.summary"></p>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="rounded-full bg-white/15 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/25"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-6">
                <template x-for="section in content.sections" :key="section.title">
                    <section class="rounded-2xl border border-base-300 bg-base-200 p-4">
                        <h4 class="text-sm font-black uppercase tracking-[0.18em] text-success" x-text="section.title"></h4>
                        <ul class="mt-3 space-y-2 text-sm text-base-content/80">
                            <template x-for="item in section.items" :key="item">
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 h-2 w-2 flex-none rounded-full bg-success"></span>
                                    <span x-text="item"></span>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>
        </div>
    </div>
    </template>
</div>
