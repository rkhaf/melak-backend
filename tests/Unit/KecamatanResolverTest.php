<?php

namespace Tests\Unit;

use App\Support\KecamatanResolver;
use Tests\TestCase;

/**
 * Pemetaan koordinat → kecamatan untuk peta sebaran (FR-07.3).
 *
 * Memakai file asli resources/data/kecamatan_indramayu.geojson (31 kecamatan
 * Kab. Indramayu) supaya perubahan data wilayah ikut teruji.
 */
class KecamatanResolverTest extends TestCase
{
    public function test_loads_all_31_kecamatan_of_indramayu(): void
    {
        $names = app(KecamatanResolver::class)->names();

        $this->assertCount(31, $names);
        $this->assertContains('Indramayu', $names);
        $this->assertContains('Jatibarang', $names);
    }

    public function test_resolves_coordinates_inside_kecamatan(): void
    {
        $resolver = app(KecamatanResolver::class);

        $this->assertSame('Indramayu', $resolver->resolve(-6.346685, 108.335385));
        $this->assertSame('Jatibarang', $resolver->resolve(-6.44973, 108.317185));
        $this->assertSame('Haurgeulis', $resolver->resolve(-6.445695, 107.936725));
        $this->assertSame('Patrol', $resolver->resolve(-6.319895, 108.000665));
    }

    public function test_returns_null_outside_indramayu_or_for_missing_coordinates(): void
    {
        $resolver = app(KecamatanResolver::class);

        // Jakarta, laut utara Indramayu, titik di luar Indonesia.
        $this->assertNull($resolver->resolve(-6.2088, 106.8456));
        $this->assertNull($resolver->resolve(-5.5, 108.5));
        $this->assertNull($resolver->resolve(0.0, 0.0));

        // Koordinat belum tersedia.
        $this->assertNull($resolver->resolve(null, 108.325));
        $this->assertNull($resolver->resolve(-6.3275, null));
        $this->assertNull($resolver->resolve(null, null));
    }
}
