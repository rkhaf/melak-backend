#!/usr/bin/env python3
"""
Ekstrak polygon kecamatan Kab. Indramayu dari shapefile batas administrasi
menjadi GeoJSON ringkas untuk resolver kecamatan backend (FR-07.3).

Sumber data : https://github.com/Alf-Anas/batas-administrasi-indonesia
              (file "2020/Batas Kecamatan SHP.zip" — batas administrasi Indonesia,
               atribut Provinsi/Kab_Kota/Kecamatan/Kode_Kec)
Dipakai oleh : Kaliwedi-Backend/app/Support/KecamatanResolver.php
Atribusi wajib disertakan ketika file hasil diubah/didistribusikan ulang.

Pemakaian:
    python scripts/extract_kecamatan_geojson.py <path_zip> [path_keluaran]
"""

from __future__ import annotations

import json
import os
import sys
import tempfile
import zipfile

import shapefile

KODE_KAB = "3212"  # Kabupaten Indramayu (Kemendagri/BPS)
ROUND = 5  # ~1,1 m — cukup presisi untuk titik scan & batas kecamatan
TOLERANCE = 0.0008  # Douglas-Peucker, ~88 m


def perpendicular_distance(p, a, b) -> float:
    (x, y), (x1, y1), (x2, y2) = p, a, b
    dx, dy = x2 - x1, y2 - y1
    if dx == dy == 0:
        return ((x - x1) ** 2 + (y - y1) ** 2) ** 0.5
    t = ((x - x1) * dx + (y - y1) * dy) / (dx * dx + dy * dy)
    t = max(0.0, min(1.0, t))
    px, py = x1 + t * dx, y1 + t * dy
    return ((x - px) ** 2 + (y - py) ** 2) ** 0.5


def simplify(points, tolerance: float):
    """Douglas-Peucker (iteratif) — pertahankan titik pertama & terakhir."""
    if len(points) < 3:
        return points

    keep = [False] * len(points)
    keep[0] = keep[-1] = True
    stack = [(0, len(points) - 1)]

    while stack:
        start, end = stack.pop()
        max_dist, index = 0.0, -1
        for i in range(start + 1, end):
            d = perpendicular_distance(points[i], points[start], points[end])
            if d > max_dist:
                max_dist, index = d, i
        if max_dist > tolerance and index != -1:
            keep[index] = True
            stack.append((start, index))
            stack.append((index, end))

    return [p for p, k in zip(points, keep) if k]


def clean_ring(ring, tolerance: float):
    pts = [(round(x, ROUND), round(y, ROUND)) for x, y in ring]
    if len(pts) > 1 and pts[0] == pts[-1]:
        pts = pts[:-1]
    pts = simplify(pts, tolerance)
    if len(pts) < 3:
        return None
    return pts + [pts[0]]  # tutup ring


def main() -> int:
    zip_path = sys.argv[1]
    out_path = sys.argv[2] if len(sys.argv) > 2 else os.path.join(
        os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
        "resources", "data", "kecamatan_indramayu.geojson",
    )

    with zipfile.ZipFile(zip_path) as zf, tempfile.TemporaryDirectory() as tmp:
        members = {}
        for info in zf.infolist():
            ext = os.path.splitext(info.filename)[1].lower()
            if ext in {".shp", ".shx", ".dbf"}:
                target = os.path.join(tmp, "batas" + ext)
                with zf.open(info) as src, open(target, "wb") as dst:
                    dst.write(src.read())
                members[ext] = target

        reader = shapefile.Reader(members[".shp"])
        features = []
        kab_seen = set()

        try:
            for shape_record in reader.iterShapeRecords():
                rec = shape_record.record
                kode_kab = str(rec[2]).strip()  # Kode_Kab
                if kode_kab != KODE_KAB:
                    continue

                kab_seen.add(str(rec[5]).strip())  # Kab_Kota

                nama = str(rec[6]).strip().title()  # Kecamatan (INDRAMAYU → Indramayu)
                kode_kec = str(rec[3]).strip()  # Kode_Kec (mis. 321223)

                geo = shape_record.shape.__geo_interface__
                polygons = (
                    [geo["coordinates"]]
                    if geo["type"] == "Polygon"
                    else geo["coordinates"]
                )

                rings_out = []
                point_count = 0
                for polygon in polygons:
                    for ring in polygon:
                        cleaned = clean_ring(ring, TOLERANCE)
                        if cleaned is None:
                            continue
                        rings_out.append(cleaned)
                        point_count += len(cleaned)

                if not rings_out:
                    continue

                features.append({
                    "type": "Feature",
                    "properties": {"kecamatan": nama, "kode": kode_kec},
                    "geometry": {"type": "Polygon", "coordinates": rings_out},
                    "_points": point_count,
                })
        finally:
            # Windows tidak mengizinkan hapus file yang masih dibuka reader.
            reader.close()

    features.sort(key=lambda f: f["properties"]["kecamatan"].lower())
    total_points = sum(f.pop("_points") for f in features)

    if len(features) != 31:
        print(f"PERHATIAN: jumlah kecamatan {len(features)} (ekspektasi 31)", file=sys.stderr)

    collection = {
        "type": "FeatureCollection",
        "name": "kecamatan_kabupaten_indramayu",
        "attribution": (
            "Sumber: batas administrasi Indonesia (github.com/Alf-Anas/"
            "batas-administrasi-indonesia). Disederhanakan untuk Melak."
        ),
        "features": features,
    }

    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with open(out_path, "w", encoding="utf-8") as fh:
        json.dump(collection, fh, ensure_ascii=False, separators=(",", ":"))

    size = os.path.getsize(out_path)
    print(f"kabupaten : {sorted(kab_seen)}")
    print(f"kecamatan : {len(features)}")
    print(f"titik     : {total_points}")
    print(f"ukuran    : {size:,} bytes")
    for f in features:
        print(f"  - {f['properties']['kecamatan']} ({f['properties']['kode']})")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
