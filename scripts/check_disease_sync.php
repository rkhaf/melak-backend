<?php

/** Cek kesesuaian label_map.json ML service dengan tabel diseases (FR-10.11). */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Disease;
use Illuminate\Support\Str;

$diseases = Disease::query()->orderBy('id')->get(['id', 'slug', 'name', 'severity', 'is_published']);
$labels = json_decode(file_get_contents(__DIR__.'/../../ml-service/label_map.json'), true)['labels'];

printf("DB: %s | diseases: %d | label_map: %d\n\n", config('database.default'), $diseases->count(), count($labels));

printf("%-4s %-30s %-38s %-9s\n", '#', 'label_map slug', 'diseases slug (kandidat)', 'cocok?');
echo str_repeat('-', 90)."\n";

$slugs = $diseases->pluck('slug')->all();
$names = $diseases->pluck('name')->all();

foreach ($labels as $i => $row) {
    $slug = $row['slug'];
    $label = $row['label'];

    $match = collect($slugs)->first(fn ($s) => $s === $slug || Str::slug($s) === Str::slug($slug));

    if ($match === null) {
        $match = collect($slugs)->first(fn ($s) => Str::contains(Str::slug($s), [Str::slug($slug), Str::slug($label)]));
    }

    printf(
        "%-4d %-30s %-38s %s\n",
        $i,
        $slug,
        $match ?? '— tidak ada —',
        $match === null ? 'X' : '= se'
    );
}

echo "\n== tabel diseases ==\n";

foreach ($diseases as $d) {
    printf("id=%-3d slug=%-32s name=%-40s sev=%-8s pub=%s\n", $d->id, $d->slug, $d->name, $d->severity, $d->is_published ? 'y' : 'n');
}
