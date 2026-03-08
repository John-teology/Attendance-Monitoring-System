<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Info</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="ps-4">
                            <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                        </td>
                        <td class="fw-bold text-primary">{{ $user->id_number }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user text-secondary"></i>
                                </div>
                                <div>{{ $user->full_name }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="small text-muted">
                                @if($user->department)
                                    <div><i class="fas fa-building me-1"></i> {{ $user->department->name ?? '' }}</div>
                                @endif
                                @if($user->course)
                                    <div><i class="fas fa-graduation-cap me-1"></i> {{ $user->course->name ?? '' }}</div>
                                @endif
                                @if($user->yearLevel)
                                    <div><i class="fas fa-layer-group me-1"></i> {{ $user->yearLevel->name ?? '' }}</div>
                                @endif
                                @if($user->designation)
                                    <div><i class="fas fa-id-badge me-1"></i> {{ $user->designation->name ?? '' }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $user->user_type == 'student' ? 'bg-info bg-opacity-10 text-info' : 'bg-warning bg-opacity-10 text-warning' }}">
                                {{ ucfirst($user->user_type) }}
                            </span>
                        </td>
                        <td>
                            @if($user->status == 'active')
                                <span class="badge bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle me-1"></i> Active</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-times-circle me-1"></i> Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light text-primary edit-btn" data-url="{{ route('admin.users.edit', $user->id, false) }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="{{ route('admin.users.download-qr', $user->id, false) }}" class="btn btn-light text-dark" title="Download QR">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <button class="btn btn-light text-danger delete-btn" data-url="{{ route('admin.users.destroy', $user->id, false) }}" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
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
    <div class="card-footer bg-white">
        {{ $users->links() }}
    </div>
</div>
