const PACKAGE_FIELDS = [
    'paket_pembayaran',
    'paket_pembayaran_2',
    'paket_pembayaran_3',
    'paket_pembayaran_4',
    'paket_pembayaran_5',
];

export function indexById(items) {
    return Object.fromEntries(items.map(item => [Number(item.id), item]));
}

export function calculateScheduleStatus(student, scheduleMeta, packageIndex) {
    const total = Number(scheduleMeta[student.id]?.total || 0);
    const quota = PACKAGE_FIELDS.reduce((sum, field) => {
        const packageItem = packageIndex[Number(student[field])];
        return sum + Number(packageItem?.pertemuan || 0);
    }, 0);

    return {
        total,
        kuota: quota,
        isKurang: total < quota,
        isComplete: total >= quota && quota > 0,
    };
}

export function buildStudentStatusIndex(students, scheduleMeta, packageIndex) {
    return Object.fromEntries(students.map(student => [
        Number(student.id),
        calculateScheduleStatus(student, scheduleMeta, packageIndex),
    ]));
}

function includesAny(values, selectedValues) {
    if (selectedValues.length === 0) return true;
    const selected = new Set(selectedValues.map(Number));
    return values.some(value => selected.has(Number(value)));
}

function compareBy(field, order) {
    const direction = order === 'asc' ? 1 : -1;

    return (left, right) => {
        const leftValue = left[field] || '';
        const rightValue = right[field] || '';

        if (typeof leftValue === 'string') {
            return leftValue.localeCompare(rightValue) * direction;
        }

        return (leftValue - rightValue) * direction;
    };
}

export function filterStudents({
    students,
    archives,
    mode,
    search,
    filters,
    scheduleMeta,
    sortField,
    sortOrder,
}) {
    const query = search.toLocaleLowerCase('id-ID').trim();
    const source = mode === 'aktif' ? students : archives;

    return source.filter(student => {
        const matchesSearch = query === '' || [student.name, student.kelas]
            .filter(Boolean)
            .some(value => value.toLocaleLowerCase('id-ID').includes(query));

        if (!matchesSearch || mode !== 'aktif') return matchesSearch;

        const metadata = scheduleMeta[student.id] || {};
        return (!filters.kelas || student.kelas === filters.kelas)
            && (!filters.paket || Number(student.paket_pembayaran) === Number(filters.paket))
            && (!filters.kemampuan || Number(student.tingkat_kemampuan_id) === Number(filters.kemampuan))
            && includesAny(metadata.sesi_ids || [], filters.sesiIds)
            && includesAny(metadata.guru_ids || [], filters.guruIds)
            && includesAny(metadata.ruang_ids || [], filters.ruangIds);
    }).sort(compareBy(sortField, sortOrder));
}
