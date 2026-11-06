<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $appName }} — Deteksi Penyakit Tanaman & Komunitas Kebun</title>
  <meta name="description" content="Melak: scan foto tanaman, AI memprediksi penyakit dalam hitungan detik. Ensiklopedia penyakit, forum kebun, dan peta sebaran wabah dalam satu aplikasi." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}" />
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <style>
    .cta-card {
        position: relative;
        overflow: hidden;
    }

    .cta-bg-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        z-index: 0;
        opacity: 0.65;
        pointer-events: none;
    }

    .cta-copy, 
    .spark, 
    .cta-visual {
        position: relative;
        z-index: 1;
    }
  </style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Set kuncian koordinat khusus Indramayu
        var batasIndramayu = L.latLngBounds(
            L.latLng(-6.65, 107.85), // Batas Barat Daya
            L.latLng(-6.10, 108.60)  // Batas Timur Laut
        );

        // 2. Inisialisasi peta + fitur kuncian wilayah
        var map = L.map('peta-sebaran', {
            center: [-6.3275, 108.3249],
            zoom: 11,
            minZoom: 10,
            maxZoom: 18,
            maxBounds: batasIndramayu,
            maxBoundsViscosity: 1.0 // Efek membal pas digeser ke luar batas
        });

        // 3. Pake tampilan Google Maps Satelit Hybrid
        L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            subdomains: ['0', '1', '2', '3'],
            updateWhenIdle: true,
            keepBuffer: 2,
            maxZoom: 20
        }).addTo(map);

        // 4. Jaga-jaga biar peta gak render kotak abu-abu
        setTimeout(function () {
            map.invalidateSize();
        }, 500);

        // --- TITIK WABAH (DARI KODEMU) ---

        // 1. Wabah Merah (Kritis)
        var wabahMerah = L.circle([-6.2780, 108.3249], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.4,
            radius: 1000
        }).addTo(map);
        wabahMerah.bindPopup("<b>Waspada!</b><br>Tren penyakit tanaman meningkat di area ini.");

        // 2. Wabah Kuning 1 (Sedang)
        var wabahKuning1 = L.circle([-6.2980, 108.2820], {
            color: '#ffc107',
            fillColor: '#ffc107',
            fillOpacity: 0.4,
            radius: 2000
        }).addTo(map);
        wabahKuning1.bindPopup("Indikasi jamur skala sedang.");

        // 3. Wabah Kuning 2 / Oranye (Sedang)
        var wabahKuning2 = L.circle([-6.3280, 108.2220], {
            color: '#fdba74',
            fillColor: '#fdba74',
            fillOpacity: 0.4,
            radius: 2000
        }).addTo(map);
        wabahKuning2.bindPopup("Indikasi jamur skala sedang.");
    });
