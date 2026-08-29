@extends('layout.app')

@section('title', 'Hiring Status')
@section('page-title', 'Hiring Status')
@section('page-description', 'Monitor and update applicant recruitment stages')

@section('content')
@php($prefix = request()->routeIs('hr.*') ? 'hr' : 'admin')

<div class="row g-3 mb-4">
    @foreach($stages as $stage)
        <div class="col-6 col-md-4 col-xl">
            <div class="stage-card">
                <span>{{ $stage }}</span>
                <strong>{{ $counts[$stage] ?? 0 }}</strong>
            </div>
        </div>
    @endforeach
</div>

<div class="card premium-page-card">
    <div class="premium-page-header">
        <div class="premium-page-heading">
            <div class="premium-page-icon"><i class="bi bi-arrow-repeat"></i></div>
            <div><h4 class="premium-page-title">Hiring Pipeline</h4><p class="premium-page-subtitle">Review applicants and update their current recruitment stage.</p></div>
        </div>
        <button type="button" class="premium-secondary-btn" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i><span>Refresh Records</span></button>
    </div>
    <div class="premium-table-wrap"><div class="table-responsive"><table id="dataTable" class="table align-middle w-100"><thead><tr><th>Reference</th><th>Applicant</th><th>Position</th><th>Department</th><th>Status</th><th>Last Updated</th><th>Action</th></tr></thead><tbody></tbody></table></div></div>
</div>

