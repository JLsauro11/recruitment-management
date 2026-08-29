@extends('layout.app')

@section('title', 'Job Vacancies')
@section('page-title', 'Job Vacancies')
@section('page-subtitle', 'Manage recruitment openings')

@section('content')
    <div class="card premium-page-card">
        <div class="premium-page-header">
            <div class="premium-page-heading">
                <div class="premium-page-icon">
                    <i class="bi bi-megaphone-fill"></i>
                </div>

                <div>
                    <h4 class="premium-page-title">Job Vacancy Management</h4>
                    <p class="premium-page-subtitle">
                        Create, update, and monitor available recruitment openings.
                    </p>
                </div>
            </div>

            <div class="premium-header-actions">
                <button type="button" class="premium-secondary-btn" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh Records</span>
                </button>
                <button type="button" class="premium-primary-btn" id="addBtn">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Job Vacancy</span>
                </button>
            </div>
        </div>

        <div class="premium-table-wrap">
            <div class="table-responsive">
                <table id="dataTable" class="table align-middle w-100">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Slots</th>
                        <th>Applications</th>
                        <th>Opening</th>
                        <th>Closing</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade premium-form-modal" id="formModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="dataForm">
                    @csrf
                    <div class="premium-modal-header">
                        <div class="premium-modal-icon">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>

                        <div>
                            <h5 id="modalTitle">Add Job Vacancy</h5>
                            <p>Create or update job vacancy information.</p>
                        </div>

                        <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="record_id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="position_id" class="form-label">
                                    Position
                                </label>

                                <select
                                        name="position_id"
                                        id="position_id"
                                        class="form-select"
                                >
                                    <option value="">Select Position</option>

                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">
                                            {{ $position->name }}
                                            @if($position->department)
                                                - {{ $position->department->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                <div class="invalid-feedback position_id_error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="title" class="form-label">
                                    Vacancy Title
                                </label>

                                <input
                                        type="text"
                                        name="title"
                                        id="title"
                                        class="form-control"
                                        placeholder="Example: Sales Executive"
                                >

                                <div class="invalid-feedback title_error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="employment_type" class="form-label">
                                    Employment Type
                                </label>

                                <select
                                        name="employment_type"
                                        id="employment_type"
                                        class="form-select"
                                >
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>

                                <div class="invalid-feedback employment_type_error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="slots" class="form-label">
                                    Available Slots
                                </label>

                                <input
                                        type="number"
                                        name="slots"
                                        id="slots"
                                        class="form-control"
                                        min="1"
                                        value="1"
                                >

                                <div class="invalid-feedback slots_error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">
                                    Status
                                </label>

                                <select
                                        name="status"
                                        id="status"
                                        class="form-select"
                                >
                                    <option value="Draft">Draft</option>
                                    <option value="Open">Open</option>
                                    <option value="Closed">Closed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>

                                <div class="invalid-feedback status_error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="salary_min" class="form-label">
                                    Minimum Salary
                                </label>

                                <input
                                        type="number"
                                        name="salary_min"
                                        id="salary_min"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="Optional"
                                >

                                <div class="invalid-feedback salary_min_error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="salary_max" class="form-label">
                                    Maximum Salary
                                </label>

                                <input
                                        type="number"
                                        name="salary_max"
                                        id="salary_max"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="Optional"
                                >

                                <div class="invalid-feedback salary_max_error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="opening_date" class="form-label">
                                    Opening Date
                                </label>

                                <input
                                        type="date"
                                        name="opening_date"
                                        id="opening_date"
                                        class="form-control"
                                >

                                <div class="invalid-feedback opening_date_error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="closing_date" class="form-label">
                                    Closing Date
                                </label>

                                <input
                                        type="date"
                                        name="closing_date"
                                        id="closing_date"
                                        class="form-control"
                                >

                                <div class="invalid-feedback closing_date_error"></div>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">
                                    Job Description
                                </label>

                                <textarea
                                        name="description"
                                        id="description"
                                        class="form-control"
                                        rows="5"
                                        placeholder="Enter the job description..."
                                ></textarea>

                                <div class="invalid-feedback description_error"></div>
                            </div>

                            <div class="col-12">
                                <label for="qualifications" class="form-label">
                                    Qualifications
                                </label>

                                <textarea
                                        name="qualifications"
                                        id="qualifications"
                                        class="form-control"
                                        rows="5"
                                        placeholder="Enter the required qualifications..."
                                ></textarea>

                                <div class="invalid-feedback qualifications_error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button
                                type="submit"
                                id="saveBtn"
                                class="btn btn-danger"
                        >
                            Save Job Vacancy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade premium-vacancy-modal" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content vacancy-details-modal">
                <div class="vacancy-hero">
                    <button
                            type="button"
                            class="btn-close btn-close-white vacancy-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                    ></button>

                    <div class="vacancy-hero-content">
                        <div class="vacancy-icon">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div class="vacancy-eyebrow">JOB VACANCY DETAILS</div>

                            <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                                <h2 id="view_title" class="vacancy-title mb-0"></h2>
                                <div id="view_status"></div>
                            </div>

                            <div class="vacancy-hero-meta mt-3">
                            <span>
                                <i class="bi bi-person-vcard-fill"></i>
                                <span id="view_position"></span>
                            </span>

                                <span>
                                <i class="bi bi-building-fill"></i>
                                <span id="view_department"></span>
                            </span>

                                <span>
                                <i class="bi bi-clock-fill"></i>
                                <span id="view_employment_type"></span>
                            </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-body vacancy-details-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-sm-6 col-xl-3">
                                    <div class="vacancy-stat-card">
                                        <div class="vacancy-stat-icon blue">
                                            <i class="bi bi-people-fill"></i>
                                        </div>

                                        <div>
                                            <small>Available Slots</small>
                                            <strong id="view_slots"></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-xl-3">
                                    <div class="vacancy-stat-card">
                                        <div class="vacancy-stat-icon purple">
                                            <i class="bi bi-file-earmark-person-fill"></i>
                                        </div>

                                        <div>
                                            <small>Applications</small>
                                            <strong id="view_applications"></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-xl-3">
                                    <div class="vacancy-stat-card">
                                        <div class="vacancy-stat-icon green">
                                            <i class="bi bi-calendar-check-fill"></i>
                                        </div>

                                        <div>
                                            <small>Opening Date</small>
                                            <strong id="view_opening_date"></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-xl-3">
                                    <div class="vacancy-stat-card">
                                        <div class="vacancy-stat-icon orange">
                                            <i class="bi bi-calendar-x-fill"></i>
                                        </div>

                                        <div>
                                            <small>Closing Date</small>
                                            <strong id="view_closing_date"></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="vacancy-section mt-4">
                                <div class="vacancy-section-heading">
                                    <div class="section-icon">
                                        <i class="bi bi-card-text"></i>
                                    </div>

                                    <div>
                                        <h5>Job Description</h5>
                                        <p>Primary duties and responsibilities for this role.</p>
                                    </div>
                                </div>

                                <div class="vacancy-copy" id="view_description"></div>
                            </div>

                            <div class="vacancy-section mt-4">
                                <div class="vacancy-section-heading">
                                    <div class="section-icon">
                                        <i class="bi bi-patch-check-fill"></i>
                                    </div>

                                    <div>
                                        <h5>Qualifications</h5>
                                        <p>Required skills, experience, and credentials.</p>
                                    </div>
                                </div>

                                <div class="vacancy-copy" id="view_qualifications"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="salary-card">
                                <span class="salary-label">MONTHLY SALARY RANGE</span>

                                <div class="salary-value" id="view_salary"></div>

                                <p class="mb-0">
                                    Final offer may vary based on experience and qualifications.
                                </p>
                            </div>

                            <div class="vacancy-side-card mt-3">
                                <h6>Recruitment Overview</h6>

                                <div class="vacancy-side-row">
                                <span>
                                    <i class="bi bi-briefcase"></i>
                                    Position
                                </span>

                                    <strong id="view_position_side"></strong>
                                </div>

                                <div class="vacancy-side-row">
                                <span>
                                    <i class="bi bi-building"></i>
                                    Department
                                </span>

                                    <strong id="view_department_side"></strong>
                                </div>

                                <div class="vacancy-side-row">
                                <span>
                                    <i class="bi bi-clock"></i>
                                    Employment
                                </span>

                                    <strong id="view_employment_type_side"></strong>
                                </div>

                                <div class="vacancy-side-row">
                                <span>
                                    <i class="bi bi-circle-fill"></i>
                                    Current Status
                                </span>

                                    <div id="view_status_side"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer vacancy-details-footer">
                    <button
                            type="button"
                            class="btn btn-light px-4"
                            data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('styles')
<style>

    /* =========================================================
       SHARED PREMIUM ADMIN MODULE DESIGN
    ========================================================= */

    .premium-page-card {
        overflow: hidden;
        border: 1px solid #edf0f4 !important;
        border-radius: 22px !important;
        background: #fff;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .07) !important;
    }

    .premium-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 24px 26px;
        border-bottom: 1px solid #eef1f5;
        background:
                radial-gradient(circle at top right, rgba(237, 28, 36, .08), transparent 34%),
                linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
    }

    .premium-page-heading {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
    }

    .premium-page-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        color: #fff;
        background: linear-gradient(135deg, #ed1c24, #a30d13);
        box-shadow: 0 10px 24px rgba(237, 28, 36, .22);
        font-size: 21px;
        line-height: 1;
    }

    .premium-page-icon i,
    .premium-page-icon i::before {
        display: block;
        line-height: 1;
    }

    .premium-page-title {
        margin: 0 0 4px;
        color: #17233e;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -.25px;
    }

    .premium-page-subtitle {
        margin: 0;
        color: #8b96a9;
        font-size: 12px;
        line-height: 1.55;
    }

    .premium-primary-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 16px;
        border: 1px solid #dc161e;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #ed1c24, #c61219);
        box-shadow: 0 9px 22px rgba(237, 28, 36, .22);
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        transition: .2s ease;
    }

    .premium-primary-btn:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(237, 28, 36, .28);
    }

    .premium-primary-btn i,
    .premium-primary-btn i::before {
        display: block;
        line-height: 1;
    }

    .premium-table-wrap {
        padding: 22px 24px 24px;
    }

    .premium-table-wrap .dataTables_length,
    .premium-table-wrap .dataTables_filter,
    .premium-table-wrap .dataTables_info,
    .premium-table-wrap .dataTables_paginate {
        color: #66738a;
        font-size: 12px;
    }

    .premium-table-wrap .dataTables_length select,
    .premium-table-wrap .dataTables_filter input {
        min-height: 36px;
        border: 1px solid #dfe5ec;
        border-radius: 9px;
        background: #fff;
        box-shadow: none;
    }

    .premium-table-wrap table.dataTable {
        margin-top: 16px !important;
        margin-bottom: 12px !important;
        border-collapse: separate !important;
        border-spacing: 0;
    }

    .premium-table-wrap table.dataTable thead th {
        padding: 13px 12px;
        border-top: 0;
        border-bottom: 1px solid #e8edf3;
        color: #66738a;
        background: #f8fafc;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    .premium-table-wrap table.dataTable tbody td {
        padding: 14px 12px;
        border-bottom: 1px solid #eef2f6;
        color: #42516b;
        font-size: 13px;
        vertical-align: middle;
    }

    .premium-table-wrap table.dataTable tbody tr:last-child td {
        border-bottom: 0;
    }

    .premium-table-wrap table.dataTable tbody tr:hover {
        background: #fffafa;
    }

    .premium-action-group {
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    .premium-icon-btn {
        width: 34px !important;
        height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border-radius: 9px !important;
        line-height: 1 !important;
    }

    .premium-form-modal .modal-dialog {
        width: min(980px, calc(100vw - 28px));
        max-width: 980px;
        margin: 1.75rem auto;
    }

    .premium-form-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 20px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, .22);
    }

    .premium-modal-header {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 22px 58px 22px 22px;
        color: #fff;
        border: 0;
        background:
                radial-gradient(circle at top right, rgba(255,255,255,.13), transparent 34%),
                linear-gradient(135deg, #151d34 0%, #26304d 65%, #8f1117 145%);
    }

    .premium-modal-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 14px;
        color: #fff;
        background: rgba(255,255,255,.12);
        font-size: 20px;
    }

    .premium-modal-icon i,
    .premium-modal-icon i::before {
        display: block;
        line-height: 1;
    }

    .premium-modal-header h5 {
        margin: 0 0 3px;
        color: #fff;
        font-size: 17px;
        font-weight: 800;
    }

    .premium-modal-header p {
        margin: 0;
        color: rgba(255,255,255,.68);
        font-size: 11px;
    }

    .premium-modal-header .btn-close {
        position: absolute;
        top: 18px;
        right: 18px;
    }

    .premium-form-modal .modal-body {
        padding: 24px;
        background: #fbfcfe;
    }

    .premium-form-modal .modal-footer {
        padding: 15px 22px;
        border-top: 1px solid #edf0f4;
        background: #fff;
    }

    .premium-form-modal .form-label {
        margin-bottom: 7px;
        color: #35425a;
        font-size: 12px;
        font-weight: 700;
    }

    .premium-form-modal .form-control,
    .premium-form-modal .form-select {
        min-height: 46px;
        border: 1px solid #dfe5ec;
        border-radius: 11px;
        color: #334155;
        background-color: #fff;
        box-shadow: none;
    }

    .premium-form-modal textarea.form-control {
        min-height: 110px;
    }

    .premium-form-modal .form-control:focus,
    .premium-form-modal .form-select:focus {
        border-color: #ed1c24;
        box-shadow: 0 0 0 4px rgba(237, 28, 36, .08);
    }

    @media (max-width: 767.98px) {
        .premium-page-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 20px;
        }

        .premium-page-heading {
            align-items: flex-start;
        }

        .premium-page-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            font-size: 18px;
        }

        .premium-primary-btn {
            width: 100%;
        }

        .premium-table-wrap {
            padding: 16px;
        }

        .premium-table-wrap .dataTables_length,
        .premium-table-wrap .dataTables_filter {
            width: 100%;
            margin-bottom: 10px;
            text-align: left !important;
        }

        .premium-table-wrap .dataTables_filter input {
            width: calc(100% - 54px);
            margin-left: 6px;
        }

        .premium-form-modal {
            padding: 0 !important;
        }

        .premium-form-modal .modal-dialog {
            width: 100%;
            min-height: 100dvh;
            margin: 0;
        }

        .premium-form-modal .modal-content,
        .premium-form-modal form {
            min-height: 100dvh;
            border-radius: 0;
        }

        .premium-form-modal form {
            display: flex;
            flex-direction: column;
        }

        .premium-form-modal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .premium-form-modal .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 5;
        }
    }

    /* =========================================================
       JOB VACANCY PAGE
    ========================================================= */

    .card-header {
        gap: 1rem;
    }

    #dataTable_wrapper {
        width: 100%;
    }

    #dataTable {
        min-width: 1180px;
    }

    #dataTable td,
    #dataTable th {
        vertical-align: middle;
        white-space: nowrap;
    }

    #dataTable .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    #dataTable .btn i,
    #dataTable .btn i::before {
        display: block;
        line-height: 1;
    }

    /* =========================================================
       VIEW MODAL BASE
    ========================================================= */

    .premium-vacancy-modal {
        padding: 16px !important;
    }

    .premium-vacancy-modal .modal-dialog {
        width: min(1120px, calc(100vw - 32px));
        max-width: 1120px;
        margin: 1.75rem auto;
    }

    .vacancy-details-modal {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-height: calc(100dvh - 56px);
        overflow: hidden;
        border: 0;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 28px 80px rgba(15, 23, 42, .24);
    }

    /* =========================================================
       HERO
    ========================================================= */

    .vacancy-hero {
        position: relative;
        flex: 0 0 auto;
        padding: 30px 64px 30px 34px;
        overflow: hidden;
        color: #fff;
        background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .14), transparent 34%),
                linear-gradient(135deg, #141b31 0%, #252f4c 62%, #8f1117 140%);
    }

    .vacancy-hero::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -80px;
        bottom: -145px;
        border: 38px solid rgba(255, 255, 255, .06);
        border-radius: 50%;
    }

    .vacancy-close {
        position: absolute;
        z-index: 5;
        top: 20px;
        right: 22px;
        opacity: .92;
    }

    .vacancy-hero-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .vacancy-icon {
        width: 72px !important;
        height: 72px !important;
        min-width: 72px !important;
        flex: 0 0 72px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 20px;
        color: #fff;
        background: rgba(255, 255, 255, .12);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .12);
        backdrop-filter: blur(8px);
    }

    .vacancy-icon > i {
        width: 32px !important;
        height: 32px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 29px !important;
        line-height: 1 !important;
    }

    .vacancy-icon > i::before {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1 !important;
        transform: translateY(-1px);
    }

    .vacancy-eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .16em;
        color: #fca5a5;
    }

    .vacancy-title {
        color: #fff;
        font-size: clamp(25px, 3vw, 36px);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -.8px;
        overflow-wrap: anywhere;
    }

    .vacancy-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 22px;
        color: rgba(255, 255, 255, .78);
        font-size: 13px;
    }

    .vacancy-hero-meta > span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .vacancy-hero-meta i {
        color: #fca5a5;
    }

    /* =========================================================
       STATUS BADGE
    ========================================================= */

    .premium-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 999px;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .premium-status-badge::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #fff;
    }

    .premium-status-badge.status-open {
        background: #16a34a;
        border: 1px solid #15803d;
        box-shadow: 0 8px 18px rgba(22, 163, 74, .30);
    }

    .premium-status-badge.status-draft {
        background: #64748b;
        border: 1px solid #475569;
        box-shadow: 0 8px 18px rgba(100, 116, 139, .30);
    }

    .premium-status-badge.status-closed {
        background: #334155;
        border: 1px solid #1e293b;
        box-shadow: 0 8px 18px rgba(51, 65, 85, .30);
    }

    .premium-status-badge.status-cancelled {
        background: #dc2626;
        border: 1px solid #b91c1c;
        box-shadow: 0 8px 18px rgba(220, 38, 38, .30);
    }

    /* =========================================================
       MODAL BODY
    ========================================================= */

    .vacancy-details-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 30px 34px;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        background: #f8fafc;
    }

    .vacancy-stat-card {
        min-height: 96px;
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 16px;
        border: 1px solid #edf0f4;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .045);
    }

    .vacancy-stat-card small,
    .vacancy-stat-card strong {
        display: block;
    }

    .vacancy-stat-card small {
        margin-bottom: 3px;
        color: #8b95a7;
        font-size: 11px;
    }

    .vacancy-stat-card strong {
        color: #1f2a44;
        font-size: 14px;
        line-height: 1.35;
    }

    .vacancy-stat-icon,
    .vacancy-section-heading .section-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .vacancy-stat-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 13px;
        font-size: 18px;
    }

    .vacancy-stat-icon i,
    .vacancy-stat-icon i::before,
    .vacancy-section-heading .section-icon i,
    .vacancy-section-heading .section-icon i::before {
        display: block;
        line-height: 1;
    }

    .vacancy-stat-icon.blue {
        color: #2563eb;
        background: #dbeafe;
    }

    .vacancy-stat-icon.purple {
        color: #7c3aed;
        background: #ede9fe;
    }

    .vacancy-stat-icon.green {
        color: #16a34a;
        background: #dcfce7;
    }

    .vacancy-stat-icon.orange {
        color: #ea580c;
        background: #ffedd5;
    }

    .vacancy-section {
        padding: 24px;
        border: 1px solid #edf0f4;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .04);
    }

    .vacancy-section-heading {
        display: flex;
        align-items: center;
        gap: 13px;
        padding-bottom: 17px;
        margin-bottom: 17px;
        border-bottom: 1px solid #eef1f5;
    }

    .vacancy-section-heading .section-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        color: #ed1c24;
        border-radius: 13px;
        background: #fff1f2;
        font-size: 18px;
    }

    .vacancy-section-heading h5 {
        margin: 0 0 3px;
        color: #1f2a44;
        font-size: 16px;
        font-weight: 800;
    }

    .vacancy-section-heading p {
        margin: 0;
        color: #94a0b2;
        font-size: 12px;
    }

    .vacancy-copy {
        color: #58667d;
        font-size: 14px;
        line-height: 1.85;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .salary-card {
        padding: 25px;
        color: #fff;
        border-radius: 20px;
        background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .16), transparent 35%),
                linear-gradient(145deg, #ed1c24, #a30d13);
        box-shadow: 0 14px 34px rgba(237, 28, 36, .23);
    }

    .salary-label {
        display: block;
        color: rgba(255, 255, 255, .72);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .14em;
    }

    .salary-value {
        margin: 13px 0 12px;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .salary-card p {
        color: rgba(255, 255, 255, .74);
        font-size: 12px;
        line-height: 1.6;
    }

    .vacancy-side-card {
        padding: 22px;
        border: 1px solid #edf0f4;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .04);
    }

    .vacancy-side-card h6 {
        padding-bottom: 14px;
        margin-bottom: 4px;
        border-bottom: 1px solid #eef1f5;
        color: #1f2a44;
        font-size: 14px;
        font-weight: 800;
    }

    .vacancy-side-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 14px 0;
        border-bottom: 1px solid #f1f3f6;
    }

    .vacancy-side-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .vacancy-side-row > span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #8a95a7;
        font-size: 12px;
    }

    .vacancy-side-row > span i {
        color: #ed1c24;
    }

    .vacancy-side-row strong {
        max-width: 58%;
        color: #29364f;
        font-size: 12px;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .vacancy-details-footer {
        position: relative;
        z-index: 4;
        flex: 0 0 auto;
        padding: 16px 28px;
        border-top: 1px solid #edf0f4;
        background: #fff;
    }

    .vacancy-details-footer .btn {
        border-radius: 11px;
    }

    /* =========================================================
       LARGE LAPTOP / SMALL DESKTOP
    ========================================================= */

    @media (max-width: 1199.98px) {
        .premium-vacancy-modal .modal-dialog {
            width: calc(100vw - 32px);
            max-width: 1040px;
        }

        .vacancy-details-body {
            padding: 24px;
        }

        .vacancy-stat-card {
            min-height: 88px;
            padding: 13px;
        }

        .salary-value {
            font-size: 19px;
        }
    }

    /* =========================================================
       TABLET
    ========================================================= */

    @media (max-width: 991.98px) {
        .card-header {
            align-items: flex-start !important;
        }

        .premium-vacancy-modal {
            padding: 12px !important;
        }

        .premium-vacancy-modal .modal-dialog {
            width: calc(100vw - 24px);
            max-width: none;
            margin: 12px auto;
        }

        .vacancy-details-modal {
            max-height: calc(100dvh - 24px);
            border-radius: 20px;
        }

        .vacancy-details-body > .row {
            --bs-gutter-y: 1rem;
        }

        .vacancy-details-body .col-lg-8,
        .vacancy-details-body .col-lg-4 {
            width: 100%;
        }

        .salary-card {
            margin-top: 0;
        }

        .vacancy-side-card {
            margin-bottom: 4px;
        }
    }

    /* =========================================================
       MOBILE LANDSCAPE / LARGE PHONE
    ========================================================= */

    @media (max-width: 767.98px) {
        .card-header {
            flex-direction: column;
        }

        #addBtn {
            width: 100%;
        }

        .premium-vacancy-modal {
            padding: 0 !important;
        }

        .premium-vacancy-modal .modal-dialog {
            width: 100%;
            max-width: none;
            min-height: 100dvh;
            margin: 0;
        }

        .vacancy-details-modal {
            width: 100%;
            height: 100dvh;
            max-height: 100dvh;
            border-radius: 0;
        }

        .vacancy-hero {
            padding: 18px 48px 18px 16px;
        }

        .vacancy-close {
            top: 15px;
            right: 15px;
        }

        .vacancy-hero-content {
            align-items: flex-start;
            gap: 12px;
        }

        .vacancy-icon {
            width: 50px !important;
            height: 50px !important;
            min-width: 50px !important;
            flex-basis: 50px !important;
            border-radius: 14px;
        }

        .vacancy-icon > i {
            width: 24px !important;
            height: 24px !important;
            font-size: 22px !important;
        }

        .vacancy-eyebrow {
            font-size: 9px;
            letter-spacing: .12em;
        }

        .vacancy-title {
            font-size: 22px;
            letter-spacing: -.35px;
        }

        .vacancy-hero-content .d-flex.flex-wrap {
            gap: 7px !important;
            margin-top: 5px !important;
        }

        .premium-status-badge {
            gap: 6px;
            padding: 6px 10px;
            font-size: 10px;
        }

        .premium-status-badge::before {
            width: 6px;
            height: 6px;
        }

        .vacancy-hero-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            margin-top: 9px !important;
            font-size: 11px;
        }

        .vacancy-details-body {
            padding: 14px;
        }

        .vacancy-details-body > .row {
            --bs-gutter-x: .85rem;
            --bs-gutter-y: .85rem;
        }

        .vacancy-details-body .col-sm-6 {
            width: 50%;
        }

        .vacancy-stat-card {
            min-height: 80px;
            gap: 10px;
            padding: 11px;
            border-radius: 14px;
        }

        .vacancy-stat-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 11px;
            font-size: 15px;
        }

        .vacancy-stat-card small {
            font-size: 9px;
            line-height: 1.25;
        }

        .vacancy-stat-card strong {
            font-size: 12px;
            line-height: 1.3;
        }

        .vacancy-section {
            padding: 16px;
            border-radius: 15px;
        }

        .vacancy-section.mt-4,
        .vacancy-side-card.mt-3 {
            margin-top: .85rem !important;
        }

        .vacancy-section-heading {
            align-items: flex-start;
            gap: 10px;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .vacancy-section-heading .section-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 11px;
            font-size: 15px;
        }

        .vacancy-section-heading h5 {
            font-size: 14px;
        }

        .vacancy-section-heading p {
            font-size: 10px;
            line-height: 1.4;
        }

        .vacancy-copy {
            font-size: 12px;
            line-height: 1.7;
        }

        .salary-card,
        .vacancy-side-card {
            padding: 18px;
            border-radius: 15px;
        }

        .salary-value {
            margin: 9px 0;
            font-size: 19px;
        }

        .vacancy-side-row {
            gap: 12px;
            padding: 12px 0;
        }

        .vacancy-side-row > span,
        .vacancy-side-row strong {
            font-size: 11px;
        }

        .vacancy-details-footer {
            padding: 10px 14px;
            box-shadow: 0 -8px 22px rgba(15, 23, 42, .06);
        }

        .vacancy-details-footer .btn {
            min-width: 92px;
        }

        #formModal .modal-dialog {
            width: 100%;
            min-height: 100dvh;
            margin: 0;
        }

        #formModal .modal-content {
            min-height: 100dvh;
            border-radius: 0;
        }

        #formModal .modal-header,
        #formModal .modal-body,
        #formModal .modal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        #formModal .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 5;
            background: #fff;
        }
    }

    /* =========================================================
       SMALL PHONE
    ========================================================= */

    @media (max-width: 479.98px) {
        .vacancy-hero {
            padding: 15px 42px 15px 13px;
        }

        .vacancy-hero-content {
            gap: 10px;
        }

        .vacancy-icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            flex-basis: 44px !important;
            border-radius: 12px;
        }

        .vacancy-icon > i {
            width: 22px !important;
            height: 22px !important;
            font-size: 20px !important;
        }

        .vacancy-title {
            font-size: 19px;
        }

        .vacancy-details-body {
            padding: 10px;
        }

        .vacancy-details-body .col-sm-6 {
            width: 100%;
        }

        .vacancy-stat-card {
            min-height: 66px;
        }

        .salary-card,
        .vacancy-side-card,
        .vacancy-section {
            padding: 15px;
        }

        .vacancy-side-row {
            align-items: flex-start;
        }

        .vacancy-side-row strong {
            max-width: 54%;
        }

        .vacancy-details-footer {
            justify-content: stretch;
        }

        .vacancy-details-footer .btn {
            width: 100%;
        }
    }

    /* =========================================================
       VERY SHORT SCREENS / LANDSCAPE PHONES
    ========================================================= */

    @media (max-height: 650px) and (min-width: 768px) {
        .premium-vacancy-modal .modal-dialog {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .vacancy-details-modal {
            max-height: calc(100dvh - 20px);
        }

        .vacancy-hero {
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .vacancy-icon {
            width: 56px !important;
            height: 56px !important;
            min-width: 56px !important;
            flex-basis: 56px !important;
        }

        .vacancy-title {
            font-size: 26px;
        }

        .vacancy-details-body {
            padding-top: 18px;
            padding-bottom: 18px;
        }
    }


    /* Exact optical centering for page header icons */
    .premium-page-icon {
        position: relative !important;
        display: block !important;
        flex: 0 0 50px !important;
        width: 50px !important;
        height: 50px !important;
        min-width: 50px !important;
        padding: 0 !important;
        line-height: 0 !important;
        overflow: hidden;
    }

    .premium-page-icon > i {
        position: absolute !important;
        inset: 0 !important;
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 21px !important;
        line-height: 0 !important;
    }

    .premium-page-icon > i::before {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        display: block !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1 !important;
        transform: translate(-50%, -50%) !important;
    }

    @media (max-width: 767.98px) {
        .premium-page-icon {
            flex-basis: 44px !important;
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
        }

        .premium-page-icon > i {
            font-size: 18px !important;
        }
    }


    /* =========================================================
       FINAL ICON CENTERING OVERRIDES
       Centers the actual Bootstrap Icons ::before glyph.
    ========================================================= */

    .premium-page-icon,
    .premium-modal-icon {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        line-height: 1 !important;
        overflow: hidden;
    }

    .premium-page-icon > i,
    .premium-modal-icon > i {
        position: relative !important;
        display: block !important;
        flex: 0 0 auto !important;
        width: 24px !important;
        height: 24px !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 21px !important;
        line-height: 1 !important;
    }

    .premium-modal-icon > i {
        width: 22px !important;
        height: 22px !important;
        font-size: 20px !important;
    }

    .premium-page-icon > i::before,
    .premium-modal-icon > i::before {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        display: block !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1 !important;
        transform: translate(-50%, -50%) !important;
    }

    .premium-primary-btn > i,
    .premium-secondary-btn > i,
    .premium-icon-btn > i {
        position: relative !important;
        display: block !important;
        flex: 0 0 16px !important;
        width: 16px !important;
        height: 16px !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 15px !important;
        line-height: 1 !important;
    }

    .premium-primary-btn > i::before,
    .premium-secondary-btn > i::before,
    .premium-icon-btn > i::before {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        display: block !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1 !important;
        transform: translate(-50%, -50%) !important;
    }

    .premium-icon-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        line-height: 1 !important;
    }

    @media (max-width: 767.98px) {
        .premium-page-icon > i {
            width: 21px !important;
            height: 21px !important;
            font-size: 18px !important;
        }
    }

    /* =========================================================
       FINAL VACANCY FORM MODAL LAYOUT
       Compact on desktop, scrollable on short screens,
       fixed header and footer, full-screen only on mobile.
    ========================================================= */

    #formModal {
        padding: 20px !important;
        overflow-y: auto;
    }

    #formModal .modal-dialog {
        width: min(920px, calc(100vw - 40px));
        max-width: 920px;
        margin: 20px auto;
    }

    #formModal .modal-content {
        display: flex;
        flex-direction: column;
        max-height: calc(100dvh - 40px);
        overflow: hidden;
        border: 0;
        border-radius: 20px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, .24);
    }

    #formModal #dataForm {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: calc(100dvh - 40px);
    }

    #formModal .premium-modal-header {
        flex: 0 0 auto;
        padding: 18px 58px 18px 20px;
    }

    #formModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow-x: hidden;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        background: #fbfcfe;
    }

    #formModal .form-control,
    #formModal .form-select {
        min-height: 42px;
    }

    #formModal textarea.form-control {
        min-height: 86px;
        height: 86px;
        resize: vertical;
    }

    #formModal .modal-footer {
        position: relative !important;
        z-index: 10;
        flex: 0 0 auto;
        margin: 0;
        padding: 12px 20px;
        border-top: 1px solid #e8edf3;
        background: #fff;
        box-shadow: 0 -5px 18px rgba(15, 23, 42, .04);
    }

    #formModal .modal-footer .btn {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
    }

    @media (max-height: 760px) and (min-width: 768px) {
        #formModal {
            padding: 10px !important;
        }

        #formModal .modal-dialog {
            margin: 10px auto;
        }

        #formModal .modal-content,
        #formModal #dataForm {
            max-height: calc(100dvh - 20px);
        }

        #formModal .premium-modal-header {
            padding-top: 14px;
            padding-bottom: 14px;
        }

        #formModal .modal-body {
            padding: 16px 20px;
        }

        #formModal textarea.form-control {
            min-height: 72px;
            height: 72px;
        }
    }

    @media (max-width: 991.98px) {
        #formModal .modal-dialog {
            width: calc(100vw - 24px);
            max-width: none;
            margin: 12px auto;
        }

        #formModal .modal-content,
        #formModal #dataForm {
            max-height: calc(100dvh - 24px);
        }
    }

    @media (max-width: 767.98px) {
        #formModal {
            padding: 0 !important;
            overflow: hidden;
        }

        #formModal .modal-dialog {
            width: 100%;
            height: 100dvh;
            max-width: none;
            margin: 0;
        }

        #formModal .modal-content,
        #formModal #dataForm {
            width: 100%;
            height: 100dvh;
            max-height: 100dvh;
            border-radius: 0;
        }

        #formModal .modal-body {
            padding: 16px;
        }

        #formModal .modal-footer {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 10px;
            padding: 11px 16px;
        }

        #formModal .modal-footer .btn {
            width: 100%;
            margin: 0;
        }
    }

    @media (max-width: 420px) {
        #formModal .modal-footer {
            grid-template-columns: 1fr;
        }
    }



    /* =========================================================
       ADMIN TABLE TYPOGRAPHY
       Larger, clearer text for every admin DataTable.
    ========================================================= */

    .premium-table-wrap .dataTables_length,
    .premium-table-wrap .dataTables_filter,
    .premium-table-wrap .dataTables_info,
    .premium-table-wrap .dataTables_paginate {
        font-size: 14px !important;
    }

    .premium-table-wrap .dataTables_length label,
    .premium-table-wrap .dataTables_filter label {
        font-size: 14px !important;
        font-weight: 500;
    }

    .premium-table-wrap .dataTables_length select,
    .premium-table-wrap .dataTables_filter input {
        min-height: 40px;
        font-size: 14px !important;
    }

    .premium-table-wrap table.dataTable thead th {
        font-size: 13px !important;
        line-height: 1.35;
        letter-spacing: .025em;
    }

    .premium-table-wrap table.dataTable tbody td {
        font-size: 14px !important;
        line-height: 1.55;
    }

    .premium-table-wrap .premium-status,
    .premium-table-wrap .premium-status-badge {
        font-size: 12px !important;
    }

    .premium-table-wrap .paginate_button,
    .premium-table-wrap .page-link {
        font-size: 14px !important;
    }

    @media (max-width: 767.98px) {
        .premium-table-wrap .dataTables_length,
        .premium-table-wrap .dataTables_filter,
        .premium-table-wrap .dataTables_info,
        .premium-table-wrap .dataTables_paginate,
        .premium-table-wrap .dataTables_length label,
        .premium-table-wrap .dataTables_filter label {
            font-size: 13px !important;
        }

        .premium-table-wrap table.dataTable thead th {
            font-size: 12px !important;
        }

        .premium-table-wrap table.dataTable tbody td {
            font-size: 13px !important;
        }
    }

