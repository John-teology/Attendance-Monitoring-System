@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Data Management</h2>
        <p class="text-muted">Manage dropdown options for Departments, Courses, Designations, and Year Levels.</p>
    </div>
</div>

@php
    $activeType = request()->query('tab', 'department');
    $idFromType = function($t) { return \Illuminate\Support\Str::plural($t === 'year_level' ? 'year' : $t); };
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
        <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto text-nowrap pb-1" id="dataTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link {{ $activeType === 'department' ? 'active' : '' }}" id="dept-tab" data-bs-toggle="tab" data-bs-target="#departments" type="button" role="tab">Departments</button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeType === 'course' ? 'active' : '' }}" id="course-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab">Courses</button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeType === 'designation' ? 'active' : '' }}" id="desig-tab" data-bs-toggle="tab" data-bs-target="#designations" type="button" role="tab">Designations</button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeType === 'year_level' ? 'active' : '' }}" id="year-tab" data-bs-toggle="tab" data-bs-target="#years" type="button" role="tab">Year Levels</button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeType === 'terms' ? 'active' : '' }}" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">Terms</button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="dataTabsContent">
            
            @foreach(['department' => $departments, 'course' => $courses, 'designation' => $designations, 'year_level' => $year_levels] as $type => $items)
            <div class="tab-pane fade {{ $activeType === $type ? 'show active' : '' }}" id="{{ $idFromType($type) }}" role="tabpanel">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
                    <h5 class="fw-bold mb-0">{{ ucfirst(str_replace('_', ' ', $type)) }} List</h5>
                    <div class="page-actions">
                        <button class="btn btn-danger btn-sm btn-bulk-delete d-none" data-type="{{ $type }}">
                            <i class="fas fa-trash me-1"></i> Delete Selected
                        </button>
                        <a href="{{ route('admin.lookups.template', $type) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i> Template
                        </a>
                        <button class="btn btn-success btn-sm btn-import" data-type="{{ $type }}">
                            <i class="fas fa-file-import me-1"></i> Import
                        </button>
                        <button class="btn btn-primary btn-sm btn-add" data-type="{{ $type }}">
                            <i class="fas fa-plus me-1"></i> Add New
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input select-all" type="checkbox" data-type="{{ $type }}">
                                    </div>
                                </th>
                                <th class="ps-2">Name</th>
                                @if($type === 'year_level')
                                <th class="ps-2">Term Type</th>
                                @endif
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input item-select" type="checkbox" value="{{ $item->id }}" data-type="{{ $type }}">
                                    </div>
                                </td>
                                <td class="ps-2">{{ $item->name }}</td>
                                @if($type === 'year_level')
                                <td class="ps-2">{{ $item->term_type ?? 'semestral' }}</td>
                                @endif
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border btn-edit" 
                                            data-id="{{ $item->id }}" 
                                            data-name="{{ $item->name }}"
                                            @if($type === 'year_level') data-term-type="{{ $item->term_type ?? 'semestral' }}" @endif
                                            data-type="{{ $type }}">
                                        <i class="fas fa-edit text-warning"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border btn-delete" 
                                            data-id="{{ $item->id }}" 
                                            data-type="{{ $type }}">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            <div class="tab-pane fade {{ $activeType === 'terms' ? 'show active' : '' }}" id="terms" role="tabpanel">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
                    <h5 class="fw-bold mb-0">Terms List</h5>
                    <div class="page-actions">
                        <a href="{{ url('/admin/terms/template') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i> Template
                        </a>
                        <button class="btn btn-success btn-sm" id="btn-import-terms-inline">
                            <i class="fas fa-file-import me-1"></i> Import
                        </button>
                        <button class="btn btn-primary btn-sm" id="btn-add-term">
                            <i class="fas fa-plus me-1"></i> Add Term
                        </button>
                        <button class="btn btn-danger btn-sm d-none" id="btn-bulk-delete-terms-inline">
                            <i class="fas fa-trash me-1"></i> Delete Selected
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-terms-inline" type="checkbox">
                                    </div>
                                </th>
                                <th class="ps-4 th-sort" data-index="0">Name <span class="sort-indicator"></span></th>
                                <th class="th-sort" data-index="1">Type <span class="sort-indicator"></span></th>
                                <th class="th-sort" data-index="2">Year Levels <span class="sort-indicator"></span></th>
                                <th class="th-sort" data-index="3">Academic Year <span class="sort-indicator"></span></th>
                                <th class="th-sort" data-index="4">Date Range <span class="sort-indicator"></span></th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($terms as $term)
                            <tr>
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input term-select-inline" type="checkbox" value="{{ $term->id }}">
                                    </div>
                                </td>
                                <td class="ps-4">{{ $term->name }}</td>
                                <td>{{ ucfirst($term->type) }}</td>
                                <td>
                                    @php $yls = $term->yearLevels->pluck('name')->toArray(); @endphp
                                    {{ empty($yls) ? '-' : implode(', ', $yls) }}
                                </td>
                                <td>{{ $term->academic_year }}</td>
                                <td>{{ $term->start_date }} &mdash; {{ $term->end_date }}</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border btn-edit-term"
                                            data-id="{{ $term->id }}"
                                            data-name="{{ $term->name }}"
                                            data-type="{{ $term->type }}"
                                            data-year="{{ $term->academic_year }}"
                                            data-year-levels="{{ implode(',', $term->yearLevels->pluck('id')->toArray()) }}"
                                            data-start="{{ $term->start_date }}"
                                            data-end="{{ $term->end_date }}">
                                        <i class="fas fa-edit text-warning"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border btn-delete-term" data-id="{{ $term->id }}">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No terms defined.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Import Terms Modal -->
            <div class="modal fade" id="importTermsInlineModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form id="importTermsInlineForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header bg-light">
                                <h5 class="modal-title fw-bold">Import Terms</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label">Select File (CSV/Excel)</label>
                                    <input type="file" name="file" class="form-control" required accept=".csv,.xlsx,.xls,.txt">
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success" id="btn-import-terms-inline-submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="lookupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="lookupForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="itemName" class="form-control" required>
                    </div>
                    <div class="mb-3 d-none" id="termTypeGroup">
                        <label class="form-label">Term Type</label>
                        <select name="term_type" id="termType" class="form-select">
                            <option value="semestral">Semestral</option>
                            <option value="quarteral">Quarteral</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importLookupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="importForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Import Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Select File (CSV/Excel)</label>
                        <input type="file" name="file" class="form-control" required accept=".csv, .xlsx, .xls">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="btn-import">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Term Add/Edit Modal -->
