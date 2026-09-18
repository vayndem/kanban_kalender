import assert from 'node:assert/strict';
import test from 'node:test';

import { csrfToken, kirim } from '../../resources/js/core/http.js';

function pasangDom(token = 'token-uji') {
    globalThis.document = {
        querySelector: selector => (selector === 'meta[name="csrf-token"]' ? { content: token } : null),
    };
}

function lepasDom() {
    globalThis.document = { querySelector: () => null };
}

function pasangFetch({ ok = true, status = 200, body = { status: 'success' }, mentah = null } = {}) {
    const panggilan = [];

    globalThis.fetch = async (url, options) => {
        panggilan.push({ url, options });

        return {
            ok,
            status,
            json: async () => {
                if (mentah !== null) throw new SyntaxError('Unexpected token < in JSON');

                return body;
            },
        };
    };

    return panggilan;
}

test.beforeEach(() => pasangDom());

test('csrfToken reads the meta tag and falls back to an empty string', () => {
    assert.equal(csrfToken(), 'token-uji');

    lepasDom();
    assert.equal(csrfToken(), '');
});

test('an update is sent as a real PUT, not as a POST', async () => {
    const panggilan = pasangFetch();

    await kirim('/admin/diskon/7', 'PUT', { diskon: 5000 });

    assert.equal(panggilan.length, 1);
    assert.equal(panggilan[0].url, '/admin/diskon/7');
    assert.equal(panggilan[0].options.method, 'PUT');
});

test('a delete is sent as a real DELETE', async () => {
    const panggilan = pasangFetch();

    await kirim('/admin/diskon/7', 'DELETE', {});

    assert.equal(panggilan[0].options.method, 'DELETE');
});

for (const method of ['PUT', 'DELETE', 'PATCH']) {
    test(`a ${method} body never smuggles the verb through _method`, async () => {
        const panggilan = pasangFetch();

        await kirim('/admin/diskon/7', method, { diskon: 5000 });

        const body = JSON.parse(panggilan[0].options.body);
        assert.equal('_method' in body, false);
        assert.deepEqual(body, { diskon: 5000 });
    });
}

test('every request carries the csrf token and asks for json', async () => {
    const panggilan = pasangFetch();

    await kirim('/admin/diskon', 'POST', { diskon: 1 });

    const { headers } = panggilan[0].options;
    assert.equal(headers['X-CSRF-TOKEN'], 'token-uji');
    assert.equal(headers.Accept, 'application/json');
    assert.equal(headers['Content-Type'], 'application/json');
});

test('a missing payload still sends a valid json body', async () => {
    const panggilan = pasangFetch();

    await kirim('/admin/diskon/7', 'DELETE');

    assert.equal(panggilan[0].options.body, '{}');
});

for (const method of ['GET', 'HEAD']) {
    test(`a ${method} carries no body and no content type`, async () => {
        const panggilan = pasangFetch();

        await kirim('/admin/diskon', method);

        assert.equal(panggilan[0].options.body, undefined);
        assert.equal(panggilan[0].options.headers['Content-Type'], undefined);
    });
}

test('a successful payload is handed back untouched', async () => {
    pasangFetch({ body: { status: 'success', message: 'Diskon berhasil diperbarui.', data: { id: 7 } } });

    const res = await kirim('/admin/diskon/7', 'PUT', {});

    assert.deepEqual(res, { status: 'success', message: 'Diskon berhasil diperbarui.', data: { id: 7 } });
});

test('validation errors are flattened into one readable message', async () => {
    pasangFetch({
        ok: false,
        status: 422,
        body: { message: 'The given data was invalid.', errors: { diskon: ['Nominal wajib diisi.'], no_hp: ['Nomor tidak dikenali.'] } },
    });

    const res = await kirim('/admin/diskon', 'POST', {});

    assert.equal(res.status, 'error');
    assert.equal(res.message, 'Nominal wajib diisi. Nomor tidak dikenali.');
});

test('a plain error message survives when there is no errors bag', async () => {
    pasangFetch({ ok: false, status: 404, body: { message: 'Data diskon tidak ditemukan.' } });

    const res = await kirim('/admin/diskon/7', 'PUT', {});

    assert.deepEqual(res, { status: 'error', message: 'Data diskon tidak ditemukan.' });
});

test('a 405 with an empty body still surfaces something the admin can read', async () => {
    pasangFetch({ ok: false, status: 405, body: {} });

    const res = await kirim('/admin/diskon/7', 'POST', {});

    assert.deepEqual(res, { status: 'error', message: 'Terjadi kesalahan.' });
});

test('an html error page does not blow up the caller', async () => {
    pasangFetch({ ok: false, status: 500, mentah: '<!DOCTYPE html>' });

    const res = await kirim('/admin/diskon/7', 'PUT', {});

    assert.deepEqual(res, { status: 'error', message: 'Terjadi kesalahan.' });
});

test('an error the server already shaped is not rewritten', async () => {
    pasangFetch({
        ok: false,
        status: 422,
        body: { status: 'error', message: 'Nomor HP wajib diisi untuk diskon spesifik.' },
    });

    const res = await kirim('/admin/diskon', 'POST', {});

    assert.deepEqual(res, { status: 'error', message: 'Nomor HP wajib diisi untuk diskon spesifik.' });
});
