# Kanban Kalender - E-Ling Course

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=venom&color=0:1d4ed8,50:2563eb,100:f97316&height=240&section=header&text=Kanban%20Kalender&fontSize=60&fontColor=ffffff&animation=fadeIn&fontAlignY=42&stroke=ffffff&strokeWidth=1&desc=Admin%20Bimbel%20%7C%20Jadwal%20%7C%20Siswa%20%7C%20Pembayaran%20%7C%20Modul%20Ajar&descFontSize=18&descAlignY=64&descAlign=50&descFontColor=e2e8f0" alt="Kanban Kalender Header" />
</p>

<p align="center">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&weight=600&size=22&pause=1000&color=2563EB&center=true&vCenter=true&width=760&lines=Manage+class+schedules+with+conflict+protection.;Track+students%2C+payments%2C+discounts%2C+and+archives.;Run+curriculum%2C+attendance%2C+and+student+progress+reports.;Generate+formal+PDF+exports+for+operations+and+finance." alt="Typing SVG" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/TailwindCSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 3">
  <img src="https://img.shields.io/badge/daisyUI-4-5A0EF8?style=for-the-badge&logo=daisyui&logoColor=white" alt="daisyUI 4">
  <img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=0F172A" alt="Alpine.js">
  <img src="https://img.shields.io/badge/DomPDF-PDF-F97316?style=for-the-badge&logo=adobeacrobatreader&logoColor=white" alt="DomPDF">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/tests-177%20passing-16a34a?style=flat-square" alt="177 tests passing">
  <img src="https://img.shields.io/badge/static%20analysis-Larastan%20lv1-8b5cf6?style=flat-square" alt="Larastan level 1">
  <img src="https://img.shields.io/badge/style-Laravel%20Pint-f59e0b?style=flat-square" alt="Laravel Pint">
</p>

<br/>

<table align="center">
<tr>
<td align="left" width="50%">

### What is this?

**Kanban Kalender** is an internal tutoring operations system for **E-Ling Course**.

It brings scheduling, student administration, payments, curriculum, attendance, and public calendar access into one Laravel admin panel — with a separate read-mostly portal for teachers.

</td>
<td align="left" width="50%">

### Why it matters

This project is designed to keep day-to-day operations stable:

- prevent schedule collisions
- keep student data organized
- manage billing with safer payment flows
- record what was actually taught, by whom, to whom
- produce export-ready PDF reports for admin and finance

</td>
</tr>
</table>

<br/>

---

## Core Features

- Multi-tab admin dashboard: **Ringkasan**, **Jadwal**, **Data Siswa**, **Pembayaran**, plus **Workshop**, **Modul Ajar**, **Absen**, and **Akun Guru**
- Two roles (`admin` / `guru`) enforced at the route layer, with a dedicated teacher portal
- Public, no-auth calendar that deliberately exposes **no** internal data (packages, ability levels, and billing stay private)
- Conflict-safe scheduling for teacher, room, and student
- Student archive / restore / permanent-delete flow
- Ability levels (**Kemampuan**) per student, with strictly sequential numbering
- Payment packages, family discounts, universal discounts, and installment ledgers
- "Set lunas" and "Selesaikan seluruh status" workflows with duplicate-billing protection
- Curriculum per class (**Modul Ajar**) plus teaching, grading, and substitute-teacher flow
- Daily WhatsApp schedule reminder on Ringkasan, with a record of who copied it and when
- Student progress reports (**Rapor**) with attendance, score trend, and PDF export
- Bulk student import/export via Excel, with phone numbers that survive the spreadsheet
- Formal PDF exports: jadwal, data siswa, pembayaran, struk pelunasan, rapor
- Fully responsive, theme-aware UI (light/dark follows the operating system)

---

## Main Modules

### 1. Ringkasan

- today's snapshot: active classes, scheduled students, teaching staff, room usage
- daily/weekly operational statistics (room occupancy, teacher load, back-to-back warnings)
- financial reminders: uninvoiced students, aging receivables, dangling discounts
- data hygiene: students without schedules or phone numbers, stale archives and notes
- hidden-collision detection (usually the aftermath of a stash restore)
- today's schedule grid at the bottom, click a card for the student roster

