@extends('layout.app')

@section('title', 'Applicants')
@section('page-title', 'Applicants')
@section('page-subtitle', 'Review, edit applicant information, and update status')


@section('content')
@php($prefix = request()->routeIs('hr.*') ? 'hr' : 'admin')
@php($canDeleteApplicants = request()->routeIs('admin.*'))
<div class="card premium-page-card">
    <div class="premium-page-header">
        <div class="premium-page-heading">
            <div class="premium-page-icon">
                <i class="bi bi-people-fill"></i>
            </div>

            <div>
                <h4 class="premium-page-title">Applicant Records</h4>
                <p class="premium-page-subtitle">
                    Review applicant information and manage recruitment status.
                </p>
            </div>
        </div>

        <button type="button" id="refreshBtn" class="premium-secondary-btn">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Refresh Records</span>
        </button>
    </div>

    <div class="premium-table-wrap">
        <div class="table-responsive">
            <table id="applicantsTable" class="table align-middle w-100">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Applied</th>
                        <th>Applicant Stage</th>
                        <th>Interviews</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade premium-form-modal applicant-edit-modal" id="editApplicantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editApplicantForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="edit_application_id">

                <div class="premium-modal-header">
                    <div class="premium-modal-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>

                    <div>
                        <h5>Edit Applicant Information</h5>
                        <p>Update the applicant profile and submitted employment information.</p>
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div id="editApplicantLoading" class="applicant-edit-loading">
                        <div class="spinner-border text-danger" role="status" aria-hidden="true"></div>
                        <div>
                            <strong>Loading applicant information...</strong>
                            <span>Please wait while the submitted forms are being prepared.</span>
                        </div>
                    </div>

                    <div id="editApplicantLoadError" class="alert alert-danger d-none mb-0"></div>

                    <div id="editApplicantContent" class="d-none">
                        <div class="applicant-edit-summary mb-4">
                            <div>
                                <span class="applicant-edit-summary-label">Reference No.</span>
                                <strong id="editReferenceNo">-</strong>
                            </div>
                            <div>
                                <span class="applicant-edit-summary-label">Current Stage</span>
                                <strong id="editCurrentStage">-</strong>
                            </div>
                            <div class="applicant-edit-summary-note">
                                <i class="bi bi-info-circle"></i>
                                Changes saved here will update the stored applicant information.
                            </div>
                        </div>

                        <div class="applicant-edit-section-card mb-4">
                            <div class="applicant-edit-section-heading">
                                <div>
                                    <span class="applicant-edit-section-kicker">Application</span>
                                    <h6>Job Vacancy</h6>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="edit_job_vacancy_id" class="form-label">
                                        Job Vacancy <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        name="job_vacancy_id"
                                        id="edit_job_vacancy_id"
                                        class="form-select"
                                        data-validation-key="job_vacancy_id"
                                        required
                                    ></select>
                                    <small class="text-danger edit-field-error" data-error-key="job_vacancy_id"></small>
                                </div>
                            </div>
                        </div>

                        <div id="editApplicantForms"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-danger" id="saveApplicantInfoBtn">
                        <i class="bi bi-check2-circle me-1"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade premium-form-modal" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="statusForm">
                @csrf

                <div class="premium-modal-header">
                    <div class="premium-modal-icon">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>

                    <div>
                        <h5>Update Application Status</h5>
                        <p>Move the applicant to the appropriate recruitment stage.</p>
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="application_id">

                    <div class="mb-3">
                        <label for="status" class="form-label">Application Status</label>

                        <select name="status" id="status" class="form-select">
                            @foreach([
                                'New Applicant',
                                'For Screening',
                                'For Initial Interview',
                                'For Questionnaire Review',
                                'For Final Interview',
                                'For Job Offer',
                                'Hired',
                                'Rejected',
                                'Withdrawn'
                            ] as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>

                        <small class="text-danger status_error"></small>
                    </div>

                    <div class="mb-0">
                        <label for="remarks" class="form-label">Remarks</label>

                        <textarea
                            name="remarks"
                            id="remarks"
                            class="form-control"
                            rows="5"
                            placeholder="Add optional notes about this status update..."
                        ></textarea>

                        <small class="text-danger remarks_error"></small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-danger" id="saveBtn">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



@push('styles')
<style>
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

    .premium-primary-btn,
    .premium-secondary-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        transition: .2s ease;
    }

    .premium-primary-btn {
        border: 1px solid #dc161e;
        color: #fff;
        background: linear-gradient(135deg, #ed1c24, #c61219);
        box-shadow: 0 9px 22px rgba(237, 28, 36, .22);
    }

    .premium-primary-btn:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(237, 28, 36, .28);
    }

    .premium-secondary-btn {
        border: 1px solid #e2e7ee;
        color: #45536d;
        background: #fff;
    }

    .premium-secondary-btn:hover {
        color: #ed1c24;
        border-color: #f0b7ba;
        background: #fff8f8;
    }

    .premium-primary-btn i,
    .premium-secondary-btn i,
    .premium-primary-btn i::before,
    .premium-secondary-btn i::before {
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
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 9px;
        line-height: 1;
    }

    .premium-icon-btn i,
    .premium-icon-btn i::before {
        display: block;
        line-height: 1;
    }

    .premium-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 7px 11px;
        border-radius: 999px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .premium-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 3px rgba(255,255,255,.16);
    }

    .status-active,
    .status-hired,
    .status-job-offer {
        background: #16a34a;
        box-shadow: 0 6px 14px rgba(22, 163, 74, .20);
    }

    .status-inactive,
    .status-withdrawn {
        background: #64748b;
        box-shadow: 0 6px 14px rgba(100, 116, 139, .20);
    }

    .status-new {
        background: #2563eb;
        box-shadow: 0 6px 14px rgba(37, 99, 235, .20);
    }

    .status-screening {
        background: #0891b2;
        box-shadow: 0 6px 14px rgba(8, 145, 178, .20);
    }

    .status-interview,
    .status-final-interview {
        background: #d97706;
        box-shadow: 0 6px 14px rgba(217, 119, 6, .20);
    }

    .status-questionnaire {
        background: #7c3aed;
        box-shadow: 0 6px 14px rgba(124, 58, 237, .20);
    }

    .status-rejected {
        background: #dc2626;
        box-shadow: 0 6px 14px rgba(220, 38, 38, .20);
    }

    .status-admin {
        background: #7c3aed;
    }

    .status-hr {
        background: #0891b2;
    }

    .status-applicant {
        background: #2563eb;
    }

    .premium-form-modal .modal-dialog {
        width: min(620px, calc(100vw - 28px));
        max-width: 620px;
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
        border-radius: 14px;
        color: #fff;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
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

    .premium-form-modal .invalid-feedback,
    .premium-form-modal small.text-danger {
        margin-top: 5px;
        font-size: 11px;
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

        .premium-primary-btn,
        .premium-secondary-btn {
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

        .premium-form-modal .modal-content {
            min-height: 100dvh;
            border-radius: 0;
        }

        .premium-form-modal form {
            min-height: 100dvh;
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

    @media (max-width: 479.98px) {
        .premium-page-header {
            padding: 17px;
        }

        .premium-page-title {
            font-size: 17px;
        }

        .premium-table-wrap {
            padding: 12px;
        }

        .premium-form-modal .modal-body {
            padding: 18px 15px;
        }

        .premium-form-modal .modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 12px 15px;
        }

        .premium-form-modal .modal-footer .btn {
            width: 100%;
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


    /* =========================================================
       APPLICANT INFORMATION EDITOR
    ========================================================= */
    /* The generic premium modal is intentionally compact (620px). The applicant
       editor contains the full application, so give it its own fluid workspace. */
    .premium-form-modal.applicant-edit-modal .modal-dialog {
        width: min(1420px, calc(100vw - 64px));
        max-width: 1420px;
        margin: 24px auto;
    }

    .applicant-edit-modal .modal-content {
        width: 100%;
        max-width: 100%;
    }

    .applicant-edit-modal .modal-content > form {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 100%;
        max-height: calc(100dvh - 48px);
        min-height: 0;
    }

    .applicant-edit-modal .modal-body {
        min-width: 0;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        padding: clamp(18px, 2vw, 30px);
    }

    .applicant-edit-modal #editApplicantContent,
    .applicant-edit-modal #editApplicantForms,
    .applicant-edit-modal .applicant-edit-form-card,
    .applicant-edit-modal .applicant-edit-section-card,
    .applicant-edit-modal .row,
    .applicant-edit-modal .row > [class*="col-"] {
        min-width: 0;
        max-width: 100%;
    }

    .applicant-edit-modal .form-control,
    .applicant-edit-modal .form-select {
        width: 100%;
        max-width: 100%;
    }

    .applicant-edit-loading {
        min-height: 240px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #334155;
        text-align: left;
    }

    .applicant-edit-loading strong,
    .applicant-edit-loading span {
        display: block;
    }

    .applicant-edit-loading strong {
        margin-bottom: 3px;
        font-size: 14px;
        font-weight: 800;
    }

    .applicant-edit-loading span {
        color: #94a3b8;
        font-size: 12px;
    }

    .applicant-edit-summary {
        display: grid;
        grid-template-columns: minmax(160px, .7fr) minmax(180px, .8fr) minmax(280px, 1.5fr);
        gap: 12px;
        padding: 16px 18px;
        border: 1px solid #e6eaf0;
        border-radius: 15px;
        background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
    }

    .applicant-edit-summary > div:not(.applicant-edit-summary-note) {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .applicant-edit-summary-label {
        color: #94a3b8;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .applicant-edit-summary strong {
        color: #17233e;
        font-size: 13px;
    }

    .applicant-edit-summary-note {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.45;
    }

    .applicant-edit-summary-note i {
        color: #ed1c24;
        font-size: 15px;
    }

    .applicant-edit-form-card,
    .applicant-edit-section-card {
        overflow: hidden;
        border: 1px solid #e5eaf0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .035);
    }

    .applicant-edit-form-card + .applicant-edit-form-card {
        margin-top: 18px;
    }

    .applicant-edit-form-title {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 15px 18px;
        border-bottom: 1px solid #edf0f4;
        background: #f8fafc;
    }

    .applicant-edit-form-title-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: #fff;
        background: linear-gradient(135deg, #ed1c24, #b80f16);
        box-shadow: 0 7px 15px rgba(237, 28, 36, .18);
    }

    .applicant-edit-form-title h6 {
        margin: 0;
        color: #17233e;
        font-size: 14px;
        font-weight: 800;
    }

    .applicant-edit-form-title span {
        display: block;
        margin-top: 2px;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .applicant-edit-section-card {
        padding: 18px;
    }

    .applicant-edit-form-card .applicant-edit-section-card {
        margin: 14px;
        box-shadow: none;
    }

    .applicant-edit-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #e5e7eb;
    }

    .applicant-edit-section-heading h6 {
        margin: 1px 0 0;
        color: #17233e;
        font-size: 13px;
        font-weight: 800;
    }

    .applicant-edit-section-kicker {
        display: block;
        color: #ed1c24;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .applicant-edit-option-group {
        display: flex;
        flex-wrap: wrap;
        gap: 9px 14px;
        min-height: 46px;
        align-items: center;
        padding: 9px 12px;
        border: 1px solid #dfe5ec;
        border-radius: 11px;
        background: #fff;
    }

    .applicant-edit-option-group.is-invalid {
        border-color: #dc3545;
    }

    .applicant-edit-option-group .form-check {
        margin: 0;
    }

    .applicant-edit-option-group .form-check-label {
        color: #475569;
        font-size: 12px;
    }

    .applicant-edit-file-current {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        padding: 8px 10px;
        border: 1px solid #e6eaf0;
        border-radius: 9px;
        color: #64748b;
        background: #f8fafc;
        font-size: 11px;
    }

    .applicant-edit-file-current i {
        color: #ed1c24;
    }

    .applicant-edit-file-current a {
        min-width: 0;
        overflow: hidden;
        color: #334155;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .applicant-edit-file-current a:hover {
        color: #ed1c24;
    }

    .applicant-edit-modal .is-invalid.form-control,
    .applicant-edit-modal .is-invalid.form-select {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, .06);
    }

    .applicant-edit-modal .edit-field-error {
        display: block;
        min-height: 0;
    }

    @media (max-width: 1199.98px) {
        .premium-form-modal.applicant-edit-modal .modal-dialog {
            width: calc(100vw - 32px);
            max-width: none;
            margin: 16px auto;
        }

        .applicant-edit-modal .modal-content > form {
            max-height: calc(100dvh - 32px);
        }

        .applicant-edit-summary {
            grid-template-columns: 1fr 1fr;
        }

        .applicant-edit-summary-note {
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .premium-form-modal.applicant-edit-modal .modal-dialog {
            width: 100%;
            max-width: 100%;
            min-height: 100dvh;
            margin: 0;
        }

        .applicant-edit-modal .modal-content,
        .applicant-edit-modal .modal-content > form {
            width: 100%;
            min-height: 100dvh;
            max-height: none;
            border-radius: 0;
        }

        .applicant-edit-modal .premium-modal-header {
            position: sticky;
            top: 0;
            z-index: 8;
            padding: 16px 52px 16px 16px;
        }

        .applicant-edit-modal .premium-modal-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            font-size: 18px;
        }

        .applicant-edit-modal .premium-modal-header h5 {
            font-size: 15px;
        }

        .applicant-edit-modal .premium-modal-header p {
            font-size: 10px;
            line-height: 1.35;
        }

        .applicant-edit-modal .modal-body {
            flex: 1 1 auto;
            padding: 16px 12px;
        }

        .applicant-edit-summary {
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 13px;
        }

        .applicant-edit-summary-note {
            grid-column: auto;
        }

        .applicant-edit-form-card + .applicant-edit-form-card {
            margin-top: 12px;
        }

        .applicant-edit-form-title {
            padding: 13px 14px;
        }

        .applicant-edit-form-card .applicant-edit-section-card {
            margin: 8px;
            padding: 13px;
        }

        .applicant-edit-section-card {
            padding: 13px;
            border-radius: 13px;
        }

        .applicant-edit-section-heading {
            margin-bottom: 13px;
        }

        .applicant-edit-modal .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 8;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px;
        }

        .applicant-edit-modal .modal-footer .btn {
            width: 100%;
            min-height: 44px;
            margin: 0;
        }
    }

    @media (max-width: 479.98px) {
        .applicant-edit-modal .premium-modal-header p {
            max-width: 235px;
        }

        .applicant-edit-modal .applicant-edit-form-title h6 {
            font-size: 13px;
        }

        .applicant-edit-file-current {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .applicant-edit-file-current a {
            width: 100%;
            padding-left: 22px;
            white-space: normal;
            overflow-wrap: anywhere;
        }
    }

</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        const statusModalElement = document.getElementById('statusModal');
        const editApplicantModalElement = document.getElementById('editApplicantModal');

        const statusModal = new bootstrap.Modal(statusModalElement);
        const editApplicantModal = new bootstrap.Modal(editApplicantModalElement);

        function clearErrors() {
            $('.status_error').text('');
            $('.remarks_error').text('');
        }

        function clearEditErrors() {
            $('.edit-field-error').text('');
            $('#editApplicantForm [data-validation-key]').removeClass('is-invalid');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[character];
            });
        }

        function getStatusBadge(status) {
            const classes = {
                'New Applicant': 'status-new',
                'For Screening': 'status-screening',
                'For Initial Interview': 'status-interview',
                'For Questionnaire Review': 'status-questionnaire',
                'For Final Interview': 'status-final-interview',
                'For Job Offer': 'status-job-offer',
                'Hired': 'status-hired',
                'Rejected': 'status-rejected',
                'Withdrawn': 'status-withdrawn'
            };

            const badgeClass = classes[status] || 'status-inactive';

            return `
                <span class="premium-status ${badgeClass}">
                    ${escapeHtml(status)}
                </span>
            `;
        }

        function renderEditField(field) {
            const id = Number(field.id);
            const validationKey = `answers.${id}`;
            const fieldName = `answers[${id}]`;
            const width = Math.max(1, Math.min(12, Number(field.width) || 12));
            const requiredMark = field.is_required ? ' <span class="text-danger">*</span>' : '';
            const requiredAttr = field.is_required ? ' required' : '';
            const placeholder = escapeHtml(field.placeholder || '');
            const value = field.value ?? '';
            const options = Array.isArray(field.options) ? field.options : [];

            let control = '';

            if (field.field_type === 'textarea') {
                control = `
                    <textarea
                        name="${fieldName}"
                        class="form-control"
                        rows="4"
                        placeholder="${placeholder}"
                        data-validation-key="${validationKey}"
                        ${requiredAttr}
                    >${escapeHtml(value)}</textarea>
                `;
            } else if (field.field_type === 'select') {
                const optionHtml = options.map(function (option) {
                    const selected = String(option) === String(value) ? ' selected' : '';
                    return `<option value="${escapeHtml(option)}"${selected}>${escapeHtml(option)}</option>`;
                }).join('');

                control = `
                    <select
                        name="${fieldName}"
                        class="form-select"
                        data-validation-key="${validationKey}"
                        ${requiredAttr}
                    >
                        <option value="">Select an option</option>
                        ${optionHtml}
                    </select>
                `;
            } else if (field.field_type === 'radio') {
                const optionHtml = options.map(function (option, index) {
                    const optionId = `edit_field_${id}_radio_${index}`;
                    const checked = String(option) === String(value) ? ' checked' : '';
                    const radioRequired = field.is_required && index === 0 ? ' required' : '';

                    return `
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="${fieldName}"
                                id="${optionId}"
                                value="${escapeHtml(option)}"
                                ${checked}
                                ${radioRequired}
                            >
                            <label class="form-check-label" for="${optionId}">${escapeHtml(option)}</label>
                        </div>
                    `;
                }).join('');

                control = `
                    <div class="applicant-edit-option-group" data-validation-key="${validationKey}">
                        ${optionHtml || '<span class="text-muted small">No options configured.</span>'}
                    </div>
                `;
            } else if (field.field_type === 'checkbox') {
                const selectedValues = Array.isArray(value) ? value.map(String) : [];
                const optionHtml = options.map(function (option, index) {
                    const optionId = `edit_field_${id}_check_${index}`;
                    const checked = selectedValues.includes(String(option)) ? ' checked' : '';

                    return `
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="${fieldName}[]"
                                id="${optionId}"
                                value="${escapeHtml(option)}"
                                ${checked}
                            >
                            <label class="form-check-label" for="${optionId}">${escapeHtml(option)}</label>
                        </div>
                    `;
                }).join('');

                control = `
                    <div class="applicant-edit-option-group" data-validation-key="${validationKey}">
                        ${optionHtml || '<span class="text-muted small">No options configured.</span>'}
                    </div>
                `;
            } else if (field.field_type === 'file') {
                const fileRequired = field.is_required && !field.current_file_url ? ' required' : '';
                const currentFile = field.current_file_url
                    ? `
                        <div class="applicant-edit-file-current">
                            <i class="bi bi-paperclip"></i>
                            <span>Current file:</span>
                            <a href="${escapeHtml(field.current_file_url)}" target="_blank" rel="noopener noreferrer">
                                ${escapeHtml(field.current_file_name || 'View uploaded file')}
                            </a>
                        </div>
                    `
                    : '<div class="applicant-edit-file-current"><i class="bi bi-info-circle"></i><span>No file currently uploaded.</span></div>';

                control = `
                    <input
                        type="file"
                        name="${fieldName}"
                        class="form-control"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        data-validation-key="${validationKey}"
                        ${fileRequired}
                    >
                    ${currentFile}
                    <div class="form-text">Upload a new file only if you want to replace the existing one. Maximum 5 MB.</div>
                `;
            } else {
                const inputType = ['email', 'number', 'date'].includes(field.field_type)
                    ? field.field_type
                    : 'text';

                control = `
                    <input
                        type="${inputType}"
                        name="${fieldName}"
                        class="form-control"
                        value="${escapeHtml(value)}"
                        placeholder="${placeholder}"
                        data-validation-key="${validationKey}"
                        ${requiredAttr}
                    >
                `;
            }

            const responsiveWidthClass = width === 12
                ? 'col-12'
                : `col-12 col-md-6 col-xl-${width}`;

            return `
                <div class="${responsiveWidthClass}">
                    <label class="form-label">
                        ${escapeHtml(field.label)}${requiredMark}
                    </label>
                    ${control}
                    <small class="text-danger edit-field-error" data-error-key="${validationKey}"></small>
                </div>
            `;
        }

        function renderApplicantEditor(payload) {
            const application = payload.application || {};
            const vacancies = Array.isArray(payload.vacancies) ? payload.vacancies : [];
            const forms = Array.isArray(payload.forms) ? payload.forms : [];

            $('#edit_application_id').val(application.id || '');
            $('#editReferenceNo').text(application.reference_no || '-');
            $('#editCurrentStage').text(application.status || '-');

            const vacancyOptions = vacancies.map(function (vacancy) {
                const selected = Number(vacancy.id) === Number(application.job_vacancy_id)
                    ? ' selected'
                    : '';

                return `<option value="${Number(vacancy.id)}"${selected}>${escapeHtml(vacancy.label)}</option>`;
            }).join('');

            $('#edit_job_vacancy_id').html(
                `<option value="">Select a job vacancy</option>${vacancyOptions}`
            );

            const formsHtml = forms.map(function (form) {
                const sectionsHtml = (form.sections || []).map(function (section) {
                    const fieldsHtml = (section.fields || [])
                        .map(renderEditField)
                        .join('');

                    return `
                        <div class="applicant-edit-section-card">
                            <div class="applicant-edit-section-heading">
                                <div>
                                    <span class="applicant-edit-section-kicker">Section</span>
                                    <h6>${escapeHtml(section.name || 'Information')}</h6>
                                </div>
                            </div>
                            <div class="row g-3">
                                ${fieldsHtml}
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="applicant-edit-form-card">
                        <div class="applicant-edit-form-title">
                            <div class="applicant-edit-form-title-icon">
                                <i class="bi bi-ui-checks-grid"></i>
                            </div>
                            <div>
                                <h6>${escapeHtml(form.template_name || 'Submitted Form')}</h6>
                                <span>${escapeHtml(form.template_type || 'Employment Information')}</span>
                            </div>
                        </div>
                        ${sectionsHtml}
                    </div>
                `;
            }).join('');

            $('#editApplicantForms').html(
                formsHtml || '<div class="alert alert-warning mb-0">No submitted employment information was found for this applicant.</div>'
            );
        }

        function showEditValidationErrors(errors) {
            clearEditErrors();

            $.each(errors || {}, function (key, messages) {
                const message = Array.isArray(messages) ? messages[0] : messages;
                let lookupKey = key;

                if (/^answers\.\d+\.\d+$/.test(key)) {
                    lookupKey = key.split('.').slice(0, 2).join('.');
                }

                $('.edit-field-error').filter(function () {
                    return $(this).attr('data-error-key') === lookupKey;
                }).first().text(message);

                $('#editApplicantForm [data-validation-key]').filter(function () {
                    return $(this).attr('data-validation-key') === lookupKey;
                }).addClass('is-invalid');
            });

            const $firstInvalid = $('#editApplicantForm .is-invalid').first();
            if ($firstInvalid.length) {
                const modalBody = editApplicantModalElement.querySelector('.modal-body');
                const targetTop = $firstInvalid.position()?.top;

                if (modalBody && typeof targetTop === 'number') {
                    modalBody.scrollTo({ top: Math.max(0, targetTop - 70), behavior: 'smooth' });
                }
            }
        }

        const applicantsTable = $('#applicantsTable').DataTable({
            processing: true,
            responsive: true,
            autoWidth: false,

            ajax: {
                url: "{{ route($prefix . '.applicants.data') }}",
                type: "GET",
                dataType: "json",

                error: function (xhr) {
                    console.log(xhr.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Applicants',
                        text: xhr.responseJSON?.message
                            || 'Please check the browser console or Laravel log.'
                    });
                }
            },

            columns: [
                {
                    data: 'reference_no',
                    name: 'reference_no'
                },
                {
                    data: 'applicant_name',
                    name: 'applicant_name'
                },
                {
                    data: 'position',
                    name: 'position'
                },
                {
                    data: 'department',
                    name: 'department'
                },
                {
                    data: 'applied_at',
                    name: 'applied_at'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data) {
                        return getStatusBadge(data);
                    }
                },
                {
                    data: 'interviews_count',
                    name: 'interviews_count',
                    render: function (data, type, row) {
                        if (!row.latest_interview) {
                            return '<span class="text-muted">No interview yet</span>';
                        }

                        return `<div class="small"><strong>${data} record${data === 1 ? '' : 's'}</strong><br><span class="text-muted">Latest: ${escapeHtml(row.latest_interview.type)} - ${escapeHtml(row.latest_interview.status)}</span></div>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const printUrl = @json(route($prefix . '.applicants.print', ':id'))
                            .replace(':id', row.id);

                        const interviewUrl = @json(route($prefix . '.interviews.index'))
                            .concat('?application=', row.id);

                        const canDeleteApplicants = @json($canDeleteApplicants);

                        const deleteButton = canDeleteApplicants
                            ? `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger premium-icon-btn deleteApplicantBtn"
                                    data-id="${row.id}"
                                    data-name="${escapeHtml(row.applicant_name || 'Applicant')}"
                                    title="Delete Applicant and Related Records"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            `
                            : '';

                        const resumeButton = row.resume_url
                            ? `
                                <a
                                    href="${escapeHtml(row.resume_url)}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-secondary premium-icon-btn"
                                    title="View Uploaded Resume"
                                >
                                    <i class="bi bi-file-earmark-person"></i>
                                </a>
                            `
                            : `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary premium-icon-btn"
                                    title="No resume uploaded"
                                    disabled
                                >
                                    <i class="bi bi-file-earmark-person"></i>
                                </button>
                            `;

                        return `
                            <div class="d-inline-flex align-items-center gap-1">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark premium-icon-btn editApplicantBtn"
                                    data-id="${row.id}"
                                    title="Edit Applicant Information"
                                >
                                    <i class="bi bi-person-lines-fill"></i>
                                </button>

                                <a
                                    href="${printUrl}"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-danger premium-icon-btn"
                                    title="View and Print Submitted Forms (${row.forms_count || 0})"
                                >
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                                ${resumeButton}

                                <a
                                    href="${interviewUrl}"
                                    class="btn btn-sm btn-outline-primary premium-icon-btn"
                                    title="Schedule or View Interviews (${row.interviews_count || 0})"
                                >
                                    <i class="bi bi-calendar2-check"></i>
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger premium-icon-btn statusBtn"
                                    data-id="${row.id}"
                                    data-status="${escapeHtml(row.status)}"
                                    title="Update Application Status"
                                >
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>

                                ${deleteButton}
                            </div>
                        `;
                    }
                }
            ],

            order: [[4, 'desc']],

            language: {
                emptyTable: 'No applicants found.',
                processing: 'Loading applicants...'
            }
        });

        $('#refreshBtn').on('click', function () {
            const $button = $(this);
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Refreshing...</span>');

            applicantsTable.ajax.reload(function () {
                $button.prop('disabled', false).html(originalHtml);
                showToast('success', 'Records refreshed successfully.');
            }, false);
        });

        $('#applicantsTable').on('click', '.editApplicantBtn', function () {
            const applicationId = $(this).data('id');
            const editUrl = @json(route($prefix . '.applicants.edit', ':id'))
                .replace(':id', applicationId);

            clearEditErrors();
            $('#editApplicantForm')[0].reset();
            $('#edit_application_id').val(applicationId);
            $('#editApplicantContent').addClass('d-none');
            $('#editApplicantLoadError').addClass('d-none').text('');
            $('#editApplicantLoading').removeClass('d-none');
            $('#editApplicantForms').empty();

            editApplicantModal.show();

            $.ajax({
                url: editUrl,
                type: 'GET',
                dataType: 'json',

                success: function (response) {
                    renderApplicantEditor(response);
                    $('#editApplicantLoading').addClass('d-none');
                    $('#editApplicantContent').removeClass('d-none');
                },

                error: function (xhr) {
                    console.log(xhr.responseText);
                    $('#editApplicantLoading').addClass('d-none');
                    $('#editApplicantLoadError')
                        .removeClass('d-none')
                        .text(xhr.responseJSON?.message || 'Unable to load the applicant information.');
                }
            });
        });

        $('#editApplicantForm').on('input change', '[data-validation-key]', function () {
            const key = $(this).attr('data-validation-key');
            $(this).removeClass('is-invalid');

            $('.edit-field-error').filter(function () {
                return $(this).attr('data-error-key') === key;
            }).text('');
        });

        $('#editApplicantForm').submit(function (e) {
            e.preventDefault();

            clearEditErrors();

            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            const applicationId = $('#edit_application_id').val();
            const updateUrl = @json(route($prefix . '.applicants.update', ':id'))
                .replace(':id', applicationId);
            const formData = new FormData(this);
            formData.append('_method', 'PUT');

            const $saveButton = $('#saveApplicantInfoBtn');
            const originalButtonHtml = $saveButton.html();

            $saveButton
                .prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving...');

            Swal.fire({
                title: 'Saving Changes...',
                text: 'Please wait while the applicant information is being updated.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: updateUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,

                success: function (response) {
                    Swal.close();
                    editApplicantModal.hide();
                    showToast('success', response.message || 'Applicant information updated successfully.');
                    applicantsTable.ajax.reload(null, false);
                },

                error: function (xhr) {
                    Swal.close();

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        showEditValidationErrors(errors);

                        Swal.fire({
                            icon: 'error',
                            title: 'Please Check the Information',
                            text: 'Some fields need to be corrected before the changes can be saved.'
                        });
                    } else {
                        console.log(xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: xhr.responseJSON?.message || 'Unable to update the applicant information.'
                        });
                    }
                },

                complete: function () {
                    $saveButton
                        .prop('disabled', false)
                        .html(originalButtonHtml);
                }
            });
        });

        $('#applicantsTable').on('click', '.statusBtn', function () {
            clearErrors();

            $('#statusForm')[0].reset();

            $('#application_id').val($(this).data('id'));
            $('#status').val($(this).data('status'));

            statusModal.show();
        });

        $('#applicantsTable').on('click', '.deleteApplicantBtn', function () {
            const applicationId = $(this).data('id');
            const applicantName = $(this).data('name') || 'this applicant';
            const deleteUrl = @json(route('admin.applicants.destroy', ':id'))
                .replace(':id', applicationId);

            Swal.fire({
                icon: 'warning',
                title: 'Delete Applicant?',
                html: `This will permanently delete <strong>${escapeHtml(applicantName)}</strong>, including interviews, submitted forms, answers, status history, uploaded files, and the applicant profile when it is no longer used.`,
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete permanently',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true
            }).then(function (result) {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while the applicant records are being removed.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    dataType: 'json',
                    data: {
                        _token: @json(csrf_token())
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: response.message || 'Applicant deleted successfully.'
                        });

                        applicantsTable.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        console.log(xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: xhr.responseJSON?.message || 'Unable to delete the applicant.'
                        });
                    }
                });
            });
        });

        $('#statusForm').submit(function (e) {
            e.preventDefault();

            clearErrors();

            const applicationId = $('#application_id').val();

            const url = "{{ route($prefix . '.applicants.status', ':id') }}"
                .replace(':id', applicationId);

            $('#saveBtn')
                .prop('disabled', true)
                .text('Updating...');

            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while updating the application status.',
                allowOutsideClick: false,
                allowEscapeKey: false,

                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: 'PUT',
                url: url,
                dataType: 'json',
                data: $(this).serialize(),

                success: function (response) {
                    Swal.close();
                    statusModal.hide();
                    showToast('success', response.message);
                    applicantsTable.ajax.reload(null, false);
                },

                error: function (xhr) {
                    Swal.close();

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors || {};

                        $.each(errors, function (key, value) {
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
                            text: xhr.responseJSON?.message || 'Something went wrong.'
                        });
                    }
                },

                complete: function () {
                    $('#saveBtn')
                        .prop('disabled', false)
                        .text('Update Status');
                }
            });
        });
    });
</script>
@endpush