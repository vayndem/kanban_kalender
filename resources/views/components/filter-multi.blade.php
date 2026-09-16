@props([
    'label',
    'model',
    'options',
    'noun' => 'item',
    'searchPlaceholder' => 'Cari pilihan...',
])

<div x-data="filterMulti()" @click.outside="tutup()" @keydown.escape.window="tutup()" class="relative">
    <label class="app-label">{{ $label }}</label>

    <button type="button" @click="alih()" :aria-expanded="buka ? 'true' : 'false'"
        class="searchable-select-trigger">
        <span class="searchable-select-trigger-label"
            :class="{{ $model }}.length ? 'font-bold text-primary' : 'text-base-content/70'"
            x-text="{{ $model }}.length ? {{ $model }}.length + ' {{ $noun }} dipilih' : 'Semua {{ $label }}'"></span>
        <i class="fas fa-chevron-down searchable-select-chevron" :class="buka ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="buka" x-cloak class="searchable-select-panel">
        <div class="searchable-select-search-wrap">
            <i class="fas fa-search"></i>
            <input type="search" x-model="cari" placeholder="{{ $searchPlaceholder }}"
                class="searchable-select-input">
        </div>

        <div class="flex items-center justify-between gap-2 border-b border-base-300 px-2 py-1.5">
            <button type="button" class="btn btn-ghost btn-xs"
                @click="{{ $model }} = pilihSemua({{ $model }}, {{ $options }})">
                <i class="fas fa-check-double"></i> Pilih semua tampil
            </button>
            <button type="button" class="btn btn-ghost btn-xs text-error" x-show="{{ $model }}.length"
                @click="{{ $model }} = []">
                Kosongkan
            </button>
        </div>

        <div class="searchable-select-results">
            <template x-for="opsi in hasil({{ $options }})" :key="opsi.value">
                <label class="flex cursor-pointer items-center gap-3 rounded-field px-3 py-2.5 transition hover:bg-primary/10">
                    <input type="checkbox" :value="opsi.value" x-model="{{ $model }}"
                        class="checkbox checkbox-primary shrink-0 sm:checkbox-sm">
                    <span class="min-w-0">
                        <span class="block truncate text-sm text-base-content" x-text="opsi.label"></span>
                        <template x-if="opsi.sub">
                            <span class="block text-[11px] text-base-content/60" x-text="opsi.sub"></span>
                        </template>
                    </span>
                </label>
            </template>

            <p x-show="! hasil({{ $options }}).length"
                class="px-3 py-4 text-center text-xs text-base-content/60">
                Tidak ada pilihan yang cocok.
            </p>
        </div>
    </div>
</div>
