<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin OTP Mail')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 10px 0;
            }
            .main-container {
                border-radius: 10px;
                padding: 15px;
            }
        }
        
        .header-section {
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .header-section {
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
        }
        
        .header-title {
            color: #667eea;
            font-weight: 700;
        }
        
        @media (max-width: 576px) {
            .header-title {
                font-size: 1.5rem;
            }
        }
        
        .refresh-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
        }
        
        @media (max-width: 576px) {
            .refresh-info {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
        }
        
        .status-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container">
        <div class="main-container">
            <!-- Navigation Menu -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
                <div class="container-fluid">
                    <a class="navbar-brand" href="{{ route('admin.otp') }}">
                        <i class="fas fa-envelope-open-text"></i> Mail.TM Monitor
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.otp') ? 'active' : '' }}" 
                                   href="{{ route('admin.otp') }}">
                                    <i class="fas fa-key"></i> OTP Monitor
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.mail-accounts.*') ? 'active' : '' }}" 
                                   href="{{ route('admin.mail-accounts.index') }}">
                                    <i class="fas fa-cog"></i> Mail Accounts
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="header-title mb-0">
                        <i class="fas fa-envelope-open-text"></i> @yield('title', 'Admin OTP Mail')
                    </h1>
                    @yield('header-actions')
                </div>
            </div>
            
            @yield('content')
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
