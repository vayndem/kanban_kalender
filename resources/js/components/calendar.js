const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const DESKTOP_QUERY = '(min-width: 768px)';

export function calendarApp(exportBase) {
    return {
        query: '',
        filterHari: '',
        filterMapel: '',
        filterGuru: '',
        filterRuang: '',
        todayLabel: '',
        isDesktop: window.matchMedia(DESKTOP_QUERY).matches,
        exportBase,

        init() {
            this.todayLabel = new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'full',
            }).format(new Date());

            window.matchMedia(DESKTOP_QUERY).addEventListener('change', event => {
                this.isDesktop = event.matches;
            });
        },

        get hasActiveFilters() {
            return this.query !== '' || this.filterHari !== '' || this.filterMapel !== '' ||
                this.filterGuru !== '' || this.filterRuang !== '';
        },

        resetFilters() {
            this.query = '';
            this.filterHari = '';
            this.filterMapel = '';
            this.filterGuru = '';
            this.filterRuang = '';
        },

        matches(text, ids = {}) {
            const query = this.query.toLocaleLowerCase('id-ID').trim();
            if (query !== '' && !text.includes(query)) return false;
            if (this.filterHari && Number(ids.hari) !== Number(this.filterHari)) return false;
            if (this.filterMapel && Number(ids.mapel) !== Number(this.filterMapel)) return false;
            if (this.filterGuru && Number(ids.guru) !== Number(this.filterGuru)) return false;
            if (this.filterRuang && Number(ids.ruang) !== Number(this.filterRuang)) return false;
            return true;
        },

        isCurrentDay(dayName) {
            return DAYS[new Date().getDay()] === dayName;
        },

        get exportUrl() {
            const search = this.query.trim();
            return search ? `${this.exportBase}?search=${encodeURIComponent(search)}` : this.exportBase;
        },
    };
}
