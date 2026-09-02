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

1. **Jadwal** (`JadwalController`) — schedule CRUD, drag/move, collision protection, PDF export, WhatsApp-text generation.
2. **Data Siswa** (`SiswaController`, `ArsipController`) — student CRUD, archive/restore/permanent-delete, PDF export.
3. **Pembayaran** (`PembayaranController`, `PembayaranDetailsController`) — invoicing, mass billing, installments, discounts, "lunas" (paid-off) workflows, receipts, PDF export. This is the most fragile controller in the codebase.
4. **Public calendar** (`jadwal-kalender` routes) — read-only schedule view, no auth.

`DashboardController` is the single admin entry point (`/dashboard`) and deliberately loads data **per active tab**, not all at once — a prior full-payload version triggered `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE` on Vercel's serverless functions. Any change here must be checked for response size and eager-loading blast radius; the same applies to the "family payment detail" endpoint (`GET /admin/pembayaran/keluarga/{no_hp}/detail`), which exists specifically to avoid shipping the full invoice history up front.

**Domain model:** `Siswa`, `Jadwal`, `Hari`, `Sesi`, `Guru`, `Ruang`, `MataPelajaran`, `Paket`, `Pembayaran`, `PembayaranDetail`, `Diskon`, `Tanda`, `Arsip`, `BatchPembayaranLog`. One class group is shown as a single card in the UI but is persisted as multiple `jadwals` rows (one per student) — collision checks and edits must account for hari/sesi/guru/ruang/siswa combinations across all of them, not a subset. `Pembayaran` is the invoice/header record per student; `PembayaranDetail` is the append-only ledger of deposits/installments/settlements — payment status changes must add a detail row, never silently overwrite totals. Payment status stays a legacy integer (`0` belum bayar / `1` proses / `2` lunas); don't change its type without an explicit decision.

Family/discount logic keys off `no_hp`. As of Sept 2026 production data was normalized to `+62...` in a single reviewed pass (`pembayaran:normalisasi-hp`), so the format is now genuinely uniform across `siswas`, `pembayarans`, `arsips`, `diskons`. Never normalize phone numbers silently inside request handling — but note there is still **no guard at input time**, so the format will drift again as staff type `08...` into the student form. Adding normalization at the input boundary is an open decision, not a forbidden one.

## Payment integrity invariants

These were established while fixing a production incident where mass billing duplicated manually-created invoices (Rp 10.375.000 across 31 groups). Do not weaken them.

- **Package invoices are anchored, not matched by text.** `pembayarans.id_paket` + `pembayarans.periode` (`YYYY-MM`) uniquely identify "this student's bill for this package this month", enforced by `UNIQUE(id_siswa, id_paket, periode)`. The old dedup compared the free-text `keterangan`, which silently missed manual invoices worded differently. Any new invoice-creating path must set the anchor. Invoices with `id_paket = NULL` are free-form (buku, denda, kegiatan) and are intentionally unconstrained — they may repeat.
- **Mass actions run once per period.** `penagihanMassal` and the global `lunasSemua` acquire a lock row in `batch_pembayaran_logs` (`UNIQUE(jenis, periode)`) before doing work, and release it if the run produced nothing. Per-family settlement (`keLunasMassal`) is deliberately *not* locked — it's a daily action.
- **Double-submit is blocked in three layers**, because the old US-region Vercel deploy was slow enough that staff clicked twice: a full-screen blocking overlay bound to `isLoading`, `:disabled` on every money-touching button, and a server-side guard (`PembayaranController::JEDA_ANTI_GANDA`, 180s) rejecting identical payment/invoice submissions. The server layer is the one that matters — the others can be bypassed by refresh or retry.
- **Destructive artisan commands are blocked in production** via `DB::prohibitDestructiveCommands()` in `AppServiceProvider`. `.env` is swapped back and forth between local MariaDB and production TiDB, so `migrate:fresh` against the wrong target would destroy the books. `--force` cannot bypass this guard.

Maintenance commands (all default to a read-only/dry-run mode; `--force` applies):

```bash
php artisan pembayaran:audit-duplikat      # report duplicate invoices (read-only, never writes)
php artisan pembayaran:rapikan-duplikat    # consolidate duplicates, archive removals
php artisan pembayaran:normalisasi-hp      # normalize phone numbers to +62 across all tables
```

`pembayaran:rapikan-duplikat` keeps the earliest invoice, drops payment details that are exact twins (same amount + same date) or `Selesai sistem`, **moves** any genuinely different payment to the survivor rather than deleting it, and archives every removed row as JSON into `koreksi_pembayaran_logs` before deleting.

**PDF exports** live in `resources/views/pdf/*.blade.php` (jadwal, siswa, pembayaran, struk) and are rendered via DomPDF (`barryvdh/laravel-dompdf`). They must be kept in sync with whatever filters (status/month/search) are active in the corresponding admin view — export queries diverging from display queries is a recurring source of bugs. DomPDF has limited CSS support, so keep PDF styling conservative.

**Excel import/export** uses `maatwebsite/excel` via `app/Exports/*Export.php` and `app/Imports/SiswaImport.php`.

**Services:** `PaymentBatchService` (mass billing / settlement logic) and `RingkasanService` (dashboard summary aggregation) hold business logic that would otherwise bloat the controllers — check these before adding payment or dashboard-summary logic directly in a controller.

## Frontend conventions

Button color semantics (`resources/css/app.css`): `btn-neutral` (cancel/back), `btn-primary` (default action), `btn-success` (safe/restore/copy), `btn-warning` (needs attention), `btn-export` (generate document), `btn-accent` (supporting feature), `btn-sacred` (destructive/sensitive — use for permanent delete). Use SweetAlert2 for all confirmations/notifications, never native `alert`/`confirm`. Important dropdowns are expected to be searchable.

