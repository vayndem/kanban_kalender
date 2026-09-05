import { indexById } from './student-list';

export function normalizeStudentSchedules(schedules, days, sessions) {
    const dayIndex = indexById(days);
    const sessionIndex = indexById(sessions);

    return (schedules || []).map(schedule => {
        const subject = schedule.mata_pelajaran || schedule.mataPelajaran;
        const day = schedule.hari || dayIndex[Number(schedule.hari_id)];
        const session = schedule.sesi || sessionIndex[Number(schedule.sesi_id)];
        const start = session?.start_time?.substring(0, 5) || '';
        const end = session?.end_time?.substring(0, 5) || '';

        return {
            id: schedule.id,
            mapel_name: subject?.name || 'N/A',
            guru_name: schedule.guru?.name || 'N/A',
            ruang_name: schedule.ruang?.name || 'N/A',
            hari_name: day?.name || day?.nama || 'N/A',
            sesi_name: session?.name || session?.nama_sesi || `Sesi ${schedule.sesi_id}`,
            sesi_time: start && end ? `${start} - ${end}` : '',
        };
    });
}

export function buildOccupancyIndex(occupancy) {
    return occupancy.reduce((index, item) => {
        const key = `${Number(item.hari_id)}:${Number(item.sesi_id)}`;
        (index[key] ||= []).push(item);
        return index;
    }, {});
}

