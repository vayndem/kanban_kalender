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

    // Laravel's default validation-exception response ({message, errors}) has no
    // status field of its own -- surface the specific field messages instead of
    // letting callers show a generic "gagal" toast.
    if (!response.ok && data.status !== 'error') {
        const pesan = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Terjadi kesalahan.');
        return { status: 'error', message: pesan };
    }

    return data;
}
