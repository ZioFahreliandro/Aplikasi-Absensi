<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aplikasi Absensi Selfie</title>
  <!-- CSRF Token for Laravel Security -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- CSS Stylesheet -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="app-header">
    <div class="header-logo">
      <div class="logo-icon">
        <i data-lucide="shield-check"></i>
      </div>
      <div class="logo-text">
        <h1>Absen<span>Go</span></h1>
      </div>
    </div>
    <nav class="header-nav">
      <button id="nav-kiosk" class="nav-btn active">
        <i data-lucide="camera"></i> Absen
      </button>

      @guest
        <a href="{{ route('login') }}" class="nav-btn">
          <i data-lucide="log-in"></i> Login Google
        </a>
      @endguest

      @auth
        <form action="{{ route('logout') }}" method="POST" class="inline-block">
          @csrf
          <button type="submit" class="nav-btn">
            <i data-lucide="log-out"></i> Logout
          </button>
        </form>
      @endauth
    </nav>
  </header>

  <main class="app-container">

    <!-- 1. KIOSK ABSEN VIEW -->
    <section id="view-kiosk" class="view-section active">
      <div class="kiosk-grid">

        <!-- Left Side: Interactive Attendance Form & Clock -->
        <div class="kiosk-left">
          <!-- Premium Clock Card -->
          <div class="card clock-card">
            <div class="clock-icon">
              <i data-lucide="clock"></i>
            </div>
            <div>
              <div id="live-clock" class="time-display">00:00:00</div>
              <div id="live-date" class="date-display">Hari ini, Tanggal Bulan Tahun</div>
            </div>
          </div>

          <!-- Attendance Action Card -->
          <div class="card attendance-card">
            <h2 class="section-title">Absensi</h2>
            <p class="section-subtitle">Ambil foto selfie untuk melakukan absensi.</p>

            <form id="attendance-form" class="app-form">
              <!-- Clock In/Out buttons -->
              <div class="attendance-actions">
                <button type="button" id="btn-clock-in" class="action-btn clock-in-btn">
                  <i data-lucide="log-in"></i> Masuk
                </button>
                <button type="button" id="btn-clock-out" class="action-btn clock-out-btn">
                  <i data-lucide="log-out"></i> Pulang
                </button>
              </div>
              <p id="attendance-selection-help" class="camera-tip" style="margin-top: 10px;">Pilih aksi Masuk atau Pulang dulu, lalu ambil foto.</p>
            </form>
          </div>
        </div>

        <!-- Right Side: Live Selfie Preview Frame -->
        <div class="kiosk-right">
          <div class="card camera-card">
            <div class="camera-header">
              <h2>Kamera Pengenal</h2>
            </div>

            <div class="camera-frame-container">
              <div class="camera-circle-wrapper">
                <video id="webcam" autoplay playsinline muted></video>
                <canvas id="photo-canvas" style="display: none;"></canvas>
                <div id="camera-placeholder" class="camera-placeholder">
                  <i data-lucide="camera-off" class="placeholder-icon"></i>
                  <p>Kamera Belum Aktif</p>
                  <button id="btn-init-camera" class="btn-primary-small">
                    <i data-lucide="video"></i> Aktifkan Kamera
                  </button>
                </div>
              </div>
              <div class="attendance-actions" style="margin-top: 12px; justify-content: center;">
                <button type="button" id="btn-submit-attendance" class="btn-primary-small" disabled>
                  <i data-lucide="camera"></i> Ambil Foto & Kirim
                </button>
              </div>
              <p class="camera-tip">Posisikan wajah Anda tepat di dalam lingkaran</p>
            </div>
          </div>
        </div>

      </div>
    </section>

>>>>>>> 453a8f6ec9bce382a2b1ab518a77196627d29f77
  </main>

  <!-- Photo Preview Modal -->
  <div id="modal-photo" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <h3>Foto Selfie Absensi</h3>
      <div class="modal-body-img">
        <img id="modal-expanded-img" src="" alt="Selfie Absensi">
      </div>
      <div id="modal-photo-details" class="modal-details-text">
        <!-- Details populate here -->
      </div>
    </div>
  </div>

  <!-- Toast Notification System -->
  <div id="toast-container" class="toast-container"></div>

  <!-- JavaScript Modules -->
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
