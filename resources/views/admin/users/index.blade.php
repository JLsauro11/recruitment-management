@extends('layout.app')

@section('title', 'User Accounts')
@section('page-title', 'User Accounts')
@section('page-subtitle', 'Manage user accounts')


@section('content')
<div class="card premium-page-card">
    <div class="premium-page-header">
        <div class="premium-page-heading">
            <div class="premium-page-icon">
                <i class="bi bi-person-gear"></i>
            </div>

            <div>
                <h4 class="premium-page-title">User Account Management</h4>
                <p class="premium-page-subtitle">Control system access, roles, and account availability.</p>
            </div>
        </div>

        <button type="button" class="premium-primary-btn" id="addBtn">
            <i class="bi bi-plus-lg"></i>
            <span>Add User</span>
        </button>
    </div>

    <div class="premium-table-wrap">
        <div class="table-responsive">
            <table id="dataTable" class="table align-middle w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dataForm">
                @csrf

                <div class="premium-modal-header">
                    <div class="premium-modal-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>

                    <div>
                        <h5 id="modalTitle">Add User</h5>
                        <p>Create or update a system user account.</p>
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

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control"
                        >

                        <div class="invalid-feedback name_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                        >

                        <div class="invalid-feedback email_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password
                            <small class="text-muted">
                                (leave blank when editing)
                            </small>
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                        >

                        <div class="invalid-feedback password_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>

                        <select
                            name="role"
                            id="role"
                            class="form-select"
                        >
                            <option value="admin">Admin</option>
                            <option value="hr">HR</option>
                            <option value="applicant">Applicant</option>
                        </select>

                        <div class="invalid-feedback role_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>

                        <select
                            name="status"
                            id="status"
                            class="form-select"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <div class="invalid-feedback status_error"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>

                    <button
                        type="submit"
                        id="saveBtn"
                        class="btn btn-danger"
                    >
                        Save
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

    .status-examination {
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

</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const formModal = new bootstrap.Modal(
        document.getElementById('formModal')
    );

    let table;

    function clearErrors() {
        $('.invalid-feedback').text('');
        $('.form-control, .form-select').removeClass('is-invalid');
    }

    function loadData() {
        if ($.fn.DataTable.isDataTable('#dataTable')) {
            table.destroy();
        }

        table = $('#dataTable').DataTable({
            processing: true,
            responsive: true,
            autoWidth: false,

            ajax: {
                url: "{{ route('admin.users.data') }}",
                type: "GET",
                dataType: "json",

                error: function (xhr) {
                    console.log(xhr.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Users',
                        text: xhr.responseJSON?.message
                            || 'Something went wrong while loading users.'
                    });
                }
            },

            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'email' },
                {
                    data: 'role',
                    render: function (data) {
                        if (!data) {
                            return 'N/A';
                        }

                        const roleClass = {
                            'admin': 'status-admin',
                            'hr': 'status-hr',
                            'applicant': 'status-applicant'
                        }[data] || 'status-inactive';

                        const label = data.charAt(0).toUpperCase() + data.slice(1);

                        return `
                            <span class="premium-status ${roleClass}">
                                ${label}
                            </span>
                        `;
                    }
                },
                {
                    data: 'status',
                    render: function (data) {
                        const badgeClass = data === 'active'
                            ? 'status-active'
                            : 'status-inactive';

                        return `
                            <span class="premium-status ${badgeClass}">
                                ${data}
                            </span>
                        `;
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
                                    class="btn btn-sm btn-primary premium-icon-btn editBtn"
                                    data-id="${row.id}"
                                >
                                    <i class="bi bi-pencil-square"></i>

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger premium-icon-btn deleteBtn"
                                    data-id="${row.id}"
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
                emptyTable: 'No users found.',
                processing: 'Loading users...'
            }
        });
    }

    $('#addBtn').click(function () {
        clearErrors();

        $('#dataForm')[0].reset();
        $('#record_id').val('');
        $('#modalTitle').text('Add User');
        $('#saveBtn').text('Save');

        formModal.show();
    });

    $('#dataTable').on('click', '.editBtn', function () {
        clearErrors();

        const id = $(this).data('id');

        const url = "{{ route('admin.users.show', ':id') }}"
            .replace(':id', id);

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',

            success: function (response) {
                $('#record_id').val(response.id);
                $('#name').val(response.name);
                $('#email').val(response.email);
                $('#role').val(response.role);
                $('#status').val(response.status);

                $('#password').val('');
                $('#password_confirmation').val('');

                $('#modalTitle').text('Edit User');
                $('#saveBtn').text('Update');

                formModal.show();
            },

            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message
                        || 'Unable to retrieve user information.'
                });
            }
        });
    });

    $('#dataForm').submit(function (e) {
        e.preventDefault();

        clearErrors();

        const id = $('#record_id').val();

        const url = id
            ? "{{ route('admin.users.update', ':id') }}".replace(':id', id)
            : "{{ route('admin.users.store') }}";

        const method = id ? 'PUT' : 'POST';

        $('#saveBtn')
            .prop('disabled', true)
            .text('Saving...');

        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while saving the user account.',
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
                    .text(id ? 'Update' : 'Save');
            }
        });
    });

    $('#dataTable').on('click', '.deleteBtn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this user account?',
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

            const url = "{{ route('admin.users.destroy', ':id') }}"
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