<div class="modal fade premium-form-modal" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="statusForm">
                @csrf
                <div class="premium-modal-header"><div class="premium-modal-icon"><i class="bi bi-arrow-repeat"></i></div><div><h5>Update Hiring Status</h5><p>Move the applicant to the appropriate recruitment stage.</p></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="application_id">
                    <div class="mb-3"><label for="status" class="form-label">Status</label><select name="status" id="status" class="form-select">@foreach($stages as $stage)<option value="{{ $stage }}">{{ $stage }}</option>@endforeach</select><div class="invalid-feedback status_error"></div></div>
                    <div class="mb-0"><label for="remarks" class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="5" placeholder="Optional status update notes..."></textarea><div class="invalid-feedback remarks_error"></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" id="saveBtn" class="btn btn-danger">Update Status</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stage-card{height:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;border:1px solid #edf0f4;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.05)}.stage-card span{color:#7b879a;font-size:11px;line-height:1.35}.stage-card strong{color:#17233e;font-size:22px}.premium-page-card{overflow:hidden;border:1px solid #edf0f4!important;border-radius:22px!important;background:#fff;box-shadow:0 14px 38px rgba(15,23,42,.07)!important}.premium-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px 26px;border-bottom:1px solid #eef1f5;background:radial-gradient(circle at top right,rgba(237,28,36,.08),transparent 34%),linear-gradient(180deg,#fff 0%,#fcfcfd 100%)}.premium-page-heading{display:flex;align-items:center;gap:15px}.premium-page-icon{width:50px;height:50px;min-width:50px;display:flex;align-items:center;justify-content:center;border-radius:15px;color:#fff;background:linear-gradient(135deg,#ed1c24,#a30d13);font-size:21px}.premium-page-title{margin:0 0 4px;color:#17233e;font-size:20px;font-weight:800}.premium-page-subtitle{margin:0;color:#8b96a9;font-size:13px}.premium-secondary-btn{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;border:1px solid #e2e7ee;border-radius:12px;color:#45536d;background:#fff;font-size:13px;font-weight:700}.premium-table-wrap{padding:22px 24px 24px}.premium-table-wrap th{font-size:13px!important}.premium-table-wrap td{font-size:14px!important;vertical-align:middle}.pipeline-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;color:#fff;font-size:12px;font-weight:700}.pipeline-pill::before{content:"";width:6px;height:6px;border-radius:50%;background:#fff}.p-new{background:#2563eb}.p-screening{background:#0891b2}.p-interview{background:#d97706}.p-questionnaire{background:#7c3aed}.p-offer,.p-hired{background:#16a34a}.p-rejected{background:#dc2626}.p-withdrawn{background:#64748b}.premium-form-modal .modal-dialog{width:min(560px,calc(100vw - 32px));max-width:560px}.premium-form-modal .modal-content{overflow:hidden;border:0;border-radius:20px}.premium-modal-header{position:relative;display:flex;align-items:center;gap:14px;padding:20px 58px 20px 22px;color:#fff;background:linear-gradient(135deg,#151d34 0%,#26304d 65%,#8f1117 145%)}.premium-modal-icon{width:48px;height:48px;min-width:48px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:rgba(255,255,255,.12);font-size:20px}.premium-modal-header h5{margin:0 0 3px;color:#fff;font-size:17px;font-weight:800}.premium-modal-header p{margin:0;color:rgba(255,255,255,.68);font-size:11px}.premium-modal-header .btn-close{position:absolute;top:18px;right:18px}.premium-form-modal .modal-body{padding:22px;background:#fbfcfe}.premium-form-modal .modal-footer{padding:14px 22px}.premium-form-modal .form-label{font-size:12px;font-weight:700}.premium-form-modal .form-control,.premium-form-modal .form-select{min-height:44px;border-radius:11px}@media(max-width:767.98px){.premium-page-header{flex-direction:column;align-items:flex-start}.premium-secondary-btn{width:100%}.premium-form-modal{padding:0!important}.premium-form-modal .modal-dialog{width:100%;height:100dvh;margin:0}.premium-form-modal .modal-content,.premium-form-modal form{height:100dvh;border-radius:0}.premium-form-modal form{display:flex;flex-direction:column}.premium-form-modal .modal-body{flex:1 1 auto;overflow-y:auto}.premium-form-modal .modal-footer{display:grid;grid-template-columns:1fr 1.3fr;gap:10px}.premium-form-modal .modal-footer .btn{width:100%;margin:0}}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const prefix = @json($prefix);
    const statusModalElement = document.getElementById('statusModal');
    const statusModal = new bootstrap.Modal(statusModalElement);
    let table;

    function clearErrors(){ $('.invalid-feedback').text(''); $('.form-control,.form-select').removeClass('is-invalid'); }
    function statusClass(status){const map={'New Applicant':'p-new','For Screening':'p-screening','For Initial Interview':'p-interview','For Questionnaire Review':'p-questionnaire','For Final Interview':'p-interview','For Job Offer':'p-offer','Hired':'p-hired','Rejected':'p-rejected','Withdrawn':'p-withdrawn'};return map[status]||'p-withdrawn';}

    function loadData(){
        if($.fn.DataTable.isDataTable('#dataTable')) table.destroy();
        table=$('#dataTable').DataTable({processing:true,responsive:true,autoWidth:false,scrollX:true,ajax:{url:"{{ route($prefix . '.hiring-status.data') }}",type:'GET',dataType:'json',error:function(xhr){console.log(xhr.responseText);Swal.fire({icon:'error',title:'Unable to Load Pipeline',text:xhr.responseJSON?.message||'Something went wrong while loading hiring status.'});}},columns:[{data:'reference_no'},{data:'applicant'},{data:'position'},{data:'department'},{data:'status',render:function(data){return `<span class="pipeline-pill ${statusClass(data)}">${data}</span>`;}},{data:'updated_at'},{data:null,orderable:false,searchable:false,render:function(data,type,row){return `<button type="button" class="btn btn-sm btn-danger updateBtn" data-id="${row.id}" data-status="${row.status}"><i class="bi bi-pencil-square me-1"></i>Update</button>`;}}],order:[[5,'desc']],language:{emptyTable:'No applications found.',processing:'Loading hiring status...'}});
    }

    $('#refreshBtn').on('click',function(){const $button=$(this);const originalHtml=$button.html();$button.prop('disabled',true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Refreshing...</span>');table.ajax.reload(function(){$button.prop('disabled',false).html(originalHtml);showToast('success','Records refreshed successfully.');},false);});
    $('#dataTable').on('click','.updateBtn',function(){clearErrors();$('#application_id').val($(this).data('id'));$('#status').val($(this).data('status'));$('#remarks').val('');statusModal.show();});
    $('#statusForm').on('submit',function(e){e.preventDefault();clearErrors();const id=$('#application_id').val();const url="{{ route($prefix . '.hiring-status.update', ':id') }}".replace(':id',id);$('#saveBtn').prop('disabled',true).text('Saving...');Swal.fire({title:'Saving...',text:'Please wait while updating the hiring status.',allowOutsideClick:false,allowEscapeKey:false,didOpen:()=>Swal.showLoading()});$.ajax({type:'PUT',url:url,dataType:'json',data:$(this).serialize(),success:function(response){Swal.close();statusModal.hide();showToast('success',response.message);table.ajax.reload(null,false);},error:function(xhr){Swal.close();if(xhr.status===422){const errors=xhr.responseJSON.errors||{};$.each(errors,function(key,value){$(`[name="${key}"]`).addClass('is-invalid');$('.'+key+'_error').text(value[0]);});Swal.fire({icon:'error',title:'Validation Error',text:'Please check the required fields.'});}else{Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message||'Something went wrong.'});}},complete:function(){$('#saveBtn').prop('disabled',false).text('Update Status');}});});

    loadData();
});
</script>
@endpush
