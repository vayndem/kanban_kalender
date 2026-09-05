@php
    $tabs = [
        'ringkasan' => ['label' => 'Ringkasan', 'icon' => 'fa-chart-line'],
        'jadwal' => ['label' => 'Jadwal Pelajaran', 'icon' => 'fa-calendar-days'],
        'data_siswa' => ['label' => 'Data Siswa', 'icon' => 'fa-user-graduate'],
        'pembayaran' => ['label' => 'Pembayaran', 'icon' => 'fa-wallet'],
    ];

    $extraTabs = [
        'workshop' => ['label' => 'Workshop', 'icon' => 'fa-toolbox', 'route' => 'admin.workshop.index'],
        'modul_ajar' => ['label' => 'Modul Ajar', 'icon' => 'fa-book-open-reader', 'route' => 'modulAjar.index'],
        'akun_guru' => ['label' => 'Akun Guru', 'icon' => 'fa-user-shield', 'route' => 'admin.akunGuru.index'],
    ];
@endphp

<div
    class="mb-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <nav class="flex min-w-max gap-1" aria-label="Tabs">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('dashboard', ['tab' => $key]) }}"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $activeTab === $key ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200 dark:shadow-none' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                <i class="fas {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </a>
        @endforeach

        @foreach ($extraTabs as $key => $tab)
            <a href="{{ route($tab['route']) }}"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $activeTab === $key ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200 dark:shadow-none' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                <i class="fas {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
