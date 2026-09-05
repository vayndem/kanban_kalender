import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'sweetalert2/dist/sweetalert2.min.css';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Swal from 'sweetalert2';
import { installJadwalDragDrop, jadwalHandler } from './admin/jadwal';
import { akunGuruHandler } from './admin/akun-guru';
import { pembayaranHandler } from './admin/pembayaran';
import { siswaHandler } from './admin/siswa';
import { workshopHandler } from './admin/workshop';
import { modulAjarHandler } from './admin/modul-ajar';
import { calendarApp } from './components/calendar';
import { installAlerts } from './core/alerts';
import { salinTeksJadwal } from './core/http';
import { buildPaymentSummary } from './domain/payment-summary';
import {
    buildOccupancyIndex,
    normalizeStudentSchedules,
} from './domain/schedule';
import {
    buildStudentStatusIndex,
    calculateScheduleStatus,
    filterStudents,
    indexById,
} from './domain/student-list';
import { installButtonLoading } from './ui/button-loading';
import { installSearchableSelects } from './ui/searchable-select';

window.Alpine = Alpine;
window.Sortable = Sortable;
window.Swal = Swal;
window.salinTeksJadwal = salinTeksJadwal;
window.AppDomain = Object.freeze({
    buildPaymentSummary,
    buildOccupancyIndex,
    buildStudentStatusIndex,
    calculateScheduleStatus,
    filterStudents,
    indexById,
    normalizeStudentSchedules,
});

installAlerts();
installButtonLoading();

Alpine.data('calendarApp', calendarApp);
Alpine.data('jadwalHandler', jadwalHandler);
Alpine.data('siswaHandler', siswaHandler);
Alpine.data('pembayaranHandler', pembayaranHandler);
Alpine.data('akunGuruHandler', akunGuruHandler);
Alpine.data('workshopHandler', workshopHandler);
Alpine.data('modulAjarHandler', modulAjarHandler);
Alpine.start();

installJadwalDragDrop();
installSearchableSelects();
