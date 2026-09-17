@extends('layout.app')

@section('title', 'Employment Forms')
@section('page-title', 'Employment Forms')
@section('page-subtitle', 'System-managed employment forms and optional custom templates')

@section('content')
@php $prefix = request()->routeIs('hr.*') ? 'hr' : 'admin'; @endphp

<div class="template-manager-shell">
    <div class="template-manager-header">
        <div class="template-manager-heading">
            <div class="template-manager-icon"><i class="bi bi-ui-checks-grid"></i></div>
            <div>
                <span class="template-manager-kicker">EMPLOYMENT FORMS</span>
                <h4>Employment Form Templates</h4>
                <p>The public Application for Employment and Employment Questionnaire are system-managed so Assessment Insights always receives the evidence it expects.</p>
            </div>
        </div>

        <div class="template-manager-actions">
            <a href="{{ route('careers.index') }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Open Careers Page</span>
            </a>
            <button type="button" class="btn btn-danger" id="addBtn">
                <i class="bi bi-plus-lg"></i>
                <span>New Template</span>
            </button>
        </div>
    </div>

    <div class="template-manager-body">
        <div class="template-summary-row">
            <div>
                <strong id="templateCount">0 Templates</strong>
                <span>System-managed forms are fixed and used automatically. Custom templates may still be created for other workflows, but they do not replace the assessment form pair.</span>
            </div>
            <button type="button" class="btn btn-light" id="refreshBtn">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>

        <div class="template-card-grid" id="templateGrid"></div>
        <div class="template-empty d-none" id="templateEmpty">
            <div class="template-empty-icon"><i class="bi bi-ui-checks"></i></div>
            <h5>No form templates yet</h5>
            <p>Create your first application or questionnaire template.</p>
            <button type="button" class="btn btn-danger" id="emptyAddBtn">Create Template</button>
        </div>
    </div>
</div>

<div class="modal fade template-builder-modal" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="templateForm">
                @csrf

                <div class="template-modal-header">
                    <div class="template-modal-icon"><i class="bi bi-ui-checks-grid"></i></div>
                    <div>
                        <span>EMPLOYMENT FORM BUILDER</span>
                        <h5 id="modalTitle">Add Form Template</h5>
                        <p>Organize sections and fields using drag and drop.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="record_id">

                    <section class="template-config-card">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <label class="form-label">Template Name</label>
                                <input name="name" id="name" class="form-control" required>
                                <div class="invalid-feedback name_error"></div>
                            </div>
                            <div class="col-lg-3 col-md-5">
                                <label class="form-label">Template Type</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="application">Application for Employment</option>
                                    <option value="questionnaire">Questionnaire</option>
                                </select>
                                <div class="invalid-feedback type_error"></div>
                            </div>
                            <div class="col-lg-2 col-md-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" min="1" value="1">
                                <small class="text-muted d-block mt-1">Occupied orders swap automatically.</small>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="2" placeholder="Describe the purpose of this template."></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="builder-workspace">
                        <div class="builder-workspace-header">
                            <div>
                                <span class="builder-kicker">STRUCTURE AND ORDER</span>
                                <h5>Sections and Fields</h5>
                                <p>Drag a section to reorder the entire group. Drag a field to move it within or between sections.</p>
                            </div>
                            <div class="builder-workspace-actions">
                                <button type="button" class="btn btn-outline-secondary" id="addSectionBtn">
                                    <i class="bi bi-folder-plus"></i> Add Section
                                </button>
                                <button type="button" class="btn btn-danger" id="addFieldBtn">
                                    <i class="bi bi-plus-lg"></i> Add Field
                                </button>
                            </div>
                        </div>

                        <div class="builder-alert d-none" id="duplicateAlert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span></span>
                        </div>

                        <div class="section-board" id="sectionsBoard"></div>

                        <div class="builder-empty d-none" id="builderEmpty">
                            <i class="bi bi-layout-text-sidebar-reverse"></i>
                            <h6>No sections yet</h6>
                            <p>Add a section, then create fields inside it.</p>
                        </div>
                        <div class="fields_error text-danger small"></div>
                    </section>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="saveBtn">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content section-picker-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Add Section</h5>
                    <p class="mb-0">Use an existing section name or create a new one.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Existing Sections</label>
                <select class="form-select mb-3" id="existingSectionSelect">
                    <option value="">Select an existing section</option>
                </select>
                <div class="section-divider"><span>OR</span></div>
                <label class="form-label">New Section Name</label>
                <input type="text" class="form-control" id="newSectionName" placeholder="Example: Personal Information">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmAddSection">Add Section</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.template-manager-shell{overflow:hidden;border:1px solid #e9edf3;border-radius:24px;background:#fff;box-shadow:0 18px 46px rgba(15,23,42,.08)}
