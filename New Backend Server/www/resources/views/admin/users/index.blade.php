@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0" style="color: #800000;">Student & Faculty Management</h2>
        <p class="text-muted">Manage library access for students and faculty members.</p>
    </div>
    <div class="col-md-6 mt-3 mt-md-0">
        <div class="d-flex justify-content-md-end flex-wrap">
            <a href="{{ route('admin.users.export-qr-pdf', request()->query()) }}" class="btn btn-outline-dark btn-sm text-nowrap me-2 mb-2">
                <i class="fas fa-qrcode me-1"></i> Export QRs
            </a>
            <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm text-nowrap me-2 mb-2">
                <i class="fas fa-file-export me-1"></i> Export CSV
            </a>
            <button type="button" class="btn btn-success btn-sm text-nowrap me-2 mb-2" data-bs-toggle="modal" data-bs-target="#importModal" style="background-color: #198754; border-color: #198754;">
                <i class="fas fa-file-import me-1"></i> Import
            </button>
            <button type="button" class="btn btn-info btn-sm text-nowrap text-white me-2 mb-2" data-bs-toggle="modal" data-bs-target="#uploadPhotosModal" style="background-color: #0dcaf0; border-color: #0dcaf0;">
                <i class="fas fa-images me-1"></i> Upload Photos
            </button>
            <button type="button" class="btn btn-danger btn-sm text-nowrap d-none me-2 mb-2" id="btn-bulk-delete">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selected-count">0</span>)
            </button>
            <button type="button" class="btn btn-primary btn-sm text-nowrap mb-2" id="btn-add-user" style="background-color: #800000; border-color: #800000;">
                <i class="fas fa-plus me-1"></i> Add User
            </button>
        </div>
    </div>
