<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Đăng nhập</title>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 430px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 18px 50px rgba(56, 76, 119, 0.15);
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="d-inline-block mb-2">
                    <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" height="40" />
                </a>
                <h4 class="card-title mb-1">Đăng nhập</h4>
                <p class="text-muted mb-0">Nhập email và mật khẩu của bạn để tiếp tục.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <input id="password" type="password" name="password" class="form-control" required />
                        <button type="button" class="btn btn-light border-0 d-flex align-items-center gap-1" id="togglePassword" aria-label="Hiện mật khẩu">
                            <i class="mdi mdi-eye-off" id="togglePasswordIcon"></i>
                            <span id="togglePasswordText">Hiện</span>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input id="remember" type="checkbox" name="remember" class="form-check-input" />
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Đăng nhập</button>
                </div>
            </form>

            <div class="mt-3 text-center text-muted">
                Nếu bạn chưa có tài khoản, vui lòng liên hệ quản trị viên.
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        var togglePasswordButton = document.getElementById('togglePassword');
        if (togglePasswordButton) {
            togglePasswordButton.addEventListener('click', function () {
                var passwordInput = document.getElementById('password');
                var icon = document.getElementById('togglePasswordIcon');
                var text = document.getElementById('togglePasswordText');
                var isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('mdi-eye', !isPassword);
                icon.classList.toggle('mdi-eye-off', isPassword);
                text.textContent = isPassword ? 'Ẩn' : 'Hiện';
            });
        }
    </script>
</body>
</html>
