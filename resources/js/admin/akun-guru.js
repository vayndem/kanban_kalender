import { kirim } from '../core/http.js';
import { isDarkMode } from '../core/theme.js';

export const akunGuruHandler = ({ routes }) => ({
    routes,
    isLoading: false,

    async buatAkun(id, nama, email) {
        const isDark = isDarkMode();

        const { value: form } = await Swal.fire({
            title: `Buat akun login untuk ${nama}`,
            html: `
                <div style="text-align:left" class="space-y-3">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin-bottom:6px;">Email</label>
                        <input id="swal-email" type="email" value="${email || ''}" placeholder="nama@elingcourse.com"
                            style="width:100%;border-radius:12px;padding:10px 14px;font-size:14px;background:${isDark ? '#1f2937' : '#fff'};border:1px solid ${isDark ? '#4b5563' : '#d1d5db'};color:${isDark ? '#fff' : '#000'};">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin-bottom:6px;">Password (minimal 8 karakter)</label>
                        <input id="swal-password" type="text" placeholder="Password awal untuk guru"
                            style="width:100%;border-radius:12px;padding:10px 14px;font-size:14px;background:${isDark ? '#1f2937' : '#fff'};border:1px solid ${isDark ? '#4b5563' : '#d1d5db'};color:${isDark ? '#fff' : '#000'};">
                    </div>
                    <p style="font-size:11px;color:#9ca3af;line-height:1.5;">
                        Catat password ini dan sampaikan ke guru yang bersangkutan. Guru hanya bisa melihat jadwalnya sendiri.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Buat akun',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#4b5563',
            background: isDark ? '#111827' : '#fff',
            color: isDark ? '#fff' : '#000',
            preConfirm: () => {
                const em = document.getElementById('swal-email').value.trim();
                const pw = document.getElementById('swal-password').value;
                if (!em) {
                    Swal.showValidationMessage('Email wajib diisi.');
                    return false;
                }
                if (!pw || pw.length < 8) {
                    Swal.showValidationMessage('Password minimal 8 karakter.');
                    return false;
                }
                return { email: em, password: pw };
            },
        });

        if (!form) return;

        this.isLoading = true;
        try {
            const payload = await kirim(`${this.routes.buatAkunBase}/${id}/akun`, 'POST', form);
            if (payload.status === 'success') {
                await AppSwal.toast(payload.message);
                window.location.reload();
            } else {
                AppSwal.error(payload.message || 'Gagal membuat akun.');
            }
        } catch (e) {
            AppSwal.error('Gagal membuat akun guru.');
        } finally {
            this.isLoading = false;
        }
    },

    async gantiPassword(id, nama) {
        const { value: hasil } = await Swal.fire({
            title: `Password baru ${nama}`,
            html: `
                <p style="font-size:13px;text-align:left;margin-bottom:10px">
                    Setelah diganti, ${nama} langsung keluar dari semua perangkat dan harus login ulang.
                    Sampaikan password barunya langsung ke yang bersangkutan.
                </p>
                <input id="sandi-baru" type="password" class="swal2-input" placeholder="Password baru (min. 8 karakter)" autocomplete="new-password">
                <input id="sandi-ulang" type="password" class="swal2-input" placeholder="Ketik ulang password baru" autocomplete="new-password">
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Ganti Password',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#4b5563',
            background: isDarkMode() ? '#111827' : '#fff',
            color: isDarkMode() ? '#fff' : '#000',
            preConfirm: () => {
                const baru = document.getElementById('sandi-baru').value;
                const ulang = document.getElementById('sandi-ulang').value;

                if (!baru || baru.length < 8) {
                    Swal.showValidationMessage('Password baru minimal 8 karakter.');
                    return false;
                }
                if (baru !== ulang) {
                    Swal.showValidationMessage('Ketikan ulang password belum sama.');
                    return false;
                }

                return { baru, ulang };
            },
        });

        if (!hasil) return;

        this.isLoading = true;
        try {
            const payload = await kirim(`${this.routes.buatAkunBase}/${id}/password`, 'PUT', {
                password: hasil.baru,
                password_confirmation: hasil.ulang,
            });

            if (payload.status === 'success') {
                AppSwal.toast(payload.message);
            } else {
                AppSwal.error(payload.message || 'Gagal mengganti password.');
            }
        } catch (e) {
            AppSwal.error('Gagal mengganti password guru.');
        } finally {
            this.isLoading = false;
        }
    },

    async ubahEmail(id, nama, email) {
        const { value: baru } = await Swal.fire({
            title: `Email ${nama}`,
            input: 'email',
            inputValue: email || '',
            inputPlaceholder: 'nama@elingcourse.com',
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#4b5563',
            background: isDarkMode() ? '#111827' : '#fff',
            color: isDarkMode() ? '#fff' : '#000',
        });

        if (baru === undefined) return;

        this.isLoading = true;
        try {
            const payload = await kirim(`${this.routes.buatAkunBase}/${id}/email`, 'PUT', { email: baru });
            if (payload.status === 'success') {
                await AppSwal.toast(payload.message);
                window.location.reload();
            } else {
                AppSwal.error(payload.message || 'Gagal memperbarui email.');
            }
        } catch (e) {
            AppSwal.error('Gagal memperbarui email guru.');
        } finally {
            this.isLoading = false;
        }
    },
});
