<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\HR\DashboardController as HRDashboardController;
use App\Http\Controllers\Applicant\DashboardController as ApplicantDashboardController;
use App\Http\Controllers\Admin\{
    DepartmentController, PositionController, VacancyController, UserController, ApplicantController
};

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', fn()=>auth()->check() ? redirect()->route(auth()->user()->role . '.dashboard') : redirect()->route('login'));

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('departments/data', [DepartmentController::class, 'data'])->name('departments.data');
    Route::resource('departments', DepartmentController::class)->except(['create', 'edit']);
    Route::get('positions/data', [PositionController::class, 'data'])->name('positions.data');
    Route::resource('positions', PositionController::class)->except(['create', 'edit']);
    Route::get('vacancies/data', [VacancyController::class, 'data'])->name('vacancies.data');
    Route::resource('vacancies', VacancyController::class)->except(['create', 'edit']);
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::resource('users', UserController::class)->except(['create', 'edit']);
    Route::get('applicants/data', [ApplicantController::class, 'data'])->name('applicants.data');
    Route::get('applicants', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::get('applicants/{application}', [ApplicantController::class, 'show'])->name('applicants.show');
    Route::put('applicants/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');
});

Route::prefix('hr')->name('hr.')->middleware(['auth', 'role:hr'])->group(function () {
    Route::get('/', [HRDashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('applicant')->name('applicant.')->middleware(['auth', 'role:applicant'])->group(function () {
    Route::get('/', [ApplicantDashboardController::class, 'index'])->name('dashboard');
});