</script>

  <link rel="icon" type="image/svg+xml" href="images/logo_trns.png" />
  {{-- <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle cx='32' cy='32' r='32' fill='%230EA5E9'/%3E%3Cellipse cx='32' cy='30' rx='16' ry='10' fill='%23fff'/%3E%3Cpath d='M16 30 8 24v12z' fill='%23fff'/%3E%3Ccircle cx='42' cy='27' r='2.5' fill='%230369A1'/%3E%3C/svg%3E" /> --}}
</head>
<body>

  @php
      $waHref = 'https://wa.me/6289512668224';
      foreach ($contacts as $c) {
          if ($c->type === 'whatsapp') {
              $d = preg_replace('/\D/', '', $c->value);
              $d = str_starts_with($d, '0') ? '62'.substr($d, 1) : $d;
              $waHref = 'https://wa.me/'.$d;
              break;
          }
      }
  @endphp

  <!-- ══ Navbar pill melayang ══ -->
  <header class="navbar" id="navbar">
    <div class="container nav-pill">
      <a href="#hero" class="brand">
        <span class="brand-logo" aria-hidden="true">
          <img src="images/logo_trns.png" alt="" width="38" height="38" style="min-width: 32px;">
        </span>
        <span class="brand-name">{{ $appName }}</span>
      </a>

      <nav class="nav-links" id="navLinks">
        <a href="#kenapa">Kenapa Melak</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#penyakit">Ensiklopedia</a>
        <a href="#peta">Peta Sebaran</a>
        <a href="#faq">FAQ</a>
        <a href="#unduh" class="nav-cta">Unduh APK</a>
      </nav>

      <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- ══ Hero banner membulat ══ -->
  <section class="hero" id="hero">
    <div class="container">
      <div class="hero-banner">
        <div class="hero-bg" aria-hidden="true">
          <div class="bubble b1"></div><div class="bubble b2"></div><div class="bubble b3"></div>
          <div class="bubble b4"></div><div class="bubble b5"></div>
        </div>
        <div class="hero-inner">
          <div class="hero-copy reveal">
            <span class="hero-badge">Prediksi AI · Komunitas Botanis</span>
            <h1>Deteksi penyakit tanaman <span class="hl">sejak dini</span>, rawat kebunmu dengan tepat</h1>
            <p class="hero-sub">
              Cukup foto tanaman yang sakit — Melak menebak jenis penyakitnya dalam hitungan detik,
              lengkap dengan gejala, panduan penanganan, dan jawaban dari komunitas kebun.
            </p>
            <div class="hero-actions">
              <a href="#unduh" class="btn btn-light">Unduh Aplikasi</a>
              <a href="#cara-kerja" class="btn btn-ghost">Lihat Cara Kerja</a>
            </div>
            <ul class="hero-stats">
              <li><strong>50+</strong><span>Jenis penyakit tercatat</span></li>
              <li><strong>&lt;10 dtk</strong><span>Rata-rata hasil scan</span></li>
              <li><strong>24/7</strong><span>Siaga bersama komunitas</span></li>
            </ul>
          </div>

          <div class="hero-visual reveal delay-1" aria-hidden="true">
            <div class="phone">
              <div class="phone-notch"></div>
              <div class="phone-screen">
                <img src="images/dashboard.jpeg" alt="">
              </div>
            </div>
            <div class="float-chip chip-1">Penanganan: garam tanaman + naikkan suhu</div>
            <div class="float-chip chip-2">3 jawaban baru di Forum</div>
          </div>
        </div>
        <div class="hero-dots" aria-hidden="true"><span class="on"></span><span></span><span></span></div>
      </div>
    </div>
  </section>

  <!-- ══ Strip kepercayaan ══ -->
  <section class="trust">
    <div class="container trust-row">
      <span class="trust-pill reveal"><i>✓</i> Gratis selamanya</span>
      <span class="trust-pill reveal delay-1"><i>✓</i> Hasil &lt; 10 detik</span>
      <span class="trust-pill reveal delay-2"><i>✓</i> 50+ penyakit tercatat</span>
      <span class="trust-pill reveal delay-3"><i>✓</i> Data aman &amp; privat</span>
    </div>
  </section>

  <!-- ══ Kenapa Melak (6 kartu fitur) ══ -->
  <section class="section" id="kenapa">
    <div class="container">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">Kenapa Melak</span> --}}
        <h2>Satu aplikasi untuk seluruh siklus kebun</h2>
        <p>Dari deteksi dini sampai berbagi pengalaman — semuanya dirancang untuk pekebun Indonesia.</p>
      </div>
      <div class="feature-grid">
        <article class="card reveal">
          <span class="icon-circle ic-primary">
            <svg viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Zm8-8.9V5a1 1 0 0 0-1-1h-1.6L16 2.4 14.6 4H9.4L8 2.4 6.6 4H5a1 1 0 0 0-1 1v1.6L2.4 6.6 4 8v8l-1.6 1.4L4 18.6V19a1 1 0 0 0 1 1h1.6L8 21.6 9.4 20h5.2l1.4 1.6L17.6 20H19a1 1 0 0 0 1-1v-1.6l1.6-1.4L20 14V8l1.6-1.4L20 5.1Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          </span>
          <h3>Scan Foto, AI Bekerja</h3>
          <p>Unggah 1–5 foto tanaman. Model prediksi memberi nama penyakit, skor keyakinan, dan gambaran area terdampak (Grad-CAM).</p>
        </article>

        <article class="card reveal delay-1">
          <span class="icon-circle ic-secondary">
            <svg viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5Zm0 0V20.5M8 7h8M8 11h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <h3>Ensiklopedia Penyakit</h3>
          <p>Gejala, penyebab, langkah penanganan, obat dosis, pencegahan, hingga perkiraan hari sembuh — tersusun rapi dan mudah dicari.</p>
        </article>

        <article class="card reveal delay-2">
          <span class="icon-circle ic-accent">
            <svg viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-5A8 8 0 1 1 21 12Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 11h6M9 14h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
          </span>
          <h3>Forum Komunitas</h3>
          <p>Tanyakan hasil scan, bagikan kasus langka, dan terima suara terbaik dari pekebun serta ahli lain di seluruh Indonesia.</p>
        </article>

        <article class="card reveal">
          <span class="icon-circle ic-danger">
            <svg viewBox="0 0 24 24"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
          </span>
          <h3>Peta Sebaran Wabah</h3>
          <p>Lihat titik kasus penyakit per provinsi secara real-time dan dapatkan peringatan outbreak sebelum menyebar ke kebunmu.</p>
        </article>

        <article class="card reveal delay-1">
          <span class="icon-circle ic-primary">
            <svg viewBox="0 0 24 24"><path d="M3 16c2.5-2 4.5 1.5 7-0.5S14 14 16.5 15.5 21 15 21 15M3 20h18M5 12V6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5V12" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <h3>Manajemen kebun</h3>
          <p>Catat kebun, jenis tanaman, dan kapasitas. Skor kesehatan kebun + grafik tren membantumu membaca kondisi budidaya.</p>
        </article>

        <article class="card reveal delay-2">
          <span class="icon-circle ic-secondary">
            <svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6ZM10.5 19a2 2 0 0 0 3 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <h3>Notifikasi Pintar</h3>
          <p>Hasil scan, balasan forum, tips harian, dan peringatan wabah dikirim tepat waktu — atur per tipe sesukamu.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ══ Untuk siapa (3 kartu berwarna) ══ -->
  <section class="section section-alt" id="untuk-siapa">
    <div class="container">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">Untuk Siapa</span> --}}
        <h2>Dibuat untuk petani dan pekebun</h2>
        <p>Dari kebun halaman rumah sampai pembudidayaan komersial.</p>
      </div>
      <div class="pillar-grid">
        <article class="pillar pillar-sky reveal">
          <span class="pillar-emoji" aria-hidden="true">🪴</span>
          <h3>Pekebun Kecil</h3>
          <p>Modal ponsel saja — scan penyakit kapan saja, tanya komunitas, dan ikuti panduan penanganan langkah demi langkah.</p>
          <ul>
            <li>Diagnosis awal tanpa alat</li>
            <li>Panduan obat & dosis</li>
            <li>Tanya jawab komunitas</li>
          </ul>
        </article>
        <article class="pillar pillar-teal reveal delay-1">
          <span class="pillar-emoji" aria-hidden="true">🏪</span>
          <h3>Pembudidaya Komersial</h3>
          <p>Kelola banyak kebun dalam satu tempat, pantau skor kesehatan, dan baca tren sebelum jatuh korban massal.</p>
          <ul>
            <li>Manajemen multi-kebun</li>
            <li>Skor &amp; grafik kesehatan</li>
            <li>Peringatan wabah dini</li>
          </ul>
        </article>
        <article class="pillar pillar-amber reveal delay-2">
          <span class="pillar-emoji" aria-hidden="true">🎓</span>
          <h3>Penyuluh &amp; Akademisi</h3>
          <p>Data sebaran wabah per provinsi, ensiklopedia lengkap, dan materi edukasi siap pakai untuk mendampingi petani.</p>
          <ul>
            <li>Peta panas per provinsi</li>
            <li>Ensiklopedia rujukan</li>
            <li>Statistik kasus terkini</li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <!-- ══ Cara Kerja ══ -->
  <section class="section" id="cara-kerja">
    <div class="container">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">Cara Kerja</span> --}}
        <h2>Tiga langkah, satu jawaban</h2>
        <p>Tanpa alat khusus — cukup ponsel di saku pekebun.</p>
      </div>
      <div class="steps">
        <div class="step reveal">
          <span class="step-num">1</span>
          <h3>Foto tanaman</h3>
          <p>Ambil 1–5 foto tanaman yang tampak lesu, berbintik, atau luka. Kompresi otomatis, hemat kuota.</p>
        </div>
        <div class="step-line" aria-hidden="true"></div>
        <div class="step reveal delay-1">
          <span class="step-num">2</span>
          <h3>AI menganalisis</h3>
          <p>Model prediksi bekerja di latar belakang. Hasil muncul rata-rata di bawah 10 detik, maksimal 30 detik.</p>
        </div>
        <div class="step-line" aria-hidden="true"></div>
        <div class="step reveal delay-2">
          <span class="step-num">3</span>
          <h3>Ikuti penanganan</h3>
          <p>Dapatkan panduan langkah-demi-langkah, dosis obat, dan tanya komunitas bila masih ragu.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ Ensiklopedia preview ══ -->
  <section class="section section-alt" id="penyakit">
    <div class="container">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">Ensiklopedia</span> --}}
        <h2>Kenali penyakit, kenali gejalanya</h2>
        <p>Kenali penyakit tanaman pangan dan sayuran dengan panduan penanganan di aplikasi.</p>
      </div>
      <div class="disease-grid">
        <article class="disease-card reveal">
          <div class="disease-top"><span class="pill pill-sedang">Sedang</span><span class="disease-tag">Bakteri</span></div>
          <h3>Bercak Bakteri pada Tomat</h3>
          <p>Bercak gelap pada daun dan bercak kasar pada buah tomat.</p>
          <div class="disease-foot"><span>Sanitasi kebun · Bibit sehat</span></div>
        </article>
        <article class="disease-card reveal delay-1">
          <div class="disease-top"><span class="pill pill-berat">Berat</span><span class="disease-tag">Jamur</span></div>
          <h3>Hawar Daun Jagung</h3>
          <p>Lesi memanjang berbentuk cerutu dapat membuat daun jagung mengering.</p>
          <div class="disease-foot"><span>Pantau gejala · Kelola residu</span></div>
        </article>
        <article class="disease-card reveal delay-2">
          <div class="disease-top"><span class="pill pill-sedang">Sedang</span><span class="disease-tag">Jamur</span></div>
          <h3>Bercak Alternaria pada Tomat</h3>
          <p>Bercak cokelat dengan lingkaran konsentris sering muncul pada daun tua.</p>
          <div class="disease-foot"><span>Buang daun sakit · Rotasi tanaman</span></div>
        </article>
        <article class="disease-card reveal delay-3">
          <div class="disease-top"><span class="pill pill-ringan">Ringan</span><span class="disease-tag">Kondisi sehat</span></div>
          <h3>Jagung Sehat</h3>
          <p>Tidak tampak gejala penyakit umum pada bagian tanaman yang difoto.</p>
          <div class="disease-foot"><span>Pantau tanaman secara rutin</span></div>
        </article>
      </div>
    </div>
  </section>

  <!-- ══ Peta sebaran ══ -->
  <section class="section" id="peta">
    <div class="container peta-inner">
      <div class="peta-copy reveal">
        {{-- <span class="eyebrow">Peta Sebaran</span> --}}
        <h2>Wabah terlihat sebelum menyentuh kebunmu</h2>
        <p>
          Pantau sebaran kasus penyakit tanaman di sekitar wilayah Indramayu secara real-time. Ketuk titik indikasi pada peta untuk melihat tingkat keparahan & jenis penyakit.
        </p>
        <ul class="check-list">
          <li>Pemetaan radius kasus berbasis lokasi</li>
          <li>Indikator zona bahaya (Ringan hingga Kritis)</li>
          <li>Akses langsung ke Ensiklopedia Penyakit</li>
        </ul>
        <a href="#unduh" class="btn btn-primary">Lihat di Aplikasi</a>
      </div>
      <div class="peta-visual reveal delay-1" aria-hidden="true">
        <div class="map-card">

          <div class="map-card" style="overflow: hidden;">
              <!-- Ini kanvas buat peta aslinya. Wajib dikasih tinggi (height) -->
              <div id="peta-sebaran" style="width: 100%; height: 320px; border-radius: 16px;z-index: 0;"></div>
              
              <!-- Bagian map-legend biarin aja tetep di bawahnya -->
              <div class="map-legend">
                  <!-- ... kodingan legend dot ringan, sedang, berat ... -->
              </div>
          </div>
          {{-- <div class="map-blob">
            <span class="heat h1"></span><span class="heat h2"></span>
            <span class="heat h3"></span><span class="heat h4"></span>
            <span class="map-pin p1"></span><span class="map-pin p2"></span><span class="map-pin p3"></span>
          </div> --}}
          <div class="map-legend">
            <span><i class="dot d-ringan"></i> Ringan</span>
            <span><i class="dot d-sedang"></i> Sedang</span>
            <span><i class="dot d-berat"></i> Berat</span>
            <span><i class="dot d-kritis"></i> Kritis</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ Testimoni carousel ══ -->
  <section class="section section-alt" id="testimoni">
    <div class="container">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">Komunitas</span> --}}
        <h2>Dipercaya pekebun di lapangan</h2>
      </div>
      <div class="carousel reveal">
        <button class="car-btn" data-car-prev="quotes" aria-label="Sebelumnya">‹</button>
        <div class="car-track" data-car-track="quotes">
          <figure class="quote-card">
            <div class="quote-stars" aria-label="5 dari 5">★★★★★</div>
            <blockquote>Daun tomat saya mulai berbintik setelah hujan. Panduan Melak membantu saya memeriksa gejala dan menjaga kebun tetap bersih.</blockquote>
            <figcaption><span class="avatar">AP</span><span><strong>Agus P.</strong><span>Petani tomat · Karangsong</span></span></figcaption>
          </figure>
          <figure class="quote-card">
            <div class="quote-stars" aria-label="5 dari 5">★★★★★</div>
            <blockquote>Forum komunitas membantu saya berdiskusi soal bercak daun jagung dan cara memantau penyebarannya.</blockquote>
            <figcaption><span class="avatar">N</span><span><strong>Ningsih</strong><span>Petani jagung · Pabean Ilir</span></span></figcaption>
          </figure>
          <figure class="quote-card">
            <div class="quote-stars" aria-label="5 dari 5">★★★★★</div>
            <blockquote>Panduan sanitasi dan pemantauan rutin membantu saya menjaga tanaman cabai tetap sehat.</blockquote>
            <figcaption><span class="avatar">B</span><span><strong>Budiono</strong><span>Petani cabai · Losarang</span></span></figcaption>
          </figure>
        </div>
        <button class="car-btn" data-car-next="quotes" aria-label="Berikutnya">›</button>
      </div>
    </div>
  </section>

  <!-- ══ CTA Unduh ══ -->
  <section class="section cta-section" id="unduh">
    <div class="container">
      <div class="cta-card reveal">
        <img src="{{ asset('images/kebun.jpg') }}" class="cta-bg-img" alt="" aria-hidden="true">
        <span class="spark s1" aria-hidden="true">✦</span>
        <span class="spark s2" aria-hidden="true">✦</span>
        <span class="spark s3" aria-hidden="true">✦</span>
        <div class="cta-copy">
          <h2>Siap jaga kebunmu dengan {{ $appName }}?</h2>
          <p>Unduh aplikasinya gratis — scan penyakit, tanya komunitas, dan pantau wabah dari genggaman.</p>
          <div class="cta-actions">
            <a href="{{ $apkUrl ?: '#' }}" class="btn btn-light" id="apkBtn" @if(!$apkUrl) aria-describedby="apkNote" @endif>Unduh APK untuk Android</a>
            <a href="#kenapa" class="btn btn-outline-light">Pelajari Fitur</a>
          </div>
          <span class="cta-note" id="apkNote">Versi {{ $apkVersion }} · Android 6.0+ · Data aman</span>
        </div>
        <div class="cta-visual" aria-hidden="true">

        </div>
      </div>
    </div>
  </section>

  <!-- ══ Marquee fitur ══ -->
  <div class="marquee" aria-hidden="true">
    <div class="marquee-track">
      <span>Deteksi AI</span><i>✦</i><span>Forum Komunitas</span><i>✦</i><span>Peta Sebaran Wabah</span><i>✦</i>
      <span>Ensiklopedia Penyakit</span><i>✦</i><span>Manajemen kebun</span><i>✦</i><span>Notifikasi Pintar</span><i>✦</i>
      <span>Tips Harian</span><i>✦</i><span>Skor Kesehatan Kebun</span><i>✦</i>
      <span>Deteksi AI</span><i>✦</i><span>Forum Komunitas</span><i>✦</i><span>Peta Sebaran Wabah</span><i>✦</i>
      <span>Ensiklopedia Penyakit</span><i>✦</i><span>Manajemen kebun</span><i>✦</i><span>Notifikasi Pintar</span><i>✦</i>
      <span>Tips Harian</span><i>✦</i><span>Skor Kesehatan Kebun</span><i>✦</i>
    </div>
  </div>

  <!-- ══ FAQ accordion ══ -->
  <section class="section" id="faq">
    <div class="container faq-wrap">
      <div class="section-head reveal">
        {{-- <span class="eyebrow">FAQ</span> --}}
        <h2>Pertanyaan yang sering diajukan</h2>
        <p>Belum menemukan jawaban? Hubungi kami lewat kontak di bawah.</p>
      </div>
      <div class="faq-list reveal" id="faqList">
        @forelse ($faqs as $i => $faq)
          <details class="faq-item{{ $i >= 4 ? ' faq-extra' : '' }}">
            <summary>{{ $faq->question }}<span class="faq-icon" aria-hidden="true"></span></summary>
            <div class="faq-body"><p>{{ $faq->answer }}</p></div>
          </details>
        @empty
          {{-- <details class="faq-item">
            <summary>Bagaimana cara AI Melak mengklasifikasikan tanaman?<span class="faq-icon" aria-hidden="true"></span></summary>
            <div class="faq-body"><p>Unggah foto tanaman yang ingin diperiksa. Sistem menganalisis pola visual dan menampilkan hasil serta tingkat keyakinannya.</p></div>
          </details>
          <details class="faq-item">
            <summary>Apakah aplikasi Melak dapat digunakan secara gratis?<span class="faq-icon" aria-hidden="true"></span></summary>
            <div class="faq-body"><p>Melak dapat digunakan secara gratis untuk membantu petani mengenali gejala penyakit tanaman dan berdiskusi dengan komunitas.</p></div>
          </details>
          <details class="faq-item">
            <summary>Jenis tanaman apa saja yang didukung?<span class="faq-icon" aria-hidden="true"></span></summary>
            <div class="faq-body"><p>Klasifikasi yang disediakan backend mencakup tomat, jagung, paprika, dan cabai. Cakupan hasil bergantung pada model klasifikasi yang terhubung.</p></div>
          </details> --}}
        @endforelse
      </div>
      @if ($faqs->count() > 4)
        <div class="faq-more-wrap">
          <button class="faq-more" id="faqMore" type="button">Lihat semua pertanyaan</button>
        </div>
      @endif
    </div>
  </section>

  <!-- ══ Footer gradasi membulat ══ -->
  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <a href="#hero" class="brand">
          <span class="brand-name">{{ $appName }}</span>
        </a>
        <p>Prediksi penyakit tanaman &amp; platform komunitas kebun untuk pekebun Indonesia.</p>
        <div class="footer-social">
          <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg></a>
          <a href="{{ $waHref }}" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.5 9.5c0 3.5 2.5 6 6 6l1.5-1.5-2-1.5-.8.8c-1-.5-2-1.5-2.5-2.5l.8-.8L10 8 8.5 9.5Z" fill="currentColor"/></svg></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Produk</h4>
        <a href="#kenapa">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#peta">Peta Sebaran</a>
        <a href="#faq">FAQ</a>
        <a href="#unduh">Unduh</a>
      </div>
      <div class="footer-col">
        <h4>Bantuan</h4>
        <a href="{{ route('admin.login') }}">Masuk Admin</a>
        <a href="#">Kebijakan Privasi</a>
        <a href="#">Syarat &amp; Ketentuan</a>
      </div>
      <div class="footer-col">
        <h4>Kontak</h4>
        @forelse ($contacts as $c)
          @php
              $raw = trim($c->value);
              if ($c->type === 'email') {
                  $href = 'mailto:'.$raw;
              } elseif (in_array($c->type, ['phone', 'tel'])) {
                  $href = 'tel:'.preg_replace('/[^\d+]/', '', $raw);
              } elseif ($c->type === 'whatsapp') {
                  $d = preg_replace('/\D/', '', $raw);
                  $d = str_starts_with($d, '0') ? '62'.substr($d, 1) : $d;
                  $href = 'https://wa.me/'.$d;
              } else {
                  $href = str_starts_with($raw, 'http') ? $raw : 'https://'.preg_replace('/^www\./', '', $raw);
              }
          @endphp
          <a href="{{ $href }}" @if(in_array($c->type, ['whatsapp', 'website'])) target="_blank" rel="noopener" @endif>{{ $c->title }}: {{ $c->value }}</a>
        @empty
          <a href="mailto:support@melak.id">support@melak.id</a>
        @endforelse
      </div>
    </div>
    <div class="container footer-bottom">
      <span>© <span id="year">2026</span> {{ $appName }}. Dibuat untuk pekebun Indonesia.</span>
      <span>versi {{ $apkVersion }}</span>
    </div>
  </footer>

  <!-- ══ Tombol melayang ══ -->
  <a class="fab fab-wa" href="{{ $waHref }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <svg viewBox="0 0 24 24" width="26" height="26"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.5 9.5c0 3.5 2.5 6 6 6l1.5-1.5-2-1.5-.8.8c-1-.5-2-1.5-2.5-2.5l.8-.8L10 8 8.5 9.5Z" fill="currentColor"/></svg>
  </a>
  <button class="fab fab-top" id="backToTop" aria-label="Kembali ke atas">
    <svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 19V5m0 0-6 6m6-6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </button>

  <script src="{{ asset('js/landing.js') }}"></script>

</body>
</html>
