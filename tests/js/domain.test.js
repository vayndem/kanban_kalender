import assert from 'node:assert/strict';
import test from 'node:test';

import { buildPaymentSummary } from '../../resources/js/domain/payment-summary.js';
import {
    calculateScheduleStatus,
    filterStudents,
    indexById,
    studentPackageIds,
} from '../../resources/js/domain/student-list.js';
import { filterMulti } from '../../resources/js/ui/filter-multi.js';

test('payment summary groups a family and applies specific plus universal discounts', () => {
    const summaries = [
        { no_hp: '0812', bulan: '08', status: 0, harga: 100_000, total_sudah_dibayar: 20_000, siswa: { name: 'A' } },
        { no_hp: '0812', bulan: '08', status: 0, harga: 50_000, total_sudah_dibayar: 0, siswa: { name: 'B' } },
    ];
    const discounts = [
        { id: 1, no_hp: '0812', diskon: 10_000, keterangan: 'Keluarga' },
        { id: 2, no_hp: null, diskon: 5_000, keterangan: 'Promo' },
    ];

    const result = buildPaymentSummary(summaries, discounts, {
        search: '',
        month: '08',
        status: '0',
    });

    assert.equal(result.items.length, 1);
    assert.equal(result.items[0].siswa_names, 'A, B');
    assert.equal(result.items[0].total_akhir, 135_000);
    assert.equal(result.items[0].remaining_amount, 115_000);
    assert.equal(result.stats.totalRemaining, 115_000);
});

test('student status totals quota from every assigned package', () => {
    const packages = indexById([
        { id: 1, pertemuan: 4 },
        { id: 2, pertemuan: 8 },
    ]);
    const student = { id: 10, paket_pembayaran: 1, paket_pembayaran_2: 2 };

    assert.deepEqual(calculateScheduleStatus(student, { 10: { total: 9 } }, packages), {
        total: 9,
        kuota: 12,
        isKurang: true,
        isComplete: false,
    });
});

test('student filtering does not mutate the original student order', () => {
    const students = [
        { id: 2, name: 'Zaki', kelas: 'B', paket_pembayaran: 1 },
        { id: 1, name: 'Ana', kelas: 'A', paket_pembayaran: 1 },
    ];

    const result = filterStudents({
        students,
        archives: [],
        mode: 'aktif',
        search: '',
        filters: { kelas: '', paket: '', sesiIds: [], guruIds: [], ruangIds: [] },
        scheduleMeta: {},
        sortField: 'name',
        sortOrder: 'asc',
    });

    assert.deepEqual(result.map(student => student.name), ['Ana', 'Zaki']);
    assert.deepEqual(students.map(student => student.name), ['Zaki', 'Ana']);
});

test('multi-select filters keep a student that matches any chosen value', () => {
    const students = [
        { id: 1, name: 'Ana', kelas: '5', paket_pembayaran: 7 },
        { id: 2, name: 'Budi', kelas: '6', paket_pembayaran: 8 },
        { id: 3, name: 'Cici', kelas: '7', paket_pembayaran: 9 },
    ];
    const scheduleMeta = {
        1: { sesi_ids: [100], guru_ids: [10], ruang_ids: [1] },
        2: { sesi_ids: [200], guru_ids: [20], ruang_ids: [2] },
        3: { sesi_ids: [300], guru_ids: [30], ruang_ids: [3] },
    };

    const jalankan = filters => filterStudents({
        students,
        archives: [],
        mode: 'aktif',
        search: '',
        filters: { kelas: [], paket: [], kemampuan: [], sesiIds: [], guruIds: [], ruangIds: [], ...filters },
        scheduleMeta,
        sortField: 'name',
        sortOrder: 'asc',
    }).map(student => student.name);

    assert.deepEqual(jalankan({}), ['Ana', 'Budi', 'Cici']);
    assert.deepEqual(jalankan({ kelas: ['5', '7'] }), ['Ana', 'Cici']);
    assert.deepEqual(jalankan({ sesiIds: ['100', '200'] }), ['Ana', 'Budi']);
    assert.deepEqual(jalankan({ guruIds: ['30'] }), ['Cici']);
    assert.deepEqual(jalankan({ ruangIds: ['2', '3'] }), ['Budi', 'Cici']);
});

test('checkbox ids arrive as strings but still match numeric ids from the server', () => {
    const students = [{ id: 1, name: 'Ana', kelas: '5' }];
    const scheduleMeta = { 1: { sesi_ids: [183714], guru_ids: [4], ruang_ids: [2] } };

    const cocok = filterStudents({
        students,
        archives: [],
        mode: 'aktif',
        search: '',
        filters: { kelas: [], paket: [], kemampuan: [], sesiIds: ['183714'], guruIds: [], ruangIds: [] },
        scheduleMeta,
        sortField: 'name',
        sortOrder: 'asc',
    });

    assert.deepEqual(cocok.map(student => student.name), ['Ana']);
});

test('package filter finds a package held in any of the five slots', () => {
    const students = [
        { id: 1, name: 'Ana', paket_pembayaran: 1 },
        { id: 2, name: 'Budi', paket_pembayaran: 9, paket_pembayaran_3: 1 },
        { id: 3, name: 'Cici', paket_pembayaran: 9 },
    ];

    assert.deepEqual(studentPackageIds(students[1]), [9, 1]);

    const cocok = filterStudents({
        students,
        archives: [],
        mode: 'aktif',
        search: '',
        filters: { kelas: [], paket: [1], kemampuan: [], sesiIds: [], guruIds: [], ruangIds: [] },
        scheduleMeta: {},
        sortField: 'name',
        sortOrder: 'asc',
    });

    assert.deepEqual(cocok.map(student => student.name), ['Ana', 'Budi']);
});

test('a student with no schedule disappears once a session filter is chosen', () => {
    const students = [{ id: 1, name: 'Ana', kelas: '5' }];

    const cocok = filterStudents({
        students,
        archives: [],
        mode: 'aktif',
        search: '',
        filters: { kelas: [], paket: [], kemampuan: [], sesiIds: ['100'], guruIds: [], ruangIds: [] },
        scheduleMeta: {},
        sortField: 'name',
        sortOrder: 'asc',
    });

    assert.deepEqual(cocok, []);
});

test('filterMulti searches label and subtitle, and adds only what is on screen', () => {
    const komponen = filterMulti();
    const opsi = [
        { value: 1, label: 'SESI 01.00', sub: '13:00 - 14:00' },
        { value: 2, label: 'Sesi 1', sub: '13:30 - 14:30' },
        { value: 3, label: 'Sesi 7', sub: '19:30 - 20:30' },
    ];

    assert.equal(komponen.hasil(opsi).length, 3);

    komponen.cari = '13:30';
    assert.deepEqual(komponen.hasil(opsi).map(item => item.value), [2]);

    assert.deepEqual(komponen.pilihSemua([], opsi), ['2']);

    komponen.cari = '';
    assert.deepEqual(komponen.pilihSemua(['2'], opsi), ['2', '1', '3']);
});
