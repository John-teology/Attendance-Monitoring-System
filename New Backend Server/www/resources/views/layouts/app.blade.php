<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $systemSettings->school_name ?? "Library Attendance" }} - Library System</title>
    <link rel="stylesheet" href="{{ asset('css/poppins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/local-select2.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fa-compat.css') }}">
    
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap-compat.js') }}"></script>
    <script src="{{ asset('js/local-select2.js') }}"></script>

    <script src="{{ asset('js/resize-observer.js') }}"></script>
    <script src="{{ asset('js/apexcharts.js') }}"></script>
    <style>
        html { overflow-y: auto; }
        :root {
            --sidebar-bg: #1e293b;
            --sidebar-hover: #2c3e50;
            --primary-color: #800000;
            --accent-color: #ffc107;
            --bg-color: #f3f4f6;
            --text-color: #334155;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand {
            font-size: 1.2rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-item {
            margin-bottom: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255,255,255,0.05);
            color: #fff;
            border-right: 4px solid var(--primary-color);
        }

        .sidebar-link i {
            width: 25px;
            margin-right: 10px;
        }

        .user-profile {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 0;
            transition: all 0.3s;
        }

        /* Top Header */
        .top-header {
            background-color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
        }

        .header-logo {
            height: 35px;
            margin-right: 12px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            color: #64748b;
            font-size: 1.1rem;
            cursor: pointer;
            position: relative;
        }
        
        .badge-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.65rem;
            padding: 2px 5px;
        }

        .page-content {
            padding: 30px;
        }
        
        /* Unified page action bar styling */
        .page-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .page-actions .btn {
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin: 4px 6px 4px 0;
        }
        .page-actions .btn-group .btn {
            margin-right: 6px;
        }
        .page-actions .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #6a0000;
            border-color: #6a0000;
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background-color: var(--primary-color);
            color: #fff;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .bg-primary {
            --bs-bg-opacity: 1;
            background-color: rgba(128,0,0,var(--bs-bg-opacity)) !important;
        }
        .border-primary {
            border-color: var(--primary-color) !important;
        }
        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }
        .role-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-right: 12px;
        }
        .role-super { background: #dc3545; }
        .role-admin { background: #0d6efd; }
        .role-editor { background: #ffc107; color: #111; }
        .role-viewer { background: #6c757d; }
        .page-content h1,
        .page-content h2,
        .page-content h3,
        .page-content h4,
        .page-content h5 {
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header justify-content-between">
            <a href="#" class="sidebar-brand d-flex align-items-center">
                @if(isset($systemSettings) && $systemSettings->school_logo)
                    <img src="{{ asset('storage/' . $systemSettings->school_logo) }}" alt="Logo" style="width: 30px; height: 30px; margin-right: 10px; object-fit: contain;">
                @else
                    <img src="{{ asset('img/login-logo.png') }}" alt="Logo" style="width: 30px; height: 30px; margin-right: 10px;">
                @endif
                <span style="font-size: 0.9rem; line-height: 1.2;">{!! nl2br(e($systemSettings->school_name ?? "Library\nAttendance")) !!}</span>
            </a>
            <button class="btn btn-link text-white d-md-none" id="sidebar-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            @if(in_array(auth('admin')->user()->role, ['admin', 'super_admin', 'viewer', 'editor']))
            <li class="sidebar-item">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
            </li>
            @endif

            @if(in_array(auth('admin')->user()->role, ['admin', 'editor', 'super_admin']))
            <li class="sidebar-item">
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Student & Faculty Management
                </a>
            </li>
            @endif

            @if(in_array(auth('admin')->user()->role, ['admin', 'editor', 'super_admin']))
            <li class="sidebar-item">
                <a href="{{ route('admin.lookups.index') }}" class="sidebar-link {{ request()->routeIs('admin.lookups.*') ? 'active' : '' }}">
                    <i class="fas fa-database"></i> Data Management
                </a>
            </li>
            @endif

            @if(in_array(auth('admin')->user()->role, ['admin', 'super_admin', 'viewer']))
            <li class="sidebar-item">
                <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </li>
            @endif

            @if(auth('admin')->user()->role == 'super_admin')
            <li class="sidebar-item">
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <!-- License Menu - Only for Super Admin -->
            <li class="sidebar-item">
                <a href="{{ route('admin.license.index') }}" class="sidebar-link {{ request()->routeIs('admin.license.*') ? 'active' : '' }}">
                    <i class="fas fa-key"></i> License
                </a>
            </li>
            @elseif(auth('admin')->user()->role == 'admin')
            <li class="sidebar-item">
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            @endif
            <li class="sidebar-item mt-auto">
                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                    @csrf
                    <a href="#" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </form>
            </li>
        </ul>

        <div class="user-profile">
            @php
                $r = auth('admin')->user()->role ?? 'admin';
                $icon = $r === 'super_admin' ? 'fa-crown' : ($r === 'admin' ? 'fa-user-shield' : ($r === 'editor' ? 'fa-pen-to-square' : 'fa-eye'));
                $bg = $r === 'super_admin' ? 'role-super' : ($r === 'admin' ? 'role-admin' : ($r === 'editor' ? 'role-editor' : 'role-viewer'));
            @endphp
            <div class="role-avatar {{ $bg }}"><i class="fas {{ $icon }}"></i></div>
            <div class="d-flex flex-column">
                <span class="text-white small fw-bold">{{ auth('admin')->user()->name ?? 'Administrator' }}</span>
                <span class="text-muted small" style="font-size: 0.75rem;">{{ ucfirst(auth('admin')->user()->role ?? 'Admin') }}</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-title">
                <button class="btn btn-link text-dark me-3 d-md-none p-0" id="sidebar-toggle">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <!-- Logo moved to sidebar, just title or breadcrumb here if needed, or empty -->
                <span class="text-muted small d-none d-sm-inline">Library Management System - {{ $systemSettings->school_name ?? "Library Attendance" }}</span>
                <span class="text-muted small d-inline d-sm-none">Library System</span>
            </div>
            <div class="header-actions">
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content" id="spa-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-exclamation-triangle me-2"></i> <strong>There were some problems with your input:</strong>
                    </div>
                    <ul class="mb-0 ps-4 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
    
    @php
        $defaultTitleUsed = empty($systemSettings->school_name);
        $isAndroid = stripos(request()->header('User-Agent') ?? '', 'Android') !== false;
    @endphp
    @if($defaultTitleUsed && $isAndroid)
        <footer class="text-center small text-muted py-3">
            Contact: sales.techyitsolutions@gmail.com
        </footer>
    @endif

    <script>
        // Sidebar Mobile Toggle
        $(document).ready(function() {
            $('#sidebar-toggle').on('click', function() {
                $('.sidebar').addClass('active');
                // Create overlay
                if($('.sidebar-overlay').length === 0) {
                    $('body').append('<div class="sidebar-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;"></div>');
                }
            });

            $(document).on('click', '#sidebar-close, .sidebar-overlay', function() {
                $('.sidebar').removeClass('active');
                $('.sidebar-overlay').remove();
            });
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Global SPA Loader Function
        window.loadSpaPage = function(url) {
            // Visual feedback
            // $('#spa-content').css('opacity', '0.5'); // Removed for instant feel

            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-SPA-REQUEST': 'true'
                },
                success: function(response) {
                    let doc = new DOMParser().parseFromString(response, 'text/html');
                    let newContentWrapper = doc.getElementById('spa-content');
                    let newContent = newContentWrapper.innerHTML;
                    
                    $('#spa-content').html(newContent);
                    $('#spa-content').css('opacity', '1');
                    $('body').removeClass('modal-open').css('overflow', 'auto');
                    $('.modal-backdrop').remove();
                    
                    // Execute scripts found in the new content
                    // IMPORTANT: We need to manually re-instantiate charts if any
                    // The standard $.globalEval might execute the code, but ApexCharts needs the DOM to be ready
                    let scripts = newContentWrapper.getElementsByTagName("script");
                    for(let i=0; i<scripts.length; i++) {
                        if (scripts[i].src) {
                            // Load external scripts (like ApexCharts) if not already loaded
                            if (!$('script[src="' + scripts[i].src + '"]').length) {
                                $.getScript(scripts[i].src, function() {
                                    // Callback if needed
                                });
                            }
                        } else {
                            // Execute inline scripts
                            try {
                                // Execute immediately via window.eval to ensure global scope
                                // This is more reliable than $.globalEval for function definitions
                                window.eval(scripts[i].innerText);
                            } catch(e) {
                                console.error("SPA Script Error:", e);
                            }
                        }
                    }
                    
                    window.history.pushState({path: url}, '', url);
                    
                    // Update active sidebar state
                    $('.sidebar-link').removeClass('active');
                    $('.sidebar-link[href="' + url + '"]').addClass('active');
                },
                error: function() {
                    window.location.href = url;
                }
            });
        };

        // Sidebar Click Handler
        $(document).on('click', '.sidebar-link', function(e) {
            let url = $(this).attr('href');
            if (url === '#' || $(this).closest('form').length > 0) return;
            // Force full page load for Settings to ensure scripts and modals initialize cleanly
            if (url.indexOf('/admin/settings') !== -1) {
                return; // allow default navigation
            }
            e.preventDefault();
            loadSpaPage(url);
        });

        // Handle Back/Forward buttons
        window.onpopstate = function(e) {
            if(e.state) {
                // We can reuse loadSpaPage but without pushState
                // For simplicity, just reload or basic fetch
                 $.ajax({
                    url: window.location.href,
                    headers: {'X-SPA-REQUEST': 'true'},
                    success: function(response) {
                        let doc = new DOMParser().parseFromString(response, 'text/html');
                        $('#spa-content').html(doc.getElementById('spa-content').innerHTML);
                    }
                 });
            } else {
                window.location.reload();
            }
        };
        
        window.openPasswordModal = function(id, name) {
            var form = document.getElementById('passwordForm');
            if (!form) return;
            var baseUrl = "{{ route('admin.settings.index') }}";
            form.action = baseUrl + "/" + id + "/password";
            var nameSpan = document.getElementById('adminName');
            if (nameSpan) nameSpan.textContent = name;
            var modalEl = document.getElementById('passwordModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        };
        window.openEditModal = function(id, name, email) {
            var form = document.getElementById('editAdminForm');
            if (!form) return;
            var baseUrl = "{{ route('admin.settings.index') }}";
            form.action = baseUrl + "/" + id;
            var nameInput = document.getElementById('editAdminName');
            var emailInput = document.getElementById('editAdminEmail');
            if (nameInput) nameInput.value = name;
            if (emailInput) emailInput.value = email;
            var modalEl = document.getElementById('editAdminModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        };
    </script>
    @stack('scripts')
</body>
</html>
