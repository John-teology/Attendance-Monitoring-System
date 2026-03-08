<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Saint Mary's Academy</title>
    <link rel="stylesheet" href="{{ asset('css/poppins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background-color: #fff;
            padding: 30px 30px 10px;
            text-align: center;
        }

        .login-logo {
            width: 60px;
            margin-bottom: 15px;
        }

        .login-body {
            background-color: #fff;
            padding: 20px 30px 40px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .btn-primary {
            background-color: #3b82f6;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('img/login-logo.png') }}" alt="Logo" class="login-logo">
            <h5 class="fw-bold mb-1">Admin Portal</h5>
            <p class="text-muted small">Sign in to manage the library system</p>
        </div>
        <div class="login-body">
            @if ($errors->any())
                <div class="alert alert-danger small p-2 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
