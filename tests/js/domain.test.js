import assert from 'node:assert/strict';
import test from 'node:test';

import { buildPaymentSummary } from '../../resources/js/domain/payment-summary.js';
import {
    calculateScheduleStatus,
    filterStudents,
    indexById,
} from '../../resources/js/domain/student-list.js';

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
