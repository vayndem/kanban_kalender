# Kanban Kalender - E-Ling Course

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=venom&color=0:1d4ed8,50:2563eb,100:f97316&height=240&section=header&text=Kanban%20Kalender&fontSize=60&fontColor=ffffff&animation=fadeIn&fontAlignY=42&stroke=ffffff&strokeWidth=1&desc=Admin%20Bimbel%20%7C%20Jadwal%20%7C%20Data%20Siswa%20%7C%20Pembayaran&descFontSize=18&descAlignY=64&descAlign=50&descFontColor=e2e8f0" alt="Kanban Kalender Header" />
</p>

<p align="center">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&weight=600&size=22&pause=1000&color=2563EB&center=true&vCenter=true&width=760&lines=Manage+class+schedules+with+conflict+protection.;Track+students%2C+payments%2C+discounts%2C+and+archives.;Generate+formal+PDF+exports+for+operations+and+finance." alt="Typing SVG" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/TailwindCSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=0F172A" alt="Alpine.js">
  <img src="https://img.shields.io/badge/DomPDF-PDF-F97316?style=for-the-badge&logo=adobeacrobatreader&logoColor=white" alt="DomPDF">
</p>

<br/>

<table align="center">
<tr>
<td align="left" width="50%">

### What is this?

**Kanban Kalender** is an internal tutoring operations system for **E-Ling Course**.

It brings scheduling, student administration, payment handling, notes, and public calendar access into one Laravel-based admin panel.

</td>
<td align="left" width="50%">

### Why it matters

This project is designed to keep day-to-day operations stable:

- prevent schedule collisions
- keep student data organized
- manage billing with safer payment flows
- produce export-ready PDF reports for admin and finance

</td>
</tr>
</table>

<br/>

---

## Core Features

- Multi-tab admin dashboard for:
  - Jadwal
  - Data Siswa
  - Pembayaran
- Public calendar view for active schedules
- Conflict-safe scheduling for:
  - teacher
  - room
  - student
- Student archive and restore flow
- Payment package, family discount, and universal discount handling
- Installment payment recording with detail history
- "Set lunas" and "Selesaikan seluruh status" workflow
- Formal PDF exports:
  - jadwal
  - data siswa
  - pembayaran
  - struk pelunasan
- Responsive dark admin UI with SweetAlert-based interactions

---

## Main Modules

### 1. Jadwal

- manage day, session, room, teacher, and subject slots
- create and move class groups
- protect against schedule collisions
- export operational schedule PDF
- copy WhatsApp-friendly schedule text

### 2. Data Siswa

- create, edit, archive, restore, and permanently remove student records
- filter by class, package, session, teacher, and room
- inspect the schedules joined by each student
- export filtered student lists to PDF

### 3. Pembayaran

- create manual invoices
- generate mass billing from active packages
- record installment payments
- apply family and universal discounts
- mark invoice groups as paid safely
- print receipt / proof of settlement
- export finance-oriented PDF reports

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

- Alpine.js
- SweetAlert2
- DomPDF

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

### 4. Frontend build

```bash
npm run build
```

### 5. Run the app

```bash
php artisan serve
```

---

## Testing

Main feature test:

- `tests/Feature/ScheduleAndPaymentTest.php`

Covered flows include:

- atomic schedule creation
- teacher / room / student collision rejection
- preserved `+62...` phone format
- payment allocation correctness
- overpayment rejection
- auto-generated `Selesai sistem` payment detail
- receipt rendering
- anti-duplicate mass billing
- lightweight dashboard payload by tab

Example command for Windows + project-local PHP 8.3:

```powershell
& 'C:\PHP 8.3\php.exe' -c 'D:\Backlash\PRIBADI\kanban_kalender\php83.ini' vendor\bin\phpunit tests\Feature\ScheduleAndPaymentTest.php
```

---

## Important Files

### Controllers

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/JadwalController.php`
- `app/Http/Controllers/SiswaController.php`
- `app/Http/Controllers/PembayaranController.php`

### Admin Views

- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/card.blade.php`
- `resources/views/admin/form.blade.php`
- `resources/views/admin/pembayaran.blade.php`

### PDF Templates

- `resources/views/pdf/jadwal.blade.php`
- `resources/views/pdf/siswa.blade.php`
- `resources/views/pdf/pembayaran.blade.php`
- `resources/views/pdf/struk.blade.php`

### Styling & Routing

- `resources/css/app.css`
- `routes/web.php`

---

## Local-Only Files

These files are intended for local machine setup and should stay ignored:

- `php83.ini`
- `project-terminal.cmd`

They exist to make this project use **PHP 8.3 specifically** without disturbing other PHP projects on the same machine.

---

## Internal Technical Memory

For deeper technical refresh, architecture notes, fragile areas, DB access flow, and testing references, see:

- [FEED.md](FEED.md)

---

## License

Internal / private project workflow.

<br/>

<p align="center">
  Made by <strong>Vayndem</strong> with love
</p>

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0:f97316,50:2563eb,100:1d4ed8&height=120&section=footer" alt="Footer Banner" />
</p>