## Roles and access

Built Sept 2026 with `spatie/laravel-permission`. Two roles: `admin` and `guru`.

- Every `/admin/*` route plus `/dashboard` sits behind `role:admin`. The teacher portal `/guru` sits behind `role:guru`. Profile routes are open to both. Access is enforced at the route layer, not by hiding buttons.
- `RoleSeeder` creates both roles and grants `admin` to any account that has none — existing accounts predate roles and must not be locked out. Run it after migrating.
- `gurus.email` is nullable: teachers that already existed have no email, and the admin fills them in one by one from Master Data. Teachers without an account remain fully schedulable.
- A login account links via `users.guru_id` → `gurus`. The direction matters: `Guru` is the domain entity referenced by `jadwals`, `User` is only the way in. Deleting a user never touches the schedule.
- `UserFactory` creates admins by default (matching how the app behaved before roles existed); `->guru($guru)` and `->tanpaPeran()` are the other states. Role assignment happens in chained `afterCreating` callbacks, not a custom property — Laravel builds a new factory instance on `state()`, which silently discards custom properties.

`GuruPortalController` is read-only by construction: the `guru` role has no write route anywhere in the app. It groups a teacher's `jadwals` rows into class cards using the same key as the admin screen.

`MasterDataController` backs the standalone Master Data menu. Besides listing gurus/ruangs/sesis/mapel/pakets it computes usage context (how many slots each teacher fills, how many students use each package, which mapel are safe to delete) and a **hari × sesi availability map** showing which rooms and teachers are still free — the point being that nobody has to open another page to check whether something exists before adding a class.

Avoid `COUNT(DISTINCT a, b)`: it is MySQL-only and breaks the SQLite test suite. Use `->distinct()->get()` and count in PHP.

## Planned direction (not built yet)

Recorded Sept 2026. Items 1 and 2 of the original list are done (see "Roles and access" above). The rest is intent — confirm details with the owner before building, since several items reshape the domain model.

**3. Kurikulum per meeting.** Each room-in-a-session (i.e. each class) gains a curriculum, fillable by both the assigned teacher and admin. Curriculum is tracked **per pertemuan (per meeting)**, not per class as a whole — so it needs a meeting-level record that the current schema has no place for.

**4. Per-meeting teacher report (before/after).** For every class meeting the teacher must record: whether the meeting actually took place, which students attended, and a **before/after assessment with a score** capturing whether each student improved.

**5. Attendance for students and teachers.**
- Student attendance feeds into a **score**.
- Teacher attendance feeds **daily payroll**: a teacher is paid for a day they attended, and not paid for a day they did not. Confirmed by the owner Sept 2026 (the word used was "pengajian", meaning *penggajian*/payroll).

**6. Payroll itself is explicitly deferred** — the owner said to leave it for later. Build the attendance data it will consume, not the payout feature.

Because payroll is money, it inherits the payment module's rules when it is eventually built: an append-only ledger rather than mutable totals, a recorded actor and timestamp for every entry, no hard deletes (void/reverse instead), and idempotency against double submission. Two things the current schema lacks and will need first: a daily rate per teacher (`gurus` has only `name`), and the dated meeting/attendance record described above. Attendance is the *input* to pay — so once a payout has been calculated from a day's attendance, later edits to that attendance must not silently change history.

Note how much of this depends on a per-meeting entity that does not exist yet: curriculum, the before/after report, and both attendance types all hang off "a specific meeting of a specific class on a specific date". A `jadwals` row today represents a recurring weekly slot for one student, with no dated occurrence. Designing that meeting/occurrence model is the prerequisite for items 3–5, and should be settled before any of them is started.

## Testing notes

`tests/Feature/ScheduleAndPaymentTest.php` is the primary regression suite and covers: atomic schedule creation, teacher/room/student collision rejection, `+62` phone format preservation, payment allocation correctness, overpayment rejection, auto-generated `Selesai sistem` payment detail on settlement, receipt rendering, anti-duplicate mass billing, monthly batch locks, and per-tab dashboard payload size. When changing payment logic, re-verify: remaining balance math, discount calculation, "set lunas", "selesaikan seluruh status", and struk (receipt) rendering. When changing schedule logic, re-verify: collision validation, transactional store, card grouping, and schedule PDF export.

Supporting suites: `AntiDoubleClickTest` (server-side duplicate-submission guard), `RapikanDuplikatPembayaranTest` (duplicate consolidation — note its `setUp()` drops the anchor unique index to recreate legacy data), `NormalisasiNomorHpTest`, `ProduksiTerlindungiTest` (destructive-command guard), `DemoSeederTest`.

Tests run on SQLite in-memory (`phpunit.xml`), so they never touch local MariaDB or production TiDB — this is the safe way to verify migrations without running `php artisan migrate` anywhere real.

`database/seeders/DemoSeeder.php` builds a realistic working state (15 students in 10 families with siblings sharing a phone, 3 months of mixed-status invoices, installments, discounts, 17 collision-free classes, archived students). It is wired into `DatabaseSeeder` behind an `isProduction()` guard, so `php artisan migrate:fresh --seed` gives a ready local environment in one command. `DemoSeederTest` asserts the generated data satisfies the real invariants (details sum to `total_sudah_dibayar`, status matches amounts, no duplicate anchors, no schedule collisions) — keep it passing when changing the seeder, since misleading demo data produces misleading manual testing.
