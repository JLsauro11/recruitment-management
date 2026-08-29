@extends('layout.app')

@section('title', 'Exam Results')
@section('page-title', 'Exam Results')
@section('page-description', 'Record and manage applicant examination results')

@section('content')
@php($prefix = request()->routeIs('hr.*') ? 'hr' : 'admin')

<div class="card premium-page-card">
    <div class="premium-page-header">
        <div class="premium-page-heading">
            <div class="premium-page-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
            <div>
                <h4 class="premium-page-title">Examination Results</h4>
                <p class="premium-page-subtitle">Record scores and monitor applicant examination outcomes.</p>
            </div>
        </div>
        <div class="premium-header-actions">
            <button type="button" class="premium-secondary-btn" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i><span>Refresh Records</span></button>
            <button type="button" class="premium-primary-btn" id="addBtn"><i class="bi bi-plus-lg"></i><span>Add Exam Result</span></button>
        </div>
    </div>

    <div class="premium-table-wrap">
        <div class="table-responsive">
            <table id="dataTable" class="table align-middle w-100">
                <thead><tr><th>Reference</th><th>Applicant</th><th>Position</th><th>Exam Type</th><th>Score</th><th>Passing</th><th>Result</th><th>Date</th><th>Actions</th></tr></thead>
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
                    <div class="premium-modal-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
                    <div><h5 id="modalTitle">Add Exam Result</h5><p>Create or update an applicant examination result.</p></div>
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
                                    <option value="{{ $application->id }}">{{ $application->reference_no }} - {{ $application->applicant?->full_name ?? 'N/A' }} ({{ $application->vacancy?->title ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback application_id_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="exam_type" class="form-label">Exam Type</label>
                            <input type="text" name="exam_type" id="exam_type" class="form-control" placeholder="Example: Technical Examination">
                            <div class="invalid-feedback exam_type_error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="score" class="form-label">Score</label>
                            <input type="number" name="score" id="score" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback score_error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="passing_score" class="form-label">Passing Score</label>
                            <input type="number" name="passing_score" id="passing_score" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback passing_score_error"></div>
                        </div>
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Optional examination notes..."></textarea>
                            <div class="invalid-feedback remarks_error"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" id="saveBtn" class="btn btn-danger">Save Result</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .premium-page-card{overflow:hidden;border:1px solid #edf0f4!important;border-radius:22px!important;background:#fff;box-shadow:0 14px 38px rgba(15,23,42,.07)!important}.premium-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px 26px;border-bottom:1px solid #eef1f5;background:radial-gradient(circle at top right,rgba(237,28,36,.08),transparent 34%),linear-gradient(180deg,#fff 0%,#fcfcfd 100%)}.premium-page-heading{display:flex;align-items:center;gap:15px}.premium-page-icon{width:50px;height:50px;min-width:50px;display:flex;align-items:center;justify-content:center;border-radius:15px;color:#fff;background:linear-gradient(135deg,#ed1c24,#a30d13);box-shadow:0 10px 24px rgba(237,28,36,.22);font-size:21px}.premium-page-title{margin:0 0 4px;color:#17233e;font-size:20px;font-weight:800}.premium-page-subtitle{margin:0;color:#8b96a9;font-size:13px}.premium-primary-btn{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;border:1px solid #dc161e;border-radius:12px;color:#fff;background:linear-gradient(135deg,#ed1c24,#c61219);font-size:13px;font-weight:700}.premium-table-wrap{padding:22px 24px 24px}.premium-table-wrap th{font-size:13px!important}.premium-table-wrap td{font-size:14px!important;vertical-align:middle}.premium-action-group{display:flex;gap:7px}.premium-icon-btn{width:34px!important;height:34px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:0!important}.result-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;color:#fff;font-size:12px;font-weight:700}.result-pill::before{content:"";width:6px;height:6px;border-radius:50%;background:#fff}.result-Passed{background:#16a34a}.result-Failed{background:#dc2626}.premium-form-modal .modal-dialog{width:min(700px,calc(100vw - 32px));max-width:700px;margin:20px auto}.premium-form-modal .modal-content{overflow:hidden;border:0;border-radius:20px;box-shadow:0 26px 70px rgba(15,23,42,.22)}.premium-form-modal form{display:flex;flex-direction:column;max-height:calc(100dvh - 40px)}.premium-modal-header{position:relative;display:flex;align-items:center;gap:14px;padding:20px 58px 20px 22px;color:#fff;background:linear-gradient(135deg,#151d34 0%,#26304d 65%,#8f1117 145%)}.premium-modal-icon{width:48px;height:48px;min-width:48px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(255,255,255,.12);font-size:20px}.premium-modal-header h5{margin:0 0 3px;color:#fff;font-size:17px;font-weight:800}.premium-modal-header p{margin:0;color:rgba(255,255,255,.68);font-size:11px}.premium-modal-header .btn-close{position:absolute;top:18px;right:18px}.premium-form-modal .modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:22px;background:#fbfcfe}.premium-form-modal .modal-footer{padding:14px 22px;background:#fff}.premium-form-modal .form-label{font-size:12px;font-weight:700}.premium-form-modal .form-control,.premium-form-modal .form-select{min-height:44px;border-radius:11px}@media(max-width:767.98px){.premium-page-header{flex-direction:column;align-items:flex-start}.premium-primary-btn{width:100%}.premium-form-modal{padding:0!important}.premium-form-modal .modal-dialog{width:100%;height:100dvh;margin:0}.premium-form-modal .modal-content,.premium-form-modal form{height:100dvh;max-height:100dvh;border-radius:0}.premium-form-modal .modal-footer{display:grid;grid-template-columns:1fr 1.3fr;gap:10px}.premium-form-modal .modal-footer .btn{width:100%;margin:0}}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const prefix = @json($prefix);
    const formModalElement = document.getElementById('formModal');
    const formModal = new bootstrap.Modal(formModalElement);
    let table;

    function clearErrors(){ $('.invalid-feedback').text(''); $('.form-control,.form-select').removeClass('is-invalid'); }
    function resetForm(){ clearErrors(); $('#dataForm')[0].reset(); $('#record_id').val(''); $('#modalTitle').text('Add Exam Result'); $('#saveBtn').text('Save Result'); }

    function loadData(){
        if($.fn.DataTable.isDataTable('#dataTable')) table.destroy();
        table=$('#dataTable').DataTable({
            processing:true,responsive:true,autoWidth:false,scrollX:true,
            ajax:{url:"{{ route($prefix . '.exam-results.data') }}",type:'GET',dataType:'json',error:function(xhr){console.log(xhr.responseText);Swal.fire({icon:'error',title:'Unable to Load Results',text:xhr.responseJSON?.message||'Something went wrong while loading exam results.'});}},
            columns:[
                {data:'reference_no'},{data:'applicant'},{data:'position'},{data:'exam_type'},{data:'score'},{data:'passing_score'},
                {data:'result',render:function(data){return `<span class="result-pill result-${data}">${data}</span>`;}},
                {data:'date'},
                {data:null,orderable:false,searchable:false,render:function(data,type,row){return `<div class="premium-action-group"><button type="button" class="btn btn-sm btn-primary premium-icon-btn editBtn" data-id="${row.id}" title="Edit"><i class="bi bi-pencil-square"></i></button><button type="button" class="btn btn-sm btn-danger premium-icon-btn deleteBtn" data-id="${row.id}" title="Delete"><i class="bi bi-trash"></i></button></div>`;}}
            ],order:[[7,'desc']],language:{emptyTable:'No exam results found.',processing:'Loading exam results...'}
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

    $('#addBtn').on('click',function(){resetForm();formModal.show();});

    $('#dataTable').on('click','.editBtn',function(){
        clearErrors();
        const id=$(this).data('id');
        const url="{{ route($prefix . '.exam-results.show', ':id') }}".replace(':id',id);
        $.ajax({type:'GET',url:url,dataType:'json',success:function(response){$('#record_id').val(response.id);$('#application_id').val(response.application_id);$('#exam_type').val(response.exam_type);$('#score').val(response.score);$('#passing_score').val(response.passing_score);$('#remarks').val(response.remarks);$('#modalTitle').text('Edit Exam Result');$('#saveBtn').text('Update Result');formModal.show();},error:function(xhr){Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message||'Unable to retrieve exam result.'});}});
    });

    $('#dataForm').on('submit',function(e){
        e.preventDefault();clearErrors();
        const id=$('#record_id').val();
        const url=id?"{{ route($prefix . '.exam-results.update', ':id') }}".replace(':id',id):"{{ route($prefix . '.exam-results.store') }}";
        const method=id?'PUT':'POST';
        $('#saveBtn').prop('disabled',true).text('Saving...');
        Swal.fire({title:'Saving...',text:'Please wait while saving the exam result.',allowOutsideClick:false,allowEscapeKey:false,didOpen:()=>Swal.showLoading()});
        $.ajax({type:method,url:url,dataType:'json',data:$(this).serialize(),success:function(response){Swal.close();formModal.hide();showToast('success',response.message);table.ajax.reload(null,false);},error:function(xhr){Swal.close();if(xhr.status===422){const errors=xhr.responseJSON.errors||{};$.each(errors,function(key,value){$(`[name="${key}"]`).addClass('is-invalid');$('.'+key+'_error').text(value[0]);});Swal.fire({icon:'error',title:'Validation Error',text:'Please check the required fields.'});}else{Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message||'Something went wrong.'});}},complete:function(){$('#saveBtn').prop('disabled',false).text(id?'Update Result':'Save Result');}});
    });

    $('#dataTable').on('click','.deleteBtn',function(){
        const id=$(this).data('id');
        Swal.fire({title:'Delete this exam result?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Yes, delete it'}).then(function(result){if(!result.isConfirmed)return;const url="{{ route($prefix . '.exam-results.destroy', ':id') }}".replace(':id',id);$.ajax({type:'DELETE',url:url,dataType:'json',success:function(response){showToast('success',response.message);table.ajax.reload(null,false);},error:function(xhr){Swal.fire({icon:'error',title:'Unable to Delete',text:xhr.responseJSON?.message||'Something went wrong.'});}});});
    });

    loadData();
});
</script>
@endpush
