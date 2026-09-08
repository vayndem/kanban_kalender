import { isDarkMode } from './theme';

const theme = () => {
    const dark = isDarkMode();

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f8fafc' : '#0f172a',
    };
};

export function installAlerts() {
    window.AppSwal = {
        toast(message, icon = 'success') {
            return window.Swal.fire({
                ...theme(),
                icon,
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2600,
                timerProgressBar: true,
            });
        },

        error(message = 'Terjadi kesalahan. Silakan coba kembali.') {
            return window.Swal.fire({
                ...theme(),
                icon: 'error',
                title: 'Gagal',
                text: message,
                confirmButtonColor: '#ef4444',
            });
        },

        confirm(title, text, confirmText = 'Ya, lanjutkan') {
            return window.Swal.fire({
                ...theme(),
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            });
        },
    };
}
