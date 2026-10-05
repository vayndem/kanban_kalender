import { isDarkMode } from './theme.js';

export async function tanyaPaksa(error) {
    if (!error || !error.butuh_paksa) return false;

    const jawab = await Swal.fire({
        title: 'Guru sedang ditandai tidak bisa',
        text: error.message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Tetap Simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: '#4b5563',
        background: isDarkMode() ? '#111827' : '#fff',
        color: isDarkMode() ? '#fff' : '#000',
        footer: 'Kalau tetap disimpan, pemaksaan ini tercatat di Jejak Perubahan beserta nama Anda.',
    });

    return jawab.isConfirmed === true;
}
