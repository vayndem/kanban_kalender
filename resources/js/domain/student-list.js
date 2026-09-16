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

function toList(value) {
    if (Array.isArray(value)) return value;
    if (value === '' || value === null || value === undefined) return [];
    return [value];
}

function isFilled(value) {
    return value !== '' && value !== null && value !== undefined;
}

export function studentPackageIds(student) {
    return PACKAGE_FIELDS.map(field => student[field]).filter(isFilled);
}

function includesAny(values, selectedValues) {
    const selected = toList(selectedValues);
    if (selected.length === 0) return true;

    const wanted = new Set(selected.filter(isFilled).map(String));
    if (wanted.size === 0) return true;

    return values.filter(isFilled).some(value => wanted.has(String(value)));
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
        return includesAny([student.kelas], filters.kelas)
            && includesAny(studentPackageIds(student), filters.paket)
            && includesAny([student.tingkat_kemampuan_id], filters.kemampuan)
            && includesAny(metadata.sesi_ids || [], filters.sesiIds)
            && includesAny(metadata.guru_ids || [], filters.guruIds)
            && includesAny(metadata.ruang_ids || [], filters.ruangIds);
    }).sort(compareBy(sortField, sortOrder));
}
