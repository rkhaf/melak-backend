<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berhenti Berlanggana — Melak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-teal-50 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 max-w-md w-full text-center">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-teal-100 flex items-center justify-center">
            <i class="fa-solid fa-envelope-circle-xmark text-teal-600 text-2xl"></i>
        </div>
        <h1 class="text-xl font-extrabold text-gray-800 mb-2">Email Dinonaktifkan</h1>
        <p class="text-sm text-gray-500 mb-6">
            Email bertipe <strong>{{ $type }}</strong> tidak akan lagi dikirim ke
            <strong>{{ $email }}</strong>. Anda dapat mengaktifkan kembali lewat
            menu Pengaturan Notifikasi di aplikasi Melak kapan saja.
        </p>
        <a href="{{ config('app.url') }}" class="inline-block w-full bg-[#0F767E] hover:bg-teal-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-colors">
            Buka Aplikasi
        </a>
    </div>
</body>
</html>
