<!DOCTYPE html>
<html lang="id" dir="ltr">

<head>
    <!-- Metadata Dasar -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Presensi MTs Negeri 1 Pandeglang adalah sistem kehadiran otomatis berbasis kartu RFID yang cepat, akurat, dan mudah diintegrasikan.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Yahya Zulfikri">

    <!-- Metadata Open Graph (Sosial Media) -->
    <meta property="og:title" content="Presensi MTs Negeri 1 Pandeglang | Sistem Kehadiran Otomatis Berbasis RFID">
    <meta property="og:description"
        content="Solusi presensi siswa & pegawai real-time, hemat biaya, dan terintegrasi API.">
    <meta property="og:image" content="{{ asset('images/default.png') }}">

    <!-- PWA & Mobile Optimization -->
    <meta name="apple-mobile-web-app-title" content="Presensi MTs Negeri 1 Pandeglang">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0f0f23" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#D1EFD7" media="(prefers-color-scheme: light)">

    <!-- Preload Resources (Font & Gambar Penting) -->
    <link rel="preload" href="{{ asset('icons/512x512.png') }}" as="image">
    <link rel="preload" as="font" href="{{ asset('fonts/Ubuntu-Light.woff2') }}" type="font/woff2"
        crossorigin="anonymous">
    <link rel="preload" as="font" href="{{ asset('fonts/Ubuntu-Regular.woff2') }}" type="font/woff2"
        crossorigin="anonymous">
    <link rel="preload" as="font" href="{{ asset('fonts/Ubuntu-Medium.woff2') }}" type="font/woff2"
        crossorigin="anonymous">
    <link rel="preload" as="font" href="{{ asset('fonts/Ubuntu-Bold.woff2') }}" type="font/woff2"
        crossorigin="anonymous">

    <!-- Icon & PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/192x192.png') }}">
    <link rel="apple-touch-startup-image" href="/icons/512x512.png">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/192x192.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">

    <!-- Judul Halaman -->
    <title>Presensi MTs Negeri 1 Pandeglang | Sistem Kehadiran Otomatis Berbasis RFID</title>
</head>

<body>
    <header>
        <nav class="container" role="navigation" aria-label="Navigasi utama">
            <a href="#" class="logo">Presensi RFID</a>
            <button class="menu-toggle" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="main-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-links">
                <li><a href="https://docs.mtsn1pandeglang.sch.id/#keunggulan-utama" target="_blank">Fitur</a></li>
                <li><a href="https://docs.mtsn1pandeglang.sch.id" target="_blank">Dokumentasi</a></li>
                <li><a href="#testimonials">Testimoni</a></li>
            </ul>
        </nav>
    </header>

    <main>
        {{-- Hero Section --}}
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="pulse"><span>Presensi Otomatis Berbasis RFID</span></h1>
                    <p class="hero-subtitle">
                        Tingkatkan efisiensi kehadiran siswa dan pegawai dengan sistem presensi RFID otomatis yang
                        cepat, akurat, dan real-time.
                    </p>
                    <div class="code-snippet pulse">
                        <code><b>ESP32 + MFRC522 + OLED + Buzzer + HTTP API</b></code>
                    </div>
                    <a href="/admin" class="cta-button">Login Aplikasi</a>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section class="features" id="features">
            <div class="container">
                <h2 class="section-title">Kenapa Memilih Presensi RFID?</h2>
                <p class="section-subtitle">
                    Sistem presensi cerdas berbasis RFID dengan integrasi backend yang fleksibel, real-time, dan mudah
                    disesuaikan.
                </p>
                <div class="features-grid">
                    @include('landing.features')
                </div>
            </div>
        </section>

        {{-- Code Example Section --}}
        <section id="docs" class="code-examples">
            <div class="container">
                <h2 class="section-title">Contoh Implementasi Firmware</h2>
                <p class="section-subtitle">
                    Gunakan ESP32 dan sensor MFRC522 untuk mengirim data ke server secara otomatis.
                </p>
                <div class="code-container">
                    <div class="code-line"><span class="keyword">#include</span> &lt;WiFi.h&gt;</div>
                    <div class="code-line"><span class="keyword">#include</span> &lt;HTTPClient.h&gt;</div>
                    <div class="code-line"><span class="keyword">#include</span> &lt;MFRC.h&gt;</div>
                    <div class="code-line"><span class="function">void loop()</span> {</div>
                    <div class="code-line"> <span class="function">if (cardDetected())</span> sendToServer(uid);</div>
                    <div class="code-line">}</div>
                </div>
            </div>
        </section>

        {{-- Testimonials Section --}}
        <section id="testimonials" class="testimonials">
            <div class="container">
                <h2 class="section-title">Terbukti Dipercaya oleh Pengguna</h2>
                <p class="section-subtitle">Apa kata pengguna kami setelah menggunakan Presensi RFID.</p>
                <div class="testimonials-grid">
                    @include('landing.testimonials')
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer>
        <div class="container">
            {{-- <div class="footer-content">
            @include('landing.footer_links')
            </div> --}}
            <div class="footer-bottom">
                <p>&copy; 2022 - {{ date('Y') }} <a href="https://mtsn1pandeglang.sch.id" target="_blank"
                        style="color: inherit; text-decoration: none;">MTs Negeri 1 Pandeglang</a>.<br>Seluruh hak
                    cipta
                    dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="{{ asset('js/landing.js') }}" defer></script>

</body>

</html>
