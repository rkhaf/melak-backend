<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Riwayat Scan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        .header { background: #0f767e; color: #fff; padding: 14px 18px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 4px 0 0; font-size: 10px; opacity: .9; }
        .content { padding: 16px 18px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #e5e7eb; padding: 5px 6px; text-align: left; }
        th { background: #f9fafb; font-weight: 700; }
        .muted { color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Melak — Riwayat Scan</h1>
        <p>Dicetak {{ $generatedAt->format('d/m/Y H:i') }} · {{ $rows->count() }} data</p>
    </div>

    <div class="content">
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Kolam</th>
                <th>Jenis Ikan</th>
                <th>Penyakit</th>
                <th>Confidence</th>
                <th>Tingkat</th>
                <th>Tindakan</th>
            </tr>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['kolam'] }}</td>
                    <td>{{ $row['jenis_ikan'] }}</td>
                    <td>{{ $row['penyakit'] }}</td>
                    <td>{{ $row['confidence'] }}</td>
                    <td>{{ $row['tingkat'] }}</td>
                    <td>{{ $row['tindakan'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada data scan.</td></tr>
            @endforelse
        </table>

        <p class="muted" style="margin-top: 16px;">Dokumen dihasilkan otomatis oleh Melak.</p>
    </div>
</body>
</html>
