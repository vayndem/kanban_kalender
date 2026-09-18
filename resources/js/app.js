import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'sweetalert2/dist/sweetalert2.min.css';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Swal from 'sweetalert2';
import { installJadwalDragDrop, jadwalHandler } from './admin/jadwal.js';
import { akunGuruHandler } from './admin/akun-guru.js';
import { pembayaranHandler } from './admin/pembayaran.js';
import { siswaHandler } from './admin/siswa.js';
import { workshopHandler } from './admin/workshop.js';
import { modulAjarHandler } from './admin/modul-ajar.js';
import { absenHandler } from './admin/absen.js';
import { payrollHandler } from './admin/payroll.js';
import { resultHandler } from './admin/result.js';
import { calendarApp } from './components/calendar.js';
import { installAlerts } from './core/alerts.js';
import { salinTeksJadwal } from './core/http.js';
import { buildPaymentSummary } from './domain/payment-summary.js';
import {
    buildOccupancyIndex,
    normalizeStudentSchedules,
} from './domain/schedule.js';
import {
    buildStudentStatusIndex,
    calculateScheduleStatus,
    filterStudents,
    indexById,
    studentPackageIds,
} from './domain/student-list.js';
import { installButtonLoading } from './ui/button-loading.js';
import { filterMulti } from './ui/filter-multi.js';
import { installSearchableSelects } from './ui/searchable-select.js';

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
    studentPackageIds,
});

installAlerts();
installButtonLoading();

Alpine.data('calendarApp', calendarApp);
Alpine.data('jadwalHandler', jadwalHandler);
Alpine.data('siswaHandler', siswaHandler);
Alpine.data('pembayaranHandler', pembayaranHandler);
Alpine.data('akunGuruHandler', akunGuruHandler);
Alpine.data('workshopHandler', workshopHandler);
Alpine.data('payrollHandler', payrollHandler);
Alpine.data('resultHandler', resultHandler);
Alpine.data('modulAjarHandler', modulAjarHandler);
Alpine.data('absenHandler', absenHandler);
Alpine.data('filterMulti', filterMulti);
Alpine.start();

installJadwalDragDrop();
installSearchableSelects();
