<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/melak.svg') }}">
    <title>Masuk Admin - Melak</title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Vite Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#E0F2FE] via-slate-100 to-sky-50 min-h-screen flex items-center justify-center p-6 sm:p-8 relative overflow-hidden">
    
    <!-- Floating background blur shapes -->
    <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-sky-100/30 rounded-full blur-3xl -translate-y-16 translate-x-16"></div>
    <div class="absolute bottom-0 left-0 w-[300px] h-[300px] bg-amber-100/20 rounded-full blur-3xl translate-y-16 -translate-x-16"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo and Brand Title -->
        <div class="flex flex-col items-center mb-8">
            <a href="{{ route('admin.login') }}" class="flex flex-col items-center gap-3">
                <img src="{{ asset('images/melak.svg') }}" alt="Logo Melak" class="w-16 h-16 rounded-2xl bg-white p-1.5 shadow-md object-contain hover:scale-105 transition-transform">
                <span class="font-extrabold text-3xl tracking-wide text-[#0369A1] mt-1">Melak</span>
                <span class="text-xs text-[#0369A1] uppercase tracking-widest font-extrabold -mt-1">Melak Admin Console</span>
            </a>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-2xl border border-gray-150/80">
            <h2 class="text-xl font-bold text-[#1F2937] text-center mb-1">Masuk Administrator</h2>
            <p class="text-xs text-gray-400 text-center mb-8 font-normal">Silakan masuk menggunakan kredensial administrator Melak Anda.</p>

            <!-- Success Alert -->
            @if (session('success'))
                <div class="mb-6 p-3.5 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-emerald-800 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-sm text-emerald-500"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Errors Alert -->
            @if ($errors->any())
                <div class="mb-6 p-3.5 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-xs flex flex-col gap-1.5">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation text-sm text-rose-500"></i>
                            <span class="font-medium">{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('admin.login.post') }}" method="POST" class="flex flex-col gap-5">
                @csrf
                
                <!-- Email / Username Input -->
                <div class="flex flex-col gap-1.5">
                    <label for="login" class="text-xs font-bold text-[#1F2937] uppercase tracking-wider">Email atau Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-regular fa-user text-sm"></i>
                        </span>
                        <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus placeholder="Masukkan email/username" 
                               class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:bg-white focus:ring-2 focus:ring-[#0369A1]/25 focus:border-[#0369A1] focus:outline-none transition-all">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <label for="password" class="text-xs font-bold text-[#1F2937] uppercase tracking-wider">Kata Sandi</label>
                        <a href="{{ route('admin.forgot-password') }}" class="text-xs font-semibold text-[#0369A1] hover:text-[#0369A1] hover:underline">Lupa sandi?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" id="password" required placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" 
                               class="w-full pl-10 pr-10 py-3 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:bg-white focus:ring-2 focus:ring-[#0369A1]/25 focus:border-[#0369A1] focus:outline-none transition-all">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fa-regular fa-eye text-sm" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center gap-2 mt-1">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-gray-300 text-[#0369A1] focus:ring-[#0369A1]">
                    <label for="remember" class="text-xs text-gray-500 font-semibold cursor-pointer select-none">Ingat masuk saya</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-[#0369A1] hover:bg-[#075985] text-white font-bold py-3.5 rounded-xl transition-all shadow-md hover:shadow-lg hover:scale-[1.01] mt-2 flex items-center justify-center gap-2">
                    <span>Masuk ke Panel</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('admin.login') }}" class="text-xs font-semibold text-gray-500 hover:text-[#0369A1] flex items-center justify-center gap-1.5 hover:underline">
                <i class="fa-solid fa-circle-arrow-left"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
