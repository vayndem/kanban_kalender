# CLAUDE.md

Guidance for Claude Code (claude.ai/code) working in this repository.

**Kanban Kalender** — Laravel 12 / PHP 8.3 internal admin system for **E-Ling Course**, a tutoring business: class schedules (jadwal), student records (siswa), billing (pembayaran), teaching records (modul ajar / absen), teacher payroll, plus a public read-only calendar. Blade + Tailwind v4 + daisyUI v5 + Alpine, SweetAlert2, DomPDF, deployed to Vercel as serverless PHP (`vercel.json`, `api/index.php`).

**[FEED.md](FEED.md)** holds controller-by-controller data flow and the export matrix (in Indonesian). Treat it as authoritative, not background — read the relevant section before touching Jadwal or Pembayaran.

## Commands

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build            # or npm run dev
php artisan serve        # or: composer run dev (server+queue+logs+vite)

vendor/bin/phpunit                          # full suite
vendor/bin/phpunit --filter=test_name
npm run test:js                             # node --test, no framework
vendor/bin/phpstan analyse                  # Larastan level 1, Controllers+Models
vendor/bin/pint
```

Windows with project-local PHP (`php83.ini`, `project-terminal.cmd`, both gitignored):
`& 'C:\PHP 8.3\php.exe' -c 'php83.ini' vendor\bin\phpunit`. If `npm run build` hits a PowerShell execution policy, use `cmd /c npm run build`. PHPStan may need `--memory-limit=1G`.

## Architecture

Domains, all routed from `routes/web.php`:

1. **Jadwal** (`JadwalController`) — schedule CRUD, drag-move, collision protection, PDF, WhatsApp text. No longer owns reference-data CRUD.
2. **Data Siswa** (`SiswaController`, `ArsipController`) — listing, filter, archive/restore/permanent-delete, export. Create/edit moved to Workshop; this tab keeps lifecycle actions plus the `Tanda` notes panel.
3. **Pembayaran** (`PembayaranController`, `PembayaranDetailsController`) — invoicing, mass billing, installments, discounts, settlement, receipts. The most fragile controller here.
4. **Public calendar** (`jadwal-kalender`) — read-only, no auth.
5. **Workshop** (`WorkshopController`) — the only place to create/edit Mapel, Guru, Ruang, Sesi, Paket, Kemampuan, Siswa.
6. **Modul Ajar** (`ModulAjarController`, `role:admin|guru`) — curriculum per class.
7. **Absen** (`ModulAjarController::absen`, `role:admin|guru`) — day x session teaching board.
8. **Payroll** (`PayrollController`, `PayrollService`) — rates, payslips, period close. Admin only.
9. **Result** (`ResultController`) — assessment aspects plus every student's progress card. Admin only.

`DashboardController` (`/dashboard`) loads data **per active tab**, never all at once — a full-payload version triggered `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE` on Vercel. Check response size and eager-loading blast radius on any change here, and likewise for `GET /admin/pembayaran/keluarga/{no_hp}/detail`, which exists to avoid shipping full invoice history up front.

**Domain model:** `Siswa`, `Jadwal`, `Hari`, `Sesi`, `Guru`, `Ruang`, `MataPelajaran`, `Paket`, `Pembayaran`, `PembayaranDetail`, `Diskon`, `Tanda`, `Arsip`, `BatchPembayaranLog`, `TingkatKemampuan`, `ModulAjar`, `ModulAjarDetail`, `ModulAjarAbsensi`, `AbsensiGuru`, `Penggajian`, `JadwalTeksLog`, `StashPemulihanLog`, `AspekPenilaian`, `NilaiAspek`.

- One class shows as a single card but is **many `jadwals` rows, one per student**. Collision checks and edits must cover every row, not a subset.
- `Pembayaran` is the invoice header; `PembayaranDetail` is the **append-only** ledger. Status changes add a detail row, never overwrite totals.
- Payment status is a legacy integer (`0` belum bayar / `1` proses / `2` lunas). Don't change the type without an explicit decision.

**Phone numbers.** Family and discount logic keys off `no_hp`. `App\Models\Concerns\MenormalisasiNoHp` (a `setNoHpAttribute` mutator over `App\Support\NomorHp::normalkan()`) is applied to `Siswa`, `Pembayaran`, `Arsip`, `Diskon`, so anything written through Eloquent is normalized to `+62...` on save. `SiswaController` validation only rejects values `normalkan()` cannot parse at all. `php artisan pembayaran:normalisasi-hp` remains for legacy rows and raw `DB::table()` writes that bypass Eloquent.

**Services** hold the logic that would otherwise bloat controllers — check them first: `PaymentBatchService` (mass billing/settlement), `RingkasanService` (dashboard aggregation), `RaporService` (per-student progress), `PayrollService` (rates, payslips, close), `IrisanSesiService` (session overlap), `StashJadwalService` (stash restore), `StrukPembayaranService` (receipts).

## Money invariants — do not weaken

Established while fixing a production incident where mass billing duplicated manual invoices (Rp 10.375.000 across 31 groups).

- **Package invoices are anchored, not text-matched.** `pembayarans.id_paket` + `periode` (`YYYY-MM`) uniquely identify one student's bill for one package in one month, enforced by `UNIQUE(id_siswa, id_paket, periode)`. Every new invoice-creating path must set the anchor. `id_paket = NULL` invoices are free-form (buku, denda, kegiatan) and may repeat by design.
- **Mass actions run once per period.** `penagihanMassal` and global `lunasSemua` take a lock row in `batch_pembayaran_logs` (`UNIQUE(jenis, periode)`) and release it if the run produced nothing. Per-family `keLunasMassal` is deliberately unlocked — it's a daily action.
- **Double-submit is blocked in three layers** (blocking overlay, `:disabled`, and a server-side 180s guard `PembayaranController::JEDA_ANTI_GANDA`). Only the server layer really counts; the others fall to a refresh.
- **Destructive artisan commands are blocked in production** by `DB::prohibitDestructiveCommands()` in `AppServiceProvider`. `.env` is swapped between local MariaDB and production TiDB, so `migrate:fresh` on the wrong target would destroy the books. `--force` cannot bypass it.
- **`PembayaranController::update()`** cannot reassign an invoice that already has settlement history, and cannot lower `harga` below `total_sudah_dibayar`. To fix a misassigned invoice with existing details, create a new invoice instead.

Maintenance commands (dry-run by default, `--force` applies):

```bash
php artisan pembayaran:audit-duplikat      # read-only report
php artisan pembayaran:rapikan-duplikat    # consolidate duplicates
php artisan pembayaran:normalisasi-hp      # normalize phones to +62
```

`rapikan-duplikat` keeps the earliest invoice, drops exact-twin details (same amount + date) and `Selesai sistem` rows, **moves** genuinely different payments to the survivor, and archives every removed row as JSON into `koreksi_pembayaran_logs` first.

### Payroll

Rates live on `gurus` (`gaji_bawaan`, `gaji_per_kehadiran`), payslips in `penggajians`. Payroll inherits the payment rules because it is money: recorded actor and timestamp, no hard deletes, idempotent against double submission.

- **Runs are ad-hoc, not monthly, and the base salary is paid in full every run** — never prorated. A guru with zero attendance still gets a base-salary payslip; that is intended.
- **"Siap Lakukan" closes the period, it does not delete.** It stamps every `absensi_gurus` row with `penggajian_id IS NULL` with the new payslip id plus `ditutup_pada`. The running counter is `WHERE penggajian_id IS NULL`, so it falls to zero on its own while history survives. **Never implement the reset as a DELETE.**
- The claim is a single atomic `UPDATE ... WHERE penggajian_id IS NULL` whose affected-row count **is** the authoritative attendance figure, so concurrent runs cannot double-pay.
- **Rates are snapshotted onto the payslip.** Raising a rate later must not change an issued payslip.
- **Correcting a payslip is void-then-reissue, never edit.** `batalkan()` marks it cancelled (actor, timestamp, required reason) and releases its attendance rows back to `penggajian_id = null`.
- **Double-submit guard is time-based** (`PayrollService::JEDA_ANTI_GANDA`, 180s), not row-count-based — a zero-attendance run is legitimate, so "no rows claimed" cannot detect a duplicate.
- **`perkiraanBerjalan()` is "what is still owed", not "what a run would cost"** — it returns 0 when there is no unpaid attendance, so the tab goes clean after a close. It is deliberately separate from `hitungTotal()`, which computes the payslip; **do not merge them**. Consequence: a guru showing Rp 0 still gets a base-salary payslip if you run payroll on them.
- **Known gap, owner's explicit decision (Sept 2026): nothing warns when `gaji_per_kehadiran` is 0.** Running payroll then closes the attendance and pays Rp 0 for it, unrecoverable except by voiding. The owner chose to guard this manually rather than have the app block or warn — don't add the guard without asking again.

## Schedule rules

### Sessions overlap in time — never compare `sesi_id` for equality

`sesis` rows are **not** mutually exclusive slots: starts are 30 minutes apart while each session runs 60, so an hour track and a half-hour track interleave (production has 17 sessions forming 11 overlapping pairs). A room, teacher, or student occupied in one session is **also occupied in every session whose clock time overlaps it**.

`App\Services\IrisanSesiService` is the single definition. Every surface goes through it; none may filter `sesi_id = ?`:

- `JadwalController::ensureNoConflicts()` — store, drag-move, edit-class
- `WorkshopController::petaKetersediaan()` — Slot Kosong must not offer a room busy in an overlapping session
- `RingkasanService::bentrokTersembunyi()` — reports cross-session clashes too
- `RingkasanService::okupansiRuang()` — denominator is `kapasitasSlotPerHari()`, **not** `Sesi::count()`, which overstates capacity
- `DemoSeeder` — its guard is overlap-aware, and `DemoSeederTest` asserts the seeded schedule is clean

**Touching at the edge is not a conflict** — 13:00-14:00 and 14:00-15:00 may share a room. The comparison is strict (`a.start < b.end && b.start < a.end`); if a changeover gap is ever needed, that one comparison is the only change. `BentrokIrisanSesiTest` pins both directions.

Collision messages name the **other** session ("... pada sesi SESI 01.00 - 13:00–14:00 yang jamnya bertindih") — without the name the admin cannot tell what to fix.

**"Back-to-back" teacher load is measured in clock time, not list position.** `RingkasanService::bebanGuru()` chains by time; with interleaved tracks, adjacent list positions usually mean *overlapping*.

### `hari_id` is a row id, not the ISO weekday number

Use `Hari::idHariIni()`, which matches on the day *name* and only falls back to the ISO number when no name matches. Filtering with `where('hari_id', Carbon::now()->isoFormat('E'))` assumes `haris` is numbered 1..7 in weekday order; re-add one day and Ringkasan silently shows the wrong day.

Avoid `COUNT(DISTINCT a, b)` — MySQL-only, breaks the SQLite test suite. Use `->distinct()->get()` and count in PHP.

### `Sesi::start_time` / `end_time` are plain `HH:MM` strings

The columns are SQL `time`. A `datetime:H:i` cast used to sit on them, but casts only apply on model serialization — the moment a raw Carbon went into an array (`RingkasanService::kelasHariIni()`), it serialized as a full ISO datetime and every `substr($t, 0, 5)` in the views rendered `"2026-"`. Accessors normalize to `HH:MM`; `label` / `rentang_jam` give display forms. **Don't reintroduce a datetime cast.**

### `kode_kelas` is class identity

A class is a group of `jadwals` rows sharing hari/sesi/mapel/guru/ruang. That composite is not a safe identity — it changes on a drag-move, and unrelated classes can share mapel+guru+ruang at different times. `jadwals.kode_kelas` (UUID, backfilled by migration) is carried through every editing path: `store()` generates it, `updatePosisi()` leaves it untouched, `updateKelas()` captures and reapplies it across the delete-and-reinsert. Stash round-trips it; a legacy stash without the field falls back to `JadwalController::isiKodeKelasKosong()`.

### Scoring is per aspect, defined by the admin

A meeting is not scored with one number. `aspek_penilaians` (nama, indikator, urutan, aktif) is the admin-defined list of what gets assessed, and **every active aspect must be scored 1-5 for each present student** before a meeting can be completed. Aspects are **global**, not per subject — the owner chose that deliberately, knowing a Math class inherits the same list.

- `nilai_aspeks` hangs off `modul_ajar_absensis`, not off the detail directly, so `hadir` stays the single source of truth and the scores cascade away with the attendance row. `unique(modul_ajar_absensi_id, aspek_penilaian_id)`.
- **`modul_ajar_absensis.nilai` is legacy.** New grading writes `null` there and puts the scores in `nilai_aspeks`. `ModulAjarAbsensi::rataAspek()` averages the aspect scores and **falls back to the old `nilai`** when a row has none, which is what keeps pre-existing rows readable in Rapor. Don't drop the column and don't start writing to it again.
- **An aspect that has ever been scored cannot be deleted** — `ResultController::hapusAspek()` rejects with a 422 telling the admin to deactivate instead. Deactivating removes it from the grading screen while old rapor rows keep showing it, because scores reference the aspect by id rather than copying its name. This is the same shape as the reference-data delete guards.
- **Adding an aspect mid-term retroactively makes old gradings incomplete.** That is intended: re-opening such a meeting shows the new aspect empty and locks "Simpan & Selesaikan" until it is filled. `AspekPenilaianTest` pins it.
- With **no active aspect at all**, grading is refused server-side and the Absen modal shows a "minta admin mengisi di menu Result" empty state instead of a broken form. Check this first if grading suddenly 422s.

The grading modal is a **per-student stepper**, not a student x aspect grid — the owner chose it because a grid of 5 students x 6 aspects is unusable on a phone. It keeps chips for jumping between children, a running average, a "belum lengkap" jump button, and a save button disabled until every present child is complete. Widen it via `:class="pengajaranTahap === 'nilai' ? 'max-w-3xl' : 'max-w-lg'"` rather than making every modal wide.

`RaporService` returns `per_aspek` (average, min, max and trend for each aspect) alongside `ringkasan` and `per_mapel`, and the rapor PDF prints it as its own table. A meeting's score is the **average of its aspects**, so `rata_nilai` is a float now, not an integer.

**Rapor Perkembangan lives in Result, not Data Siswa.** The panel and its routes moved to `ResultController::rapor()` / `raporPdf()`; `SiswaController` no longer has them.

**The help FAB sits at `z-30`, below every modal.** It used to be `z-[120]` and covered the save button of the grading modal on a phone. Its drawer is a sibling at `z-[210]`, deliberately *not* nested inside a positioned wrapper — nesting it would trap the drawer in the button's stacking context and push it under the sticky tab bar.

## Roles and access

Two roles via `spatie/laravel-permission`: `admin`, `guru`.

- All `/admin/*` plus `/dashboard` sit behind `role:admin`; `/guru` behind `role:guru`; profile open to both. **Enforced at the route layer, not by hiding buttons.**
- `RoleSeeder` creates both roles and grants `admin` to any account with none — pre-role accounts must not be locked out. Run it after migrating.
- `gurus.email` is nullable; teachers without an account remain fully schedulable.
- A login links via `users.guru_id` → `gurus`. Direction matters: `Guru` is the domain entity `jadwals` reference, `User` is only the way in. Deleting a user never touches the schedule.
- `UserFactory` creates admins by default; `->guru($guru)` and `->tanpaPeran()` are the other states. Role assignment happens in chained `afterCreating` callbacks — `state()` builds a new factory instance and silently discards custom properties.

`GuruPortalController` is read-only by construction (no write route). The only place a guru writes is Modul Ajar.

### Modul Ajar, teaching and attendance

- **Data shape:** `modul_ajars` (one per `kode_kelas`; `tujuan_pembelajaran`, `kompetensi_awal`, `model_pembelajaran`, `sarana_media`, all required) has many `modul_ajar_details` (`materi` required; `sub_materi`, `cara_mengajar`, `tugas`, `tujuan`, `hasil_akhir_pembelajaran`, `keterangan` optional; zero details is a normal state). The teaching columns live on the same rows: `sedang_dipersiapkan`, `guru_pengganti_id`, `diajarkan_oleh_guru_id`, `tanggal_diajarkan`.
- **Permissions split by verb, not just ownership.** Admin and the assigned guru can view and **create** header and details. Only admin can **update or delete** — a guru's `simpanHeader` is 403 if a header already exists, and `updateDetail`/`hapusDetail` reject non-admins. Deliberate; don't loosen without asking.
- **Each detail row is the teaching event** — no dated "Pertemuan" entity. `mulaiPersiapan()` marks it in progress (card turns amber); `simpanNilai()` grades every student (`hadir` + `nilai` 1-5 in `modul_ajar_absensis`, unique per student per detail), completes the detail and clears `guru_pengganti_id`. Re-teaching **overwrites** grades rather than versioning them — a deliberate simplification, since this isn't money.
- **Substitution never touches `jadwals.guru_id`.** Guru A names guru B as `guru_pengganti_id` on one detail; that is what lets B pass `bolehNilai()`. It resets to null after grading. `index()` therefore scopes a guru's kanban to their own classes **plus** any class where they are currently a substitute — without that, the substitute cannot reach the class.
- **Attendance credit follows whoever actually taught.** `absensi_gurus` gets one row per graded detail (`unique(modul_ajar_detail_id)`, upserted so re-teaching moves credit rather than duplicating), crediting `guru_pengganti_id ?: jadwal.guru_id`. Counted **per graded session**, not per day — 3 classes in a day earns 3.
- Monthly totals sum `absensi_gurus` over a date range, not a running counter, and are deliberately **not** filtered by `ditutup_pada`: "how many sessions did I teach this month" must not reset because payroll ran.

## Frontend conventions

### Design system: Tailwind v4 + daisyUI v5, CSS-first

**There is no `tailwind.config.js` and no `postcss.config.js` — deleting them was the migration.** Everything lives in `resources/css/app.css`, compiled by the `@tailwindcss/vite` plugin in `vite.config.js`: `@import 'tailwindcss'`, `@source` scan paths, `@plugin 'daisyui' { themes: false }`, then two `@plugin 'daisyui/theme'` blocks (`eling` default, `eling-dark` prefersdark).

- **Never hardcode a Tailwind palette color for chrome** (`bg-white`, `text-gray-500`, …). Use semantic tokens: `bg-base-100` (surface), `bg-base-200` (sunken/hover), `bg-base-300` (borders), `text-base-content` with an opacity suffix, and `primary`/`accent`/`success`/`warning`/`error`/`info`/`neutral` for meaning.
- **`text-base-content/60` is the opacity floor for readable text.** `/40` and `/50` measure 2.55:1 and 3.41:1 against `base-100` in light — both fail WCAG AA. `/40` is fine for a decorative icon only.
- **No `dark:` variants for token-based colors.** daisyUI emits the dark theme at `:root:not([data-theme])` inside `@media (prefers-color-scheme: dark)`; tokens already flip. Nothing sets a `.dark` class. In JS use `isDarkMode()` from `resources/js/core/theme.js`, never `classList.contains('dark')`.
- **The brand is emerald and stays emerald.** Chrome — nav, tabs, focus rings, brand gradients, logo — must never go blue or purple; a whole-theme purple pass was rejected outright. This is a rule about chrome only.
- **Each button role owns a distinct hue; never collapse them into one family.** An all-green toolbar was rejected. `primary` emerald = default action, `accent` violet = supporting/management dialog, `info` sky = record/backup, `success` green = safe confirm & WhatsApp, `warning` amber = mass action needing care, `btn-export` rose = generate document, `btn-sacred` deep rose = destructive/sensitive, `btn-neutral` = cancel/back. `accent` must stay out of the green family.
- **Custom button/badge variants must be unlayered and set daisyUI's custom properties.** daisyUI paints from `--btn-color`/`--btn-fg` (and `--badge-color`/`--badge-fg`), so plain `background-color` loses to `.btn`'s own declaration. And the compiled layer order is `properties, theme, base, components, utilities, daisyui` — **`daisyui` is last and a cascade layer beats specificity outright**, so nothing inside `@layer components` can ever win. That is why the `.btn.btn-*` / `.badge.badge-*` overrides sit at the very end of `app.css` outside any layer. Moving them back inside silently reverts every custom colour.
- **Solid green surfaces always carry white text, decoupled from the tokens.** No single green is both a good white-text button (≥4.5:1 on white) and a readable `text-primary` on `base-100` — the requirements pull opposite ways. Tokens stay light in dark mode (good for `text-primary`), while `.btn.btn-primary`/`.btn.btn-success`/`.btn.btn-accent`, matching badges, `.brand-chip`, `.app-tab-active` and `.modal-header-brand` pin explicit dark greens (#047857 / #15803d / #0f766e) with white text, identical in both themes at 5.0–5.5:1. Don't "simplify" these onto `bg-primary`/`text-primary-content`.
- **v4 `@apply` only takes utilities** — not your `@layer components` classes, not daisyUI components like `btn`. The four composable bases (`app-card`, `app-tab`, `icon-action`, `brand-chip`) are declared with `@utility` at top level so others can `@apply` them. `btn-export`/`btn-sacred` are modifiers expecting `btn` beside them.
- **Renames already applied, don't reintroduce:** `rounded-btn`→`rounded-field`, `rounded-badge`→`rounded-selector`, `outline-none`→`outline-hidden`, `flex-shrink-0`→`shrink-0`, `flex-grow`→`grow`, shadow/blur scale shifted one step (`shadow`→`shadow-sm`, `shadow-sm`→`shadow-xs`). daisyUI v5 dropped `input-bordered`/`select-bordered`. v4's default border color is `currentColor`, so `app.css` restores `*, ::before, ::after { border-color: var(--color-base-300) }` — don't "fix" bare `border` back to `border-gray-200`.
- Where a name collides with a daisyUI component, **daisyUI wins and ours is prefixed `app-`**: `.app-canvas`, `.app-card`/`-hover`/`-pad`, `.app-stat`/`-value`/`-label`, `.app-section-head`/`-title`/`-sub`, `.app-empty`/`-icon`/`-title`/`-text`, `.app-label`, `.app-input`, `.app-chip`, `.app-tab`/`-active`, `.app-table-wrap`, `.brand-chip`. Use daisyUI's own `badge`, `table`, `join`, `alert`, `checkbox`, `radio`, `select`, `input`, `modal`, `loading` rather than reinventing them.
- Reuse before hand-rolling: `x-list-panel` + `x-list-row`, `x-portal-nav`, `x-filter-multi`, and the Breeze form components (`x-text-input`, `x-input-label`, `x-input-error`, `x-primary-button`, …).

### Alpine and form-control traps

Each of these was a real reported bug; regression tests pin the fixed markup.

- **Tailwind cannot see interpolated class names.** `bg-{{ $warna }}-50` produces nothing. Put the complete class string in the PHP array/ternary.
- **Never put `x-show` on a `<select>`.** `installSearchableSelects()` hides the real select with `display:none !important` and paints a `<button>` trigger beside it; `x-show` writes to that same inline property, clears the `!important`, and the raw OS-styled select reappears *next to* the trigger. The enhancer selector now ends `:not([x-show]):not([x-transition])`, but the right shape for a conditional select is `:disabled` plus a dimming class — a stable row height also stops the layout jumping. For a genuinely short list add `data-native-select="true"`.
- **Every form control gets its daisyUI class.** A bare `<input type="checkbox">` renders as an OS-blue box. Found three times now (a raw 16px checkbox, the Absen "Hadir" tick, 12px `w-3 h-3` payment radios).
- **`@click.outside` belongs on the wrapper that contains the trigger, never on the panel.** Alpine registers it on `document` and skips only when `el.contains(e.target)`. On a panel created by `x-if`, the very click that opened the dropdown reaches `document` after the panel mounts and closes it instantly — the control looks dead. See `components/dropdown.blade.php` and `components/filter-multi.blade.php`.
- **`[x-cloak] { display: none !important }` lives in `@layer base`.** 14 elements used `x-cloak` while nothing defined it. Don't delete the rule assuming it's unused.
- **A `transform` on an ancestor makes `position: fixed` resolve against that ancestor.** An animated wrapper around the admin slot once pushed every modal off-screen. Keep entry animations on leaf cards, never on a wrapper containing a modal.
- **Responsive is verified by measurement**, at 375px and 768px, for horizontal overflow (`scrollWidth` vs `clientWidth`, ignoring own-`overflow-x` containers) and tap targets under 32px. Size controls **up** for touch and only shrink at `sm:` and above (`checkbox checkbox-primary sm:checkbox-sm`) — a `btn-xs sm:btn-sm` toggle that gave phones the smallest size was a real bug.

### Shared JS

- `resources/js/core/http.js`'s **`kirim(url, method, payload)`** is the shared POST/PUT/DELETE-via-`_method`-override fetch helper. It translates Laravel's validation-exception shape (`{message, errors}`) into this app's `{status, message}` by joining field messages — without it a `validate()` failure surfaces as a generic "The given data was invalid." toast instead of the controller's Indonesian message. New callers go through it.
- **`installSearchableSelects()`** (`resources/js/ui/searchable-select.js`) auto-enhances every plain `<select>` (including ones Alpine renders later, via `MutationObserver`) unless it is `multiple`, `data-native-select="true"`, or inside a SweetAlert2 popup. You rarely need to hand-build a searchable picker.
- **Multi-value filters use `<x-filter-multi>`**, not a hand-rolled dropdown per field: `label`, `model` (parent Alpine array, interpolated into `x-model` by Blade), `options` (expression yielding `{value, label, sub}`), `noun`. All six Data Siswa filters use it. Checkbox `value` is **always a string**, so `filterStudents` compares with `String()`, not `Number()` — production session ids look like `183714` and a mixed string/number array misses silently.
- Use SweetAlert2 for all confirmations; never native `alert`/`confirm`.

## Data entry surfaces

### Workshop vs Akun Guru

- **`WorkshopController`** (`/admin/workshop`) is the *only* place to create or edit reference data and students. It reuses the existing `admin.mapel.*`/`admin.guru.*`/`admin.ruang.*`/`admin.sesi.*`/`admin.siswa.*` routes unchanged — a new UI over old controllers, not a new API.
  - Forms show live "you already have something like this" hints, computed client-side against preloaded lists. They are **informational and never block submission**; server-side unique constraints are what actually prevent duplicates.
  - Siswa-specific hints: a matching `no_hp` flags a likely sibling (family billing should merge); typing a `kelas` shows which classes already run for that grade (`WorkshopController::petaKelas()`).
  - A Data Siswa row's "Detail & Catatan" links to `Workshop?edit_siswa={id}`, which pre-opens that edit form (`editSiswaId`).
- **`AkunGuruController`** (`/admin/akun-guru`) is scoped *only* to teacher login accounts. Don't grow it back into a general reference-data page.

### A student holds up to five packages

`siswas` has `paket_pembayaran` plus `paket_pembayaran_2..5`, and the Data Siswa quota is the **sum of `pertemuan` across all five**. Three places used to read only the first slot and were fixed together: the Workshop form (reveals the next slot as you fill one, shows the running pertemuan and rupiah total), the Data Siswa package filter and student row, and `SiswaController::store()/update()` validation. Keep `SiswaController::KOLOM_PAKET` and `student-list.js`'s `PACKAGE_FIELDS` in step.

### Bulk student import

"Download Kerangka" (`SiswaController::downloadImportTemplate` → `SiswaTemplateExport`) gives an `.xlsx` with `Nama Lengkap`, `Panggilan`, `Kelas`, `No. HP`, `Kemampuan`, and `Nama Paket` through `Nama Paket 5`. Upload (`SiswaController::import` → `SiswaMassalImport`) matches rows to existing students **by exact trimmed name** — match updates, no match creates.

- A **blank cell never overwrites** an existing value, so staff can update one column safely.
- `Kemampuan` holds the **level number** (`1`, `2`, …), resolved against `tingkat_kemampuans.level` — not the id, because the number is what staff know.
- Package names resolve by exact `Paket.nama_paket`. An unrecognized package or level is skipped without failing the row.
- Unrelated to the legacy `SiswaImport` / `siswa:import` command, which **truncates** `pakets`/`siswas`/`jadwals`/`pembayarans` before importing a fixed legacy layout. Don't point anyone at it for routine entry, and don't merge the two.

### The help centre is content, not markup

`App\Support\PusatBantuan` holds every screen's guide, keyed by route name (and `dashboard.<tab>`); `layouts/admin-help.blade.php` is just the drawer. It was a 200-line `@php` if/elseif chain inside that layout, which is why six screens silently fell through to a placeholder. Guru-facing pages are standalone HTML documents, so each `@include`s the drawer before `</body>`.

**Add a `PusatBantuan` entry whenever you add a screen** — the fallback is a safety net, not an acceptable end state. Write for a non-technical reader: what the button does and what happens after, not what the field is called.

## Exports and security-sensitive paths

**PDF exports** live in `resources/views/pdf/*.blade.php` (jadwal, siswa, pembayaran, struk, rapor) via DomPDF. Keep export queries in sync with the filters active in the corresponding admin view — divergence is a recurring bug source. DomPDF's CSS support is limited; keep styling conservative.

**Excel** uses `maatwebsite/excel` (`app/Exports/*`, `app/Imports/*`). Every sheet with a phone-shaped column must implement `WithCustomValueBinder` via `App\Exports\Concerns\MemaksaTeksUntukAwalanPlus` — otherwise PhpSpreadsheet guesses `+6281234567890` is a number, drops the `+`/leading `0`, and renders scientific notation. New phone columns need the trait; being a string is not enough.

### The public schedule PDF must never carry internal notes

`/jadwal-kalender/export` is deliberately **unauthenticated** (the public calendar has an Export button and the admin dashboard reuses the route). It used to attach `studentsWithNotes`, printing every internal `Tanda` to anyone with the URL. `JadwalController::exportPdf()` now collects notes only when `auth()->check() && hasRole('admin')`. `KeamananEksporDanStashTest` parses the PDF with `smalot/pdfparser` — a byte-level assertion cannot work, DomPDF flate-compresses its streams. **If anything else is added to that PDF, ask first whether a stranger may read it.**

### Restoring a stash is reversible, audited, and validated up front

`uploadStash()` replaces **every** `jadwals` row — the most destructive action an admin can take. `App\Services\StashJadwalService` guarantees four things, in order:

1. **Structure is validated before anything is touched** — `content` present and an array, every row carrying numeric `h/s/m/g/r/si`, with the offending row number in the message.
2. **Referenced ids are checked to still exist.** A stash naming a deleted guru/ruang/sesi/siswa used to blow up on the foreign key *after* the delete; it is now rejected up front and nothing is deleted.
3. **The pre-restore state is archived** into `stash_pemulihan_logs` (precedent: `koreksi_pembayaran_logs`): who, when, counts before/after, and the whole previous schedule as a `.stash` payload. `GET admin/jadwal/cadangan-stash/{pemulihan}` downloads it and the success dialog links to it, so restoring the wrong file is undoable.
4. **Collisions the incoming data would create are counted and reported** at restore time, not left to be discovered in Ringkasan.

Catch clauses here are `\Throwable`, not `\Exception` — a `TypeError` from a malformed file used to escape the handler and leave the schedule deleted. Restore deliberately bypasses collision *validation* (a real timetable may need reinstating as-is), which is why the count is surfaced and `bentrokTersembunyi()` keeps reporting it.

### Deleting reference data that's in use

`GuruController`, `RuangController`, `MapelController`, `SesiController` and `PaketController::destroy()` all check for dependent `jadwals` rows (for Paket, any of the five `paket_pembayaran*` columns) and reject with 422. `jadwals.guru_id`/`ruang_id`/`mata_pelajaran_id`/`sesi_id` are all `onDelete('cascade')`, so deleting a teacher who is still teaching would otherwise silently wipe their classes. Keep this guard on any future destroy path.

### `RingkasanService::kebersihanData()`

Its `tanda_lama` entry (notes older than `TANDA_LAMA_HARI`, 14 days) is a pure age check against `created_at` — there is no "resolved" flag on `Tanda`, so a note keeps surfacing until someone deletes it.

## Deployment (Vercel)

Serverless PHP via the community `vercel-php` runtime (`vercel.json`, `api/index.php` → `public/index.php`).

- **Build-time config/route caching was tried and reverted — it took production down**, breaking *every* route including the public `/` page, which is the signature of a bad cached config rather than a feature bug. `vercel.json`'s four `/tmp` env redirects (`APP_CONFIG_CACHE`, `APP_ROUTES_CACHE`, …) are back and the `vercel` composer script is gone. **Don't reintroduce it without a way to verify against a real (throwaway preview) deploy** — reasoning from code alone was not enough, and Vercel's log truncation hid the actual exception both times. Instrument it if attempted again.
- **SweetAlert2 and Font Awesome are bundled via Vite, not CDN.** `resources/js/app.js` imports both and sets `window.Swal` explicitly so pre-existing bare `Swal.fire(...)` call sites keep working; new code should still use `AppSwal` (`resources/js/core/alerts.js`). This buys one origin and no third-party outage dependency, not a smaller payload. Bunny Fonts stays on CDN deliberately. Every page loads `app.js`; a page that needed these without it would need a different fix.
- Slow production responses were the root cause of the double-click epidemic the payment guards exist to survive. Making responses faster reduces the frequency but never removes the need for those guards.

## Testing notes

Tests run on **SQLite in-memory** (`phpunit.xml`), so they never touch local MariaDB or production TiDB — this is the safe way to verify migrations. `phpunit.xml` also raises `memory_limit` to 512M, because parsing generated PDFs in `KeamananEksporDanStashTest` blows past PHP's 128M default.

`tests/Feature/ScheduleAndPaymentTest.php` is the primary regression suite: atomic schedule creation, collision rejection, `+62` preservation, payment allocation, overpayment rejection, auto `Selesai sistem` detail on settlement, receipt rendering, anti-duplicate mass billing, batch locks, per-tab payload size. When changing payment logic re-verify remaining-balance math, discounts, "set lunas", "selesaikan seluruh status", struk rendering; when changing schedule logic re-verify collision validation, transactional store, card grouping, PDF export.

Supporting suites:

| Suite | Covers |
| --- | --- |
| `AntiDoubleClickTest` | server-side duplicate-submission guard |
| `RapikanDuplikatPembayaranTest` | duplicate consolidation (its `setUp()` drops the anchor unique index on purpose, to recreate legacy data) |
| `NormalisasiNomorHpTest` | phone normalization (dirty-data setups write via `DB::table()` to bypass the mutator on purpose — that's how it simulates legacy rows, not a workaround to "fix") |
| `ProduksiTerlindungiTest` | destructive-command guard |
| `DemoSeederTest` | seeded data satisfies the real invariants, one `kode_kelas` per class |
| `PeranDanPortalGuruTest`, `AkunGuruTest` | role gating, guru portal isolation, teacher accounts |
| `WorkshopTest` | reference-data CRUD, hint data, `edit_siswa` deep link, phone accept/reject |
| `SiswaImportTest`, `PaketDanImportSiswaTest` | insert-vs-update-by-name, blank cells not overwriting, paket-by-name, blank-name rows skipped, real end-to-end upload; all five package slots, bad package id rejected, slot cleared, Kemampuan by level, packages 2–5 by name |
| `ExportPhoneNumberFormatTest` | generates a real file per sheet and reads the cell back — a string assertion on the mapped array is not enough, corruption happens in the value binder |
| `PerlindunganHapusDanUbahTest` | delete-in-use guards, Pembayaran update guards |
| `FilterSiswaTest` | Data Siswa export accepting `kelas[]`/`paket_ids[]`/`sesi_ids[]`/`guru_ids[]`/`ruang_ids[]`, legacy comma and single-`paket_id` URLs, package in any slot, six checkbox dropdowns rendering |
| `KontrolFormTampilanTest` | daisyUI classes on the Absen checkbox and payment radios, Nilai select opting out of the enhancer, `[x-cloak]` rule and `:not([x-show])` guard present |
| `PayrollTest` | total arithmetic, counter resetting while attendance rows survive, rate snapshot surviving a raise, zero-attendance base salary, double-submit guard, void releasing attendance, running estimate clearing after close, page/route/validation |
| `BentrokIrisanSesiTest`, `HariIniTest`, `SesiWaktuTest` | overlap rule both directions, "today" by day name, `HH:MM` round-trip and non-negative duration |
| `StashJadwalTest`, `KeamananEksporDanStashTest` | restore log, download-and-restore round-trip, missing-reference rejection, collision warning; public PDF note leak |
| `ModulAjarTest` | admin-sees-all vs guru-sees-own, create-yes/update-no split, `kode_kelas` surviving drag-move / edit-modal / stash round-trip, teaching + substitute + re-teach flow |
| `RaporSiswaTest` | aggregation, date filtering, 4-score minimum before a trend, PDF download, guru denied |
| `PusatBantuanTest` | every guide populated, no screen on the fallback, each route rendering its own |

**Watch for editor auto-reformatting breaking `assertSee`.** Something here occasionally re-wraps long Blade lines, inserting a newline between text that used to be adjacent (e.g. `Rp` and the number). The page still renders fine but `assertSee('Rp 200.000')` fails. If a passing assertion starts failing with no nearby logic change, check for line-wrapping before suspecting the data.

`database/seeders/DemoSeeder.php` builds a realistic state — 16 students in 10 families with siblings sharing a phone, 3 months of mixed-status invoices, installments, discounts, collision-free classes, archived students — **plus the states that are otherwise invisible until someone happens to be in them**:

- a class **mid-lesson** (`sedang_dipersiapkan`), an **open slot** (`tidak_bisa_hadir`), a class held by a **substitute**, and one lesson whose attendance credit deliberately lands on the substitute rather than the schedule's owner
- an **inactive assessment aspect** that still carries old scores, so the Nonaktif badge and the "old rapor survives deactivation" rule are both exercised
- a **cancelled payslip** with its reason, its attendance released back, alongside two live ones dated into the past
- a **teacher with no account and no rates** — the majority state in production
- **free-form invoices** with `id_paket = NULL` (buku, denda, tryout), one settled and one not
- a student **not yet scheduled**, a student holding **three packages**, and a note older than `TANDA_LAMA_HARI` — the three things the Ringkasan cleanliness panel looks for
- operational traces that normally only appear after real use: a `batch_pembayaran_logs` lock row, `jadwal_teks_logs` entries, and a `stash_pemulihan_logs` record carrying a real restorable payload

It also **creates `admin@example.com` itself** rather than relying on `DatabaseSeeder`, because it prints those credentials at the end and running it alone used to make that message a lie. Its session list deliberately overlaps so the timing rule is exercised by the demo data. Wired into `DatabaseSeeder` behind an `isProduction()` guard, so `php artisan migrate:fresh --seed` gives a ready environment in one command.

`DemoSeederTest` asserts both the invariants (details sum to `total_sudah_dibayar`, status matches amounts, no duplicate anchors, no schedule collisions, every present student scored on every active aspect) **and the richness above** — if you simplify the seeder and a state stops being represented, a test fails rather than the demo quietly going bland. It must also stay idempotent: `test_running_it_twice_does_not_duplicate_anything` reruns the whole thing.

## Open design question

Curriculum, the per-meeting before/after report, and both attendance types all conceptually hang off "a specific meeting of a specific class on a specific date" — but a `jadwals` row is a recurring weekly slot with no date, and Modul Ajar's `kode_kelas` anchor deliberately has no date dimension.

Two roadmap items remain unbuilt because of this: a distinct **before/after** assessment pair (today there is one score, not a pair capturing improvement), scored per syllabus item rather than per calendar meeting. Designing the dated meeting/occurrence model is the prerequisite, and should be settled with the owner before either is started.
