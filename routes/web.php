<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationFormController;
use App\Http\Controllers\EvaluationPeriodController;
use App\Http\Controllers\EvaluationQuestionController;
use App\Http\Controllers\EvaluationScoreMetricController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\OrganizationalRoleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffLookupOptionController;
use App\Http\Controllers\StaffMemberController;
use App\Http\Controllers\StaffStatusController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('home')
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:Super Admin')
        ->name('dashboard');

    // Colleges
    Route::middleware('permission:colleges.manage')->group(function () {
        Route::resource('colleges', CollegeController::class)->except(['show']);
    });

    // Departments
    Route::middleware('permission:departments.manage')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });

    // Staff (full manage or department head)
    Route::resource('staff', StaffMemberController::class);
    Route::middleware('permission:staff.import')->group(function () {
        Route::get('staff-import/template', [StaffMemberController::class, 'template'])->name('staff.template');
        Route::post('staff-import', [StaffMemberController::class, 'import'])->name('staff.import');
    });

    Route::middleware(['permission:staff.manage', 'role:Super Admin'])->group(function () {
        Route::post('staff-purge-all', [StaffMemberController::class, 'purgeAll'])->name('staff.purge-all');
    });

    // Master data CSV export / import
    Route::middleware('role:Super Admin')->prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::get('export/colleges', [MasterDataController::class, 'exportColleges'])->name('export.colleges');
        Route::get('export/departments', [MasterDataController::class, 'exportDepartments'])->name('export.departments');
        Route::get('export/staff-field-options', [MasterDataController::class, 'exportStaffFieldOptions'])->name('export.staff-field-options');
        Route::post('import/colleges', [MasterDataController::class, 'importColleges'])->name('import.colleges');
        Route::post('import/departments', [MasterDataController::class, 'importDepartments'])->name('import.departments');
        Route::post('import/staff-field-options', [MasterDataController::class, 'importStaffFieldOptions'])->name('import.staff-field-options');
    });

    // Staff field options (status, employee type, qualification, title, position)
    Route::middleware('permission:staff_options.manage|staff_status.manage')->group(function () {
        Route::resource('staff-options', StaffLookupOptionController::class)
            ->parameters(['staff-options' => 'staff_option'])
            ->except(['show']);

        Route::resource('staff-statuses', StaffStatusController::class)
            ->parameters(['staff-statuses' => 'staff_status'])
            ->except(['show']);
    });

    // Coordinators
    Route::middleware('permission:coordinators.manage')->group(function () {
        Route::resource('coordinators', CoordinatorController::class)->except(['show']);
    });

    // Super Admins (manage who has full system access)
    Route::middleware('role:Super Admin')->group(function () {
        Route::resource('super-admins', SuperAdminController::class)
            ->except(['show'])
            ->parameters(['super-admins' => 'super_admin']);
    });

    // Users
    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'store', 'show']);
    });

    // Organizational roles (dean / head / quality dept)
    Route::middleware('permission:org_roles.manage')->group(function () {
        Route::get('org-roles', [OrganizationalRoleController::class, 'index'])->name('org-roles.index');
        Route::post('org-roles/college/{college}', [OrganizationalRoleController::class, 'updateCollege'])->name('org-roles.college.update');
        Route::post('org-roles/department/{department}', [OrganizationalRoleController::class, 'updateDepartment'])->name('org-roles.department.update');
    });

    // Evaluation periods
    Route::middleware('permission:periods.manage')->group(function () {
        Route::resource('periods', EvaluationPeriodController::class)->except(['show']);
    });

    // Evaluation forms
    Route::middleware('permission:forms.manage')->group(function () {
        Route::resource('forms', EvaluationFormController::class);
        Route::post('forms/{form}/questions', [EvaluationQuestionController::class, 'store'])->name('forms.questions.store');
        Route::put('forms/{form}/questions/{question}', [EvaluationQuestionController::class, 'update'])->name('forms.questions.update');
        Route::delete('forms/{form}/questions/{question}', [EvaluationQuestionController::class, 'destroy'])->name('forms.questions.destroy');
        Route::post('forms/{form}/questions-reorder', [EvaluationQuestionController::class, 'reorder'])->name('forms.questions.reorder');
        Route::post('forms/{form}/categories', [EvaluationFormController::class, 'storeCategory'])->name('forms.categories.store');
        Route::put('forms/{form}/categories/{category}', [EvaluationFormController::class, 'updateCategory'])->name('forms.categories.update');
        Route::delete('forms/{form}/categories/{category}', [EvaluationFormController::class, 'destroyCategory'])->name('forms.categories.destroy');
        Route::post('forms/{form}/score-metrics', [EvaluationScoreMetricController::class, 'store'])->name('forms.score-metrics.store');
        Route::put('forms/{form}/score-metrics/{metric}', [EvaluationScoreMetricController::class, 'update'])->name('forms.score-metrics.update');
        Route::delete('forms/{form}/score-metrics/{metric}', [EvaluationScoreMetricController::class, 'destroy'])->name('forms.score-metrics.destroy');
    });

    // Committees
    Route::middleware('permission:committees.manage')->group(function () {
        Route::get('committees/staff-options', [CommitteeController::class, 'staffOptions'])->name('committees.staff-options');
        Route::resource('committees', CommitteeController::class)->except(['edit', 'update']);
    });

    // Evaluations
    Route::middleware('permission:evaluations.submit')->group(function () {
        Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('evaluations.show');
        Route::get('evaluations/{evaluation}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
        Route::put('evaluations/{evaluation}', [EvaluationController::class, 'update'])->name('evaluations.update');
        Route::post('evaluations/{evaluation}/submit', [EvaluationController::class, 'submit'])->name('evaluations.submit');
    });

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/staff', [ReportController::class, 'staff'])->name('reports.staff');
        Route::get('reports/university', [ReportController::class, 'university'])->name('reports.university');
        Route::get('reports/staff/{staff}/details', [ReportController::class, 'staffDetails'])->name('reports.staff.details');
        Route::get('reports/staff/{staff}/export/pdf', [ReportController::class, 'exportStaffPdf'])->name('reports.staff.export.pdf');
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    });

    // Activity Log
    Route::middleware('permission:activity_log.view')->group(function () {
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });
});
