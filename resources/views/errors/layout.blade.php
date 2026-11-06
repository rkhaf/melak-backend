<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/melak.svg') }}">

    <title>@yield('title', 'Error') | Melak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen antialiased flex items-center justify-center relative overflow-hidden bg-[#0F172A]">
    <!-- Background decoration -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-[#0F767E]/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-[#F97316]/20 blur-3xl"></div>
        <div class="absolute top-1/3 left-1/2 w-72 h-72 rounded-full bg-[#0F767E]/30 blur-3xl -translate-x-1/2"></div>
    </div>

    <div class="relative z-10 w-full max-w-lg px-6 py-12 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/10 border border-white/10 mb-8 shadow-2xl">
            <img src="{{ asset('images/melak.svg') }}" alt="Melak" class="w-12 h-12 object-contain">
        </div>

        <p class="font-black text-8xl sm:text-9xl text-transparent bg-clip-text bg-gradient-to-r from-[#0F767E] via-[#0E8C94] to-[#22D3EE] tracking-tight select-none">
            @yield('code')
        </p>

        <div class="inline-flex items-center gap-2 mt-4 mb-3">
            <i class="fa-solid @yield('icon', 'fa-triangle-exclamation') text-[#F97316]"></i>
            <h1 class="font-bold text-2xl sm:text-3xl text-white" style="font-family: 'Fredoka', sans-serif;">@yield('heading')</h1>
        </div>

        <p class="text-white/60 text-sm leading-relaxed mb-8">
            @yield('message')
        </p>

        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-xl px-6 py-3 bg-[#0F767E] hover:bg-[#0a5258] text-white font-bold text-sm shadow-lg shadow-[#0F767E]/30 transition-all hover:scale-105">
                <i class="fa-solid fa-house"></i> Kembali ke Beranda
            </a>
            <button type="button" onclick="history.back()" class="inline-flex items-center gap-2 rounded-xl px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-bold text-sm transition-all">
                <i class="fa-solid fa-arrow-left"></i> Halaman Sebelumnya
            </button>
        </div>

        <p class="mt-10 text-[10px] text-white/30 uppercase tracking-widest font-bold">Melak · Deteksi Penyakit Ikan</p>
    </div>
</body>
</html>
