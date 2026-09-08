# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Kanban Kalender** is a Laravel 12 / PHP 8.3 internal admin system for **E-Ling Course**, a tutoring business. It manages class schedules (jadwal), student records (siswa), and payments/billing (pembayaran), plus exposes a public read-only calendar. Stack: Blade + Tailwind CSS + Alpine.js, SweetAlert2 for UX, DomPDF for exports, deployed to Vercel as serverless PHP (see `vercel.json`, `api/index.php`).

For deep architecture notes, fragile areas, controller-by-controller data flow, and the export matrix, read **[FEED.md](FEED.md)** — it is a maintained technical memory doc for this repo (in Indonesian) and should be treated as authoritative context, not just background reading. When touching Jadwal or Pembayaran logic, read the relevant FEED.md section first.

## Commands

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build          # or: npm run dev (Vite dev server)
php artisan serve
```

Full dev stack (server + queue + logs + vite concurrently):

```bash
composer run dev
```

Run the main PHP test suite:

```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/Feature/ScheduleAndPaymentTest.php
vendor/bin/phpunit --filter=test_method_name
```

On Windows with a project-local PHP 8.3 (see `php83.ini`, `project-terminal.cmd` — local-only, gitignored):

```powershell
& 'C:\PHP 8.3\php.exe' -c 'php83.ini' vendor\bin\phpunit tests\Feature\ScheduleAndPaymentTest.php
```

If `npm run build` fails under PowerShell execution policy, fall back to `cmd /c npm run build`.

JS unit tests (plain Node test runner, no framework):

```bash
npm run test:js
```

Static analysis (Larastan, level 1, scoped to Controllers + Models):

```bash
vendor/bin/phpstan analyse
```

Code style:

```bash
vendor/bin/pint
```

## Architecture

The app is split into three admin domains plus one public area, all routed through `routes/web.php`:

1. **Jadwal** (`JadwalController`) — schedule CRUD, drag/move, collision protection, PDF export, WhatsApp-text generation. It no longer owns reference-data CRUD (mapel/guru/ruang/sesi) — that moved to Workshop, see below.
2. **Data Siswa** (`SiswaController`, `ArsipController`) — read-only listing, filter, archive/restore/permanent-delete, PDF export. Creating and editing a student's core fields (name, kelas, no_hp, paket) moved to **Workshop**; this tab keeps the lifecycle actions (archive/restore/delete) plus a read-only detail panel where `Tanda`/catatan notes are now managed per student.
3. **Pembayaran** (`PembayaranController`, `PembayaranDetailsController`) — invoicing, mass billing, installments, discounts, "lunas" (paid-off) workflows, receipts, PDF export. This is the most fragile controller in the codebase.
4. **Public calendar** (`jadwal-kalender` routes) — read-only schedule view, no auth.
5. **Workshop** (`WorkshopController`) — the single place to create/edit Mata Pelajaran, Guru, Ruang, Sesi, and Siswa. See "Roles and access" below for why this exists and how it differs from Akun Guru.
6. **Modul Ajar** (`ModulAjarController`) — the one screen shared by both roles (`role:admin|guru`). Admin and guru fill in curriculum per class; see "Modul Ajar and `kode_kelas`" below for the identity mechanism and the split CRUD permissions.

`DashboardController` is the single admin entry point (`/dashboard`) and deliberately loads data **per active tab**, not all at once — a prior full-payload version triggered `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE` on Vercel's serverless functions. Any change here must be checked for response size and eager-loading blast radius; the same applies to the "family payment detail" endpoint (`GET /admin/pembayaran/keluarga/{no_hp}/detail`), which exists specifically to avoid shipping the full invoice history up front.

**Domain model:** `Siswa`, `Jadwal`, `Hari`, `Sesi`, `Guru`, `Ruang`, `MataPelajaran`, `Paket`, `Pembayaran`, `PembayaranDetail`, `Diskon`, `Tanda`, `Arsip`, `BatchPembayaranLog`. One class group is shown as a single card in the UI but is persisted as multiple `jadwals` rows (one per student) — collision checks and edits must account for hari/sesi/guru/ruang/siswa combinations across all of them, not a subset. `Pembayaran` is the invoice/header record per student; `PembayaranDetail` is the append-only ledger of deposits/installments/settlements — payment status changes must add a detail row, never silently overwrite totals. Payment status stays a legacy integer (`0` belum bayar / `1` proses / `2` lunas); don't change its type without an explicit decision.

Family/discount logic keys off `no_hp`. As of Sept 2026 production data was normalized to `+62...` in a single reviewed pass (`pembayaran:normalisasi-hp`), so the format is now genuinely uniform across `siswas`, `pembayarans`, `arsips`, `diskons`. The input-time gap this used to describe is closed: `App\Models\Concerns\MenormalisasiNoHp` (a `setNoHpAttribute` mutator, backed by `App\Support\NomorHp::normalkan()` — the same algorithm `pembayaran:normalisasi-hp` uses) is applied to all four models, so any `no_hp` written through Eloquent — the Workshop form, the bulk Siswa import, factories, anywhere — is normalized to `+62...` on save, not just on request. `SiswaController`'s validation was loosened to match: it no longer requires the caller to already type `+62...`, it just rejects values `NomorHp::normalkan()` can't recognize at all. `pembayaran:normalisasi-hp` is now mainly for pre-existing legacy rows and anything written by raw `DB::table()` writes that bypass Eloquent (e.g. `uploadStash`, which touches `jadwals` not `siswas`, is unaffected either way).

## Deployment & performance (Vercel)

The app deploys to Vercel as serverless PHP via the community `vercel-php` runtime (`vercel.json`, `api/index.php` → `public/index.php`). Two things were changed Sept 2026 specifically to shorten production load time, since a slow response was the root cause of the double-click epidemic the anti-double-click guards (above) exist to survive — this doesn't remove the need for those guards, it just makes the slow case rarer.

- **Config/route build-time caching was tried and reverted (Sept 2026) — it took production down.** The idea: bake `config:cache`/`route:cache` into `bootstrap/cache/*.php` during Vercel's build (via a `composer.json` `scripts.vercel` hook) instead of pointing `APP_CONFIG_CACHE`/`APP_ROUTES_CACHE`/etc. at `/tmp` (where nothing ever actually got cached, since nothing wrote there). This was flagged at the time as "needs verifying on a real deploy," and the real deploy broke *every* route, including the plain public `/` welcome page — not just one tab — which is exactly the signature of a bad cached config/route file affecting the whole app rather than a feature-specific bug. Reverted: `vercel.json`'s four `/tmp` env redirects are back, and the `vercel` composer script is gone. **Don't reintroduce build-time config/route caching without a way to actually verify it against a real deploy first** (e.g. a preview deployment you can throw away) — reasoning about it from the code alone was not enough last time. If this gets attempted again, the prime suspect if it breaks was never confirmed (Vercel's log line truncation cut off the actual exception both times), so instrument it — a Sentry-style external logger or writing the last exception to a `/tmp` file page could help.
- **SweetAlert2 and Font Awesome are bundled via Vite, not loaded from `cdn.jsdelivr.net`/`cdnjs.cloudflare.com`.** Every page used to independently load both from CDN `<script>`/`<link>` tags (7 different layout/view files, each hand-duplicated) — `resources/js/app.js` now `import`s both directly and sets `window.Swal` explicitly (so the many pre-existing bare `Swal.fire(...)` call sites in `jadwal.js`/`pembayaran.js`/`akun-guru.js` keep working unchanged; only the truly global-shaped code path uses `window.Swal`, everything new should still go through `AppSwal`/`resources/js/core/alerts.js`). This trades "possibly-cached third-party bytes" for "one origin, no extra DNS/TLS handshake, no dependency on a third-party CDN being reachable" — it is not primarily a byte-count reduction (Font Awesome's full icon set is still ~250KB across the woff2 files, just self-hosted now), it's a fewer-round-trips and no-external-outage-dependency change. Google/Bunny Fonts (`fonts.bunny.net`) were deliberately left on CDN — that one's a small, purpose-built font host, not a general-purpose CDN. If a page ever needs Font Awesome or SweetAlert2 without loading `app.js`, it needs a different fix (there isn't one currently — every page loads `app.js`).

## Payment integrity invariants

These were established while fixing a production incident where mass billing duplicated manually-created invoices (Rp 10.375.000 across 31 groups). Do not weaken them.

- **Package invoices are anchored, not matched by text.** `pembayarans.id_paket` + `pembayarans.periode` (`YYYY-MM`) uniquely identify "this student's bill for this package this month", enforced by `UNIQUE(id_siswa, id_paket, periode)`. The old dedup compared the free-text `keterangan`, which silently missed manual invoices worded differently. Any new invoice-creating path must set the anchor. Invoices with `id_paket = NULL` are free-form (buku, denda, kegiatan) and are intentionally unconstrained — they may repeat.
- **Mass actions run once per period.** `penagihanMassal` and the global `lunasSemua` acquire a lock row in `batch_pembayaran_logs` (`UNIQUE(jenis, periode)`) before doing work, and release it if the run produced nothing. Per-family settlement (`keLunasMassal`) is deliberately *not* locked — it's a daily action.
- **Double-submit is blocked in three layers**, because the old US-region Vercel deploy was slow enough that staff clicked twice: a full-screen blocking overlay bound to `isLoading`, `:disabled` on every money-touching button, and a server-side guard (`PembayaranController::JEDA_ANTI_GANDA`, 180s) rejecting identical payment/invoice submissions. The server layer is the one that matters — the others can be bypassed by refresh or retry.
- **Destructive artisan commands are blocked in production** via `DB::prohibitDestructiveCommands()` in `AppServiceProvider`. `.env` is swapped back and forth between local MariaDB and production TiDB, so `migrate:fresh` against the wrong target would destroy the books. `--force` cannot bypass this guard.
- **`PembayaranController::update()` cannot reassign an invoice that already has settlement history**, and cannot lower `harga` below `total_sudah_dibayar`. Both were previously unguarded — an admin could silently move a paid invoice onto a different student's record, or set a price under what was already collected. If you need to fix a misassigned invoice with existing `PembayaranDetail` rows, create a new invoice for the correct student instead of reassigning the old one.

Maintenance commands (all default to a read-only/dry-run mode; `--force` applies):

```bash
php artisan pembayaran:audit-duplikat      # report duplicate invoices (read-only, never writes)
php artisan pembayaran:rapikan-duplikat    # consolidate duplicates, archive removals
php artisan pembayaran:normalisasi-hp      # normalize phone numbers to +62 across all tables
```

`pembayaran:rapikan-duplikat` keeps the earliest invoice, drops payment details that are exact twins (same amount + same date) or `Selesai sistem`, **moves** any genuinely different payment to the survivor rather than deleting it, and archives every removed row as JSON into `koreksi_pembayaran_logs` before deleting.

**PDF exports** live in `resources/views/pdf/*.blade.php` (jadwal, siswa, pembayaran, struk) and are rendered via DomPDF (`barryvdh/laravel-dompdf`). They must be kept in sync with whatever filters (status/month/search) are active in the corresponding admin view — export queries diverging from display queries is a recurring source of bugs. DomPDF has limited CSS support, so keep PDF styling conservative.

**Excel import/export** uses `maatwebsite/excel` via `app/Exports/*Export.php` and `app/Imports/SiswaImport.php`. Every export sheet that includes a `no_hp` (or any other phone-number-shaped) column implements `WithCustomValueBinder` via the shared `App\Exports\Concerns\MemaksaTeksUntukAwalanPlus` trait — without it, PhpSpreadsheet's default value binder guesses that a string like `+6281234567890` or `081234567890` is a number, silently drops the `+`/leading `0`, and renders it as scientific notation (`6,2851E+12`) once the column is wide enough to trigger it. Any *new* export column holding a phone number needs this same trait; it won't happen automatically just by being a string.

**Services:** `PaymentBatchService` (mass billing / settlement logic) and `RingkasanService` (dashboard summary aggregation) hold business logic that would otherwise bloat the controllers — check these before adding payment or dashboard-summary logic directly in a controller. `RingkasanService::kebersihanData()` includes a `tanda_lama` entry (notes older than 14 days, `TANDA_LAMA_HARI`) surfaced on the Ringkasan tab — it's a pure age check against `created_at`, there's no "resolved" flag on `Tanda`, so a note keeps showing up until someone deletes it.

## Frontend conventions

### Design system: daisyUI tokens, not hardcoded palettes

The UI was unified onto daisyUI's theme tokens (Sept 2026). **Never write a hardcoded Tailwind palette color for chrome** (`bg-white`, `text-gray-500`, `border-gray-200`, `bg-slate-900`, …) — use the semantic tokens instead: `bg-base-100` (card/surface), `bg-base-200` (sunken/hover), `bg-base-300` (borders/dividers), `text-base-content` with an opacity suffix for de-emphasis (`/80`, `/70`, `/60`, `/50`, `/40`), and `primary` / `accent` / `success` / `warning` / `error` / `info` / `neutral` for meaning. Raw palette colors are still fine for genuinely decorative one-offs, not for structure.

**Do not add `dark:` variants for token-based colors.** daisyUI injects the whole `dark` theme at `:root` inside `@media (prefers-color-scheme: dark)` (see `tailwind.config.js` `daisyui.themes`, and `injectThemes` in `node_modules/daisyui/src/theming/functions.js`), so `bg-base-100`/`text-base-content` already flip on their own. A `dark:` variant on top of a token is redundant at best and fights the theme at worst. The project has **no** `darkMode: 'class'` and nothing sets a `.dark` class — dark mode is purely OS preference. In JS, detect it with `isDarkMode()` from `resources/js/core/theme.js`, never `classList.contains('dark')`.

The palette split that matters: **`primary` is blue (brand/chrome — nav, tabs, focus rings, default actions) and `success` is emerald (things that are done, paid, present)**. They used to both be emerald, which made "submit" and "settled" look identical. Keep them distinct.

`resources/css/app.css` defines the reusable pieces on top of daisyUI. Where a name would collide with a daisyUI component (`card`, `badge`, `stat`, `tab`, `table`, `skeleton`, …) **daisyUI wins and ours is prefixed `app-`**: `.app-canvas` (page background), `.app-card` / `.app-card-hover` / `.app-card-pad`, `.app-stat` / `.app-stat-value` / `.app-stat-label`, `.app-section-head` / `.app-section-title` / `.app-section-sub`, `.app-empty` + `-icon` / `-title` / `-text`, `.app-label`, `.app-input`, `.app-chip`, `.app-tab` / `.app-tab-active`, `.app-table-wrap`. Use daisyUI's own `badge`, `stats`, `table`, `join`, `alert`, `checkbox`, `select`, `input`, `modal`, `loading` directly rather than reinventing them.

Shared Blade components worth reusing before hand-rolling markup: `x-list-panel` + `x-list-row` (the tinted "header + count + scrolling list" cards on Ringkasan), `x-portal-nav` (the sticky top bar shared by Portal Guru / Absen / Modul Ajar), and the Breeze form components (`x-text-input`, `x-input-label`, `x-input-error`, `x-primary-button`, …), all of which are now token-based.

**Tailwind cannot see interpolated class names.** `bg-{{ $warna }}-50` silently produces nothing — a real bug this refactor found and fixed in Workshop's summary cards. Always put the complete class string in the PHP array/ternary, never build it from fragments.

Bare `border` and `ring` (no color) resolve to the theme via `theme.extend.borderColor.DEFAULT` / `ringColor.DEFAULT` in `tailwind.config.js`, so they are safe in dark mode; don't "fix" them back to `border-gray-200`.

Button color semantics (`resources/css/app.css`): `btn-neutral` (cancel/back), `btn-primary` (default action), `btn-success` (safe/restore/copy), `btn-warning` (needs attention), `btn-export` (generate document), `btn-accent` (supporting feature), `btn-sacred` (destructive/sensitive — use for permanent delete). Use SweetAlert2 for all confirmations/notifications, never native `alert`/`confirm`. Important dropdowns are expected to be searchable — in practice this mostly happens for free via `resources/js/ui/searchable-select.js`'s `installSearchableSelects()`, which auto-enhances every plain `<select>` on the page (including ones Alpine renders later, via a `MutationObserver`) unless it's `multiple`, marked `data-native-select="true"`, or inside a SweetAlert2 popup — you don't need to hand-build a searchable picker for a new `<select>` unless you're opting out of this.

`resources/js/core/http.js`'s `kirim(url, method, payload)` is the shared POST/PUT/DELETE-via-`_method`-override fetch helper used by `workshop.js`, `modul-ajar.js`, and `akun-guru.js`. It normalizes Laravel's default validation-exception response (`{message, errors}`, no `status` field) into this app's own `{status: 'error', message}` shape by joining the field-level messages — without this, a `$request->validate()` failure would surface as a useless generic "The given data was invalid." toast instead of the specific Indonesian message the controller defined. Any new caller should go through `kirim()` rather than hand-rolling `fetch()`, specifically to keep this translation in one place.

## Roles and access

Built Sept 2026 with `spatie/laravel-permission`. Two roles: `admin` and `guru`.

- Every `/admin/*` route plus `/dashboard` sits behind `role:admin`. The teacher portal `/guru` sits behind `role:guru`. Profile routes are open to both. Access is enforced at the route layer, not by hiding buttons.
- `RoleSeeder` creates both roles and grants `admin` to any account that has none — existing accounts predate roles and must not be locked out. Run it after migrating.
- `gurus.email` is nullable: teachers that already existed have no email, and the admin fills them in one by one from Master Data. Teachers without an account remain fully schedulable.
- A login account links via `users.guru_id` → `gurus`. The direction matters: `Guru` is the domain entity referenced by `jadwals`, `User` is only the way in. Deleting a user never touches the schedule.
- `UserFactory` creates admins by default (matching how the app behaved before roles existed); `->guru($guru)` and `->tanpaPeran()` are the other states. Role assignment happens in chained `afterCreating` callbacks, not a custom property — Laravel builds a new factory instance on `state()`, which silently discards custom properties.

`GuruPortalController` (the `/guru` schedule view) is read-only by construction — that controller itself has no write route. It groups a teacher's `jadwals` rows into class cards using the same key as the admin screen. The one exception to "guru never writes" app-wide is Modul Ajar (below): guru accounts can create curriculum entries for their own classes there, though update/delete stays admin-only.

### Modul Ajar and `kode_kelas`

Built Sept 2026 as the first (and only) shared admin/guru write surface, and the first implementation of item 3 from "Planned direction" (curriculum per meeting) — see that section for why it turned out simpler than originally scoped.

- **The problem it solves:** a "class" (one kanban card in Jadwal Pelajaran) is really a group of `jadwals` rows — one per student — that share `hari_id`/`sesi_id`/`mata_pelajaran_id`/`guru_id`/`ruang_id`. That composite key is not a safe long-term identity: it changes the moment the class is dragged to a new day/session, and two unrelated classes can legitimately share the same mapel+guru+ruang at different times. Modul Ajar needed something that survives a move and never collides.
- **The fix:** `jadwals.kode_kelas` (string, backfilled by migration `2026_09_05_100000_...`) is a UUID assigned once per class and carried forward through every schedule-editing path: `JadwalController::store()` generates one for a new class, `updatePosisi()` (drag-move) leaves it untouched since it only updates `hari_id`/`sesi_id` in place, and `updateKelas()` (the edit-class modal, which deletes and reinserts rows) explicitly captures the old `kode_kelas` before deleting and reapplies it to the reinserted rows. `downloadStash()`/`uploadStash()` round-trip it too; a legacy stash file with no `kode_kelas` field falls back to `JadwalController::isiKodeKelasKosong()`, which backfills fresh codes per distinct class combination after restore — the same grouping logic as the original migration.
- **Data shape:** `modul_ajars` (one row per `kode_kelas`: `tujuan_pembelajaran`, `kompetensi_awal`, `model_pembelajaran`, `sarana_media` — all required, this is the "header") has many `modul_ajar_details` (`materi` required, everything else — `sub_materi`, `cara_mengajar`, `tugas`, `tujuan`, `hasil_akhir_pembelajaran`, `keterangan` — optional; zero detail rows is a normal, expected state).
- **Permissions are split by verb, not just by class ownership.** Both admin and the assigned guru can view a class and create its header and detail rows. Only admin can update or delete either — a guru's own `simpanHeader` call is rejected with 403 if a header already exists for that `kode_kelas` (`ModulAjarController::simpanHeader`), and `updateDetail`/`hapusDetail` reject any non-admin outright. Don't loosen this without asking — it was an explicit, deliberate choice, not an oversight.
- The kanban card in `resources/views/modul-ajar/index.blade.php` goes light-gray once a header exists, with a badge showing the detail count — mirrors the "aware se aware2nya" pattern used throughout Workshop.

**Teaching, attendance, and substitute-teacher flow (Sept 2026, layered onto the same `modul_ajar_details` rows).** Rather than adding a dated "Pertemuan" entity, each detail row *is* the one-time teaching event for that syllabus item — `sedang_dipersiapkan` (bool), `guru_pengganti_id`, `diajarkan_oleh_guru_id`, and `tanggal_diajarkan` were added directly to `modul_ajar_details`.

- **Flow:** `ModulAjarController::mulaiPersiapan()` marks a detail in-progress (optionally naming a substitute guru) and turns its kanban card amber; `simpanNilai()` grades every student in the class (`hadir` bool + `nilai` 1-5 in `modul_ajar_absensis`, unique per student per detail), then marks the detail complete and clears `guru_pengganti_id`. "Ajar ulang" on an already-taught detail is just calling `mulaiPersiapan()` again — grades are **overwritten**, not versioned; this was a deliberate simplification the owner chose (unlike the payment module's append-only rule), since this isn't money.
- **Substitution never touches `jadwals.guru_id`.** Guru A designates guru B as `guru_pengganti_id` on one detail only; that's what lets guru B pass `ModulAjarController::bolehNilai()` and grade it despite not owning the class. Once graded, `guru_pengganti_id` resets to null on its own — there's nothing to "revert" because the real weekly assignment was never touched. Because of this, `ModulAjarController::index()` scopes a guru's kanban to their own classes **plus** any class where they're currently `guru_pengganti_id` on a detail — without that second half, the substitute has no way to reach the class they were asked to grade.
- **Attendance credit follows whoever actually taught**, not the jadwal's nominal owner: `AbsensiGuru` (table `absensi_gurus`) gets one row per graded detail (`unique(modul_ajar_detail_id)`, upserted — so re-teaching moves the credit rather than duplicating it), crediting `guru_pengganti_id ?: jadwal.guru_id`. It's counted **per graded session**, not deduped per day — a guru completing 3 classes in one day earns 3.
- **No live "close the period" mechanic yet.** Monthly totals (`ModulAjarController::index()`) are computed by summing `absensi_gurus` rows in a date range, not from a running counter — this avoids drift when re-teaching changes who gets credit or when. `absensi_gurus.ditutup_pada` exists now, always null, reserved for a future "tutup kas"-style payroll close; don't build against it yet.
- This is the first real implementation of roadmap items 4/5 below (attendance + per-student scoring), though narrower than what was originally speculated: no formal "before/after" report field, and payroll payout itself is still untouched.

Avoid `COUNT(DISTINCT a, b)`: it is MySQL-only and breaks the SQLite test suite. Use `->distinct()->get()` and count in PHP.

### Workshop vs Akun Guru (Sept 2026 split)

What used to be a single "Master Data" screen is now two deliberately separate menus:

- **`WorkshopController`** (`/admin/workshop`) is the *only* place to create or edit Mata Pelajaran, Guru, Ruang, Sesi, and Siswa. It reuses the existing `admin.mapel.*`/`admin.guru.*`/`admin.ruang.*`/`admin.sesi.*`/`admin.siswa.*` routes unchanged — it's a new UI in front of old, unmodified controllers, not a new API surface. The "Tambah Data Baru" dropdown that used to live inside the Jadwal tab is gone; that functionality lives here now, plus Siswa create/edit which used to live in the Data Siswa tab.
  - Every form shows live "you already have something like this" hints, computed client-side in `resources/js/admin/workshop.js` against the fully-preloaded lists (same "load everything, filter client-side" pattern used everywhere else in this app — see the searchable-select/family-lookup precedent). These are **informational only and never block submission** — the server-side unique constraints are still what actually prevents duplicates.
  - Two hints are siswa-specific: typing a `no_hp` that matches an existing student flags a likely sibling (family billing should be merged); typing a `kelas` value shows which existing classes (hari/sesi/mapel/guru/ruang) already run for that grade, sourced from `WorkshopController::petaKelas()`.
  - A Data Siswa row's "Detail & Catatan" panel links to `Workshop?edit_siswa={id}`, which pre-opens that student's edit form on load (`editSiswaId` in the Alpine payload).
  - Workshop's Siswa panel also has a bulk import (Sept 2026): "Download Kerangka" (`SiswaController::downloadImportTemplate`, via `App\Exports\SiswaTemplateExport`) gives a small `.xlsx` template with the columns `Nama Lengkap`/`Panggilan`/`Kelas`/`No. HP`/`Nama Paket`; uploading a filled copy (`SiswaController::import`, via `App\Imports\SiswaMassalImport`) matches rows to existing students **by exact trimmed name** — a match updates that student, no match creates a new one. A blank cell in an update row is left alone, not written as empty (so a staff member updating just one column can't accidentally wipe the others). `nama_paket` is resolved by an exact `Paket.nama_paket` lookup; an unrecognized package name is silently skipped (paket left unset), not an error. This is unrelated to the old `SiswaImport`/`siswa:import` command — that one is one-off legacy migration tooling that **truncates** `pakets`/`siswas`/`jadwals`/`pembayarans` before importing a fixed legacy spreadsheet layout; don't point anyone at it for routine data entry, and don't merge the two.
- **`AkunGuruController`** (`/admin/akun-guru`) is scoped *only* to teacher login accounts (email + create/change login). It does not list or edit ruang/sesi/mapel/paket at all — that's Workshop's job now. Don't grow this controller back into a general reference-data page; that's the split the Sept 2026 refactor deliberately made.

### Deleting reference data that's in use

`GuruController`, `RuangController`, `MapelController`, `SesiController`, and `PaketController`'s `destroy()` methods all check for dependent `jadwals` rows (or, for Paket, any of the five `siswa.paket_pembayaran*` columns) before deleting, and reject with a 422 if anything is still attached. This exists because `jadwals.guru_id`/`ruang_id`/`mata_pelajaran_id`/`sesi_id` are all `onDelete('cascade')` — deleting a teacher who's still teaching would otherwise silently wipe every one of their scheduled classes with no warning. Keep this guard on any future destroy() path over these tables; don't drop it for a "simpler" delete.

## Planned direction (not built yet)

Recorded Sept 2026. Items 1 and 2 of the original list are done (see "Roles and access" above). Item 3 is also done, but not the way this section originally assumed — see below. The rest is intent — confirm details with the owner before building, since several items reshape the domain model.

**3. Kurikulum per meeting — done, as Modul Ajar (Sept 2026), anchored per class rather than per dated meeting.** This section originally assumed curriculum needed a dated meeting/occurrence record, since a `jadwals` row has no date. When actually scoped with the owner, the requirement turned out to be simpler: one curriculum record **per class** (identified by the new `kode_kelas`, not by calendar date), filled once and carried forward regardless of which week it is. See "Modul Ajar and `kode_kelas`" above for the mechanism. This does **not** retire the meeting/occurrence question below — items 4 and 5 (attendance, before/after reports) are inherently tied to a specific dated meeting ("did today's class happen, who showed up"), so they still need the dated model this section describes. Don't assume Modul Ajar's anchor (`kode_kelas`) is reusable for those — it deliberately has no date dimension.

**4. Per-meeting teacher report (before/after) — partially done.** The grading step in Modul Ajar (see above) records which students attended and a 1-5 score per student per detail, and who actually taught it. What's still missing from the original ask: a distinct "before" vs "after" assessment (right now there's just one score, not a pair capturing improvement), and this is scored per syllabus item, not per literal calendar meeting.

**5. Attendance for students and teachers — partially done.**
- Student attendance: done, as `hadir`/`nilai` on `modul_ajar_absensis` (see above).
- Teacher attendance: done, as `absensi_gurus`, but **not daily** as originally planned — the owner corrected this Sept 2026: it's credited per graded class session, so a teacher who completes 3 classes in a day earns 3, not 1. Whatever payroll math is eventually built must multiply by a daily/session rate accordingly, not assume one credit per calendar day.

**6. Payroll itself is explicitly deferred** — the owner said to leave it for later. Build the attendance data it will consume, not the payout feature.

Because payroll is money, it inherits the payment module's rules when it is eventually built: an append-only ledger rather than mutable totals, a recorded actor and timestamp for every entry, no hard deletes (void/reverse instead), and idempotency against double submission. Two things the current schema lacks and will need first: a daily rate per teacher (`gurus` has only `name`), and the dated meeting/attendance record described above. Attendance is the *input* to pay — so once a payout has been calculated from a day's attendance, later edits to that attendance must not silently change history.

Note how much of this depends on a per-meeting entity that does not exist yet: curriculum, the before/after report, and both attendance types all hang off "a specific meeting of a specific class on a specific date". A `jadwals` row today represents a recurring weekly slot for one student, with no dated occurrence. Designing that meeting/occurrence model is the prerequisite for items 3–5, and should be settled before any of them is started.

## Testing notes

`tests/Feature/ScheduleAndPaymentTest.php` is the primary regression suite and covers: atomic schedule creation, teacher/room/student collision rejection, `+62` phone format preservation, payment allocation correctness, overpayment rejection, auto-generated `Selesai sistem` payment detail on settlement, receipt rendering, anti-duplicate mass billing, monthly batch locks, and per-tab dashboard payload size. When changing payment logic, re-verify: remaining balance math, discount calculation, "set lunas", "selesaikan seluruh status", and struk (receipt) rendering. When changing schedule logic, re-verify: collision validation, transactional store, card grouping, and schedule PDF export.

Supporting suites: `AntiDoubleClickTest` (server-side duplicate-submission guard), `RapikanDuplikatPembayaranTest` (duplicate consolidation — note its `setUp()` drops the anchor unique index to recreate legacy data), `NormalisasiNomorHpTest` (since the `MenormalisasiNoHp` mutator means dirty `no_hp` data can no longer exist via Eloquent, its dirty-data setups write through `DB::table()->update()` directly, bypassing the mutator on purpose — that's not a workaround to "fix", it's how the test simulates pre-existing legacy rows), `ProduksiTerlindungiTest` (destructive-command guard), `DemoSeederTest` (also asserts every seeded class gets exactly one `kode_kelas`), `PeranDanPortalGuruTest` (role gating + guru portal isolation), `AkunGuruTest`, `WorkshopTest` (reference-data CRUD, the similarity/sibling/kelas hint data, the `edit_siswa` deep link, phone-number acceptance/rejection), `SiswaImportTest` (insert-vs-update-by-name, blank cells not overwriting existing values, paket-by-name lookup, blank-name rows skipped, and one real end-to-end upload through the actual route), `ExportPhoneNumberFormatTest` (generates a real file per export sheet and reads the cell back via PhpSpreadsheet to assert `no_hp` stays `DataType::TYPE_STRING` with the `+`/leading zero intact — a plain string assertion on the mapped array isn't enough here, since the corruption only happens when PhpSpreadsheet's value binder writes the cell), `PerlindunganHapusDanUbahTest` (the guru/ruang/mapel/sesi/paket delete-in-use guards and the Pembayaran update guards above), `ModulAjarTest` (admin-sees-all vs guru-sees-own scoping, the create-yes/update-no permission split, `kode_kelas` surviving a drag-move / edit-class-modal edit / stash round-trip including the legacy-format case, and the teaching/attendance/substitute flow — persiapan permissions, grading crediting the right teacher, the substitute auto-reverting after grading, and re-teaching overwriting rather than duplicating).

**Watch for editor auto-reformatting breaking `assertSee`.** Something in this environment occasionally re-wraps long Blade lines, which can insert a newline + indentation between two pieces of text that used to be adjacent (e.g. `Rp` and the formatted number ending up on separate lines). The page still renders fine visually, but `assertSee('Rp 200.000')` then fails because that exact substring no longer appears in the raw HTML. If a previously-passing `assertSee` assertion starts failing with no logic change nearby, check whether the surrounding Blade got line-wrapped before assuming the data/logic is wrong.

Tests run on SQLite in-memory (`phpunit.xml`), so they never touch local MariaDB or production TiDB — this is the safe way to verify migrations without running `php artisan migrate` anywhere real.

`database/seeders/DemoSeeder.php` builds a realistic working state (15 students in 10 families with siblings sharing a phone, 3 months of mixed-status invoices, installments, discounts, 17 collision-free classes, archived students). It is wired into `DatabaseSeeder` behind an `isProduction()` guard, so `php artisan migrate:fresh --seed` gives a ready local environment in one command. `DemoSeederTest` asserts the generated data satisfies the real invariants (details sum to `total_sudah_dibayar`, status matches amounts, no duplicate anchors, no schedule collisions) — keep it passing when changing the seeder, since misleading demo data produces misleading manual testing.
