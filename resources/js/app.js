import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import { calendarApp } from './components/calendar';
import { installAlerts } from './core/alerts';
import { buildPaymentSummary } from './domain/payment-summary';
import {
    buildFormSources,
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
window.AppDomain = Object.freeze({
    buildPaymentSummary,
    buildFormSources,
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
Alpine.start();

installSearchableSelects();
