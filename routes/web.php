<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\HR\DashboardController as HRDashboardController;
use App\Http\Controllers\Admin\{DepartmentController, PositionController, VacancyController, UserController, ApplicantController, SettingController};
use App\Http\Controllers\Recruitment\{InterviewController, PipelineController, ReportController, NotificationController, AssessmentInsightController};
use App\Http\Controllers\Applicant\FormController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


Route::get('/careers', [FormController::class, 'careers'])->name('careers.index');
Route::post('/careers/select/{vacancy}', [FormController::class, 'selectVacancy'])->name('careers.select');
Route::get('/apply', [FormController::class, 'index'])->name('careers.apply');
// Legacy/direct vacancy URLs are intentionally blocked. Applicants must choose a role on /careers first.
Route::get('/apply/{vacancy}', fn () => redirect()->route('careers.index')
    ->with('career_error', 'Please select an open position from the careers page before starting an application.'));
Route::post('/apply/validate-step', [FormController::class, 'validateStep'])->name('careers.validate-step');
Route::post('/apply', [FormController::class, 'submit'])->name('careers.submit');
Route::get('/application-submitted', [FormController::class, 'success'])->name('careers.success');

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('careers.index');
    }

    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'hr' => redirect()->route('hr.dashboard'),
        default => redirect()->route('careers.index'),
    };
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    Route::get('applicants/data', [ApplicantController::class, 'data'])->name('applicants.data');
    Route::get('applicants', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::get('applicants/{application}/print', [ApplicantController::class, 'printForms'])->name('applicants.print');
    Route::get('applicants/{application}/edit', [ApplicantController::class, 'edit'])->name('applicants.edit');
    Route::put('applicants/{application}', [ApplicantController::class, 'update'])->name('applicants.update');
    Route::get('applicants/{application}', [ApplicantController::class, 'show'])->name('applicants.show');
    Route::put('applicants/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');
    Route::delete('/applicants/{application}', [ApplicantController::class, 'destroy'])
        ->name('applicants.destroy');

    Route::get('interviews/data', [InterviewController::class, 'data'])->name('interviews.data');
    Route::resource('interviews', InterviewController::class)->except(['create','edit']);


    Route::get('assessment-insights', [AssessmentInsightController::class, 'index'])->name('assessment-insights.index');
    Route::post('assessment-insights/recalculate', [AssessmentInsightController::class, 'recalculate'])->name('assessment-insights.recalculate');
    Route::put('assessment-insights/{vacancy}/criteria', [AssessmentInsightController::class, 'updateCriteria'])->name('assessment-insights.criteria');
    Route::put('assessment-insights/{vacancy}/templates', [AssessmentInsightController::class, 'updateTemplates'])->name('assessment-insights.templates');
    Route::put('assessment-insights/{vacancy}/mappings', [AssessmentInsightController::class, 'updateMappings'])->name('assessment-insights.mappings');
    Route::post('assessment-insights/{vacancy}/save-position-default', [AssessmentInsightController::class, 'savePositionDefault'])->name('assessment-insights.save-position-default');
    Route::post('assessment-insights/{vacancy}/reset-position-default', [AssessmentInsightController::class, 'resetPositionDefault'])->name('assessment-insights.reset-position-default');

    Route::get('hiring-status/data', [PipelineController::class, 'data'])->name('hiring-status.data');
    Route::get('hiring-status', [PipelineController::class, 'index'])->name('hiring-status.index');
    Route::put('hiring-status/{application}', [PipelineController::class, 'update'])->name('hiring-status.update');

    Route::get('departments/data', [DepartmentController::class, 'data'])->name('departments.data');
    Route::resource('departments', DepartmentController::class)->except(['create', 'edit']);
    Route::get('positions/data', [PositionController::class, 'data'])->name('positions.data');
    Route::resource('positions', PositionController::class)->except(['create', 'edit']);
    Route::get('vacancies/data', [VacancyController::class, 'data'])->name('vacancies.data');
    Route::resource('vacancies', VacancyController::class)->except(['create', 'edit']);
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::resource('users', UserController::class)->except(['create', 'edit']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

Route::prefix('hr')->name('hr.')->middleware(['auth', 'role:hr'])->group(function () {
    Route::get('/', [HRDashboardController::class, 'index'])->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    Route::get('applicants/data', [ApplicantController::class, 'data'])->name('applicants.data');
    Route::get('applicants', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::get('applicants/{application}/print', [ApplicantController::class, 'printForms'])->name('applicants.print');
    Route::get('applicants/{application}/edit', [ApplicantController::class, 'edit'])->name('applicants.edit');
    Route::put('applicants/{application}', [ApplicantController::class, 'update'])->name('applicants.update');
    Route::get('applicants/{application}', [ApplicantController::class, 'show'])->name('applicants.show');
    Route::put('applicants/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');

    Route::get('interviews/data', [InterviewController::class, 'data'])->name('interviews.data');
    Route::resource('interviews', InterviewController::class)->except(['create','edit']);


    Route::get('assessment-insights', [AssessmentInsightController::class, 'index'])->name('assessment-insights.index');
    Route::post('assessment-insights/recalculate', [AssessmentInsightController::class, 'recalculate'])->name('assessment-insights.recalculate');
    Route::put('assessment-insights/{vacancy}/criteria', [AssessmentInsightController::class, 'updateCriteria'])->name('assessment-insights.criteria');
    Route::put('assessment-insights/{vacancy}/templates', [AssessmentInsightController::class, 'updateTemplates'])->name('assessment-insights.templates');
    Route::put('assessment-insights/{vacancy}/mappings', [AssessmentInsightController::class, 'updateMappings'])->name('assessment-insights.mappings');
    Route::post('assessment-insights/{vacancy}/save-position-default', [AssessmentInsightController::class, 'savePositionDefault'])->name('assessment-insights.save-position-default');
    Route::post('assessment-insights/{vacancy}/reset-position-default', [AssessmentInsightController::class, 'resetPositionDefault'])->name('assessment-insights.reset-position-default');

    Route::get('hiring-status/data', [PipelineController::class, 'data'])->name('hiring-status.data');
    Route::get('hiring-status', [PipelineController::class, 'index'])->name('hiring-status.index');
    Route::put('hiring-status/{application}', [PipelineController::class, 'update'])->name('hiring-status.update');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
});

