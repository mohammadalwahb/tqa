# TQA — Teaching Quality Assessment

A Laravel 13 application for managing teaching staff evaluations at the
University of Zakho. Built with Blade templates + Bootstrap 5, Spatie
Permission for RBAC, and Laravel Socialite for Google OAuth.

## Features

- **Google OAuth login only.** Whitelisted by email domain
  (`@uoz.edu.krd`, `@staff.uoz.edu.krd` by default).
- **Roles & permissions** via Spatie Permission:
  Super Admin · Quality College Coordinator · Local Committee Member · HD Committee Member.
- **Organizational data**: Colleges, Departments, Staff Members, Staff Statuses.
- **CSV / Excel staff import** with column auto-detection.
- **Evaluation form builder**: categories, drag-and-drop question reordering,
  per-role question visibility, enable/disable, and three question types
  (rating 1–5, text note, number).
- **Configurable evaluation windows** (`evaluation_periods`).
- **Local & HD committees** with the exact business rules from the spec:
  - Local: 1 Quality College Coordinator + 2 same-department + 1 other-department.
  - HD: one committee per department head — Dean + Quality College Coordinator + same-dept member + college member (any dept).
- **Per-evaluator submissions** stored separately. Final result for a staff
  member is the **average of all submitted evaluator averages** across rating
  questions only. Text/comment answers are preserved but excluded from scoring.
- **Reports & dashboards**:
  - University-wide completion %
  - Per-staff completion % and average score
  - Per-question average (drill-down view)
  - PDF (DomPDF) and Excel (Maatwebsite) export
- **Activity log** for users, staff, colleges, departments, committees, evaluations.
- **Datatables, SweetAlert confirms, responsive Bootstrap UI.**

## Stack

| Layer        | Choice                                            |
|--------------|----------------------------------------------------|
| Backend      | Laravel 13, PHP 8.3+                              |
| Frontend     | Blade + Bootstrap 5 (CDN) + jQuery (datatables)   |
| Database     | MySQL 8 (project default; tests run on SQLite)    |
| Auth         | Laravel Socialite (Google)                        |
| RBAC         | spatie/laravel-permission                         |
| Imports      | maatwebsite/excel                                 |
| PDFs         | barryvdh/laravel-dompdf                           |
| Activity log | spatie/laravel-activitylog                        |
| Tables       | DataTables (Bootstrap 5 build) via CDN            |
| Drag/drop    | SortableJS                                        |
| Alerts       | SweetAlert2                                       |

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

(No frontend build step is required — assets are loaded from CDNs.)

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME=TQA
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=tqa
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_CLIENT_ID=<your-google-oauth-client-id>
GOOGLE_CLIENT_SECRET=<your-google-oauth-client-secret>
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

ALLOWED_EMAIL_DOMAINS="uoz.edu.krd,staff.uoz.edu.krd"
TQA_SUPER_ADMIN_EMAIL=admin@uoz.edu.krd
```

The Super Admin bootstrap user must belong to one of the allowed domains.

### 3. Google OAuth credentials

1. Open the [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
2. Create an *OAuth 2.0 Client ID* of type *Web application*.
3. Authorised redirect URI: `http://localhost:8000/auth/google/callback`
   (and your production URL once deployed).
4. Copy the Client ID / Secret into `.env`.

### 4. Migrate & seed

```bash
php artisan migrate:fresh --seed
```

The seeder creates:

- Roles & permissions
- A Super Admin user (`admin@uoz.edu.krd` by default — change via `TQA_SUPER_ADMIN_EMAIL`)
- Default staff statuses
- A demo organisation (3 colleges, 9 departments)
- A current evaluation period
- A default evaluation rubric with categories and questions

The Super Admin can sign in via Google as soon as the matching Google
account exists in the allowed domains.

### 5. Run

```bash
php artisan serve
```

Visit <http://localhost:8000>. You'll be redirected to the login page.

### 6. Tests

```bash
php artisan test
```

Tests use SQLite in-memory and seed the database before each run.

## Workflow

1. **Super Admin** logs in via Google.
2. Admin imports staff (CSV) and verifies colleges/departments.
3. Admin assigns Deans / Heads of Department / Quality Department
   Coordinators under **Organizational Roles**.
4. Admin creates **Quality College Coordinators** for each college.
5. Admin opens/edits an **Evaluation Period** to define the window.
6. The Quality College Coordinator logs in (Google) and:
   - Creates one **Local Committee** per department in their college.
     The system enforces 2 same-dept + 1 other-dept members and adds the
     coordinator automatically.
   - Creates **HD Committees** for departments whose head needs to be
     evaluated (Dean + Quality College Coord + same-dept + other-dept/college staff).
7. Each committee member sees their pending evaluations and submits ratings
   1–5 plus comments. Evaluation submissions are append-only — once
   submitted, the row is locked.
8. **Reports** are recalculated live. Super Admin / Coordinator can export
   to PDF or Excel.

## CSV import format

Expected headers (case/whitespace insensitive; underscores allowed):

```
Full Name in English, Full Name in Kurdish, Email, Gender, Date of Birth,
Age, Employee Type, College, Department, Qualification, Academic Title,
Position, Status
```

Email must match an allowed domain. Unknown colleges / departments /
statuses are created on the fly. Re-importing an email updates the row.

A blank template can be downloaded from the Staff page.

## Security notes

- All forms are protected with CSRF tokens.
- All inputs are validated server-side via Form Requests.
- Authorization is enforced via permissions middleware
  (`permission:colleges.manage` etc.) and a policy for evaluation
  view/edit/submit (an evaluator can only modify their own draft).
- Soft-deletes are used on all domain entities (no hard deletes).
- All actions on key models are recorded by Spatie Activity Log.
- Email domain restriction is enforced both in `GoogleAuthService` (at
  login time) and on staff/coordinator creation forms.

## Directory map

```
app/
├── Exports/                # Excel exporters
├── Http/
│   ├── Controllers/        # Thin controllers (web)
│   ├── Middleware/
│   └── Requests/           # FormRequest validators
├── Imports/                # CSV/Excel importers
├── Models/                 # Eloquent models
├── Policies/               # Authorization policies
└── Services/               # Business logic (Auth, Committees, Evaluations, Reporting)
config/tqa.php              # Domain + rating config
database/
├── migrations/             # All schema
└── seeders/                # Roles / org / form seeders
resources/views/            # Blade templates (Bootstrap 5)
routes/web.php              # All HTTP routes
tests/Feature/              # Pest feature tests (10 tests, all green)
```

## License

MIT.
