@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Attendance Reports</h2>
        <p class="text-muted">Generate and extract detailed attendance records.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="page-actions justify-content-md-end">
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i> Export to CSV
        </a>
        @if(in_array(auth('admin')->user()->role, ['admin','super_admin']))
        <button type="button" class="btn btn-outline-danger ms-2" id="btn-purge-logs">
            <i class="fas fa-trash-alt me-1"></i> Delete All Logs
        </button>
        @endif
        </div>
    </div>
</div>

<!-- Report Stats (Optional, maybe specific to reports) -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-light border shadow-sm d-flex align-items-center">
            <i class="fas fa-info-circle text-primary fa-lg me-3"></i>
            <div>
                <strong>Report Generation:</strong> Use the filters below to narrow down the data before exporting.
                The system currently holds a total of <strong>{{ $stats['total_count'] }}</strong> records.
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white pt-4 px-4">
        <h5 class="fw-bold mb-0"><i class="fas fa-filter me-2 text-primary"></i>Filter Reports</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.reports.index') }}" method="GET">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Date Range</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="student" {{ request('user_type') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="faculty" {{ request('user_type') == 'faculty' ? 'selected' : '' }}>Faculty</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                     <label class="form-label small text-muted fw-bold">Course</label>
                     <select name="course" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                     </select>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                     <label class="form-label small text-muted fw-bold">Year Level</label>
                     <select name="year_level" class="form-select">
                        <option value="">All Years</option>
                        @foreach($yearLevels as $yl)
                            <option value="{{ $yl->id }}" {{ request('year_level') == $yl->id ? 'selected' : '' }}>{{ $yl->name }}</option>
                        @endforeach
                     </select>
                </div>
                <div class="col-md-4">
                     <label class="form-label small text-muted fw-bold">Term</label>
                     <select name="term" id="filterTerm" class="form-select">
                        <option value="">All Terms</option>
                        @foreach($terms as $t)
                            <option value="{{ $t->id }}" {{ request('term') == $t->id ? 'selected' : '' }}>
                                {{ $t->academic_year }} • {{ ucfirst($t->type) }} • {{ $t->name }}
                            </option>
                        @endforeach
                     </select>
                </div>
                <div class="col-12 col-md-auto ms-md-auto align-self-end">
                    <div class="page-actions justify-content-end">
                        <button type="submit" class="btn btn-primary text-nowrap">Apply</button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-light border text-nowrap">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="logs-table-container">
    @include('admin.logs.partials.table')
</div>

<script>
    $(document).ready(function() {
        const containerId = '#logs-table-container';
        const namespace = '.logs_page';

        function updateExportUrl() {
            let params = $('form[action="{{ route('admin.reports.index') }}"]').serialize();
            let baseUrl = "{{ route('admin.reports.export') }}";
            $('.btn-success').attr('href', baseUrl + '?' + params);
        }

        function fetchLogs(url) {
            $(containerId).css('opacity', '0.5');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    $(containerId).html(data);
                    $(containerId).css('opacity', '1');
                },
                error: function() {
                    $(containerId).css('opacity', '1');
                    alert('Failed to load logs.');
                }
            });
        }

        $(document).off(namespace);

        // Filter Form
        $(document).on('submit' + namespace, 'form[action="{{ route('admin.reports.index') }}"]', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            fetchLogs(url);
            updateExportUrl();
            window.history.pushState({path: url}, '', url);
        });

        // Pagination
        $(document).on('click' + namespace, containerId + ' .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchLogs(url);
            window.history.pushState({path: url}, '', url);
        });

        // Reset
        $(document).on('click' + namespace, '.btn-light:contains("Reset")', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            $('form[action="{{ route('admin.reports.index') }}"]')[0].reset();
            fetchLogs(url);
            setTimeout(updateExportUrl, 100);
            window.history.pushState({path: url}, '', url);
        });

        // Purge all logs (admin only)
        $(document).on('click' + namespace, '#btn-purge-logs', function() {
            if (!confirm('CRITICAL: This will permanently delete ALL attendance logs. Continue?')) return;
            $.ajax({
                url: "{{ route('admin.reports.purge') }}",
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp) {
                    fetchLogs("{{ route('admin.reports.index') }}");
                    alert((resp && resp.message) ? resp.message : 'All logs deleted.');
                },
                error: function(xhr) {
                    alert('Failed to delete logs.');
                }
            });
        });

        // Initial Load
        if ($(containerId + ' tbody tr').length === 0) {
            $(containerId + ' tbody').html('<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading logs...</div></td></tr>');
            fetchLogs(window.location.href);
        }
    });
</script>
@endsection