.template-manager-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:26px 28px;border-bottom:1px solid #edf1f5;background:radial-gradient(circle at top right,rgba(237,28,36,.10),transparent 36%),linear-gradient(180deg,#fff,#fcfcfd)}
.template-manager-heading{display:flex;align-items:center;gap:16px}.template-manager-icon{width:54px;height:54px;min-width:54px;display:grid;place-items:center;border-radius:17px;color:#fff;background:linear-gradient(135deg,#ed1c24,#a30d13);box-shadow:0 12px 26px rgba(237,28,36,.24);font-size:22px}.template-manager-kicker{display:block;margin-bottom:4px;color:#ed1c24;font-size:9px;font-weight:800;letter-spacing:.16em}.template-manager-heading h4{margin:0;color:#17233e;font-size:21px;font-weight:800}.template-manager-heading p{margin:4px 0 0;color:#8b96a9;font-size:12px}.template-manager-actions{display:flex;gap:10px}.template-manager-actions .btn{min-height:42px;display:inline-flex;align-items:center;gap:8px;border-radius:12px;font-size:13px;font-weight:700}.template-manager-body{padding:24px 26px 28px}.template-summary-row{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.template-summary-row strong,.template-summary-row span{display:block}.template-summary-row strong{color:#26334d;font-size:15px}.template-summary-row span{margin-top:3px;color:#929daf;font-size:11px}.template-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.template-card{position:relative;overflow:hidden;border:1px solid #e6ebf1;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.05);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}.template-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(15,23,42,.09)}.template-card.is-dragging{opacity:.48;transform:scale(.985);box-shadow:none}.template-card.is-drag-over{border-color:#ed1c24;box-shadow:0 0 0 3px rgba(237,28,36,.09),0 16px 34px rgba(15,23,42,.08)}.template-card-drag{position:absolute;right:16px;top:16px;width:36px;height:36px;display:grid;place-items:center;border:1px solid #e7ebf1;border-radius:10px;color:#8c98aa;background:#fff;cursor:grab;z-index:2}.template-card-drag:active{cursor:grabbing}.template-card-drag i{font-size:17px;line-height:1}.template-order-pill{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;color:#8b1620;background:#fff0f1;font-size:10px;font-weight:800}.template-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 64px 16px 20px}.template-card-identity{display:flex;gap:13px;min-width:0}.template-card-icon{width:46px;height:46px;min-width:46px;display:grid;place-items:center;border-radius:14px;color:#fff;background:linear-gradient(135deg,#ed1c24,#b40e15);font-size:19px}.template-card h5{margin:0;color:#1f2c46;font-size:16px;font-weight:800}.template-card p{margin:4px 0 0;color:#8d98aa;font-size:11px;line-height:1.55}.template-card-badges{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}.template-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:999px;color:#58667e;background:#f1f4f8;font-size:10px;font-weight:700}.template-pill.active{color:#fff;background:#16a34a}.template-pill.inactive{color:#fff;background:#64748b}.template-card-stats{display:grid;grid-template-columns:repeat(2,1fr);border-top:1px solid #eef2f6;border-bottom:1px solid #eef2f6;background:#fafbfd}.template-stat{padding:13px 18px}.template-stat+ .template-stat{border-left:1px solid #eef2f6}.template-stat strong,.template-stat span{display:block}.template-stat strong{color:#23304a;font-size:16px}.template-stat span{margin-top:2px;color:#98a2b3;font-size:10px}.template-card-actions{display:flex;justify-content:flex-end;gap:8px;padding:14px 18px}.template-card-actions .btn{width:36px;height:36px;display:grid;place-items:center;padding:0;border-radius:10px}.template-empty{padding:55px 20px;text-align:center}.template-empty-icon{width:66px;height:66px;display:grid;place-items:center;margin:0 auto 14px;border-radius:20px;color:#ed1c24;background:#fff0f1;font-size:26px}.template-empty h5{color:#23304a}.template-empty p{color:#8c97a9;font-size:12px}
.template-builder-modal{padding:14px!important;overflow:hidden}.template-builder-modal .modal-dialog{width:min(1240px,calc(100vw - 28px));max-width:1240px;height:calc(100dvh - 28px);margin:14px auto}.template-builder-modal .modal-content,.template-builder-modal #templateForm{height:100%;overflow:hidden;border:0;border-radius:22px}.template-builder-modal #templateForm{display:flex;flex-direction:column}.template-modal-header{position:relative;display:flex;align-items:center;gap:14px;flex:0 0 auto;padding:20px 58px 20px 22px;color:#fff;background:radial-gradient(circle at top right,rgba(255,255,255,.13),transparent 34%),linear-gradient(135deg,#151d34,#26304d 65%,#8f1117)}.template-modal-icon{width:48px;height:48px;min-width:48px;display:grid;place-items:center;border-radius:14px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.15);font-size:20px}.template-modal-header span{display:block;color:#ff9095;font-size:9px;font-weight:800;letter-spacing:.15em}.template-modal-header h5{margin:3px 0 0;color:#fff;font-size:18px;font-weight:800}.template-modal-header p{margin:3px 0 0;color:rgba(255,255,255,.62);font-size:11px}.template-modal-header .btn-close{position:absolute;right:18px;top:18px}.template-builder-modal .modal-body{flex:1 1 auto;min-height:0;padding:22px;overflow-y:auto;background:#f6f8fb}.template-builder-modal .modal-footer{flex:0 0 auto;padding:13px 22px;border-top:1px solid #e8edf3;background:#fff;box-shadow:0 -8px 24px rgba(15,23,42,.05)}.template-config-card{padding:18px;border:1px solid #e4e9ef;border-radius:17px;background:#fff;box-shadow:0 8px 20px rgba(15,23,42,.035)}.builder-workspace{margin-top:20px}.builder-workspace-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:14px}.builder-kicker{display:block;margin-bottom:4px;color:#ed1c24;font-size:9px;font-weight:800;letter-spacing:.15em}.builder-workspace-header h5{margin:0;color:#23304a;font-size:17px;font-weight:800}.builder-workspace-header p{margin:4px 0 0;color:#8e99ab;font-size:11px}.builder-workspace-actions{display:flex;gap:9px}.section-board{display:flex;flex-direction:column;gap:14px}.section-card{border:1px solid #dfe5ec;border-radius:17px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.04)}.section-card.dragging,.field-card.dragging{opacity:.42}.section-card.drag-over,.field-dropzone.drag-over{outline:2px dashed #ed1c24;outline-offset:3px}.section-card-header{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #edf1f5;background:linear-gradient(180deg,#fff,#fafbfd)}.drag-handle{cursor:grab;color:#9aa5b6;font-size:18px}.drag-handle:active{cursor:grabbing}.section-title-input{min-width:0;flex:1;border:0;background:transparent;color:#24314a;font-size:14px;font-weight:800;outline:none}.section-count{padding:5px 8px;border-radius:999px;color:#66738a;background:#eef2f6;font-size:9px;font-weight:700}.section-action-btn{width:32px;height:32px;display:grid;place-items:center;padding:0;border-radius:9px}.field-dropzone{min-height:26px;padding:12px}.field-card{position:relative;margin-bottom:10px;border:1px solid #e3e8ef;border-radius:14px;background:#fff;box-shadow:0 5px 15px rgba(15,23,42,.035)}.field-card:last-child{margin-bottom:0}.field-card.is-duplicate{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.08)}.field-card-header{display:flex;align-items:center;gap:10px;padding:12px 13px;border-bottom:1px solid #eff2f6;background:#fcfdfe}.field-card-title{min-width:0;flex:1;color:#2a3750;font-size:13px;font-weight:800}.field-type-chip{padding:5px 8px;border-radius:8px;color:#6b768a;background:#eef2f6;font-size:9px;font-weight:700;text-transform:uppercase}.field-remove{width:31px;height:31px;display:grid;place-items:center;padding:0;border-radius:8px}.field-card-body{padding:14px}.options-wrap{display:none}.options-wrap.is-visible{display:block}.field-key-feedback{display:block;min-height:16px;margin-top:4px;font-size:10px}.field-key-feedback.error{color:#dc2626}.field-key-feedback.success{color:#16a34a}.builder-alert{display:flex;align-items:center;gap:8px;padding:11px 13px;margin-bottom:12px;border:1px solid #fecaca;border-radius:11px;color:#991b1b;background:#fee2e2;font-size:11px}.builder-empty{padding:38px;text-align:center;border:1px dashed #d7dee8;border-radius:17px;color:#8c97a9;background:rgba(255,255,255,.55)}.builder-empty i{font-size:28px;color:#ed1c24}.builder-empty h6{margin:8px 0 3px;color:#334159}.builder-empty p{margin:0;font-size:11px}.section-picker-modal{border:0;border-radius:18px;overflow:hidden}.section-picker-modal .modal-header{padding:18px 20px}.section-picker-modal .modal-header h5{margin:0;color:#23304a;font-weight:800}.section-picker-modal .modal-header p{color:#8e99aa;font-size:11px}.section-divider{position:relative;margin:16px 0;text-align:center}.section-divider:before{content:"";position:absolute;left:0;right:0;top:50%;height:1px;background:#e7ebf1}.section-divider span{position:relative;padding:0 10px;color:#9ba5b5;background:#fff;font-size:9px;font-weight:800}.form-control,.form-select{border-radius:10px}.form-control:focus,.form-select:focus{border-color:#ed1c24;box-shadow:0 0 0 4px rgba(237,28,36,.08)}
@media(max-width:991.98px){.template-card-grid{grid-template-columns:1fr}.template-manager-header,.builder-workspace-header{align-items:flex-start;flex-direction:column}.template-manager-actions,.builder-workspace-actions{width:100%}.template-manager-actions .btn,.builder-workspace-actions .btn{flex:1}.template-builder-modal{padding:0!important}.template-builder-modal .modal-dialog{width:100%;height:100dvh;margin:0}.template-builder-modal .modal-content{border-radius:0}}
@media(max-width:575.98px){.template-manager-header,.template-manager-body{padding:18px}.template-manager-heading{align-items:flex-start}.template-manager-actions,.builder-workspace-actions{flex-direction:column}.template-manager-actions .btn,.builder-workspace-actions .btn{width:100%}.template-summary-row{align-items:flex-start;flex-direction:column}.template-summary-row .btn{width:100%}.template-modal-header{padding:16px 48px 16px 14px}.template-builder-modal .modal-body{padding:14px}.template-builder-modal .modal-footer{display:grid;grid-template-columns:1fr 1.3fr;gap:9px;padding:11px 14px}.template-builder-modal .modal-footer .btn{width:100%}.section-card-header{flex-wrap:wrap}.section-title-input{order:2;flex-basis:calc(100% - 45px)}}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const prefix = @json($prefix);
    const formModal = new bootstrap.Modal(document.getElementById('formModal'));
    const sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));

    let availableSections = [];
    let draggedTemplate = null;
    let draggedSection = null;
    let draggedField = null;

    const routes = {
        data: @json(route($prefix.'.forms.data')),
        sections: @json(route($prefix.'.forms.sections')),
        reorder: @json(route($prefix.'.forms.reorder')),
        store: @json(route($prefix.'.forms.store')),
        show: @json(route($prefix.'.forms.show', ':id')),
        update: @json(route($prefix.'.forms.update', ':id')),
        destroy: @json(route($prefix.'.forms.destroy', ':id')),
    };

    function routeUrl(name, id = null) {
        return id === null ? routes[name] : routes[name].replace(':id', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function optionTypes(type) {
        return ['select', 'radio', 'checkbox'].includes(type);
    }

    function refreshSections() {
        return $.get(routes.sections).done(function (response) {
            availableSections = response.data || [];
            renderExistingSectionOptions();
        });
    }

    function renderExistingSectionOptions() {
        const current = $('#existingSectionSelect').val();
        const options = ['<option value="">Select an existing section</option>']
            .concat(availableSections.map(section => `<option value="${escapeHtml(section)}">${escapeHtml(section)}</option>`));
        $('#existingSectionSelect').html(options.join('')).val(current || '');
    }

    function loadTemplates() {
        $('#templateGrid').html('<div class="text-muted small">Loading templates...</div>');

        $.get(routes.data)
            .done(function (response) {
                const rows = response.data || [];
                $('#templateCount').text(rows.length + (rows.length === 1 ? ' Template' : ' Templates'));
                $('#templateGrid').empty();
                $('#templateEmpty').toggleClass('d-none', rows.length > 0);

                rows.forEach(function (row) {
                    const typeLabel = row.type === 'application' ? 'Application for Employment' : 'Questionnaire';
                    const icon = row.type === 'application' ? 'bi-file-earmark-person-fill' : 'bi-chat-square-text-fill';
                    const statusClass = row.is_active ? 'active' : 'inactive';
                    const statusLabel = row.is_active ? 'Active' : 'Inactive';
                    const managedBadge = row.is_system_managed
                        ? '<span class="template-pill active"><i class="bi bi-shield-lock-fill"></i> System Managed</span>'
                        : '';
                    const editButton = row.is_system_managed
                        ? '<button type="button" class="btn btn-light" disabled title="Managed automatically for Assessment Insights"><i class="bi bi-lock-fill"></i></button>'
                        : `<button type="button" class="btn btn-light editBtn" data-id="${row.id}" title="Edit"><i class="bi bi-pencil-square"></i></button>`;
                    const deleteButton = row.is_system_managed
                        ? '<button type="button" class="btn btn-light" disabled title="Required by the employment application flow"><i class="bi bi-shield-lock"></i></button>'
                        : `<button type="button" class="btn btn-danger deleteBtn" data-id="${row.id}" title="Delete"><i class="bi bi-trash"></i></button>`;

                    $('#templateGrid').append(`
                        <article class="template-card" data-id="${row.id}" data-order="${row.sort_order}">
                            <span class="template-card-drag" draggable="true" title="Drag to reorder" aria-label="Drag template"><i class="bi bi-grip-vertical"></i></span>
                            <div class="template-card-top">
                                <div class="template-card-identity">
                                    <div class="template-card-icon"><i class="bi ${icon}"></i></div>
                                    <div>
                                        <h5>${escapeHtml(row.name)}</h5>
                                        <p>${escapeHtml(row.description || 'No description provided.')}</p>
                                        <div class="template-card-badges">
                                            <span class="template-pill">${typeLabel}</span>
                                            <span class="template-pill ${statusClass}">${statusLabel}</span>
                                            ${managedBadge}
                                            <span class="template-order-pill"><i class="bi bi-list-ol"></i> Order ${row.sort_order}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="template-card-stats">
                                <div class="template-stat"><strong>${row.fields_count}</strong><span>Fields</span></div>
                                <div class="template-stat"><strong>${row.submissions_count}</strong><span>Submissions</span></div>
                            </div>
                            <div class="template-card-actions">
                                ${editButton}
                                ${deleteButton}
                            </div>
                        </article>
                    `);
                });
            })
            .fail(function (xhr) {
                $('#templateGrid').html('<div class="alert alert-danger">Unable to load form templates.</div>');
                console.log(xhr.responseText);
            });
    }

    function createSection(sectionName = 'New Section') {
        const section = $(sectionMarkup(sectionName));
        $('#sectionsBoard').append(section);
        updateBuilderState();
        return section;
    }

    function sectionMarkup(sectionName) {
        return `
            <div class="section-card" draggable="true">
                <div class="section-card-header">
                    <span class="drag-handle section-drag-handle" title="Drag section"><i class="bi bi-grip-vertical"></i></span>
                    <input type="text" class="section-title-input" value="${escapeHtml(sectionName)}" placeholder="Section name">
                    <span class="section-count">0 Fields</span>
                    <button type="button" class="btn btn-light section-action-btn addFieldToSection" title="Add field"><i class="bi bi-plus-lg"></i></button>
                    <button type="button" class="btn btn-outline-danger section-action-btn removeSection" title="Remove section"><i class="bi bi-trash"></i></button>
                </div>
                <div class="field-dropzone"></div>
            </div>
        `;
    }

    function createField(field = {}, targetSection = null, prepend = true) {
        let section = targetSection;
        if (!section || !section.length) {
            section = $('#sectionsBoard .section-card').first();
        }
        if (!section.length) {
            section = createSection(field.section || 'General Information');
        }

        const card = $(fieldMarkup(field));
        const zone = section.find('.field-dropzone');
        prepend ? zone.prepend(card) : zone.append(card);
        updateBuilderState();
        validateKeys(false);
        return card;
    }

    function fieldMarkup(field) {
        const type = field.field_type || 'text';
        const options = Array.isArray(field.options) ? field.options.join(', ') : (field.options || '');
        const required = field.is_required ? '1' : '0';

        return `
            <div class="field-card" draggable="true">
                <div class="field-card-header">
                    <span class="drag-handle field-drag-handle" title="Drag field"><i class="bi bi-grip-vertical"></i></span>
                    <span class="field-card-title">${escapeHtml(field.label || 'New Field')}</span>
                    <span class="field-type-chip">${escapeHtml(type)}</span>
                    <button type="button" class="btn btn-outline-danger field-remove removeField"><i class="bi bi-trash"></i></button>
                </div>
                <div class="field-card-body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Field Label</label>
                            <input class="form-control field-label" data-field="label" value="${escapeHtml(field.label || '')}" required>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Field Key</label>
                            <input class="form-control field-key" data-field="field_key" value="${escapeHtml(field.field_key || '')}" pattern="[a-z0-9_]+" required>
                            <span class="field-key-feedback"></span>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Field Type</label>
                            <select class="form-select field-type" data-field="field_type">
                                ${['text','email','number','date','select','textarea','radio','checkbox','file'].map(value => `<option value="${value}" ${type === value ? 'selected' : ''}>${value.charAt(0).toUpperCase() + value.slice(1)}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Width</label>
                            <input type="number" min="1" max="12" class="form-control" data-field="width" value="${field.width || 12}">
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Required</label>
                            <select class="form-select" data-field="is_required">
                                <option value="0" ${required === '0' ? 'selected' : ''}>No</option>
                                <option value="1" ${required === '1' ? 'selected' : ''}>Yes</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <label class="form-label">Placeholder</label>
                            <input class="form-control" data-field="placeholder" value="${escapeHtml(field.placeholder || '')}">
                        </div>
                        <div class="col-lg-4 options-wrap ${optionTypes(type) ? 'is-visible' : ''}">
                            <label class="form-label">Options <small class="text-muted">comma separated</small></label>
                            <input class="form-control options-input" data-field="options" value="${escapeHtml(options)}" placeholder="Example: Yes, No, Maybe">
                        </div>
                        <input type="hidden" data-field="sort_order" value="${field.sort_order ?? 0}">
                    </div>
                </div>
            </div>
        `;
    }

    function normalizeNames() {
        let fieldIndex = 0;

        $('#sectionsBoard .section-card').each(function () {
            const sectionName = $(this).find('.section-title-input').val().trim() || 'General Information';

            $(this).find('.field-card').each(function () {
                const card = $(this);
                card.find('[data-field]').each(function () {
                    const key = $(this).data('field');
                    $(this).attr('name', `fields[${fieldIndex}][${key}]`);
                });

                if (!card.find('.section-hidden').length) {
                    card.append('<input type="hidden" class="section-hidden">');
                }

                card.find('.section-hidden')
                    .attr('name', `fields[${fieldIndex}][section]`)
                    .val(sectionName);

                card.find('[data-field="sort_order"]').val(fieldIndex);
                fieldIndex++;
            });
        });
    }

    function updateBuilderState() {
        $('#builderEmpty').toggleClass('d-none', $('#sectionsBoard .section-card').length > 0);

        $('#sectionsBoard .section-card').each(function () {
            const count = $(this).find('.field-card').length;
            $(this).find('.section-count').text(count + (count === 1 ? ' Field' : ' Fields'));
        });

        normalizeNames();
    }

    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback,.fields_error').text('');
        $('#duplicateAlert').addClass('d-none').find('span').text('');
    }

    function validateKeys(showAlert = true) {
        const seen = {};
        let valid = true;
        const duplicates = [];

        $('.field-card').removeClass('is-duplicate');
        $('.field-key-feedback').removeClass('error success').text('');

        $('.field-key').each(function () {
            const input = $(this);
            const key = input.val().trim();
            const card = input.closest('.field-card');
            if (!key) return;

            if (seen[key]) {
                valid = false;
                duplicates.push(key);
                card.addClass('is-duplicate');
                seen[key].closest('.field-card').addClass('is-duplicate');
                input.siblings('.field-key-feedback').addClass('error').text('Duplicate field key.');
                seen[key].siblings('.field-key-feedback').addClass('error').text('Duplicate field key.');
            } else {
                seen[key] = input;
                input.siblings('.field-key-feedback').addClass('success').text('Field key is available.');
            }
        });

        if (!valid && showAlert) {
            $('#duplicateAlert').removeClass('d-none').find('span')
                .text('Duplicate field keys found: ' + [...new Set(duplicates)].join(', '));
        } else {
            $('#duplicateAlert').addClass('d-none');
        }

        return valid;
    }

    function resetForm() {
        clearErrors();
        $('#templateForm')[0].reset();
        $('#record_id').val('');
        $('#sort_order').val(Math.max(1, $('#templateGrid .template-card').length + 1));
        $('#sectionsBoard').empty();
        createSection('General Information');
        createField({}, $('#sectionsBoard .section-card').first(), true);
        $('#modalTitle').text('Add Form Template');
        updateBuilderState();
    }

    function openSectionPicker() {
        $('#existingSectionSelect').val('');
        $('#newSectionName').val('');
        renderExistingSectionOptions();
        sectionModal.show();
    }

    $('#addBtn, #emptyAddBtn').on('click', function () {
        refreshSections().always(function () {
            resetForm();
            formModal.show();
        });
    });

    function saveTemplateOrder() {
        const order = $('#templateGrid .template-card').map(function () {
            return Number($(this).data('id'));
        }).get().filter(Boolean);

        if (!order.length) return;

        $.ajax({
            url: routes.reorder,
            type: 'POST',
            data: { order: order },
            success: function (response) {
                showToast('success', response.message || 'Template display order updated.');
                loadTemplates();
            },
            error: function (xhr) {
                loadTemplates();
                Swal.fire('Unable to Reorder', xhr.responseJSON?.message || 'The template order could not be saved.', 'error');
            }
        });
    }

    // Drag-and-drop for the template cards on the main Form Templates screen.
    $('#templateGrid').on('dragstart', '.template-card-drag', function (event) {
        draggedTemplate = $(this).closest('.template-card')[0];
        $(draggedTemplate).addClass('is-dragging');
        event.originalEvent.dataTransfer.effectAllowed = 'move';
        event.originalEvent.dataTransfer.setData('text/plain', String($(draggedTemplate).data('id')));
    });

    $('#templateGrid').on('dragend', '.template-card-drag', function () {
        if (draggedTemplate) $(draggedTemplate).removeClass('is-dragging');
        $('#templateGrid .template-card').removeClass('is-drag-over');
        draggedTemplate = null;
    });

    $('#templateGrid').on('dragover', '.template-card', function (event) {
        if (!draggedTemplate || draggedTemplate === this) return;
        event.preventDefault();
        event.originalEvent.dataTransfer.dropEffect = 'move';
        $('#templateGrid .template-card').removeClass('is-drag-over');
        $(this).addClass('is-drag-over');
    });

    $('#templateGrid').on('dragleave', '.template-card', function () {
        $(this).removeClass('is-drag-over');
    });

    $('#templateGrid').on('drop', '.template-card', function (event) {
        if (!draggedTemplate || draggedTemplate === this) return;
        event.preventDefault();
        $(this).removeClass('is-drag-over');

        const grid = $('#templateGrid')[0];
        const cards = [...grid.querySelectorAll('.template-card')];
        const draggedIndex = cards.indexOf(draggedTemplate);
        const targetIndex = cards.indexOf(this);

        if (draggedIndex < targetIndex) {
            grid.insertBefore(draggedTemplate, this.nextSibling);
        } else {
            grid.insertBefore(draggedTemplate, this);
        }

        $('#templateGrid .template-card').each(function (index) {
            $(this).attr('data-order', index + 1).data('order', index + 1);
            $(this).find('.template-order-pill').html(`<i class="bi bi-list-ol"></i> Order ${index + 1}`);
        });

        saveTemplateOrder();
    });

    $('#refreshBtn').on('click', loadTemplates);
    $('#addSectionBtn').on('click', openSectionPicker);

    $('#confirmAddSection').on('click', function () {
        const selected = $('#existingSectionSelect').val();
        const typed = $('#newSectionName').val().trim();
        const name = typed || selected;

        if (!name) {
            Swal.fire('Section Required', 'Select an existing section or enter a new section name.', 'warning');
            return;
        }

        if (!availableSections.includes(name)) {
            availableSections.push(name);
        }

        createSection(name);
        sectionModal.hide();
    });

    $('#existingSectionSelect').on('change', function () {
        if ($(this).val()) $('#newSectionName').val('');
    });

    $('#newSectionName').on('input', function () {
        if ($(this).val().trim()) $('#existingSectionSelect').val('');
    });

    $('#addFieldBtn').on('click', function () {
        const firstSection = $('#sectionsBoard .section-card').first();
        const card = createField({}, firstSection, true);
        $('.template-builder-modal .modal-body').animate({scrollTop: firstSection.position().top}, 200);
        card.find('.field-label').focus();
    });

    $('#sectionsBoard').on('click', '.addFieldToSection', function () {
        const section = $(this).closest('.section-card');
        const card = createField({}, section, true);
        card.find('.field-label').focus();
    });

    $('#sectionsBoard').on('click', '.removeField', function () {
        $(this).closest('.field-card').remove();
        updateBuilderState();
        validateKeys(false);
    });

    $('#sectionsBoard').on('click', '.removeSection', function () {
        const section = $(this).closest('.section-card');
        const count = section.find('.field-card').length;

        if (count > 0) {
            Swal.fire({
                title: 'Remove this section?',
                text: 'All fields inside this section will also be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remove Section'
            }).then(function (result) {
                if (result.isConfirmed) {
                    section.remove();
                    updateBuilderState();
                    validateKeys(false);
                }
            });
        } else {
            section.remove();
            updateBuilderState();
        }
    });

    $('#sectionsBoard').on('input', '.section-title-input', updateBuilderState);

    $('#sectionsBoard').on('input', '.field-label', function () {
        const card = $(this).closest('.field-card');
        card.find('.field-card-title').text($(this).val() || 'New Field');
        const keyInput = card.find('.field-key');

        if (!keyInput.data('touched')) {
            keyInput.val(slugify($(this).val())).trigger('input');
        }
    });

    $('#sectionsBoard').on('keydown', '.field-key', function () {
        $(this).data('touched', true);
    });

    $('#sectionsBoard').on('input', '.field-key', function () {
        $(this).val(slugify($(this).val()));
        validateKeys(false);
    });

    $('#sectionsBoard').on('change', '.field-type', function () {
        const card = $(this).closest('.field-card');
        const type = $(this).val();
        card.find('.field-type-chip').text(type);
        card.find('.options-wrap').toggleClass('is-visible', optionTypes(type));
        if (!optionTypes(type)) card.find('.options-input').val('');
    });

    $('#templateGrid').on('click', '.editBtn', function () {
        const id = $(this).data('id');
        clearErrors();

        $.when(refreshSections(), $.get(routeUrl('show', id))).done(function (sectionResponse, formResponse) {
            const response = formResponse[0];
            $('#record_id').val(response.id);
            $('#name').val(response.name);
            $('#type').val(response.type);
            $('#description').val(response.description);
            $('#is_active').val(response.is_active ? 1 : 0);
            $('#sort_order').val(Math.max(1, Number(response.sort_order) || 1));
            $('#sectionsBoard').empty();

            const sectionMap = new Map();
            (response.fields || []).forEach(function (field) {
                const sectionName = field.section || 'General Information';
                if (!sectionMap.has(sectionName)) {
                    const section = createSection(sectionName);
                    sectionMap.set(sectionName, section);
                }
                createField(field, sectionMap.get(sectionName), false);
            });

            if (!(response.fields || []).length) {
                const section = createSection('General Information');
                createField({}, section, true);
            }

            $('#modalTitle').text('Edit Form Template');
            updateBuilderState();
            formModal.show();
        });
    });

    $('#templateGrid').on('click', '.deleteBtn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete template?',
            text: 'Templates with existing submissions cannot be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: routeUrl('destroy', id),
                type: 'DELETE',
                success: function (response) {
                    showToast('success', response.message);
                    loadTemplates();
                    refreshSections();
                },
                error: function (xhr) {
                    Swal.fire('Unable to Delete', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                }
            });
        });
    });

    $('#templateForm').on('submit', function (event) {
        event.preventDefault();
        clearErrors();
        normalizeNames();

        if (!$('#sectionsBoard .field-card').length) {
            Swal.fire('No Fields', 'Add at least one field before saving the template.', 'warning');
            return;
        }

        if (!validateKeys(true)) {
            Swal.fire('Duplicate Field Keys', 'Each field key must be unique before saving.', 'error');
            return;
        }

        const id = $('#record_id').val();
        const url = id ? routeUrl('update', id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $('#saveBtn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function (response) {
                formModal.hide();
                showToast('success', response.message);
                loadTemplates();
                refreshSections();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    Object.entries(errors).forEach(function ([key, value]) {
                        if (key.includes('field_key')) {
                            $('#duplicateAlert').removeClass('d-none').find('span').text(value[0]);
                        }
                    });
                    Swal.fire('Validation Error', 'Please check the template settings and fields.', 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                }
            },
            complete: function () {
                $('#saveBtn').prop('disabled', false).text(id ? 'Update Template' : 'Save Template');
            }
        });
    });

    // Arm native dragging from the grip handles so form controls remain fully editable.
    $('#sectionsBoard').on('mousedown touchstart', '.section-drag-handle', function () {
        $(this).closest('.section-card').attr('draggable', 'true');
    });
    $('#sectionsBoard').on('mousedown touchstart', '.field-drag-handle', function () {
        $(this).closest('.field-card').attr('draggable', 'true');
    });

    // Native drag-and-drop for sections.
    $('#sectionsBoard').on('dragstart', '.section-card', function (event) {
        if ($(event.target).closest('.field-card').length) return;
        draggedSection = this;
        $(this).addClass('dragging');
        event.originalEvent.dataTransfer.effectAllowed = 'move';
    });

    $('#sectionsBoard').on('dragend', '.section-card', function () {
        $(this).removeClass('dragging');
        $('.section-card').removeClass('drag-over');
        draggedSection = null;
        updateBuilderState();
    });

    $('#sectionsBoard').on('dragover', '.section-card', function (event) {
        if (!draggedSection || draggedSection === this) return;
        event.preventDefault();
        $(this).addClass('drag-over');
    });

    $('#sectionsBoard').on('dragleave', '.section-card', function () {
        $(this).removeClass('drag-over');
    });

    $('#sectionsBoard').on('drop', '.section-card', function (event) {
        if (!draggedSection || draggedSection === this) return;
        event.preventDefault();
        $(this).removeClass('drag-over');

        const board = $('#sectionsBoard')[0];
        const targetRect = this.getBoundingClientRect();
        if (event.originalEvent.clientY < targetRect.top + targetRect.height / 2) {
            board.insertBefore(draggedSection, this);
        } else {
            board.insertBefore(draggedSection, this.nextSibling);
        }
        updateBuilderState();
    });

    // Native drag-and-drop for fields.
    $('#sectionsBoard').on('dragstart', '.field-card', function (event) {
        draggedField = this;
        $(this).addClass('dragging');
        event.stopPropagation();
        event.originalEvent.dataTransfer.effectAllowed = 'move';
    });

    $('#sectionsBoard').on('dragend', '.field-card', function () {
        $(this).removeClass('dragging');
        $('.field-dropzone').removeClass('drag-over');
        draggedField = null;
        updateBuilderState();
    });

    $('#sectionsBoard').on('dragover', '.field-dropzone', function (event) {
        if (!draggedField) return;
        event.preventDefault();
        event.stopPropagation();
        $(this).addClass('drag-over');
    });

    $('#sectionsBoard').on('dragleave', '.field-dropzone', function () {
        $(this).removeClass('drag-over');
    });

    $('#sectionsBoard').on('drop', '.field-dropzone', function (event) {
        if (!draggedField) return;
        event.preventDefault();
        event.stopPropagation();
        $(this).removeClass('drag-over');

        const pointerY = event.originalEvent.clientY;
        const siblings = [...this.querySelectorAll('.field-card:not(.dragging)')];
        const next = siblings.find(function (element) {
            const rect = element.getBoundingClientRect();
            return pointerY < rect.top + rect.height / 2;
        });

        this.insertBefore(draggedField, next || null);
        updateBuilderState();
    });

    refreshSections();
    loadTemplates();
});
</script>
@endpush
