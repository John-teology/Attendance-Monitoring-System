@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit User</h1>
    <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row mb-4">
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <img id="profilePreview" 
                         src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('img/login-logo.png') }}" 
                         alt="Profile Picture" 
                         class="img-thumbnail rounded-circle shadow-sm" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                <div class="mb-3">
                    <label for="profile_picture" class="form-label btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-camera me-1"></i> Change Photo
                    </label>
                    <input type="file" class="form-control d-none" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewImage(this)">
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="{{ $user->full_name }}" required>
                </div>
                <div class="mb-3">
                    <label>ID Number</label>
                    <input type="text" name="id_number" class="form-control" value="{{ $user->id_number }}" required>
                </div>
                <div class="mb-3">
                    <label>User Type</label>
                    <select name="user_type" id="user_type" class="form-select" required onchange="toggleFields()">
                        <option value="student" {{ $user->user_type == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="faculty" {{ $user->user_type == 'faculty' ? 'selected' : '' }}>Faculty</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Common Fields -->
        <div class="mb-3">
            <label>Department</label>
            <input type="text" name="department" class="form-control" value="{{ $user->department }}">
        </div>

        <div class="mb-3">
            <label>ID Expiration Date</label>
            <input type="date" name="id_expiration_date" class="form-control" value="{{ $user->id_expiration_date }}">
        </div>

        <!-- Student Specific Fields -->
        <div id="student_fields">
            <div class="mb-3">
                <label>Course</label>
                <input type="text" name="course" class="form-control" value="{{ $user->course }}">
            </div>
            <div class="mb-3">
                <label>Year Level</label>
                <select name="year_level" class="form-select">
                    <option value="">Select Year Level</option>
                    <option value="1st Year" {{ $user->year_level == '1st Year' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd Year" {{ $user->year_level == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd Year" {{ $user->year_level == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th Year" {{ $user->year_level == '4th Year' ? 'selected' : '' }}>4th Year</option>
                    <option value="5th Year" {{ $user->year_level == '5th Year' ? 'selected' : '' }}>5th Year</option>
                </select>
            </div>
        </div>

        <!-- Faculty Specific Fields -->
        <div id="faculty_fields" style="display: none;">
            <div class="mb-3">
                <label>Designation</label>
                <input type="text" name="designation" class="form-control" value="{{ $user->designation }}">
            </div>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select" required>
                <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="mb-3">
            <label>RFID UID (Optional)</label>
            <input type="text" name="rfid_uid" class="form-control" value="{{ $user->rfid_uid }}">
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function toggleFields() {
        const type = document.getElementById('user_type').value;
        if (type === 'student') {
            document.getElementById('student_fields').style.display = 'block';
            document.getElementById('faculty_fields').style.display = 'none';
        } else {
            document.getElementById('student_fields').style.display = 'none';
            document.getElementById('faculty_fields').style.display = 'block';
        }
    }
    // Run on load
    toggleFields();

    $(document).ready(function() {
        $('form[action="{{ route('admin.users.update', $user->id) }}"]').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');
            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');

            let formData = new FormData(this); // Use FormData for file uploads

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false, // Required for FormData
                contentType: false, // Required for FormData
                success: function(response) {
                    if (response.success) {
                        if (window.loadSpaPage) {
                            window.loadSpaPage(response.redirect_url);
                        } else {
                            window.location.href = response.redirect_url;
                        }
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Update User');
                    
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = form.find('[name="' + key + '"]');
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });
    });
</script>
@endsection
