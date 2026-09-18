import assert from 'node:assert/strict';
import test from 'node:test';

import { pembayaranHandler } from '../../resources/js/admin/pembayaran.js';

const ROUTES = {
    diskonBase: '/admin/diskon',
    diskonStore: '/admin/diskon',
    paketBase: '/admin/paket',
    paketStore: '/admin/paket',
};

let panggilan = [];
let galat = [];
let pindah = [];

function pasangLingkungan({ ok = true, status = 200, body = { status: 'success' } } = {}) {
    panggilan = [];
    galat = [];
    pindah = [];

    globalThis.document = {
        querySelector: selector => (selector === 'meta[name="csrf-token"]' ? { content: 'token-uji' } : null),
    };

    globalThis.window = {
        matchMedia: () => ({ matches: true, addEventListener: () => {} }),
        location: { href: 'http://localhost/dashboard?tab=siswa' },
        open: () => {},
    };

    globalThis.AppSwal = {
        error: pesan => {
            galat.push(pesan);
        },
        toast: () => {},
        confirm: async () => ({ isConfirmed: true }),
    };

    globalThis.fetch = async (url, options) => {
        panggilan.push({ url, method: options.method, body: options.body });

        return { ok, status, json: async () => body };
    };

    Object.defineProperty(globalThis.window.location, 'href', {
        get: () => 'http://localhost/dashboard?tab=siswa',
        set: nilai => pindah.push(nilai),
        configurable: true,
    });
}

function handler() {
    return pembayaranHandler({
        initialSummaries: [],
        initialSiswas: [],
        initialPakets: [],
        initialDiskons: [],
        initialBatchStatus: null,
        routes: ROUTES,
    });
}

test.beforeEach(() => pasangLingkungan());

test('creating a discount posts to the store route', async () => {
    const h = handler();
    h.diskonForm = { id: null, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(panggilan.length, 1);
    assert.equal(panggilan[0].method, 'POST');
    assert.equal(panggilan[0].url, '/admin/diskon');
});

test('editing a discount sends PUT to that row, not POST', async () => {
    const h = handler();
    h.diskonForm = { id: 7, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(panggilan.length, 1);
    assert.equal(panggilan[0].url, '/admin/diskon/7');
    assert.equal(panggilan[0].method, 'PUT');
});

test('editing a universal discount also goes out as PUT', async () => {
    const h = handler();
    h.diskonForm = { id: 3, no_hp: '', diskon: 5000, keterangan: '', is_universal: true };

    await h.simpanDiskon();

    assert.equal(panggilan[0].url, '/admin/diskon/3');
    assert.equal(panggilan[0].method, 'PUT');
});

test('a family discount with no phone never reaches the server', async () => {
    const h = handler();
    h.diskonForm = { id: null, no_hp: '', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(panggilan.length, 0);
    assert.equal(galat.length, 1);
});

test('a rejected save shows the server message instead of failing silently', async () => {
    pasangLingkungan({ ok: false, status: 422, body: { status: 'error', message: 'Nomor HP sudah punya aturan diskon.' } });

    const h = handler();
    h.diskonForm = { id: 7, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.deepEqual(galat, ['Nomor HP sudah punya aturan diskon.']);
    assert.deepEqual(pindah, []);
});

test('a 405 from a wrong verb is reported, never swallowed', async () => {
    pasangLingkungan({ ok: false, status: 405, body: {} });

    const h = handler();
    h.diskonForm = { id: 7, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(galat.length, 1);
    assert.deepEqual(pindah, []);
});

test('a successful save sends the admin back to the payment tab', async () => {
    const h = handler();
    h.diskonForm = { id: 7, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(pindah.length, 1);
    assert.match(pindah[0], /tab=pembayaran/);
});

test('deleting a discount sends DELETE to that row', async () => {
    const h = handler();

    await h.hapusDiskon(9);

    assert.equal(panggilan[0].url, '/admin/diskon/9');
    assert.equal(panggilan[0].method, 'DELETE');
});

test('a failed delete is reported and leaves the page where it is', async () => {
    pasangLingkungan({ ok: false, status: 404, body: { status: 'error', message: 'Data diskon tidak ditemukan.' } });

    const h = handler();

    await h.hapusDiskon(9);

    assert.deepEqual(galat, ['Data diskon tidak ditemukan.']);
    assert.deepEqual(pindah, []);
});

test('the loading flag is released even when the save is rejected', async () => {
    pasangLingkungan({ ok: false, status: 422, body: { status: 'error', message: 'Ditolak.' } });

    const h = handler();
    h.diskonForm = { id: 7, no_hp: '081234567890', diskon: 5000, keterangan: '', is_universal: false };

    await h.simpanDiskon();

    assert.equal(h.isLoading, false);
});

test('creating a package posts to the store route', async () => {
    const h = handler();
    h.paketForm = { id: null, nama_paket: 'Paket A', harga: 100000, pertemuan: 4 };

    await h.savePaket();

    assert.equal(panggilan[0].url, '/admin/paket');
    assert.equal(panggilan[0].method, 'POST');
});

test('editing a package sends PUT to that row', async () => {
    const h = handler();
    h.paketForm = { id: 4, nama_paket: 'Paket A', harga: 100000, pertemuan: 4 };

    await h.savePaket();

    assert.equal(panggilan[0].url, '/admin/paket/4');
    assert.equal(panggilan[0].method, 'PUT');
});

test('deleting a package sends DELETE to that row', async () => {
    const h = handler();

    await h.deletePaket(4);

    assert.equal(panggilan[0].url, '/admin/paket/4');
    assert.equal(panggilan[0].method, 'DELETE');
});

test('a rejected package save shows the server message', async () => {
    pasangLingkungan({ ok: false, status: 422, body: { message: 'Harga wajib diisi.' } });

    const h = handler();
    h.paketForm = { id: 4, nama_paket: 'Paket A', harga: '', pertemuan: 4 };

    await h.savePaket();

    assert.deepEqual(galat, ['Harga wajib diisi.']);
    assert.deepEqual(pindah, []);
});
