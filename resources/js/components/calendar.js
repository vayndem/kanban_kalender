const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const DESKTOP_QUERY = '(min-width: 768px)';

export function calendarApp(exportBase) {
    return {
        query: '',
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

        matches(text) {
            const query = this.query.toLocaleLowerCase('id-ID').trim();
            return query === '' || text.includes(query);
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