### 2. Jadwal

- manage day, session, room, teacher, and subject slots
- create and drag-move class groups
- protect against schedule collisions
- export operational schedule PDF
- copy WhatsApp-friendly schedule text

### 3. Data Siswa

- archive, restore, and permanently remove student records (creating/editing lives in Workshop)
- filter by class, package, ability level, session, teacher, and room
- inspect the schedules each student is joined to
- per-student notes (`Tanda`)
- **Rapor Perkembangan**: attendance, average/min/max score, trend, per-subject meeting history
- export filtered student lists to PDF and Excel

### 4. Pembayaran

- create manual invoices and generate mass billing from active packages
- record installment payments against an append-only ledger
- apply family and universal discounts
- mark invoice groups as paid safely
- print receipt / proof of settlement
- export finance-oriented PDF reports

### 5. Workshop

- the single place to create and edit Siswa, Guru, Ruang, Sesi, Mata Pelajaran, Paket, and Kemampuan
- live "you already have something like this" hints while typing (informational, never blocking)
- sibling detection by phone number, and which classes already run for a given grade
- bulk student import from a downloadable Excel template
- **Slot Kosong**: a searchable day × session grid of unused capacity

### 6. Modul Ajar

- one curriculum record per class, anchored to a stable `kode_kelas` that survives drag-moves and edits
- header (learning goals, prior competence, teaching model, media) plus a list of syllabus items
- shared write surface: admin and the assigned teacher can create; only admin can edit or delete

### 7. Absen

- day × session board of teaching sessions
- a teacher marks themselves unavailable, which instantly opens the slot to everyone (first come, first served)
- whoever actually teaches and grades gets the attendance credit
- per-student attendance and 1–5 scoring per session

### 8. Portal Guru

- read-only weekly schedule for the signed-in teacher
- direct access to Modul Ajar and Absen for their own classes

---

## Design System

The UI runs on **Tailwind CSS 3 + daisyUI 4**, and the theme is the single source of truth for color.

