@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Admin Settings</h2>
        <p class="text-muted">Manage system administrators and access roles.</p>
    </div>
</div>

@if(auth('admin')->user()->role == 'super_admin')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white pt-4 px-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i>System Branding</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.settings.branding') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- Branding Section -->
                    <div class="row align-items-center mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold text-muted" for="school_name">School Name</label>
                            <input type="text" name="school_name" id="school_name" class="form-control" value="{{ $systemSettings->school_name ?? '' }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold text-muted" for="school_logo">School Logo</label>
                            <input type="file" name="school_logo" id="school_logo" class="form-control" accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                            <div class="form-text small">PNG, JPG, SVG</div>
                            @if($systemSettings->school_logo)
                                <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                    <img src="{{ asset('storage/' . $systemSettings->school_logo) }}" alt="Logo" style="height: 30px; width: auto;">
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" name="remove_school_logo" id="removeLogo" value="1">
                                        <label class="form-check-label small text-danger fw-bold" for="removeLogo">Remove</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold text-muted" for="app_background_image">App Background</label>
                            <input type="file" name="app_background_image" id="app_background_image" class="form-control" accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                            <div class="form-text small">PNG, JPG, SVG</div>
                            @if($systemSettings->app_background_image)
                                <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                    <img src="{{ asset('storage/' . $systemSettings->app_background_image) }}" alt="Bg" style="height: 30px; width: 30px; object-fit: cover;" class="rounded">
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" name="remove_app_background_image" id="removeBg" value="1">
                                        <label class="form-check-label small text-danger fw-bold" for="removeBg">Remove</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3 border-bottom pb-2">
                         <h6 class="fw-bold text-muted small mb-0">App Customization (Android Only)</h6>
                    </div>

                    <!-- Customization Section -->
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="school_name_color">School Name Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" id="school_name_color_picker" class="form-control form-control-color" value="{{ $systemSettings->school_name_color ?? '#000000' }}" title="Choose text color" style="cursor: pointer; width: 50px;">
                                <input type="text" name="school_name_color" id="school_name_color" class="form-control" value="{{ $systemSettings->school_name_color ?? '#000000' }}" maxlength="7">
                            </div>
                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                @foreach(['#000000', '#ffffff', '#dc3545', '#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6c757d'] as $color)
                                    <div class="rounded-circle border cursor-pointer js-color-preset" data-target="school_name_color" data-color="{{ $color }}" style="width: 20px; height: 20px; background-color: {{ $color }}; cursor: pointer;" title="{{ $color }}"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="button_bg_color">Button Background</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" id="button_bg_color_picker" class="form-control form-control-color" value="{{ $systemSettings->button_bg_color ?? '#0d6efd' }}" title="Choose button color" style="cursor: pointer; width: 50px;">
                                <input type="text" name="button_bg_color" id="button_bg_color" class="form-control" value="{{ $systemSettings->button_bg_color ?? '#0d6efd' }}" maxlength="7">
                            </div>
                             <div class="mt-2 d-flex gap-1 flex-wrap">
                                @foreach(['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754'] as $color)
                                    <div class="rounded-circle border cursor-pointer js-color-preset" data-target="button_bg_color" data-color="{{ $color }}" style="width: 20px; height: 20px; background-color: {{ $color }}; cursor: pointer;" title="{{ $color }}"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="body_bg_color">Body Background</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" id="body_bg_color_picker" class="form-control form-control-color" value="{{ $systemSettings->body_bg_color ?? '#f8f9fa' }}" title="Choose background color" style="cursor: pointer; width: 50px;">
                                <input type="text" name="body_bg_color" id="body_bg_color" class="form-control" value="{{ $systemSettings->body_bg_color ?? '#f8f9fa' }}" maxlength="7">
                            </div>
                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                @foreach(['#f8f9fa', '#ffffff', '#e9ecef', '#dee2e6', '#ced4da', '#adb5bd', '#6c757d', '#343a40'] as $color)
                                    <div class="rounded-circle border cursor-pointer js-color-preset" data-target="body_bg_color" data-color="{{ $color }}" style="width: 20px; height: 20px; background-color: {{ $color }}; cursor: pointer;" title="{{ $color }}"></div>
                                @endforeach
                            </div>
                        </div>
                         <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="font_style">Font Style</label>
                            <select name="font_style" id="font_style" class="form-select" style="cursor: pointer;">
                                <option value="Sans Serif" {{ ($systemSettings->font_style ?? 'Sans Serif') == 'Sans Serif' ? 'selected' : '' }}>Sans Serif (Standard)</option>
                                <option value="Serif" {{ ($systemSettings->font_style ?? '') == 'Serif' ? 'selected' : '' }}>Serif (Classic)</option>
                                <option value="Monospace" {{ ($systemSettings->font_style ?? '') == 'Monospace' ? 'selected' : '' }}>Monospace (Code)</option>
                                <option value="Cursive" {{ ($systemSettings->font_style ?? '') == 'Cursive' ? 'selected' : '' }}>Cursive (Handwritten)</option>
                                <option value="Casual" {{ ($systemSettings->font_style ?? '') == 'Casual' ? 'selected' : '' }}>Casual (Friendly)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="card_transparency">
                                Card Transparency
                                <span class="badge bg-light text-dark border ms-1" id="card_transparency_val">{{ $systemSettings->card_transparency ?? 80 }}%</span>
                            </label>
                            <input type="range" class="form-range" name="card_transparency" id="card_transparency" min="0" max="100" value="{{ $systemSettings->card_transparency ?? 80 }}" oninput="document.getElementById('card_transparency_val').innerText = this.value + '%'">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="button_transparency">
                                Button Transparency
                                <span class="badge bg-light text-dark border ms-1" id="button_transparency_val">{{ $systemSettings->button_transparency ?? 100 }}%</span>
                            </label>
                            <input type="range" class="form-range" name="button_transparency" id="button_transparency" min="0" max="100" value="{{ $systemSettings->button_transparency ?? 100 }}" oninput="document.getElementById('button_transparency_val').innerText = this.value + '%'">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold text-muted" for="icon_color">Icon Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" id="icon_color_picker" class="form-control form-control-color" value="{{ $systemSettings->icon_color ?? '#10B981' }}" title="Choose icon color" style="cursor: pointer; width: 40px;">
                                <input type="text" name="icon_color" id="icon_color" class="form-control" value="{{ $systemSettings->icon_color ?? '#10B981' }}" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                             <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="enable_volume_control" id="enable_volume_control" value="1" {{ isset($systemSettings->volume_level) ? 'checked' : '' }} onchange="toggleVolumeControl(this)">
                                <label class="form-check-label small fw-bold text-muted" for="enable_volume_control">
                                    Force Volume Level
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="range" class="form-range" name="volume_level" id="volume_level" min="0" max="100" value="{{ $systemSettings->volume_level ?? 80 }}" {{ isset($systemSettings->volume_level) ? '' : 'disabled' }} oninput="document.getElementById('volume_level_val').innerText = this.value + '%'">
                                <span class="badge bg-light text-dark border" id="volume_level_val">{{ $systemSettings->volume_level ?? 80 }}%</span>
                            </div>
                            <div class="form-text small text-muted" style="font-size: 0.75rem;">Overrides user volume if enabled</div>
                        </div>
                    </div>

                    <script>
                        function toggleVolumeControl(checkbox) {
                            var range = document.getElementById('volume_level');
                            range.disabled = !checkbox.checked;
                            if (!checkbox.checked) {
                                // Optional: Reset to default visually or keep last value
                            }
                        }
                    </script>
                    
                    <div class="row mt-2">
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-left: 5px solid #dc3545 !important;">
            <div class="card-header bg-white pt-4 px-4">
                <h5 class="fw-bold mb-0 text-danger"><i class="fas fa-robot me-2"></i>System Limit Tester (Dummy Data)</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This tool is for testing performance limits. It will generate random user data. 
                    Do not use this in a production environment with real data unless necessary.
                </div>
                
                <div class="row g-4">
                    <!-- Generator Form -->
                    <div class="col-md-4">
                        <form action="{{ route('admin.settings.dummy-data') }}" method="POST" onsubmit="return confirm('WARNING: This will generate a large amount of data. Are you sure?');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Number of Dummy Users</label>
                                <select name="count" class="form-select">
                                    <option value="100">100 Users (Quick Test)</option>
                                    <option value="1000">1,000 Users (Load Test)</option>
                                    <option value="5000">5,000 Users (Stress Test)</option>
                                    <option value="10000">10,000 Users (Extreme Limit)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Confirmation</label>
                                <input type="text" name="confirm_text" class="form-control" placeholder="Type 'CONFIRM'" required pattern="CONFIRM">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-bolt me-1"></i> Generate
                            </button>
                        </form>
                    </div>

                    <!-- Fill Dashboard Form -->
                    <div class="col-md-4">
                        <form action="{{ route('admin.settings.dummy-data') }}" method="POST">
                            @csrf
                            <input type="hidden" name="fill_dashboard" value="1">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-success">Populate Dashboard</label>
                                <input type="text" name="confirm_text" class="form-control border-success text-success" placeholder="Type 'FILL'" required pattern="FILL">
                            </div>
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="fas fa-chart-line me-1"></i> Fill with Logs
                            </button>
                        </form>
                    </div>

                    <!-- Purge Column -->
                    <div class="col-md-4">
                        <div class="mb-4">
                            <form action="{{ route('admin.settings.dummy-data') }}" method="POST" onsubmit="return confirm('CRITICAL WARNING: This will delete ALL users (students/faculty) from the database. This action cannot be undone. Are you absolutely sure?');">
                                @csrf
                                <input type="hidden" name="delete_all" value="1">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-danger">Purge All Data</label>
                                    <input type="text" name="confirm_text" class="form-control border-danger text-danger" placeholder="Type 'DELETE ALL'" required pattern="DELETE ALL">
                                </div>
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete All
                                </button>
                            </form>
                        </div>
                        <div>
                            <form action="{{ route('admin.reports.purge') }}" method="POST" onsubmit="return confirm('This will permanently delete ALL attendance logs. Continue?');">
                                @csrf
                                <label class="form-label fw-bold small text-danger">Purge Attendance Logs</label>
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete All Logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(auth('admin')->user()->role == 'super_admin' || auth('admin')->user()->role == 'admin')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="fas fa-database me-2 text-primary"></i>Database Backup</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="text-muted">
                            Download a backup copy of the current SQLite database.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#restoreDbModal">
                                <i class="fas fa-upload me-1"></i> Restore
                            </button>
                            <a href="{{ route('admin.settings.backup-database') }}" class="btn btn-outline-primary" onclick="var btn=this; var original=btn.innerHTML; btn.innerHTML='<i class=\'fas fa-spinner fa-spin me-1\'></i> Processing...'; btn.style.pointerEvents='none'; setTimeout(function(){ btn.innerHTML=original; btn.style.pointerEvents='auto'; }, 5000);">
                                <i class="fas fa-download me-1"></i> Download Backup
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Restore DB Modal -->
@if(auth('admin')->user()->role == 'super_admin' || auth('admin')->user()->role == 'admin')
<div class="modal fade" id="restoreDbModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.restore-database') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('CRITICAL WARNING: This will OVERWRITE the entire database with the uploaded file. Current data will be lost. Are you sure?');">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Restore Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        <strong>Warning:</strong> Uploading a database file will replace the current system data.
                        Ensure the file is a valid <code>.sqlite</code> backup.
                        <br>
                        After restore, you may need to <strong>Rebind</strong> the license if the database fingerprint changes.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Backup File (.sqlite or .enc)</label>
                        <input type="file" name="database_file" class="form-control" accept=".sqlite,.enc" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Restore Database</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Create Admin Form -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white pt-4 px-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-user-shield me-2 text-primary"></i>Add New Admin</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <div class="form-text small">Min. 8 characters</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin">Administrator (Full Access)</option>
                            <option value="editor">Editor (Can Manage Users)</option>
                            <option value="viewer">Viewer (Read Only)</option>
                        </select>
                    </div>
                    @if(auth('admin')->user()->role != 'super_admin')
                        <!-- Only Super Admin can create Super Admins? Actually, only ONE super admin allowed, so no one can create another super admin -->
                    @endif
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus-circle me-1"></i> Create Admin</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin List -->
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white pt-4 px-4">
                <h5 class="fw-bold mb-0">Existing Administrators</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted small border-0 py-3">NAME</th>
                                <th class="text-muted small border-0 py-3">EMAIL</th>
                                <th class="text-muted small border-0 py-3">ROLE</th>
                                <th class="text-muted small border-0 py-3 text-end pe-4">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admins as $admin)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        @php
                                            $roleIcon = $admin->role === 'super_admin' ? 'fa-crown' : ($admin->role === 'admin' ? 'fa-user-shield' : ($admin->role === 'editor' ? 'fa-pen-to-square' : 'fa-eye'));
                                            $roleColor = $admin->role === 'super_admin' ? 'text-danger' : ($admin->role === 'admin' ? 'text-primary' : ($admin->role === 'editor' ? 'text-warning' : 'text-secondary'));
                                        @endphp
                                        <div class="avatar me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fas {{ $roleIcon }} {{ $roleColor }} small"></i>
                                        </div>
                                        <span class="fw-medium">{{ $admin->name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">{{ $admin->email }}</td>
                                <td>
                                    @if($admin->role == 'super_admin')
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill"><i class="fas fa-crown me-1"></i>Super Admin</span>
                                    @elseif($admin->role == 'admin')
                                        <span class="badge bg-light text-primary border px-3 rounded-pill"><i class="fas fa-user-shield me-1"></i>Admin</span>
                                    @elseif($admin->role == 'editor')
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 rounded-pill"><i class="fas fa-pen-to-square me-1"></i>Editor</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 rounded-pill"><i class="fas fa-eye me-1"></i>Viewer</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if(auth('admin')->id() != $admin->id)
                                        <button type="button" class="btn btn-sm btn-light border me-1 {{ $admin->role === 'super_admin' ? 'text-danger' : ($admin->role === 'admin' ? 'text-primary' : ($admin->role === 'editor' ? 'text-warning' : 'text-secondary')) }} js-edit-admin" data-id="{{ $admin->id }}" data-name="{{ addslashes($admin->name) }}" data-email="{{ addslashes($admin->email) }}" title="Edit Profile">
                                            <i class="fas {{ $roleIcon }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light text-primary border me-1 js-pass-admin" data-id="{{ $admin->id }}" data-name="{{ addslashes($admin->name) }}" title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <form action="{{ route('admin.settings.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this admin?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger border" title="Remove Access">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-light border me-1 {{ $admin->role === 'super_admin' ? 'text-danger' : ($admin->role === 'admin' ? 'text-primary' : ($admin->role === 'editor' ? 'text-warning' : 'text-secondary')) }} js-edit-admin" data-id="{{ $admin->id }}" data-name="{{ addslashes($admin->name) }}" data-email="{{ addslashes($admin->email) }}" title="Edit My Profile">
                                            <i class="fas {{ $roleIcon }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light text-primary border me-1 js-pass-admin" data-id="{{ $admin->id }}" data-name="{{ addslashes($admin->name) }}" title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <span class="badge bg-light text-muted border">Current</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAdminForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="editAdminName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="editAdminEmail" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Change Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="passwordForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Changing password for <strong id="adminName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Define functions on window to ensure global availability
    // Use a self-executing function to avoid polluting global scope with variables if not needed,
    // but here we want to attach to window explicitly.
    (function() {
        window.openPasswordModal = function(id, name) {
            let form = document.getElementById('passwordForm');
            if (!form) return; // Guard clause
            
            // Safe URL construction
            let baseUrl = "{{ route('admin.settings.index') }}";
            form.action = baseUrl + "/" + id + "/password";
            
            let nameSpan = document.getElementById('adminName');
            if (nameSpan) nameSpan.textContent = name;
            
            let modalEl = document.getElementById('passwordModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        };
        
        window.openEditModal = function(id, name, email) {
            let form = document.getElementById('editAdminForm');
            if (!form) return; // Guard clause
            
            let baseUrl = "{{ route('admin.settings.index') }}";
            form.action = baseUrl + "/" + id;
            
            let nameInput = document.getElementById('editAdminName');
            let emailInput = document.getElementById('editAdminEmail');
            
            if (nameInput) nameInput.value = name;
            if (emailInput) emailInput.value = email;
            
            let modalEl = document.getElementById('editAdminModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        };
    })();

    // Event delegation to ensure handlers work after SPA navigation
    $(document).on('click', '.js-edit-admin', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var name = $btn.data('name');
        var email = $btn.data('email');
        if (typeof window.openEditModal === 'function') {
            window.openEditModal(id, name, email);
        }
    });
    $(document).on('click', '.js-pass-admin', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var name = $btn.data('name');
        if (typeof window.openPasswordModal === 'function') {
            window.openPasswordModal(id, name);
        }
    });

    // Color Picker Sync Logic
    $(document).ready(function() {
        // Sync color input -> text input
        $('input[type="color"]').on('input change', function() {
            // Get the ID of the text input (remove _picker suffix)
            var targetId = $(this).attr('id').replace('_picker', '');
            $('#' + targetId).val($(this).val());
        });

        // Sync text input -> color input
        $('input[type="text"]').on('input keyup', function() {
            var val = $(this).val();
            // Basic hex validation
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                var pickerId = $(this).attr('id') + '_picker';
                $('#' + pickerId).val(val);
            }
        });

        // Preset Color Click
        $('.js-color-preset').on('click', function() {
            var target = $(this).data('target');
            var color = $(this).data('color');
            
            // Update Text Input
            $('#' + target).val(color);
            
            // Update Picker
            $('#' + target + '_picker').val(color);
        });
    });
</script>
@endsection
