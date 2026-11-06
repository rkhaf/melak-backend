# resources/data

## `kecamatan_indramayu.geojson`

Polygon batas **31 kecamatan Kabupaten Indramayu** dipakai `app/Support/KecamatanResolver.php`
untuk memetakan koordinat GPS scan → nama kecamatan (kolom `fish_scans.kecamatan`),
yang menjadi dasar agregasi peta sebaran per kecamatan (FR-07.3, API.md §7.11/§7.13).

- **Sumber data:** [Alf-Anas/batas-administrasi-indonesia](https://github.com/Alf-Anas/batas-administrasi-indonesia),
  file `2020/Batas Kecamatan SHP.zip` (batas administrasi Indonesia, atribut
  `Provinsi` / `Kab_Kota` / `Kecamatan` / `Kode_Kec`).
- **Filter:** `Kode_Kab = 3212` (Kabupaten Indramayu, kode Kemendagri/BPS) — 31 fitur.
- **Ringkasan:** Douglas-Peucker toleransi `0.0008°` (~88 m), koordinat dibulatkan 5 desimal (~1,1 m),
  total ±57 KB / 2.554 titik.
- **Regenerasi:**

  ```bash
  python scripts/extract_kecamatan_geojson.py <path/Batas Kecamatan SHP.zip>
  ```

File ini hanya menentukan *label kecamatan* untuk akumulasi kasus; koordinat titik scan
di endpoint peta tidak pernah disederhanakan (tetap 6 desimal).
