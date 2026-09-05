export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function kirim(url, method, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify({ ...payload, _method: method }),
    });

    const data = await response.json();

    if (!response.ok && data.status !== 'error') {
        const pesan = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Terjadi kesalahan.');
        return { status: 'error', message: pesan };
    }

    return data;
}

export async function salinTeksJadwal(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } }).then(r => r.json());
    if (res.status !== 'success') throw new Error(res.message || 'Gagal membuat teks jadwal.');
    await navigator.clipboard.writeText(res.text);
    return res;
}