</div>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold" style="color: #800000;"><i class="fas fa-filter me-2"></i>Filters & Search</span>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.index') }}" method="GET" class="row g-3">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="user_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="student" {{ request('user_type') == 'student' ? 'selected' : '' }}>Student</option>
                    <option value="faculty" {{ request('user_type') == 'faculty' ? 'selected' : '' }}>Faculty</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select name="year_level" class="form-select">
                    <option value="">All Years</option>
                    @foreach($yearLevels as $year)
                        <option value="{{ $year->id }}" {{ request('year_level') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <div class="page-actions justify-content-end">
                    <button type="submit" class="btn btn-primary text-nowrap" style="background-color: #800000; border-color: #800000;">Apply</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary text-nowrap">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="users-table-container">
    @include('admin.users.partials.table')
</div>

<!-- Import Modal -->
<div class="modal fade" id="uploadPhotosModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.users.upload-photos') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold"><i class="fas fa-images me-2 text-info"></i>Upload Photos (ZIP)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select ZIP File</label>
                        <input type="file" name="file" class="form-control" required accept=".zip">
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle me-1"></i> 
                            Upload a ZIP file containing user photos.<br>
                            Each photo must be named after the <strong>ID Number</strong> or <strong>RFID UID</strong> (e.g., <code>12345.jpg</code>).
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white"><i class="fas fa-upload me-1"></i> Upload Photos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-csv me-2 text-success"></i>Import Users CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 text-end">
                        <small class="text-muted d-block mb-2">Download Templates:</small>
                        <a href="{{ route('admin.users.template', ['type' => 'student']) }}" class="btn btn-sm btn-outline-info me-1">
                            <i class="fas fa-user-graduate me-1"></i> Student
                        </a>
                        <a href="{{ route('admin.users.template', ['type' => 'faculty']) }}" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-chalkboard-teacher me-1"></i> Faculty
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Excel File</label>
                        <input type="file" name="file" class="form-control" required accept=".xlsx, .xls, .csv">
                        <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i> Supports .xlsx with dropdowns.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i> Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="userForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="userModalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- Profile Picture Section -->
                    <div class="text-center mb-4">
                        <div class="mb-3 position-relative d-inline-block">
                            <img id="modalProfilePreview" 
                                 src="{{ asset('img/login-logo.png') }}" 
                                 alt="Profile Picture" 
                                 class="img-thumbnail rounded-circle shadow-sm" 
                                 style="width: 120px; height: 120px; object-fit: cover;">
                            <button type="button" id="btn-remove-photo" class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 shadow-sm d-none" 
                                    style="width: 32px; height: 32px; padding: 0; line-height: 30px; z-index: 100; cursor: pointer;" title="Remove Photo">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="d-block mt-2">
                            <label for="modal_profile_picture" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-camera me-1"></i> Change Photo
                            </label>
                            <input type="file" class="form-control d-none" id="modal_profile_picture" name="profile_picture" accept="image/*">
                            <input type="hidden" name="remove_profile_picture" id="remove_profile_picture" value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">RFID UID (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                <input type="text" name="rfid_uid" class="form-control" placeholder="Scan card or type UID manually">
                            </div>
                            <div class="form-text small">
                                <i class="fas fa-info-circle me-1"></i> 
                                By default, this will be set to the <strong>ID Number</strong>, but you can change it anytime.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User Type</label>
                        <select name="user_type" id="modal_user_type" class="form-select" required>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                        </select>
                    </div>

                    <!-- Common Fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID Expiration Date</label>
                            <input type="date" name="id_expiration_date" class="form-control">
                        </div>
                    </div>

                    <!-- Student Specific Fields -->
                    <div id="modal_student_fields">
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select">
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Year Level</label>
                            <select name="year_level_id" class="form-select">
                                <option value="">Select Year Level</option>
                                @foreach($yearLevels as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Faculty Specific Fields -->
                    <div id="modal_faculty_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Designation</label>
                            <select name="designation_id" class="form-select">
                                <option value="">Select Designation</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-user">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
  <div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <i class="fas fa-info-circle me-2" id="toast-icon"></i>
        <span id="toast-message">Notification</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
    $(document).ready(function() {
        const containerId = '#users-table-container';
        const namespace = '.users_page'; // Unique namespace for this page's events
        const defaultProfilePicUrl = '{{ asset('img/login-logo.png') }}';

        // Toast Helper
        function showToast(message, type = 'success') {
            const toastEl = document.getElementById('liveToast');
            const toast = new bootstrap.Toast(toastEl);
            
            $('#toast-message').text(message);
            
            if (type === 'success') {
                $(toastEl).removeClass('text-bg-danger').addClass('text-bg-success');
                $('#toast-icon').removeClass('fa-exclamation-circle').addClass('fa-check-circle');
            } else {
                $(toastEl).removeClass('text-bg-success').addClass('text-bg-danger');
                $('#toast-icon').removeClass('fa-check-circle').addClass('fa-exclamation-circle');
            }
            
            toast.show();
        }

        // Helper to toggle fields in modal
        function toggleModalFields() {
            const type = $('#modal_user_type').val();
            if (type === 'student') {
                $('#modal_student_fields').show();
                $('#modal_faculty_fields').hide();
            } else {
                $('#modal_student_fields').hide();
                $('#modal_faculty_fields').show();
            }
        }

        // Bind change event for modal select
        $(document).on('change' + namespace, '#modal_user_type', toggleModalFields);

        // Profile Picture Preview
        $(document).off('change', '#modal_profile_picture').on('change', '#modal_profile_picture', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#modalProfilePreview').attr('src', e.target.result);
                    $('#btn-remove-photo').removeClass('d-none'); // Show remove button
                    $('#remove_profile_picture').val('0'); // Reset removal flag
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Remove Photo Action
        $(document).off('click', '#btn-remove-photo').on('click', '#btn-remove-photo', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#modalProfilePreview').attr('src', defaultProfilePicUrl); // Reset to default
            $('#modal_profile_picture').val(''); // Clear file input
            $('#remove_profile_picture').val('1'); // Set removal flag
            $(this).addClass('d-none'); // Hide remove button
        });

        // Function to fetch data
        function fetchUsers(url) {
            $(containerId).css('opacity', '0.5');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    $(containerId).html(data);
                    $(containerId).css('opacity', '1');
                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                    $(containerId).css('opacity', '1');
                    showToast('Failed to load data.', 'error');
                }
            });
        }

        // UNBIND PREVIOUS EVENTS FIRST
        $(document).off(namespace);

        // 1. Filter Form Submission
        $(document).on('submit' + namespace, 'form[action="{{ route('admin.users.index') }}"]', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            fetchUsers(url);
            window.history.pushState({path: url}, '', url);
        });

        // 2. Pagination Links
        $(document).on('click' + namespace, containerId + ' .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            fetchUsers(url);
            window.history.pushState({path: url}, '', url);
        });

        // 3. Reset Button
        $(document).on('click' + namespace, '.btn-outline-secondary:contains("Reset")', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            $('form[action="{{ route('admin.users.index') }}"]')[0].reset();
            fetchUsers(url);
            window.history.pushState({path: url}, '', url);
        });

        // 4. Delete Action
        $(document).on('click' + namespace, '.delete-btn', function() {
            if (!confirm('Are you sure you want to delete this user?')) return;
            let url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function(result) {
                    let currentUrl = window.location.href;
                    fetchUsers(currentUrl);
                    showToast('User deleted successfully.', 'success');
                },
                error: function(xhr) {
                    showToast('Error deleting user.', 'error');
                }
            });
        });

        // 5. Initial Data Load
        $(containerId).html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading data...</div></div>');
        fetchUsers(window.location.href);

        // 6. Import Form Submission
        $('form[action="{{ route('admin.users.import') }}"], form[action="{{ route('admin.users.upload-photos') }}"]').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            let formData = new FormData(this);
            let originalText = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        var modalEl = form.closest('.modal')[0];
                        if (modalEl) {
                            var instance = null;
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                if (bootstrap.Modal.getOrCreateInstance) {
                                    instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                                } else {
                                    instance = new bootstrap.Modal(modalEl);
                                }
                                instance.hide();
                            } else if (typeof $ !== 'undefined' && $(modalEl).modal) {
                                $(modalEl).modal('hide');
                            }
                        }
                        fetchUsers(window.location.href);
                        showToast(response.message, 'success');
                    }
                    btn.prop('disabled', false).html(originalText);
                    form[0].reset();
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error processing request.';
                    showToast(msg, 'error');
                }
            });
        });

        // 7. Add User Button Click
        $(document).on('click' + namespace, '#btn-add-user', function() {
            $('#userForm')[0].reset();
            $('#userModalTitle').text('Add New User');
            $('#btn-save-user').text('Save User');
            $('#formMethod').val('POST');
            $('#userForm').attr('action', '{{ route('admin.users.store') }}');
            toggleModalFields(); // Reset visibility
            $('input[name="rfid_uid"]').data('auto', '');
            
            // Reset Preview
            $('#modalProfilePreview').attr('src', defaultProfilePicUrl);
            $('#btn-remove-photo').addClass('d-none'); // Hide remove button
            $('#remove_profile_picture').val('0'); // Reset removal flag
            
            $('#userModal').modal('show');
        });

        function syncRfidWithIdNumber() {
            if ($('#formMethod').val() !== 'POST') return;
            var idVal = ($('input[name="id_number"]').val() || '').trim();
            if (!idVal) return;
            var $rfid = $('input[name="rfid_uid"]');
            var current = ($rfid.val() || '').trim();
            var auto = $rfid.data('auto');
            if (current === '' || current === auto) {
                $rfid.val(idVal);
                $rfid.data('auto', idVal);
            }
        }

        $(document).on('input' + namespace, 'input[name="id_number"]', function() {
            syncRfidWithIdNumber();
        });

        $(document).on('input' + namespace, 'input[name="rfid_uid"]', function() {
            var $rfid = $(this);
            var current = ($rfid.val() || '').trim();
            var idVal = ($('input[name="id_number"]').val() || '').trim();
            $rfid.data('auto', current === idVal ? idVal : null);
        });

        // 8. Edit User Button Click
        $(document).on('click' + namespace, '.edit-btn', function() {
            let url = $(this).data('url');
            let updateUrl = url.replace('/edit', ''); // Convert edit URL to update URL if needed, or construct it
            // Better: construct route manually or use data attribute.
            // Route: admin.users.update is PUT /admin/users/{id}
            // The edit URL is /admin/users/{id}/edit
            // We can fetch data from edit URL (returns JSON now)
            
            $.get(url, function(response) {
                if(response.success) {
                    let user = response.user;
                    $('#userForm')[0].reset();
                    $('#userModalTitle').text('Edit User');
                    $('#btn-save-user').text('Update User');
                    $('#formMethod').val('PUT');
                    $('#userForm').attr('action', '/admin/users/' + user.id);
                    
                    // Populate fields
                    $('input[name="full_name"]').val(user.full_name);
                    $('input[name="id_number"]').val(user.id_number);
                    $('input[name="rfid_uid"]').val(user.rfid_uid); // Populate RFID
                    $('input[name="rfid_uid"]').data('auto', null);
                    $('select[name="user_type"]').val(user.user_type);
                    $('[name="department_id"]').val(user.department_id);
                    $('input[name="id_expiration_date"]').val(user.id_expiration_date);
                    $('[name="course_id"]').val(user.course_id);
                    $('select[name="year_level_id"]').val(user.year_level_id);
                    $('[name="designation_id"]').val(user.designation_id);
                    $('select[name="status"]').val(user.status);
                    
                    // Set Profile Picture Preview
                    if (user.profile_picture) {
                        $('#modalProfilePreview').attr('src', '/storage/' + user.profile_picture);
                        $('#btn-remove-photo').removeClass('d-none'); // Show remove button
                    } else {
                        $('#modalProfilePreview').attr('src', defaultProfilePicUrl);
                        $('#btn-remove-photo').addClass('d-none'); // Hide remove button
                    }
                    $('#remove_profile_picture').val('0'); // Reset removal flag
                    
                    toggleModalFields();
                    $('#userModal').modal('show');
                }
            });
        });

        // 9. User Form Submission (Create/Update)
        $(document).on('submit' + namespace, '#userForm', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#btn-save-user');
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');

            // Use FormData for file upload support
            let formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                type: 'POST', // Method spoofing handled by _method input
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#userModal').modal('hide');
                        fetchUsers(window.location.href);
                        showToast(response.message || 'User saved successfully.', 'success');
                    }
                    btn.prop('disabled', false).text('Save User');
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Save User');
                    if (xhr.status === 422) {
                        // ... existing validation handling ...
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = form.find('[name="' + key + '"]');
                            input.addClass('is-invalid');
                            if(input.next('.invalid-feedback').length === 0) {
                                input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                            }
                        });
                        showToast('Please fix the validation errors.', 'error');
                    } else {
                        showToast('An error occurred.', 'error');
                    }
                }
            });
        });

        // 10. Checkbox Logic
        function updateBulkDeleteButton() {
            let count = $('.user-checkbox:checked').length;
            $('#selected-count').text(count);
            if(count > 0) {
                $('#btn-bulk-delete').removeClass('d-none');
            } else {
                $('#btn-bulk-delete').addClass('d-none');
            }
        }

        $(document).on('change' + namespace, '#selectAll', function() {
            $('.user-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkDeleteButton();
        });

        $(document).on('change' + namespace, '.user-checkbox', function() {
            let allChecked = $('.user-checkbox:checked').length === $('.user-checkbox').length;
            $('#selectAll').prop('checked', allChecked);
            updateBulkDeleteButton();
        });
        
        // 11. Bulk Delete Action
        $(document).on('click' + namespace, '#btn-bulk-delete', function() {
            let ids = [];
            $('.user-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            
            if(ids.length === 0) return;
            
            if(!confirm('Are you sure you want to delete ' + ids.length + ' users?')) return;
            
            let btn = $(this);
            btn.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: '{{ route("admin.users.bulk-destroy") }}',
                type: 'POST',
                data: { ids: ids },
                success: function(response) {
                    if(response.success) {
                        fetchUsers(window.location.href);
                        $('#btn-bulk-delete').addClass('d-none'); // Hide button
                        showToast(response.message, 'success');
                    } else {
                        showToast(response.message, 'error');
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selected-count">0</span>)');
                },
                error: function() {
                    showToast('Error deleting users.', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selected-count">0</span>)');
                }
            });
        });
    });
</script>
@endsection