</style>
@endpush


@push('scripts')
<script>
    $(document).ready(function () {
        const formModal = new bootstrap.Modal(
            document.getElementById('formModal')
        );

        const viewModal = new bootstrap.Modal(
            document.getElementById('viewModal')
        );

        let table;

        function clearErrors() {
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
        }

        function resetForm() {
            clearErrors();

            $('#dataForm')[0].reset();
            $('#record_id').val('');
            $('#slots').val(1);
            $('#employment_type').val('Full-time');
            $('#status').val('Draft');
            $('#modalTitle').text('Add Job Vacancy');
            $('#saveBtn').text('Save Job Vacancy');
        }

        function formatDate(dateValue) {
            if (!dateValue) {
                return 'N/A';
            }

            return new Date(dateValue + 'T00:00:00').toLocaleDateString(
                'en-US',
                {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit'
                }
            );
        }

        function formatMoney(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(value);
        }

        function getStatusBadge(status) {
            const classes = {
                'Draft': 'status-draft',
                'Open': 'status-open',
                'Closed': 'status-closed',
                'Cancelled': 'status-cancelled'
            };

            const badgeClass = classes[status] || 'status-draft';

            return `
            <span class="premium-status-badge ${badgeClass}">
                ${status}
            </span>
        `;
        }

        function loadData() {
            if ($.fn.DataTable.isDataTable('#dataTable')) {
                table.destroy();
            }

            table = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
                autoWidth: false,
                scrollX: true,

                ajax: {
                    url: "{{ route('admin.vacancies.data') }}",
                    type: "GET",
                    dataType: "json",

                    error: function (xhr) {
                        console.log(xhr.responseText);

                        Swal.fire({
                                icon: 'error',
                                title: 'Unable to Load Vacancies',
                                text: xhr.responseJSON?.message
                            || 'Something went wrong while loading vacancies.'
                    });
                    }
                },

                columns: [
                    { data: 'id' },
                    { data: 'title' },
                    {
                        data: 'position_name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'department_name',
                        defaultContent: 'N/A'
                    },
                    { data: 'employment_type' },
                    { data: 'slots' },
                    {
                        data: 'applications_count',
                        defaultContent: 0
                    },
                    { data: 'opening_date' },
                    { data: 'closing_date' },
                    {
                        data: 'status',
                        render: function (data) {
                            return getStatusBadge(data);
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,

                        render: function (data, type, row) {
                            return `
                            <div class="premium-action-group">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light premium-icon-btn viewBtn"
                                    data-id="${row.id}"
                                    title="View"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary premium-icon-btn editBtn"
                                    data-id="${row.id}"
                                    title="Edit"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger premium-icon-btn deleteBtn"
                                    data-id="${row.id}"
                                    title="Delete"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        }
                    }
                ],

                order: [[0, 'desc']],

                language: {
                    emptyTable: 'No job vacancies found.',
                    processing: 'Loading job vacancies...'
                }
            });
        }


$('#refreshBtn').on('click', function () {
            const $button = $(this);
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Refreshing...</span>');

            table.ajax.reload(function () {
                $button.prop('disabled', false).html(originalHtml);
                showToast('success', 'Records refreshed successfully.');
            }, false);
        });

        $('#addBtn').click(function () {
            resetForm();
            formModal.show();
        });

        $('#dataTable').on('click', '.viewBtn', function () {
            const id = $(this).data('id');

            const url = "{{ route('admin.vacancies.show', ':id') }}"
                .replace(':id', id);

            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'json',

                success: function (response) {
                    $('#view_title').text(response.title || 'N/A');
                    $('#view_status').html(getStatusBadge(response.status));
                    $('#view_status_side').html(getStatusBadge(response.status));

                    $('#view_position').text(response.position_name || 'N/A');
                    $('#view_position_side').text(response.position_name || 'N/A');

                    $('#view_department').text(response.department_name || 'N/A');
                    $('#view_department_side').text(response.department_name || 'N/A');

                    $('#view_employment_type').text(response.employment_type || 'N/A');
                    $('#view_employment_type_side').text(response.employment_type || 'N/A');

                    $('#view_slots').text(response.slots ?? 0);
                    $('#view_applications').text(response.applications_count ?? 0);
                    $('#view_opening_date').text(response.opening_date || 'N/A');
                    $('#view_closing_date').text(response.closing_date || 'N/A');

                    const minimumSalary = formatMoney(response.salary_min);
                    const maximumSalary = formatMoney(response.salary_max);

                    let salaryText = 'Not specified';

                    if (minimumSalary && maximumSalary) {
                        salaryText = minimumSalary + ' - ' + maximumSalary;
                    } else if (minimumSalary) {
                        salaryText = 'Starting at ' + minimumSalary;
                    } else if (maximumSalary) {
                        salaryText = 'Up to ' + maximumSalary;
                    }

                    $('#view_salary').text(salaryText);
                    $('#view_description').text(response.description || 'No description provided.');
                    $('#view_qualifications').text(
                        response.qualifications || 'No qualifications provided.'
                    );

                    viewModal.show();
                },

                error: function (xhr) {
                    Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message
                        || 'Unable to retrieve vacancy details.'
                });
                }
            });
        });

        $('#dataTable').on('click', '.editBtn', function () {
            clearErrors();

            const id = $(this).data('id');

            const url = "{{ route('admin.vacancies.show', ':id') }}"
                .replace(':id', id);

            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'json',

                success: function (response) {
                    $('#record_id').val(response.id);
                    $('#position_id').val(response.position_id);
                    $('#title').val(response.title);
                    $('#employment_type').val(response.employment_type);
                    $('#slots').val(response.slots);
                    $('#salary_min').val(response.salary_min);
                    $('#salary_max').val(response.salary_max);
                    $('#opening_date').val(response.opening_date_raw);
                    $('#closing_date').val(response.closing_date_raw);
                    $('#description').val(response.description);
                    $('#qualifications').val(response.qualifications);
                    $('#status').val(response.status);

                    $('#modalTitle').text('Edit Job Vacancy');
                    $('#saveBtn').text('Update Job Vacancy');

                    formModal.show();
                },

                error: function (xhr) {
                    Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message
                        || 'Unable to retrieve vacancy information.'
                });
                }
            });
        });

        $('#dataForm').submit(function (e) {
            e.preventDefault();

            clearErrors();

            const id = $('#record_id').val();

            const url = id
                ? "{{ route('admin.vacancies.update', ':id') }}".replace(':id', id)
                : "{{ route('admin.vacancies.store') }}";

            const method = id ? 'PUT' : 'POST';

            $('#saveBtn')
                .prop('disabled', true)
                .text('Saving...');

            Swal.fire({
                    title: 'Saving...',
                    text: 'Please wait while saving the job vacancy.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,

                    didOpen: () => {
                    Swal.showLoading();
        }
        });

            $.ajax({
                type: method,
                url: url,
                dataType: 'json',
                data: $(this).serialize(),

                success: function (response) {
                    Swal.close();
                    formModal.hide();

                    showToast('success', response.message);

                    table.ajax.reload(null, false);
                },

                error: function (xhr) {
                    Swal.close();

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors || {};

                        $.each(errors, function (key, value) {
                            $('[name="' + key + '"]').addClass('is-invalid');
                            $('.' + key + '_error').text(value[0]);
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please check the required fields.'
                        });
                    } else {
                        console.log(xhr.responseText);

                        Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message
                            || 'Something went wrong. Please try again.'
                    });
                    }
                },

                complete: function () {
                    $('#saveBtn')
                        .prop('disabled', false)
                        .text(id ? 'Update Job Vacancy' : 'Save Job Vacancy');
                }
            });
        });

        $('#dataTable').on('click', '.deleteBtn', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete this job vacancy?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                const url = "{{ route('admin.vacancies.destroy', ':id') }}"
                    .replace(':id', id);

                $.ajax({
                    type: 'DELETE',
                    url: url,
                    dataType: 'json',

                    success: function (response) {
                        showToast('success', response.message);
                        table.ajax.reload(null, false);
                    },

                    error: function (xhr) {
                        Swal.fire({
                                icon: 'error',
                                title: 'Unable to Delete',
                                text: xhr.responseJSON?.message
                            || 'Something went wrong.'
                    });
                    }
                });
            });
        });

        loadData();
    });
</script>
@endpush
