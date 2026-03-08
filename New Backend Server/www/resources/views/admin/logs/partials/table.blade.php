<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 px-4">
         <h5 class="fw-bold mb-0">Detailed Logs</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive logs-table">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 text-muted small border-0 py-3">DATE & TIME</th>
                        <th class="text-muted small border-0 py-3">USER DETAILS</th>
                        <th class="text-muted small border-0 py-3">ID NUMBER</th>
                        <th class="text-muted small border-0 py-3">YEAR LEVEL</th>
                        <th class="text-muted small border-0 py-3">INFO</th>
                        <th class="text-muted small border-0 py-3">TERM</th>
                        <th class="text-muted small border-0 py-3">METHOD</th>
                        <th class="text-muted small border-0 py-3">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ \Carbon\Carbon::createFromTimestampMs($log->scanned_at)->timezone(config('app.timezone'))->format('M d, Y') }}</div>
                            <small class="text-muted">{{ \Carbon\Carbon::createFromTimestampMs($log->scanned_at)->timezone(config('app.timezone'))->format('h:i:s A') }}</small>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2 bg-light rounded-circle text-center" style="width:32px;height:32px;line-height:32px">
                                        <i class="fas fa-user text-secondary small"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span>{{ $log->user->full_name }}</span>
                                        <small class="text-muted">{{ ucfirst($log->user->user_type) }}</small>
                                    </div>
                                </div>
                            @else
                                <span class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> Unknown (ID: {{ $log->user_id }})</span>
                            @endif
                        </td>
                        <td>{{ $log->user ? $log->user->id_number : '-' }}</td>
                        <td>{{ $log->user && $log->user->yearLevel ? $log->user->yearLevel->name : '-' }}</td>
                        <td>
                            @if($log->user && $log->user->user_type == 'student')
                                <div class="d-flex flex-column" style="line-height: 1.2;">
                                    <span class="text-dark fw-bold small">{{ $log->user->department->name ?? '-' }}</span>
                                    <span class="text-muted small">{{ $log->user->course->name ?? '-' }}</span>
                                </div>
                            @elseif($log->user && $log->user->user_type == 'faculty')
                                <span class="small text-muted">{{ $log->user->department->name ?? '-' }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($log->term)
                                <span class="badge bg-white border text-dark">
                                    {{ $log->term->academic_year }} • {{ ucfirst($log->term->type) }} • {{ $log->term->name }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $log->scan_type == 'QR' ? 'bg-white border text-dark' : 'bg-warning text-dark' }} bg-opacity-10 border">
                                <i class="fas fa-{{ $log->scan_type == 'QR' ? 'qrcode' : 'wifi' }} me-1"></i> {{ $log->scan_type }}
                            </span>
                        </td>
                        <td>
                            @if($log->entry_type == 'IN')
                                <span class="badge bg-success w-100 py-2 rounded"><i class="fas fa-sign-in-alt me-1"></i> IN</span>
                            @else
                                <span class="badge bg-danger w-100 py-2 rounded"><i class="fas fa-sign-out-alt me-1"></i> OUT</span>
                            @endif
                        </td>
                        
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                <h6 class="text-secondary fw-bold">No Data Available</h6>
                                <p class="small text-muted mb-0">Try adjusting your filters or search criteria.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">
        {{ $logs->links() }}
    </div>
</div>
<style>
    .logs-table table { table-layout: auto; width: 100%; }
    .logs-table th, .logs-table td { vertical-align: top; }
    .logs-table td { overflow-wrap: anywhere; }
    .logs-table thead th { white-space: nowrap; }
    .logs-table td:nth-child(6) .badge {
        display: inline-block;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