- **Semantic tokens, not hardcoded palettes.** Surfaces use `base-100` / `base-200` / `base-300`, text uses `base-content` with opacity steps, and meaning uses `primary` / `accent` / `success` / `warning` / `error` / `info`.
- **Dark mode is automatic and OS-driven.** daisyUI injects the dark theme at `:root` under `@media (prefers-color-scheme: dark)`, so token-based classes flip on their own — the codebase deliberately has **no** `darkMode: 'class'` and no `dark:` variants layered on tokens.
- **`primary` (blue) is brand chrome; `success` (emerald) means done, paid, or present.** Keeping them distinct is what makes "submit" and "settled" readable at a glance.
- **daisyUI wins name collisions.** Custom helpers in `resources/css/app.css` are prefixed `app-` (`app-card`, `app-stat`, `app-empty`, `app-tab`, `app-input`, `app-chip`, `app-table-wrap`, …), while `badge`, `stats`, `table`, `join`, `alert`, `modal`, and friends come straight from daisyUI.
- Reusable Blade components: `x-list-panel` / `x-list-row` (Ringkasan's tinted list cards) and `x-portal-nav` (the shared top bar for Portal Guru, Absen, and Modul Ajar).

Full conventions live in [CLAUDE.md](CLAUDE.md#frontend-conventions).

---

## Tech Stack

### Backend & App Layer

<p align="center">
  <img src="https://skillicons.dev/icons?i=php,laravel,mysql,sqlite" alt="Backend Stack" />
</p>

### Frontend & UX

<p align="center">
  <img src="https://skillicons.dev/icons?i=html,css,js,tailwind" alt="Frontend Stack" />
</p>

### Tooling

<p align="center">
  <img src="https://skillicons.dev/icons?i=nodejs,npm,vite,git,github" alt="Tooling Stack" />
</p>

Additional libraries used in-app:

| Library | Role |
| --- | --- |
| `daisyui` | component layer and theme tokens on top of Tailwind |
| `alpinejs` | all client-side interactivity |
| `sweetalert2` | every confirmation and notification (never native `alert`/`confirm`) |
| `sortablejs` | drag-and-drop on the schedule board |
| `@fortawesome/fontawesome-free` | icons, self-hosted via Vite |
| `barryvdh/laravel-dompdf` | PDF exports |
| `maatwebsite/excel` | Excel import/export |
| `spatie/laravel-permission` | `admin` / `guru` roles |

---

## Local Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Prepare environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database setup

```bash
php artisan migrate --seed
```

This seeds roles **and** a realistic demo dataset (students in families sharing a phone number, three months of mixed-status invoices, installments, discounts, collision-free classes, archived students). The demo seeder is guarded so it never runs in production.

### 4. Frontend build

```bash
npm run build          # or: npm run dev
```

### 5. Run the app

```bash
php artisan serve
```

Or the whole stack at once (server + queue + logs + Vite):

```bash
composer run dev
```

> On Windows, if `npm run build` is blocked by the PowerShell execution policy, use `cmd /c npm run build`.

---

## Quality Gates

```bash
vendor/bin/phpunit          # 177 feature/unit tests
npm run test:js             # plain Node test runner, no framework
vendor/bin/phpstan analyse  # Larastan level 1 (Controllers + Models)
vendor/bin/pint             # code style
```

Tests run against **SQLite in-memory**, so they never touch local MySQL or production — this is the safe way to verify a migration without running it anywhere real.

The primary regression suite is `tests/Feature/ScheduleAndPaymentTest.php`, covering atomic schedule creation, teacher/room/student collision rejection, preserved `+62...` phone format, payment allocation correctness, overpayment rejection, the auto-generated `Selesai sistem` detail, receipt rendering, anti-duplicate mass billing, and per-tab dashboard payload size.

Supporting suites cover roles and portal isolation, Workshop CRUD and hints, student import, Modul Ajar permissions and the substitute flow, student reports, the Ringkasan dashboard, duplicate-invoice cleanup, phone normalization, export formatting, delete guards, the anti-double-click guard, and the demo seeder's invariants.

Example command for Windows with a project-local PHP 8.3:

```powershell
& 'C:\PHP 8.3\php.exe' -c 'php83.ini' vendor\bin\phpunit tests\Feature\ScheduleAndPaymentTest.php
```

---

## Important Files

### Controllers

- `app/Http/Controllers/DashboardController.php` — single admin entry point, loads data per active tab
- `app/Http/Controllers/JadwalController.php`
- `app/Http/Controllers/SiswaController.php`
- `app/Http/Controllers/PembayaranController.php` — the most fragile controller in the codebase
- `app/Http/Controllers/WorkshopController.php`
- `app/Http/Controllers/ModulAjarController.php`
- `app/Http/Controllers/GuruPortalController.php`

### Services

- `app/Services/PaymentBatchService.php` — mass billing and settlement
- `app/Services/RingkasanService.php` — dashboard aggregation
- `app/Services/RaporService.php` — student progress aggregation

### Views

- `resources/views/admin/` — `ringkasan`, `dashboard` (Jadwal), `card` (Data Siswa), `pembayaran`, `workshop`, `akun-guru`
- `resources/views/modul-ajar/`, `resources/views/absen/`, `resources/views/guru/`
- `resources/views/components/` — shared Blade components
- `resources/views/pdf/` — `jadwal`, `siswa`, `pembayaran`, `struk`, `rapor`

### Styling & Routing

- `resources/css/app.css` — design system on top of daisyUI
- `tailwind.config.js` — daisyUI light/dark themes and token defaults
- `routes/web.php`

---

## Local-Only Files

These files are intended for local machine setup and should stay ignored:

- `php83.ini`
- `project-terminal.cmd`

They exist to make this project use **PHP 8.3 specifically** without disturbing other PHP projects on the same machine.

---

## Internal Technical Memory

For deeper technical refresh, architecture notes, fragile areas, DB access flow, and testing references:

- [CLAUDE.md](CLAUDE.md) — architecture, invariants, and conventions
- [FEED.md](FEED.md) — technical memory doc (Indonesian)

---

## License

Internal / private project workflow.

<br/>

<p align="center">
  Made by <strong>Vayndem</strong> with ❤️
</p>

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0:f97316,50:2563eb,100:1d4ed8&height=120&section=footer" alt="Footer Banner" />
</p>
