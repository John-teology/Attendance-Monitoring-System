@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Mobile App Sync</h2>
        <p class="text-muted">Manage database synchronization with the mobile application.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Option 1: Manual Database File -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-3">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Clone Database for App</h5>
                        <span class="badge bg-success bg-opacity-10 text-success">Offline Mode</span>
                    </div>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <p class="text-muted mb-4">
                    Download a complete copy of the current <strong>Users</strong> and <strong>Attendance Logs</strong> 
                    in a format compatible with the Android App (SQLite).
                </p>
                <div class="alert alert-info border-0 d-flex align-items-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Instructions:</strong> Download this file and replace the <code>library_app.db</code> 
                        file in your Android device's storage folder.
                    </div>
                </div>
                
                <form action="{{ route('admin.sync.download') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                        <i class="fas fa-download me-2"></i> Download App Database (.sqlite)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Option 2: Developer Schema Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white pt-4 px-4 border-0">
                 <div class="d-flex align-items-center mb-2">
                    <div class="bg-secondary bg-opacity-10 p-3 rounded-circle text-secondary me-3">
                        <i class="fas fa-code fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Developer Schema</h5>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">Reference</span>
                    </div>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <p class="text-muted mb-3">Use these SQL queries to create the tables in your Android project.</p>
                
                <ul class="nav nav-tabs mb-3" id="schemaTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">Users Table</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button">Logs Table</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="schemaContent">
                    <div class="tab-pane fade show active" id="users">
<pre class="bg-light p-3 rounded border" style="font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_type TEXT,
    full_name TEXT,
    id_number TEXT UNIQUE,
    qr_code TEXT,
    rfid_uid TEXT,
    status TEXT,
    department_id INTEGER,
    course_id INTEGER,
    year_level_id INTEGER,
    designation_id INTEGER,
    id_expiration_date TEXT,
    created_at TEXT,
    updated_at TEXT
);
</pre>
                    </div>
                    <div class="tab-pane fade" id="logs">
<pre class="bg-light p-3 rounded border" style="font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    scan_type TEXT,
    entry_type TEXT,
    scanned_at INTEGER,
    created_at TEXT,
    updated_at TEXT,
    sync_status TEXT DEFAULT 'pending'
);
</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
