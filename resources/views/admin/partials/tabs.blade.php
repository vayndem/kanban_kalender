@php
    $tabs = [
        'ringkasan' => ['label' => 'Ringkasan', 'icon' => 'fa-chart-line'],
        'jadwal' => ['label' => 'Jadwal Pelajaran', 'icon' => 'fa-calendar-days'],
        'data_siswa' => ['label' => 'Data Siswa', 'icon' => 'fa-user-graduate'],
        'pembayaran' => ['label' => 'Pembayaran', 'icon' => 'fa-wallet'],
        'result' => ['label' => 'Result', 'icon' => 'fa-medal', 'route' => 'admin.result.index'],
    ];

    $extraTabs = [
        'workshop' => ['label' => 'Workshop', 'icon' => 'fa-toolbox', 'route' => 'admin.workshop.index'],
        'modul_ajar' => ['label' => 'Modul Ajar', 'icon' => 'fa-book-open-reader', 'route' => 'modulAjar.index'],
        'absen' => ['label' => 'Absen', 'icon' => 'fa-clipboard-user', 'route' => 'absen.index'],
        'payroll' => ['label' => 'Payroll', 'icon' => 'fa-money-check-dollar', 'route' => 'admin.payroll.index'],
        'akun_guru' => ['label' => 'Akun Guru', 'icon' => 'fa-user-shield', 'route' => 'admin.akunGuru.index'],
    ];

    $tautanTab = fn (string $kunci, array $tab) => isset($tab['route'])
        ? route($tab['route'])
        : route('dashboard', ['tab' => $kunci]);
@endphp

<div class="app-card sticky top-16 z-40 mb-6 overflow-x-auto p-1.5 backdrop-blur-xl">
    <nav class="flex min-w-max items-center gap-1" aria-label="Tabs">
        @foreach ($tabs as $key => $tab)
            <a href="{{ $tautanTab($key, $tab) }}"
                @if ($activeTab === $key) aria-current="page" @endif
                class="{{ $activeTab === $key ? 'app-tab-active' : 'app-tab' }}">
                <i class="fas {{ $tab['icon'] }} text-xs opacity-80"></i> {{ $tab['label'] }}
            </a>
        @endforeach

        <span class="mx-1 h-6 w-px shrink-0 bg-base-300" aria-hidden="true"></span>

        @foreach ($extraTabs as $key => $tab)
            <a href="{{ $tautanTab($key, $tab) }}"
                @if ($activeTab === $key) aria-current="page" @endif
                class="{{ $activeTab === $key ? 'app-tab-active' : 'app-tab' }}">
                <i class="fas {{ $tab['icon'] }} text-xs opacity-80"></i> {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
