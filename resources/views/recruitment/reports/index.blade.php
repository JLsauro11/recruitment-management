@extends('layout.app')
@section('title','Recruitment Reports')
@section('page-title','Recruitment Reports')
@section('page-description','Recruitment performance and downloadable applicant data')
@section('page-actions')<a href="{{ route(request()->routeIs('hr.*') ? 'hr.reports.export' : 'admin.reports.export') }}" class="btn btn-danger"><i class="bi bi-download me-1"></i>Export CSV</a>@endsection
@section('content')
<div class="row g-3 mb-4">@foreach(['applications'=>'Total Applications','hired'=>'Hired','rejected'=>'Rejected','open_vacancies'=>'Open Vacancies','scheduled_interviews'=>'Scheduled Interviews'] as $key=>$label)<div class="col-6 col-lg"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">{{ $label }}</small><h2 class="mb-0 mt-2">{{ $summary[$key] }}</h2></div></div></div>@endforeach</div>
<div class="row g-4"><div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white"><h5 class="mb-0">Applications by Status</h5></div><div class="card-body"><table class="table"><thead><tr><th>Status</th><th class="text-end">Total</th></tr></thead><tbody>@foreach($byStatus as $r)<tr><td>{{ $r->status }}</td><td class="text-end fw-bold">{{ $r->total }}</td></tr>@endforeach</tbody></table></div></div></div><div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white"><h5 class="mb-0">Applications by Position</h5></div><div class="card-body"><table class="table"><thead><tr><th>Position</th><th class="text-end">Total</th></tr></thead><tbody>@foreach($byPosition as $r)<tr><td>{{ $r->title }}</td><td class="text-end fw-bold">{{ $r->total }}</td></tr>@endforeach</tbody></table></div></div></div></div>
@endsection
