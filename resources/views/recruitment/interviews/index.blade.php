@extends('layout.app')

@section('title', 'Interviews')
@section('page-title', 'Interviews')
@section('page-description', 'Interview schedule and history linked to applicant records')

@section('content')
@php($prefix = request()->routeIs('hr.*') ? 'hr' : 'admin')

<div class="card premium-page-card">
    <div class="premium-page-header">
        <div class="premium-page-heading">
            <div class="premium-page-icon">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>

            <div>
                <h4 class="premium-page-title">Interview Schedule & History</h4>
                <p class="premium-page-subtitle">Each row is an interview event. Applicant master data and hiring stage remain in the Applicants page.</p>
            </div>
        </div>

        <div class="premium-header-actions">
            <button type="button" class="premium-secondary-btn" id="refreshBtn">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Refresh Records</span>
            </button>
            <button type="button" class="premium-primary-btn" id="addBtn">
                <i class="bi bi-plus-lg"></i>
                <span>Schedule Interview</span>
            </button>
        </div>
    </div>

    <div class="premium-table-wrap">
        <div class="table-responsive">
            <table id="dataTable" class="table align-middle w-100">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Applicant Stage</th>
                        <th>Interview Type</th>
                        <th>Schedule</th>
                        <th>Interviewer</th>
                        <th>Location/Link</th>
                        <th>Interview Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade premium-form-modal" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="dataForm">
                @csrf

                <div class="premium-modal-header">
                    <div class="premium-modal-icon">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>

                    <div>
                        <h5 id="modalTitle">Schedule Interview</h5>
                        <p>Create or update an applicant interview schedule.</p>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="record_id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="application_id" class="form-label">Applicant/Application</label>
                            <select name="application_id" id="application_id" class="form-select">
                                <option value="">Select application</option>
                                @foreach($applications as $application)
                                    <option value="{{ $application->id }}">
                                        {{ $application->reference_no }} - {{ $application->applicant?->full_name ?? 'N/A' }}
                                        ({{ $application->vacancy?->title ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback application_id_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="type" class="form-label">Interview Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="Initial Interview">Initial Interview</option>
                                <option value="HR Interview">HR Interview</option>
                                <option value="Technical Interview">Technical Interview</option>
                                <option value="Final Interview">Final Interview</option>
                            </select>
                            <div class="invalid-feedback type_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="interviewer_id" class="form-label">Interviewer</label>
                            <select name="interviewer_id" id="interviewer_id" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($interviewers as $interviewer)
                                    <option value="{{ $interviewer->id }}">{{ $interviewer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback interviewer_id_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="scheduled_at" class="form-label">Date & Time</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control">
                            <div class="invalid-feedback scheduled_at_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Rescheduled">Rescheduled</option>
                                <option value="No Show">No Show</option>
                            </select>
                            <div class="invalid-feedback status_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" name="location" id="location" class="form-control" placeholder="Office or interview room">
                            <div class="invalid-feedback location_error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="meeting_link" class="form-label">Meeting Link</label>
                            <input type="url" name="meeting_link" id="meeting_link" class="form-control" placeholder="https://...">
                            <div class="invalid-feedback meeting_link_error"></div>
                        </div>

                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Optional interview notes..."></textarea>
                            <div class="invalid-feedback remarks_error"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveBtn" class="btn btn-danger">Save Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .premium-page-card{overflow:hidden;border:1px solid #edf0f4!important;border-radius:22px!important;background:#fff;box-shadow:0 14px 38px rgba(15,23,42,.07)!important}
    .premium-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px 26px;border-bottom:1px solid #eef1f5;background:radial-gradient(circle at top right,rgba(237,28,36,.08),transparent 34%),linear-gradient(180deg,#fff 0%,#fcfcfd 100%)}
    .premium-page-heading{display:flex;align-items:center;gap:15px;min-width:0}.premium-page-icon{width:50px;height:50px;min-width:50px;display:flex;align-items:center;justify-content:center;border-radius:15px;color:#fff;background:linear-gradient(135deg,#ed1c24,#a30d13);box-shadow:0 10px 24px rgba(237,28,36,.22);font-size:21px}.premium-page-title{margin:0 0 4px;color:#17233e;font-size:20px;font-weight:800}.premium-page-subtitle{margin:0;color:#8b96a9;font-size:13px}
    .premium-primary-btn{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;border:1px solid #dc161e;border-radius:12px;color:#fff;background:linear-gradient(135deg,#ed1c24,#c61219);box-shadow:0 9px 22px rgba(237,28,36,.22);font-size:13px;font-weight:700}.premium-primary-btn:hover{color:#fff}.premium-table-wrap{padding:22px 24px 24px}.premium-table-wrap table.dataTable thead th{font-size:13px!important}.premium-table-wrap table.dataTable tbody td{font-size:14px!important;vertical-align:middle}
    .premium-action-group{display:flex;align-items:center;gap:7px;white-space:nowrap}.premium-icon-btn{width:34px!important;height:34px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:0!important;border-radius:9px!important}.status-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border-radius:999px;color:#fff;font-size:12px;font-weight:700;white-space:nowrap}.status-pill::before{content:"";width:6px;height:6px;border-radius:50%;background:#fff}.s-Scheduled,.s-Rescheduled{background:#2563eb}.s-Completed{background:#16a34a}.s-Cancelled,.s-No-Show{background:#dc2626}
    .premium-form-modal .modal-dialog{width:min(760px,calc(100vw - 32px));max-width:760px;margin:20px auto}.premium-form-modal .modal-content{overflow:hidden;border:0;border-radius:20px;box-shadow:0 26px 70px rgba(15,23,42,.22)}.premium-form-modal form{display:flex;flex-direction:column;max-height:calc(100dvh - 40px)}.premium-modal-header{position:relative;display:flex;align-items:center;gap:14px;padding:20px 58px 20px 22px;color:#fff;background:linear-gradient(135deg,#151d34 0%,#26304d 65%,#8f1117 145%)}.premium-modal-icon{width:48px;height:48px;min-width:48px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(255,255,255,.12);font-size:20px}.premium-modal-header h5{margin:0 0 3px;color:#fff;font-size:17px;font-weight:800}.premium-modal-header p{margin:0;color:rgba(255,255,255,.68);font-size:11px}.premium-modal-header .btn-close{position:absolute;top:18px;right:18px}.premium-form-modal .modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:22px;background:#fbfcfe}.premium-form-modal .modal-footer{flex:0 0 auto;padding:14px 22px;border-top:1px solid #edf0f4;background:#fff}.premium-form-modal .form-label{font-size:12px;font-weight:700;color:#35425a}.premium-form-modal .form-control,.premium-form-modal .form-select{min-height:44px;border:1px solid #dfe5ec;border-radius:11px}
    @media(max-width:767.98px){.premium-page-header{align-items:flex-start;flex-direction:column;padding:20px}.premium-primary-btn{width:100%}.premium-table-wrap{padding:16px}.premium-form-modal{padding:0!important}.premium-form-modal .modal-dialog{width:100%;height:100dvh;margin:0}.premium-form-modal .modal-content,.premium-form-modal form{height:100dvh;max-height:100dvh;border-radius:0}.premium-form-modal .modal-footer{display:grid;grid-template-columns:1fr 1.3fr;gap:10px}.premium-form-modal .modal-footer .btn{width:100%;margin:0}}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const prefix = @json($prefix);
    const selectedApplicationId = @json($selectedApplicationId);
    const selectedInterviewId = @json($selectedInterviewId);
    const formModalElement = document.getElementById('formModal');
    const formModal = new bootstrap.Modal(formModalElement);
    let table;

    function clearErrors() {
        $('.invalid-feedback').text('');
        $('.form-control, .form-select').removeClass('is-invalid');
    }

    function resetForm() {
        clearErrors();
        $('#dataForm')[0].reset();
        $('#record_id').val('');
        $('#status').val('Scheduled');
        $('#modalTitle').text('Schedule Interview');
        $('#saveBtn').text('Save Interview');
    }

    function clearAutoOpenQuery() {
        const url = new URL(window.location.href);
        let changed = false;

        ['application', 'interview'].forEach(function (key) {
            if (url.searchParams.has(key)) {
                url.searchParams.delete(key);
                changed = true;
            }
        });

        if (changed) {
            window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
        }
    }

    function openInterviewForEdit(id) {
        clearErrors();
        const url = "{{ route($prefix . '.interviews.show', ':id') }}".replace(':id', id);

        $.ajax({
            type:'GET', url:url, dataType:'json',
            success:function(response){
                resetForm();
                $('#record_id').val(response.id);
                $('#application_id').val(String(response.application_id));
                $('#interviewer_id').val(response.interviewer_id ? String(response.interviewer_id) : '');
                $('#type').val(response.type);
                $('#scheduled_at').val(response.scheduled_at);
                $('#location').val(response.location);
                $('#meeting_link').val(response.meeting_link);
                $('#status').val(response.status);
                $('#remarks').val(response.remarks);
                $('#modalTitle').text('Edit Interview');
                $('#saveBtn').text('Update Interview');
                formModal.show();
            },
            error:function(xhr){
                Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message || 'Unable to retrieve interview information.'});
            }
        });
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
                url: "{{ route($prefix . '.interviews.data') }}",
                type: 'GET',
                dataType: 'json',
                error: function (xhr) {
                    console.log(xhr.responseText);
                    Swal.fire({icon:'error',title:'Unable to Load Interviews',text:xhr.responseJSON?.message || 'Something went wrong while loading interviews.'});
                }
            },
            columns: [
                {data:'reference_no'},
                {data:'applicant'},
                {data:'position'},
                {data:'application_status'},
                {data:'type'},
                {data:'scheduled_at'},
                {data:'interviewer'},
                {data:'location'},
                {data:'status',render:function(data){return `<span class="status-pill s-${String(data).replaceAll(' ','-')}">${data}</span>`;}},
                {data:null,orderable:false,searchable:false,render:function(data,type,row){return `<div class="premium-action-group"><button type="button" class="btn btn-sm btn-primary premium-icon-btn editBtn" data-id="${row.id}" title="Edit"><i class="bi bi-pencil-square"></i></button><button type="button" class="btn btn-sm btn-danger premium-icon-btn deleteBtn" data-id="${row.id}" title="Delete"><i class="bi bi-trash"></i></button></div>`;}}
            ],
            order:[[5,'desc']],
            language:{emptyTable:'No interviews found.',processing:'Loading interviews...'}
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

    $('#addBtn').on('click', function () {
        resetForm();
        formModal.show();
    });

    $('#dataTable').on('click', '.editBtn', function () {
        openInterviewForEdit($(this).data('id'));
    });

    $('#dataForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        const id = $('#record_id').val();
        const url = id ? "{{ route($prefix . '.interviews.update', ':id') }}".replace(':id', id) : "{{ route($prefix . '.interviews.store') }}";
        const method = id ? 'PUT' : 'POST';
        $('#saveBtn').prop('disabled', true).text('Saving...');
        Swal.fire({title:'Saving...',text:'Please wait while saving the interview.',allowOutsideClick:false,allowEscapeKey:false,didOpen:()=>Swal.showLoading()});

        $.ajax({
            type:method,url:url,dataType:'json',data:$(this).serialize(),
            success:function(response){Swal.close();formModal.hide();showToast('success',response.message);table.ajax.reload(null,false);},
            error:function(xhr){
                Swal.close();
                if(xhr.status===422){
                    const errors=xhr.responseJSON.errors || {};
                    $.each(errors,function(key,value){$(`[name="${key}"]`).addClass('is-invalid');$('.'+key+'_error').text(value[0]);});
                    Swal.fire({icon:'error',title:'Validation Error',text:'Please check the required fields.'});
                }else{Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message || 'Something went wrong. Please try again.'});}
            },
            complete:function(){$('#saveBtn').prop('disabled',false).text(id ? 'Update Interview' : 'Save Interview');}
        });
    });

    $('#dataTable').on('click', '.deleteBtn', function () {
        const id = $(this).data('id');
        Swal.fire({title:'Delete this interview?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Yes, delete it',cancelButtonText:'Cancel'}).then(function(result){
            if(!result.isConfirmed) return;
            const url = "{{ route($prefix . '.interviews.destroy', ':id') }}".replace(':id', id);
            $.ajax({type:'DELETE',url:url,dataType:'json',success:function(response){showToast('success',response.message);table.ajax.reload(null,false);},error:function(xhr){Swal.fire({icon:'error',title:'Unable to Delete',text:xhr.responseJSON?.message || 'Something went wrong.'});}});
        });
    });

    loadData();

    if (selectedInterviewId) {
        clearAutoOpenQuery();
        openInterviewForEdit(selectedInterviewId);
    } else if (selectedApplicationId) {
        clearAutoOpenQuery();
        resetForm();
        $('#application_id').val(String(selectedApplicationId));
        formModal.show();
    }

    formModalElement.addEventListener('hidden.bs.modal', clearAutoOpenQuery);
});
</script>
@endpush
