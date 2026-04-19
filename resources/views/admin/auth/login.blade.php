<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Rootva</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-bg: #0f172a;
            --accent: #3b82f6;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--primary-bg);
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            width: 100%;
            max-width: 450px;
            padding: 3.5rem 3rem;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, var(--accent), transparent, #ec4899);
            border-radius: 2rem;
            z-index: -1;
            opacity: 0.3;
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent), #1e40af);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            transform: rotate(10deg);
        }

        .logo-circle i {
            color: white;
            font-size: 32px;
            transform: rotate(-10deg);
        }

        .form-label {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.75rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            color: white;
        }

        .btn-login {
            background: var(--accent);
            border: none;
            border-radius: 1rem;
            padding: 1rem;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .floating-circles div {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent), #ec4899);
            filter: blur(80px);
            opacity: 0.15;
            z-index: 1;
        }

        .c1 { width: 300px; height: 300px; top: 10%; left: 5%; }
        .c2 { width: 400px; height: 400px; bottom: -10%; right: 5%; animation: float 10s infinite alternate; }

        @keyframes float {
            from { transform: translateY(0); }
            to { transform: translateY(-50px); }
        }
    </style>
</head>
<body>

    <div class="floating-circles">
        <div class="c1"></div>
        <div class="c2"></div>
    </div>

    <div class="login-card">
        <div class="logo-circle">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        
        <div class="text-center mb-5">
            <h2 class="text-white fw-bold mb-2">Welcome Back</h2>
            <p class="text-muted small px-4">Sign in with your administrative credentials to manage Rootva Panel</p>
        </div>

        @if($errors->any())
            <div class="error-alert">
                <i class="fa-solid fa-circle-exclamation me-3"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label">Username</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control" placeholder="admin_user" value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label">Password</label>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Unlock Panel <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-5">
            <p class="text-muted extra-small mb-0">Rootva &copy; 2026 Modernized Admin Panel</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
