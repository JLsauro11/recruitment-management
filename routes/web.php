<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\HR\DashboardController as HRDashboardController;
use App\Http\Controllers\Admin\{DepartmentController, PositionController, VacancyController, UserController, ApplicantController, SettingController};
use App\Http\Controllers\Recruitment\{InterviewController, PipelineController, ReportController, FormTemplateController, NotificationController};
use App\Http\Controllers\Applicant\FormController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


Route::get('/apply', [FormController::class, 'index'])->name('careers.apply');
Route::post('/apply/validate-step', [FormController::class, 'validateStep'])->name('careers.validate-step');
Route::post('/apply', [FormController::class, 'submit'])->name('careers.submit');
Route::get('/application-submitted', [FormController::class, 'success'])->name('careers.success');

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('careers.apply');
    }

    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'hr' => redirect()->route('hr.dashboard'),
        default => redirect()->route('careers.apply'),
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

    Route::get('forms/data', [FormTemplateController::class, 'data'])->name('forms.data');
    Route::get('forms/sections', [FormTemplateController::class, 'sections'])->name('forms.sections');
    Route::resource('forms', FormTemplateController::class)->parameters(['forms' => 'formTemplate'])->except(['create','edit']);

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

    Route::get('forms/data', [FormTemplateController::class, 'data'])->name('forms.data');
    Route::get('forms/sections', [FormTemplateController::class, 'sections'])->name('forms.sections');
    Route::resource('forms', FormTemplateController::class)->parameters(['forms' => 'formTemplate'])->except(['create','edit']);

    Route::get('hiring-status/data', [PipelineController::class, 'data'])->name('hiring-status.data');
    Route::get('hiring-status', [PipelineController::class, 'index'])->name('hiring-status.index');
    Route::put('hiring-status/{application}', [PipelineController::class, 'update'])->name('hiring-status.update');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
});

