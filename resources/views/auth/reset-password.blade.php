<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Kata Sandi — Melak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-teal-50 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 max-w-md w-full">
        <h1 class="text-xl font-extrabold text-gray-800 mb-1">Reset Kata Sandi</h1>
        <p class="text-sm text-gray-500 mb-6">Masukkan kata sandi baru untuk akun Anda. Sesi lama akan otomatis keluar.</p>

        <form id="reset-form" class="space-y-4">
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Kata Sandi Baru</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div id="reset-error" class="hidden text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3"></div>
            <div id="reset-ok" class="hidden text-xs font-bold text-teal-700 bg-teal-50 border border-teal-100 rounded-xl px-4 py-3">
                Kata sandi berhasil diubah. Silakan buka aplikasi Melak dan login kembali.
            </div>
            <button type="submit"
                    class="w-full bg-[#0F767E] hover:bg-teal-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-colors">
                Simpan Kata Sandi Baru
            </button>
        </form>
    </div>

    <script>
        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const err = document.getElementById('reset-error');
            const ok = document.getElementById('reset-ok');
            err.classList.add('hidden');
            ok.classList.add('hidden');

            const res = await fetch('/api/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    token: form.token.value,
                    password: form.password.value,
                    password_confirmation: form.password_confirmation.value,
                }),
            });

            const body = await res.json().catch(() => ({}));

            if (res.ok) {
                ok.classList.remove('hidden');
                form.querySelector('button[type=submit]').disabled = true;
                return;
            }

            err.textContent = body.message || 'Gagal mereset kata sandi. Link mungkin sudah kedaluwarsa.';
            err.classList.remove('hidden');
        });
    </script>
</body>
</html>
