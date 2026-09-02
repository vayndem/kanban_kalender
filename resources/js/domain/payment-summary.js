const STATUS_LABELS = ['Belum Bayar', 'Tertagih', 'Lunas'];

function matchesFilters(item, filters) {
    const search = filters.search.toLocaleLowerCase('id-ID').trim();
    const searchableText = [item.siswa?.name, item.no_hp, item.keterangan]
        .filter(Boolean)
        .join(' ')
        .toLocaleLowerCase('id-ID');

    return (search === '' || searchableText.includes(search))
        && (filters.month === 'all' || item.bulan === filters.month)
        && String(item.status) === filters.status;
}

function createFamily(item, phone) {
    return {
        no_hp: phone,
        siswa_names_arr: [],
        total_harga: 0,
        total_sudah_dibayar: 0,
        gabungan_keterangan: [],
        raw_items: [],
        payment_details: [],
        status: item.status,
        tanggal_pembayaran: item.tanggal_pembayaran,
        pembayaran_via: item.pembayaran_via,
        tanggal_format: item.tanggal_format,
        id_siswa_trigger: item.id_siswa,
    };
}

function groupByFamily(items) {
    const families = new Map();

    items.forEach(item => {
        const phone = item.no_hp || item.siswa?.no_hp || 'N/A';
        const family = families.get(phone) || createFamily(item, phone);

        if (item.siswa?.name && !family.siswa_names_arr.includes(item.siswa.name)) {
            family.siswa_names_arr.push(item.siswa.name);
        }

        family.total_harga += Number(item.harga || 0);
        family.total_sudah_dibayar += Number(item.total_sudah_dibayar || 0);
        family.gabungan_keterangan.push(item.keterangan);
        family.raw_items.push(item);
        families.set(phone, family);
    });

    return [...families.values()];
}

function resolveStatus(items) {
    const statuses = items.map(item => Number(item.status));
    if (statuses.every(status => status === 2)) return 2;
    if (statuses.some(status => status === 1 || status === 2)) return 1;
    return 0;
}

function applyDiscount(family, discountsByPhone) {
    const specific = discountsByPhone.get(family.no_hp);
    const universal = discountsByPhone.get(null);
    const discount = Number(specific?.diskon || 0) + Number(universal?.diskon || 0);
    const discountNotes = [
        specific?.keterangan,
        universal?.keterangan ? `${universal.keterangan} (Massal)` : null,
    ].filter(Boolean);
    const total = Math.max(family.total_harga - discount, 0);
    const status = resolveStatus(family.raw_items);
    const paidPercent = total > 0
        ? Math.min(100, Math.round((family.total_sudah_dibayar / total) * 100))
        : (status === 2 ? 100 : 0);

    return {
        ...family,
        status,
        status_label: STATUS_LABELS[status],
        id_diskon: specific?.id || null,
        siswa_names: family.siswa_names_arr.join(', '),
        gabungan_keterangan: family.gabungan_keterangan.filter(Boolean).join(', '),
        nominal_diskon: discount,
        keterangan_diskon: discountNotes.join(' + ') || 'Tanpa Potongan',
        total_akhir: total,
        remaining_amount: Math.max(total - family.total_sudah_dibayar, 0),
        paid_percent: paidPercent,
    };
}

function summarize(items) {
    return items.reduce((stats, item) => ({
        totalFamilies: stats.totalFamilies + 1,
        totalNet: stats.totalNet + Number(item.total_akhir || 0),
        totalPaid: stats.totalPaid + Number(item.total_sudah_dibayar || 0),
        totalRemaining: stats.totalRemaining + Number(item.remaining_amount || 0),
    }), {
        totalFamilies: 0,
        totalNet: 0,
        totalPaid: 0,
        totalRemaining: 0,
    });
}

export function buildPaymentSummary(summaries, discounts, filters) {
    const filtered = summaries.filter(item => matchesFilters(item, filters));
    const grouped = groupByFamily(filtered);
    const discountsByPhone = new Map(discounts.map(item => [item.no_hp, item]));
    const items = grouped.map(family => applyDiscount(family, discountsByPhone));

    return { items, stats: summarize(items) };
}
