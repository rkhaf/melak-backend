<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Scan {{ $scan->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        .header { background: #0f767e; color: #fff; padding: 16px 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0 0; font-size: 11px; opacity: .9; }
        .content { padding: 20px; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: #0f767e; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin: 22px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f9fafb; font-weight: 700; }
        .meta td:first-child { width: 35%; font-weight: 700; background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .warn { background: #fef3c7; color: #92400e; padding: 8px 10px; border: 1px solid #fde68a; border-radius: 6px; }
        .muted { color: #6b7280; font-size: 11px; }
        ol, ul { margin: 4px 0; padding-left: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Melak — Hasil Scan Penyakit Tanaman</h1>
        <p>Dicetak {{ $generatedAt->format('d/m/Y H:i') }} · ID Scan: {{ $scan->id }}</p>
    </div>

    <div class="content">
        @if ($scan->status === 'failed')
            <div class="warn"><strong>Scan gagal.</strong> {{ $scan->error_message ?? 'Prediksi gagal diproses.' }}</div>
        @endif

        @if ($result && $result->low_confidence_warning)
            <div class="warn"><strong>Confidence rendah (&lt; 60%).</strong> Hasil prediksi tidak pasti — periksa kembali kondisi tanaman atau lakukan scan ulang dengan foto yang lebih jelas.</div>
        @endif

        <h2>Informasi Scan</h2>
        <table class="meta">
            <tr><td>Tanggal scan</td><td>{{ $scan->created_at?->format('d/m/Y H:i') }}</td></tr>
            <tr><td>Kebun</td><td>{{ $scan->garden?->name ?? '-' }}</td></tr>
            <tr><td>Jenis tanaman</td><td>{{ $scan->plant_type ?? '-' }}</td></tr>
            <tr><td>Deskripsi gejala</td><td>{{ $scan->symptoms_desc ?? '-' }}</td></tr>
            <tr><td>Status</td><td>{{ $scan->status }}</td></tr>
        </table>

        @if ($result)
            <h2>Hasil Prediksi</h2>
            <table class="meta">
                <tr><td>Penyakit</td><td>{{ $disease?->name ?? ($result->top_predictions[0]['name'] ?? 'Tidak diketahui') }}</td></tr>
                <tr><td>Confidence</td><td>{{ number_format((float) $result->confidence_score, 1) }}%</td></tr>
                <tr><td>Tingkat keparahan</td><td>{{ $disease?->severity ?? '-' }}</td></tr>
                <tr><td>Waktu proses</td><td>{{ $result->processing_time_ms }} ms</td></tr>
                <tr><td>Versi model</td><td>{{ $result->model_version ?? '-' }}</td></tr>
            </table>

            @if (! empty($result->top_predictions))
                <h2>Peringkat Prediksi</h2>
                <table>
                    <tr><th>#</th><th>Nama</th><th>Confidence</th></tr>
                    @foreach ($result->top_predictions as $i => $pred)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $pred['name'] }}</td>
                            <td>{{ $pred['confidence'] }}%</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endif

        @if ($disease)
            <h2>Penanganan ({{ $disease->name }})</h2>
            @if ($disease->treatments->isEmpty())
                <p class="muted">Belum ada panduan penanganan.</p>
            @else
                <ol>
                    @foreach ($disease->treatments as $treatment)
                        <li>
                            <strong>{{ $treatment->title }}</strong>
                            @if ($treatment->description)<br>{{ $treatment->description }}@endif
                            @if ($treatment->duration_days)<br><span class="muted">Durasi: {{ $treatment->duration_days }} hari</span>@endif
                        </li>
                    @endforeach
                </ol>
            @endif

            <h2>Pencegahan</h2>
            @if ($disease->preventions->isEmpty())
                <p class="muted">Belum ada panduan pencegahan.</p>
            @else
                <ul>
                    @foreach ($disease->preventions as $prevention)
                        <li>{{ $prevention->description }}</li>
                    @endforeach
                </ul>
            @endif
        @endif

        <p class="muted" style="margin-top: 24px;">Dokumen dihasilkan otomatis oleh Melak. Konsultasikan hasil ini dengan penyuluh pertanian untuk penanganan lanjutan.</p>
    </div>
</body>
</html>
