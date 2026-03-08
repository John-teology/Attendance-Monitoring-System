@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Academic Terms</h2>
        <p class="text-muted">Manage semestral and quarteral terms per year level.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.terms.template') }}" class="btn btn-outline-secondary"><i class="fas fa-download me-1"></i> Template</a>
        <button class="btn btn-success" id="btn-import-terms"><i class="fas fa-file-import me-1"></i> Import</button>
        <button class="btn btn-primary" id="btn-add-term"><i class="fas fa-plus me-1"></i> Add Term</button>
    </div>
    <div class="col-12 mt-3">
        <a href="{{ route('admin.lookups.index') }}" class="btn btn-light border"><i class="fas fa-arrow-left me-1"></i> Back to Data Management</a>
    </div>
    <div class="col-12 mt-2">
        <a href="{{ route('admin.terms.index') }}" class="btn btn-outline-secondary">Refresh</a>
    </div>
 </div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 px-4">
        <h5 class="fw-bold mb-0"><i class="fas fa-calendar me-2 text-primary"></i>Terms List</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
                    <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                                <th class="ps-4" style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-terms" type="checkbox">
                                    </div>
                                </th>
                        <th class="ps-4">Name</th>
                        <th>Type</th>
                        <th>Year Levels</th>
                        <th>Academic Year</th>
                        <th>Date Range</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terms as $term)
                    <tr>
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input term-select" type="checkbox" value="{{ $term->id }}">
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
                <div class="mt-3">
                    <button class="btn btn-danger btn-sm d-none" id="btn-bulk-delete-terms">
                        <i class="fas fa-trash me-1"></i> Delete Selected
                    </button>
                </div>
    </div>
</div>

<div class="modal fade" id="importTermsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="importTermsForm" method="POST" enctype="multipart/form-data">
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
                    <button type="submit" class="btn btn-success" id="btn-import-terms-submit">Upload</button>
                </div>
            </form>
        </div>
    </div>
 </div>
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
                            @foreach($yearLevels as $yl)
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
        var modalEl = document.getElementById('termModal');
        var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        var baseUrl = "{{ route('admin.terms.index') }}";
        var importModalEl = document.getElementById('importTermsModal');
        var importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;

        $(document).on('click', '#btn-add-term', function() {
            $('#termForm')[0].reset();
            $('#termModalTitle').text('Add Term');
            $('#termFormMethod').val('POST');
            $('#termForm').attr('action', "{{ route('admin.terms.store') }}");
            if ($.fn.select2) { try { $('#termYearLevel').select2('destroy'); } catch (e) {} $('#termYearLevel').select2({ dropdownParent: $('#termModal'), width: '100%' }); }
            if (modal) modal.show();
        });

        $(document).on('click', '.btn-edit-term', function() {
            $('#termForm')[0].reset();
            $('#termModalTitle').text('Edit Term');
            $('#termFormMethod').val('PUT');
            $('#termName').val($(this).data('name'));
            $('#termType').val($(this).data('type'));
            var ylVal = $(this).data('year-levels');
            var ylArr = Array.isArray(ylVal) ? ylVal.map(function(v){ return ''+v; }) : (ylVal ? (''+ylVal).split(',') : []);
            $('#termYearLevel').val(ylArr);
            if ($.fn.select2) { try { $('#termYearLevel').select2('destroy'); } catch (e) {} $('#termYearLevel').select2({ dropdownParent: $('#termModal'), width: '100%' }); }
            $('#termYear').val($(this).data('year'));
            $('#termStart').val($(this).data('start'));
            $('#termEnd').val($(this).data('end'));
            var id = $(this).data('id');
            $('#termForm').attr('action', baseUrl.replace('/terms', '/terms/' + id));
            if (modal) modal.show();
        });

        $(document).on('click', '#btn-import-terms', function() {
            $('#importTermsForm')[0].reset();
            $('#importTermsForm').attr('action', "{{ route('admin.terms.import') }}");
            if (importModal) importModal.show();
        });
        $(document).on('submit', '#importTermsForm', function(e) {
            e.preventDefault();
            var btn = $('#btn-import-terms-submit');
            btn.prop('disabled', true).text('Uploading...');
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (importModal) importModal.hide();
                    window.location.reload();
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Upload');
                    alert('Import failed');
                }
            });
        });
        $(document).on('submit', '#termForm', function(e) {
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
                        if (modal) modal.hide();
                        window.location.reload();
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

        $(document).on('click', '.btn-delete-term', function() {
            if (!confirm('Delete this term?')) return;
            var id = $(this).data('id');
            $.ajax({
                url: baseUrl.replace('/terms', '/terms/' + id),
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
                success: function(res) {
                    if (res && res.success) {
                        window.location.reload();
                    } else {
                        alert((res && res.message) ? res.message : 'Error deleting term.');
                    }
                },
                error: function() {
                    alert('Error deleting term.');
                }
            });
        });

        function updateBulkDeleteButton() {
            var count = $('.term-select:checked').length;
            var btn = $('#btn-bulk-delete-terms');
            if (count > 0) {
                btn.removeClass('d-none').text('Delete Selected (' + count + ')');
            } else {
                btn.addClass('d-none');
            }
        }
        $(document).on('change', '.select-all-terms', function() {
            var checked = $(this).is(':checked');
            $('.term-select').prop('checked', checked);
            updateBulkDeleteButton();
        });
        $(document).on('change', '.term-select', function() {
            var total = $('.term-select').length;
            var checked = $('.term-select:checked').length;
            $('.select-all-terms').prop('checked', total > 0 && total === checked);
            updateBulkDeleteButton();
        });
        $(document).on('click', '#btn-bulk-delete-terms', function() {
            var ids = [];
            $('.term-select:checked').each(function() { ids.push($(this).val()); });
            if (ids.length === 0) return;
            if (!confirm('Delete ' + ids.length + ' selected terms?')) return;
            var btn = $(this);
            btn.prop('disabled', true).text('Deleting...');
            $.ajax({
                url: baseUrl.replace('/terms', '/terms/bulk-destroy'),
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: ids },
                success: function(res) {
                    if (res && res.success) {
                        window.location.reload();
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
@endsection
