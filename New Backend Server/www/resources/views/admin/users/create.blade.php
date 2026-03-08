@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New User</h1>
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>ID Number</label>
            <input type="text" name="id_number" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>User Type</label>
            <select name="user_type" id="user_type" class="form-select" required onchange="toggleFields()">
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
            </select>
        </div>

        <!-- Common Fields -->
        <div class="mb-3">
            <label>Department</label>
            <input type="text" name="department" class="form-control">
        </div>

        <div class="mb-3">
            <label>ID Expiration Date</label>
            <input type="date" name="id_expiration_date" class="form-control">
        </div>

        <!-- Student Specific Fields -->
        <div id="student_fields">
            <div class="mb-3">
                <label>Course</label>
                <input type="text" name="course" class="form-control">
            </div>
            <div class="mb-3">
                <label>Year Level</label>
                <select name="year_level" class="form-select">
                    <option value="">Select Year Level</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                    <option value="5th Year">5th Year</option>
                </select>
            </div>
        </div>

        <!-- Faculty Specific Fields -->
        <div id="faculty_fields" style="display: none;">
            <div class="mb-3">
                <label>Designation</label>
                <input type="text" name="designation" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="mb-3">
            <label>RFID UID (Optional)</label>
            <input type="text" name="rfid_uid" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary">Save User</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
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
        $('form[action="{{ route('admin.users.store') }}"]').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        // Redirect via SPA
                        if (window.loadSpaPage) {
                            window.loadSpaPage(response.redirect_url);
                        } else {
                            window.location.href = response.redirect_url;
                        }
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Save User');
                    
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
