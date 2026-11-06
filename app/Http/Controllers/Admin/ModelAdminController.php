<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\MlDataset;
use App\Models\MlModel;
use App\Services\MlProbe;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * FR-09.2.2 — FR-09.2.3 — manajemen versi model ML & dataset berlabel.
 */
class ModelAdminController extends Controller
{
    public function index(Request $request)
    {
        $models = MlModel::with('uploader:id,name')->orderByDesc('is_active')->latest('id')->get();

        $datasets = MlDataset::with(['disease:id,name', 'uploader:id,name'])
            ->latest()
            ->paginate(10, ['*'], 'datasets_page')
            ->withQueryString();

        return view('admin.models.index', compact('models', 'datasets'));
    }

    /**
     * Test request ke ML service (API.md §10 / FR-10).
     *
     * Foto dari form admin dikirim lewat multipart persis seperti yang akan
     * dikirim ProcessScanJob, lalu ditampilkan 4 lapis: request, response mentah,
     * hasil lapisan adaptasi, dan resolusi slug ke tabel diseases (FR-10.11).
     *
     * Tidak melempar error: service mati / 422 / timeout justru ditampilkan.
     */
    public function test(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'fish_type' => ['nullable', 'string', 'max:100'],
        ], [
            'image.required' => 'Pilih foto yang akan dikirim ke service ML.',
            'image.file' => 'File tidak valid.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'image.max' => 'Ukuran foto melebihi 10 MB (batas API.md §2.7).',
            'fish_type.max' => 'Jenis ikan maksimal 100 karakter.',
        ]);

        $gradcam = $request->boolean('gradcam');

        $result = app(MlProbe::class)->predict(
            $request->file('image'),
            $data['fish_type'] ?? null,
            $gradcam,
        );

        $errorCode = $result['error']['code'] ?? '';

        Audit::log('model.tested', sprintf(
            'Test request ML driver=%s: %s (HTTP %s%s).',
            $result['driver'],
            $result['ok'] ? 'berhasil' : 'gagal',
            $result['http_status'] ?? '-',
            $errorCode !== '' ? ', '.$errorCode : '',
        ));

        return back()
            ->with('ml_test', $result)
            ->with('ml_test_input', [
                'fish_type' => $data['fish_type'] ?? null,
                'gradcam' => $gradcam,
            ]);
    }

    /**
     * Cek status service (GET /health, FR-10.2) tanpa perlu upload foto.
     */
    public function status()
    {
        return back()->with('ml_health', app(MlProbe::class)->health());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:100', Rule::unique('ml_models', 'version')],
            'model_file' => 'required|file|max:512000|extensions:h5,onnx,pt,pth,pb,tflite,bin,zip',
            'accuracy_pct' => 'nullable|numeric|min:0|max:100',
            'disease_labels' => 'nullable|string|max:2000',
        ], [
            'version.required' => 'Versi model wajib diisi.',
            'version.unique' => 'Versi model sudah pernah diunggah.',
            'model_file.required' => 'File model wajib diunggah.',
            'model_file.extensions' => 'Format file model tidak didukung (h5, onnx, pt, pth, pb, tflite, bin, zip).',
            'accuracy_pct.numeric' => 'Akurasi harus berupa angka (0–100).',
        ]);

        $file = $request->file('model_file');
        $path = $file->storeAs(
            'ml-models',
            $data['version'].'_'.uniqid().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $labels = collect(preg_split('/\r\n|\r|\n/', (string) ($data['disease_labels'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $model = MlModel::create([
            'version' => $data['version'],
            'filename' => $file->getClientOriginalName(),
            's3_path' => $path,
            'disease_labels' => $labels !== [] ? $labels : null,
            'accuracy_pct' => $data['accuracy_pct'] ?? null,
            'is_active' => false,
            'uploaded_by' => auth()->id(),
        ]);

        Audit::log('model.uploaded', 'Model ML versi "'.$model->version.'" diunggah.', $model);

        return redirect()->route('admin.models.index')
            ->with('success', 'Model ML versi "'.$model->version.'" berhasil diunggah.');
    }

    public function activate(MlModel $model)
    {
        if ($model->is_active) {
            return back()->with('info', 'Model versi "'.$model->version.'" sudah aktif.');
        }

        MlModel::query()->update(['is_active' => false]);
        $model->update(['is_active' => true, 'activated_at' => now()]);

        Audit::log('model.activated', 'Model ML versi "'.$model->version.'" diaktifkan.', $model);

        return back()->with('success', 'Model ML versi "'.$model->version.'" kini menjadi versi aktif.');
    }

    public function rollback()
    {
        $current = MlModel::where('is_active', true)->latest('activated_at')->first();
        $previous = MlModel::where('is_active', false)
            ->whereNotNull('activated_at')
            ->when($current, fn ($q) => $q->where('id', '!=', $current->id))
            ->latest('activated_at')
            ->first();

        if ($previous === null) {
            return back()->with('info', 'Belum ada versi model sebelumnya untuk di-rollback.');
        }

        MlModel::query()->update(['is_active' => false]);
        $previous->update(['is_active' => true, 'activated_at' => now()]);

        Audit::log(
            'model.rollback',
            'Rollback model ML dari versi '.($current?->version ?? '—').' ke versi "'.$previous->version.'".',
            $previous
        );

        return back()->with('success', 'Rollback berhasil. Versi "'.$previous->version.'" kini aktif.');
    }

    public function destroy(MlModel $model)
    {
        if ($model->is_active) {
            return back()->withErrors(['model' => 'Tidak dapat menghapus model yang sedang aktif. Aktifkan versi lain terlebih dahulu.']);
        }

        if ($model->s3_path !== null && Storage::disk('local')->exists($model->s3_path)) {
            Storage::disk('local')->delete($model->s3_path);
        }

        $version = $model->version;
        $model->delete();

        Audit::log('model.deleted', 'Model ML versi "'.$version.'" dihapus.');

        return back()->with('success', 'Model ML versi "'.$version.'" dihapus.');
    }

    public function storeDataset(Request $request)
    {
        $data = $request->validate([
            'images' => 'required|array|min:1|max:50',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'disease_id' => 'nullable|exists:diseases,id',
            'label' => 'required_without:disease_id|string|max:255',
        ], [
            'images.required' => 'Pilih minimal satu gambar dataset.',
            'images.*.image' => 'Hanya file gambar yang diizinkan.',
            'images.*.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'label.required_without' => 'Label penyakit wajib diisi jika tidak memilih penyakit.',
        ]);

        $disease = ! empty($data['disease_id']) ? Disease::find($data['disease_id']) : null;
        $label = $data['label'] ?? ($disease?->name ?? 'tanpa-label');

        foreach ($request->file('images') as $image) {
            $path = $image->storeAs('ml-datasets', uniqid('ds_').'.'.$image->getClientOriginalExtension(), 'local');

            MlDataset::create([
                'filename' => $image->getClientOriginalName(),
                'path' => $path,
                'disease_id' => $disease?->id,
                'label' => $label,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $count = count($request->file('images'));
        Audit::log('dataset.uploaded', $count.' gambar dataset label "'.$label.'" diunggah.');

        return back()->with('success', $count.' gambar dataset label "'.$label.'" berhasil diunggah.');
    }

    public function destroyDataset(MlDataset $dataset)
    {
        if ($dataset->path !== null && Storage::disk('local')->exists($dataset->path)) {
            Storage::disk('local')->delete($dataset->path);
        }

        $filename = $dataset->filename;
        $dataset->delete();

        Audit::log('dataset.deleted', 'Dataset "'.$filename.'" dihapus.');

        return back()->with('success', 'Dataset "'.$filename.'" dihapus.');
    }
}
