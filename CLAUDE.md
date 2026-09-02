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

**Domain model:** `Siswa`, `Jadwal`, `Hari`, `Sesi`, `Guru`, `Ruang`, `MataPelajaran`, `Paket`, `Pembayaran`, `PembayaranDetail`, `Diskon`, `Tanda`, `Arsip`. One class group is shown as a single card in the UI but is persisted as multiple `jadwals` rows (one per student) — collision checks and edits must account for hari/sesi/guru/ruang/siswa combinations across all of them, not a subset. `Pembayaran` is the invoice/header record per student; `PembayaranDetail` is the append-only ledger of deposits/installments/settlements — payment status changes must add a detail row, never silently overwrite totals. Payment status stays a legacy integer (`0` belum bayar / `1` proses / `2` lunas); don't change its type without an explicit decision. Family/discount logic keys off `no_hp` (phone number), which is now assumed pre-normalized to `+62...` — do not add auto-normalization from `08...` that could touch existing data.

**PDF exports** live in `resources/views/pdf/*.blade.php` (jadwal, siswa, pembayaran, struk) and are rendered via DomPDF (`barryvdh/laravel-dompdf`). They must be kept in sync with whatever filters (status/month/search) are active in the corresponding admin view — export queries diverging from display queries is a recurring source of bugs. DomPDF has limited CSS support, so keep PDF styling conservative.

**Excel import/export** uses `maatwebsite/excel` via `app/Exports/*Export.php` and `app/Imports/SiswaImport.php`.

**Services:** `PaymentBatchService` (mass billing / settlement logic) and `RingkasanService` (dashboard summary aggregation) hold business logic that would otherwise bloat the controllers — check these before adding payment or dashboard-summary logic directly in a controller.

## Frontend conventions

Button color semantics (`resources/css/app.css`): `btn-neutral` (cancel/back), `btn-primary` (default action), `btn-success` (safe/restore/copy), `btn-warning` (needs attention), `btn-export` (generate document), `btn-accent` (supporting feature), `btn-sacred` (destructive/sensitive — use for permanent delete). Use SweetAlert2 for all confirmations/notifications, never native `alert`/`confirm`. Important dropdowns are expected to be searchable.

## Testing notes

`tests/Feature/ScheduleAndPaymentTest.php` is the primary regression suite and covers: atomic schedule creation, teacher/room/student collision rejection, `+62` phone format preservation, payment allocation correctness, overpayment rejection, auto-generated `Selesai sistem` payment detail on settlement, receipt rendering, anti-duplicate mass billing, and per-tab dashboard payload size. When changing payment logic, re-verify: remaining balance math, discount calculation, "set lunas", "selesaikan seluruh status", and struk (receipt) rendering. When changing schedule logic, re-verify: collision validation, transactional store, card grouping, and schedule PDF export.