<div class="modal fade" id="termModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="termForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="POST" id="termFormMethod">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="termModalTitle">Add Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="termName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="termType" class="form-select" required>
                            <option value="semestral">Semestral</option>
                            <option value="quarteral">Quarteral</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year Levels</label>
                        <select name="year_level_ids[]" id="termYearLevel" class="form-select" multiple required>
                            @foreach($year_levels as $yl)
                                <option value="{{ $yl->id }}">{{ $yl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year (e.g., 2026)</label>
                        <input type="number" name="academic_year" id="termYear" class="form-control" min="2000" max="2100" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="termStart" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="termEnd" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-term">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var namespace = '.lookups_page';
        var baseUrl = window.location.protocol + '//' + window.location.host + '/admin/data-management';
        var termsBase = window.location.protocol + '//' + window.location.host + '/admin/terms';
        var currentType = null;

        var lookupModalEl = document.getElementById('lookupModal');
        var importModalEl = document.getElementById('importLookupModal');
        var lookupModal = lookupModalEl ? new bootstrap.Modal(lookupModalEl) : null;
        var importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;

        function titleCaseType(type) {
            return type.replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        }

        $(document).off(namespace);

        var activeTab = null;
        try { activeTab = localStorage.getItem('activeLookupTab'); } catch (e) { activeTab = null; }
        if (activeTab) {
            var tabBtn = document.querySelector('#dataTabs button[data-bs-target="' + activeTab + '"]');
            if (tabBtn) {
                new bootstrap.Tab(tabBtn).show();
            }
        }

        $(document).on('shown.bs.tab' + namespace, '#dataTabs button[data-bs-toggle="tab"]', function(e) {
            try { localStorage.setItem('activeLookupTab', $(e.target).attr('data-bs-target')); } catch (err) {}
            var target = $(e.target).attr('data-bs-target');
            if (target === '#departments') currentType = 'department';
            else if (target === '#courses') currentType = 'course';
            else if (target === '#designations') currentType = 'designation';
            else if (target === '#years') currentType = 'year_level';
            else if (target === '#terms') currentType = 'terms';
        });

        $(document).on('click' + namespace, '.btn-add', function() {
            var type = $(this).data('type');
            currentType = type;
            $('#lookupForm')[0].reset();
            $('#modalTitle').text('Add ' + titleCaseType(type));
            $('#formMethod').val('POST');
            $('#lookupForm').attr('action', baseUrl + '/' + type);
            if (type === 'year_level') {
                $('#termTypeGroup').removeClass('d-none');
            } else {
                $('#termTypeGroup').addClass('d-none');
            }
            if (lookupModal) lookupModal.show();
        });

        $(document).on('click' + namespace, '.btn-edit', function() {
            var type = $(this).data('type');
            currentType = type;
            var id = $(this).data('id');
            var name = $(this).data('name');

            $('#lookupForm')[0].reset();
            $('#modalTitle').text('Edit ' + titleCaseType(type));
            $('#formMethod').val('PUT');
            $('#itemName').val(name);
            $('#lookupForm').attr('action', baseUrl + '/' + type + '/' + id);
            if (type === 'year_level') {
                $('#termTypeGroup').removeClass('d-none');
                var termType = $(this).data('term-type') || 'semestral';
                $('#termType').val(termType);
            } else {
                $('#termTypeGroup').addClass('d-none');
            }
            if (lookupModal) lookupModal.show();
        });

        $(document).on('click' + namespace, '.btn-delete', function() {
            if (!confirm('Are you sure?')) return;
            var type = $(this).data('type');
            var id = $(this).data('id');

            $.ajax({
                url: baseUrl + '/' + type + '/' + id,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: 'DELETE'
                },
                success: function(res) {
                    if (res && res.success) {
                        var url = baseUrl + '?tab=' + encodeURIComponent(type);
                        if (window.loadSpaPage) { window.loadSpaPage(url); } else { window.location.href = url; }
                    } else {
                        alert((res && res.message) ? res.message : 'Error deleting item.');
                    }
                },
                error: function(xhr) {
                    var msg = 'Error deleting item.';
                    try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg; } catch (e) {}
                    alert(msg);
                }
            });
        });

        $(document).on('submit' + namespace, '#lookupForm', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btn-save');
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(res) {
                    if (res && res.success) {
                        if (lookupModal) lookupModal.hide();
                        var url = baseUrl + '?tab=' + encodeURIComponent(currentType || 'department');
                        if (window.loadSpaPage) { window.loadSpaPage(url); } else { window.location.href = url; }
                    } else {
                        alert((res && res.message) ? res.message : 'Error saving item.');
                    }
                    btn.prop('disabled', false).text('Save');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Save');
                    var msg = 'Unknown error';
                    try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg; } catch (e) {}
                    alert('Error: ' + msg);
                }
            });
        });

        $(document).on('click' + namespace, '.btn-import', function() {
            var type = $(this).data('type');
            currentType = type;
            $('#importForm')[0].reset();
            $('#importForm').attr('action', baseUrl + '/' + type + '/import');
            if (importModal) importModal.show();
        });

        $(document).on('submit' + namespace, '#importForm', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btn-import');
            var formData = new FormData(this);

            btn.prop('disabled', true).html('Uploading...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res && res.success) {
                        if (importModal) importModal.hide();
                        alert(res.message || 'Imported successfully.');
                        var url = baseUrl + '?tab=' + encodeURIComponent(currentType || 'department');
                        if (window.loadSpaPage) { window.loadSpaPage(url); } else { window.location.href = url; }
                    } else {
                        alert((res && res.message) ? res.message : 'Error importing data.');
                    }
                    btn.prop('disabled', false).html('Upload');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('Upload');
                    var msg = 'Unknown error';
                    try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg; } catch (e) {}
                    alert('Error: ' + msg);
                }
            });
        });

        function updateBatchDeleteButton(type) {
            var count = $('.item-select[data-type=\"' + type + '\"]:checked').length;
            var btn = $('.btn-bulk-delete[data-type=\"' + type + '\"]');
            if (count > 0) {
                btn.removeClass('d-none').text('Delete Selected (' + count + ')');
            } else {
                btn.addClass('d-none');
            }
        }

        $(document).on('change' + namespace, '.select-all', function() {
            var type = $(this).data('type');
            var isChecked = $(this).is(':checked');
            $('.item-select[data-type=\"' + type + '\"]').prop('checked', isChecked);
            updateBatchDeleteButton(type);
        });

        $(document).on('change' + namespace, '.item-select', function() {
            var type = $(this).data('type');
            var total = $('.item-select[data-type=\"' + type + '\"]').length;
            var checked = $('.item-select[data-type=\"' + type + '\"]:checked').length;

            $('.select-all[data-type=\"' + type + '\"]').prop('checked', total === checked && total > 0);
            updateBatchDeleteButton(type);
        });

        $(document).on('click' + namespace, '.btn-bulk-delete', function() {
            var type = $(this).data('type');
            var selectedIds = [];

            $('.item-select[data-type=\"' + type + '\"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) return;
            if (!confirm('Are you sure you want to delete ' + selectedIds.length + ' selected items?')) return;

            var btn = $(this);
            btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: baseUrl + '/' + type + '/bulk-destroy',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds
                },
                success: function(res) {
                    if (res && res.success) {
                        alert(res.message || 'Deleted successfully.');
                        if (window.loadSpaPage) {
                            window.loadSpaPage(window.location.href);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert((res && res.message) ? res.message : 'Error deleting items.');
                        btn.prop('disabled', false).text('Delete Selected (' + selectedIds.length + ')');
                    }
                },
                error: function(xhr) {
                    var msg = 'Unknown error';
                    try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg; } catch (e) {}
                    alert('Error: ' + msg);
                    btn.prop('disabled', false).text('Delete Selected (' + selectedIds.length + ')');
                }
            });
        });
        // Terms CRUD
        var termModalEl = document.getElementById('termModal');
        var termModal = termModalEl ? new bootstrap.Modal(termModalEl) : null;
        var importTermsInlineEl = document.getElementById('importTermsInlineModal');
        var importTermsInline = importTermsInlineEl ? new bootstrap.Modal(importTermsInlineEl) : null;

        $(document).on('click' + namespace, '#btn-add-term', function() {
            $('#termForm')[0].reset();
            $('#termModalTitle').text('Add Term');
            $('#termFormMethod').val('POST');
            $('#termForm').attr('action', termsBase);
            if ($.fn.select2) { try { $('#termYearLevel').select2('destroy'); } catch (e) {} $('#termYearLevel').select2({ dropdownParent: $('#termModal'), width: '100%' }); }
            if (termModal) termModal.show();
        });

        $(document).on('click' + namespace, '.btn-edit-term', function() {
            $('#termForm')[0].reset();
            $('#termModalTitle').text('Edit Term');
            $('#termFormMethod').val('PUT');
            $('#termName').val($(this).data('name'));
            $('#termType').val($(this).data('type'));
            var ylVal = $(this).data('year-levels');
            var ylArr = Array.isArray(ylVal) ? ylVal.map(function(v){ return ''+v; }) : (ylVal ? (''+ylVal).split(',') : []);
            $('#termYearLevel').val(ylArr);
            $('#termYear').val($(this).data('year'));
            $('#termStart').val($(this).data('start'));
            $('#termEnd').val($(this).data('end'));
            var id = $(this).data('id');
            $('#termForm').attr('action', termsBase + '/' + id);
            if ($.fn.select2) { try { $('#termYearLevel').select2('destroy'); } catch (e) {} $('#termYearLevel').select2({ dropdownParent: $('#termModal'), width: '100%' }); }
            if (termModal) termModal.show();
        });

        $(document).on('click' + namespace, '#btn-import-terms-inline', function() {
            $('#importTermsInlineForm')[0].reset();
            $('#importTermsInlineForm').attr('action', termsBase + '/import');
            if (importTermsInline) importTermsInline.show();
        });
        $(document).on('submit' + namespace, '#importTermsInlineForm', function(e) {
            e.preventDefault();
            var btn = $('#btn-import-terms-inline-submit');
            btn.prop('disabled', true).text('Uploading...');
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (importTermsInline) importTermsInline.hide();
                    window.location.href = baseUrl + '?tab=terms';
                },
                error: function() {
                    btn.prop('disabled', false).text('Upload');
                    alert('Import failed');
                }
            });
        });
        $(document).on('submit' + namespace, '#termForm', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btn-save-term');
            btn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(res) {
                    if (res && res.success) {
                        if (termModal) termModal.hide();
                        window.location.href = baseUrl + '?tab=terms';
                    } else {
                        alert((res && res.message) ? res.message : 'Error saving term.');
                    }
                    btn.prop('disabled', false).text('Save');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Save');
                    var msg = 'Unknown error';
                    try { msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg; } catch (e) {}
                    alert('Error: ' + msg);
                }
            });
        });

        $(document).on('click' + namespace, '.btn-delete-term', function() {
            if (!confirm('Delete this term?')) return;
            var id = $(this).data('id');
            $.ajax({
                url: termsBase + '/' + id,
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
                success: function(res) {
                    if (res && res.success) {
                        window.location.href = baseUrl + '?tab=terms';
                    } else {
                        alert((res && res.message) ? res.message : 'Error deleting term.');
                    }
                },
                error: function() {
                    alert('Error deleting term.');
                }
            });
        });

        $(document).on('click' + namespace, '#terms table thead th.th-sort', function() {
            var idx = parseInt($(this).data('index'));
            var dir = $(this).data('dir') === 'asc' ? 'desc' : 'asc';
            $('#terms table thead th.th-sort').data('dir', '').find('.sort-indicator').text('');
            $(this).data('dir', dir).find('.sort-indicator').text(dir === 'asc' ? '▲' : '▼');
            var rows = $('#terms table tbody tr').get();
            rows.sort(function(a, b) {
                var ta = $(a).children('td').eq(idx).text().trim();
                var tb = $(b).children('td').eq(idx).text().trim();
                var va = ta.toLowerCase();
                var vb = tb.toLowerCase();
                if (idx === 3) {
                    va = parseInt(ta, 10) || 0;
                    vb = parseInt(tb, 10) || 0;
                } else if (idx === 4) {
                    var sa = ta.split('—')[0].trim();
                    var sb = tb.split('—')[0].trim();
                    va = Date.parse(sa) || 0;
                    vb = Date.parse(sb) || 0;
                }
                if (va < vb) return -1;
                if (va > vb) return 1;
                return 0;
            });
            if (dir === 'desc') rows.reverse();
            var tbody = $('#terms table tbody');
            $.each(rows, function(_, r) { tbody.append(r); });
        });
        function updateInlineBulkDelete() {
            var count = $('.term-select-inline:checked').length;
            var btn = $('#btn-bulk-delete-terms-inline');
            if (count > 0) {
                btn.removeClass('d-none').text('Delete Selected (' + count + ')');
            } else {
                btn.addClass('d-none');
            }
        }
        $(document).on('change' + namespace, '.select-all-terms-inline', function() {
            var checked = $(this).is(':checked');
            $('.term-select-inline').prop('checked', checked);
            updateInlineBulkDelete();
        });
        $(document).on('change' + namespace, '.term-select-inline', function() {
            var total = $('.term-select-inline').length;
            var checked = $('.term-select-inline:checked').length;
            $('.select-all-terms-inline').prop('checked', total > 0 && total === checked);
            updateInlineBulkDelete();
        });
        $(document).on('click' + namespace, '#btn-bulk-delete-terms-inline', function() {
            var ids = [];
            $('.term-select-inline:checked').each(function() { ids.push($(this).val()); });
            if (ids.length === 0) return;
            if (!confirm('Delete ' + ids.length + ' selected terms?')) return;
            var btn = $(this);
            btn.prop('disabled', true).text('Deleting...');
            $.ajax({
                url: termsBase + '/bulk-destroy',
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: ids },
                success: function(res) {
                    if (res && res.success) {
                        window.location.href = baseUrl + '?tab=terms';
                    } else {
                        alert((res && res.message) ? res.message : 'Error deleting terms.');
                        btn.prop('disabled', false).text('Delete Selected (' + ids.length + ')');
                    }
                },
                error: function() {
                    alert('Error deleting terms.');
                    btn.prop('disabled', false).text('Delete Selected (' + ids.length + ')');
                }
            });
        });
    });
</script>
<style>
    #terms table thead th.th-sort { cursor: pointer; }
</style>
@endsection
