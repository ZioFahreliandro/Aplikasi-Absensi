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
      @php
        $currentUser = Auth::user();
        $employeeName = $currentUser?->name ?? 'Karyawan';
        $employeeNip = null;
        $isAdmin = $currentUser && (($currentUser->role ?? null) === 'admin');
        $isEmployee = $currentUser && !$isAdmin;
        $adminMenuOpen = $isAdmin && (session('status') || $errors->any());

        if ($currentUser && !empty($currentUser->email) && str_contains($currentUser->email, '@local')) {
          $employeeNip = explode('@', $currentUser->email, 2)[0];
        }

        $employeeInitial = strtoupper(mb_substr(trim($employeeName), 0, 1));
      @endphp

      @if ($isEmployee)
      <details class="employee-header-profile" @if(session('status') || $errors->any()) open @endif>
        <summary class="employee-header-summary">
          <div class="profile-avatar profile-avatar-sm">{{ $employeeInitial }}</div>
          <div class="employee-header-summary-copy">
            <span class="profile-kicker">Karyawan</span>
            <h2>{{ $employeeName }}</h2>
            <p>{{ $employeeNip ?? '-' }}</p>
          </div>
          <span class="employee-header-chevron" aria-hidden="true">
            <i data-lucide="chevron-down"></i>
          </span>
        </summary>

        <div class="employee-header-panel">
          @if (session('status'))
            <div class="profile-alert success">
              {{ session('status') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="profile-alert error">
              {{ $errors->first() }}
            </div>
          @endif

          <div class="profile-meta profile-meta-compact">
            <div>
              <span>NIP</span>
              <strong>{{ $employeeNip ?? '-' }}</strong>
            </div>
            <div>
              <span>Status</span>
              <strong>Karyawan Aktif</strong>
            </div>
          </div>

          <form action="{{ route('profile.password.update') }}" method="POST" class="profile-password-form profile-password-form-compact">
            @csrf
            <div class="form-group">
              <label for="current_password">Password Lama</label>
              <div class="password-field">
                <input type="password" id="current_password" name="current_password" class="input-field" placeholder="Password lama" autocomplete="current-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="password">Password Baru</label>
              <div class="password-field">
                <input type="password" id="password" name="password" class="input-field" placeholder="Password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="password_confirmation">Konfirmasi Password Baru</label>
              <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" placeholder="Ulangi password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-primary profile-submit-btn">
              <i data-lucide="key-round"></i> Simpan Password
            </button>
          </form>
        </div>
      </details>
      @else
      <div class="admin-menu-shell">
        <button
          id="admin-menu-toggle"
          type="button"
          class="admin-menu-toggle{{ $adminMenuOpen ? ' active' : '' }}"
          aria-label="Buka menu admin"
          aria-expanded="{{ $adminMenuOpen ? 'true' : 'false' }}"
          aria-controls="admin-menu-panel"
        >
          <span class="admin-menu-icon" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>

        <div id="admin-menu-panel" class="admin-menu-panel{{ $adminMenuOpen ? ' is-open' : '' }}" @unless($adminMenuOpen) hidden @endunless>
          @if (session('status'))
            <div class="profile-alert success">
              {{ session('status') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="profile-alert error">
              {{ $errors->first() }}
            </div>
          @endif

          <div class="admin-menu-profile">
            <div class="profile-avatar profile-avatar-sm">{{ $employeeInitial }}</div>
            <div class="employee-header-summary-copy">
              <span class="profile-kicker">Admin</span>
              <h2>{{ $employeeName }}</h2>
              <p>Admin Aktif</p>
            </div>
          </div>

          <div class="profile-meta profile-meta-compact">
            <div>
              <span>Status</span>
              <strong>Admin Aktif</strong>
            </div>
          </div>

          <div class="admin-menu-links">
            <button type="button" class="admin-menu-link active" data-admin-tab-target="tab-recap">
              <i data-lucide="file-text"></i> Rekap Absensi
            </button>
            <button type="button" class="admin-menu-link" data-admin-tab-target="tab-employees">
              <i data-lucide="users"></i> Kelola Karyawan
            </button>
            <button type="button" class="admin-menu-link" data-admin-tab-target="tab-settings">
              <i data-lucide="settings"></i> Pengaturan Kantor
            </button>
          </div>

          <form action="{{ route('profile.password.update') }}" method="POST" class="profile-password-form profile-password-form-compact">
            @csrf
            <div class="form-group">
              <label for="current_password">Password Lama</label>
              <div class="password-field">
                <input type="password" id="current_password" name="current_password" class="input-field" placeholder="Password lama" autocomplete="current-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="password">Password Baru</label>
              <div class="password-field">
                <input type="password" id="password" name="password" class="input-field" placeholder="Password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="password_confirmation">Konfirmasi Password Baru</label>
              <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" placeholder="Ulangi password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                  <i data-lucide="eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-primary profile-submit-btn">
              <i data-lucide="key-round"></i> Simpan Password
            </button>
          </form>

        </div>
      </div>
      @endif
    </div>
    <nav class="header-nav">
      @cannot('access-admin')
      <button id="nav-kiosk" class="nav-btn active">
        <i data-lucide="camera"></i> Absen
      </button>
      @endcannot
      @can('access-admin')
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-btn nav-btn-logout">
          <i data-lucide="log-out"></i> Keluar
        </button>
      </form>
      @endcan
      @cannot('access-admin')
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-btn">
          <i data-lucide="log-out"></i> Keluar
        </button>
      </form>
      @endcannot
    </nav>
  </header>

  <main class="app-container">

    <!-- 1. KIOSK ABSEN VIEW -->
    @cannot('access-admin')
    <section id="view-kiosk" class="view-section active">
      <div class="kiosk-grid">

        <!-- Left Side: Interactive Attendance Form & Clock -->
        <div class="kiosk-left">
          <!-- Premium Clock Card -->
          <div class="card clock-card">
            <div class="clock-greeting">
              Selamat datang {{ $employeeName }}
            </div>
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
                <button type="button" id="btn-clock-in" class="action-btn clock-in-btn" disabled>
                  <i data-lucide="log-in"></i> Masuk
                </button>
                <button type="button" id="btn-clock-out" class="action-btn clock-out-btn" disabled>
                  <i data-lucide="log-out"></i> Pulang
                </button>
              </div>
              <div class="location-health-box">
                <div class="location-health-header">
                  <span id="location-health-badge" class="location-health-badge offline">Lokasi Mati</span>
                  <span id="location-health-coords" class="location-health-coords">-</span>
                </div>
                <p id="location-health-text" class="location-health-text">
                  Mencari status lokasi...
                </p>
              </div>
              <p id="location-status" class="camera-tip">Lokasi belum aktif. Silakan nyalakan GPS dan beri izin akses lokasi dulu.</p>
              <p id="attendance-selection-help" class="camera-tip" style="margin-top: 10px;">Pilih Masuk atau Pulang dulu, lalu lanjut ambil foto.</p>
              <div id="late-reason-group" class="form-group late-reason-group" hidden>
                <label for="late-reason">Alasan Telat</label>
                <textarea id="late-reason" class="input-field late-reason-input" rows="3" placeholder="Jelaskan alasan keterlambatan" required></textarea>
                <p class="field-hint">Wajib diisi saat absen masuk terlambat. Jika tidak telat, kolom ini tidak akan muncul.</p>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Side: Live Selfie Preview Frame -->
        <div class="kiosk-right">
          <div class="card camera-card">
            <div class="camera-header">
              <h2>Kamera Pengenal</h2>
              <span id="camera-status-badge" class="badge-status offline">OFFLINE</span>
            </div>

            <div class="camera-frame-container">
              <div class="camera-action-group">
                <button id="btn-init-camera" class="btn-primary-small" type="button">
                  <i data-lucide="video"></i> Aktifkan Kamera
                </button>
                <button id="btn-toggle-camera" class="btn-secondary-small" type="button" hidden>
                  <i data-lucide="power"></i> Matikan Kamera
                </button>
              </div>

              <div class="camera-circle-wrapper">
                <video id="webcam" autoplay playsinline muted></video>
                <canvas id="photo-canvas" style="display: none;"></canvas>
                <div id="camera-placeholder" class="camera-placeholder">
                  <i data-lucide="camera-off" class="placeholder-icon"></i>
                  <p>Kamera Belum Aktif</p>
                </div>
              </div>
              <div class="attendance-actions" style="margin-top: 12px; justify-content: center;">
                <button type="button" id="btn-submit-attendance" class="btn-primary-small" disabled>
                  <i data-lucide="camera"></i> Ambil Foto & Kirim
                </button>
              </div>
              <p class="camera-tip">Posisikan wajah Anda tepat di dalam kotak</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    @endcannot

    @can('access-admin')
    <!-- 2. ADMIN DASHBOARD VIEW -->
    <section id="view-admin" class="view-section active">
      <div class="admin-layout admin-layout-menu">

        <!-- Admin Content Area -->
        <div class="admin-content">

          <!-- Sub-tab 1: Rekap Absensi -->
          <div id="sub-view-recap" class="sub-view active">
            <div class="sub-view-header">
              <div>
                <h2>Rekap Laporan Kehadiran</h2>
                <p>Tinjau dan ekspor laporan kehadiran bulanan karyawan.</p>
              </div>
              <div class="recap-filters">
                <div class="filter-group">
                  <label for="filter-month">Pilih Bulan:</label>
                  <input type="month" id="filter-month" class="input-field">
                </div>
                <button id="btn-export-csv" class="btn-success">
                  <i data-lucide="download"></i> Ekspor Excel
                </button>
                <button id="btn-delete-today" class="btn-danger">
                  <i data-lucide="trash-2"></i> Hapus Rekap Hari Ini
                </button>
              </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="stats-grid">
              <div class="card stat-card">
                <div class="stat-icon info">
                  <i data-lucide="users"></i>
                </div>
                <div>
                  <h3 id="stat-total-emp">0</h3>
                  <p>Total Karyawan</p>
                </div>
              </div>
              <div class="card stat-card">
                <div class="stat-icon success">
                  <i data-lucide="check-circle-2"></i>
                </div>
                <div>
                  <h3 id="stat-present-today">0</h3>
                  <p>Hadir Hari Ini</p>
                </div>
              </div>
              <div class="card stat-card">
                <div class="stat-icon warning">
                  <i data-lucide="alert-triangle"></i>
                </div>
                <div>
                  <h3 id="stat-outside-today">0</h3>
                  <p>Absen di Luar Radius</p>
                </div>
              </div>
            </div>

            <!-- Table Card -->
            <div class="card table-card-wrapper">
              <div class="table-responsive">
                <table id="recap-table" class="app-table">
                  <thead>
                    <tr>
                      <th>Nama Karyawan</th>
                      <th>Tanggal</th>
                      <th>Waktu</th>
                      <th>Tipe</th>
                      <th>Keterangan</th>
                      <th>Alasan</th>
                      <th>Selfie</th>
                    </tr>
                  </thead>
                  <tbody id="recap-table-body">
                    <!-- Dynamic rows here -->
                    <tr>
                      <td colspan="7" class="text-center text-muted">Memuat data absensi...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Sub-tab 2: Kelola Karyawan -->
          <div id="sub-view-employees" class="sub-view">
            <div class="sub-view-header">
              <div>
                <h2>Kelola Daftar Karyawan</h2>
                <p>Tambah, ubah, atau hapus karyawan dari sistem absensi.</p>
              </div>
            </div>

            <div class="employee-grid">
              <!-- Employee Form Card -->
              <div class="card employee-form-card">
                <h3 id="employee-form-title">Tambah Karyawan Baru</h3>
                <form id="employee-form" class="app-form">
                  <input type="hidden" id="employee-id">

                  <div class="form-group">
                    <label for="emp-name">Nama Lengkap</label>
                    <input type="text" id="emp-name" class="input-field" placeholder="Contoh: Ahmad Subardjo" required>
                  </div>

                  <div class="form-group">
                    <label for="emp-nip">NIP / Nomor Induk Pegawai</label>
                    <input type="text" id="emp-nip" class="input-field" placeholder="Contoh: 19960205" required>
                  </div>

                  <div class="form-group">
                    <label>Password Login</label>
                    <div id="employee-password-group" class="field-note-box">
                      <p class="field-hint" id="employee-password-hint">
                        Password awal otomatis dibuat dari nama yang diinput.
                        Contoh: nama <strong>Budi Santoso</strong> akan memakai password <strong>Budi Santoso</strong>.
                      </p>
                      <p class="field-hint">
                        Admin tetap bisa reset password kapan saja dari tombol di tabel karyawan.
                      </p>
                    </div>
                  </div>

                  <div class="form-actions">
                    <button type="submit" id="btn-submit-employee" class="btn-primary">
                      <i data-lucide="plus-circle"></i> Simpan Karyawan
                    </button>
                    <button type="button" id="btn-cancel-edit" class="btn-secondary" style="display: none;">
                      Batal
                    </button>
                  </div>
                </form>
              </div>

              <!-- Employee List Card -->
              <div class="card employee-list-card">
                  <div class="employee-list-header">
                  <div class="employee-list-heading">
                    <h3>Daftar Karyawan</h3>
                    <p>Cari karyawan berdasarkan nama atau NIP.</p>
                  </div>
                  <div class="employee-search-box">
                    <i data-lucide="search" aria-hidden="true"></i>
                    <input
                      type="search"
                      id="employee-search"
                      class="input-field employee-search-input"
                      placeholder="Cari karyawan..."
                      autocomplete="off"
                    >
                  </div>
                </div>
                <div class="employee-list-table-title">
                  <h4>Tabel Karyawan</h4>
                  <p>NIP, nama, dan aksi berada di bawah pencarian.</p>
                </div>
                <div class="table-responsive">
                  <table class="app-table">
                    <thead>
                      <tr>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody id="employee-table-body">
                      <!-- Dynamic list of employees -->
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Sub-tab 3: Pengaturan Kantor -->
          <div id="sub-view-settings" class="sub-view">
            <div class="sub-view-header">
              <div>
                <h2>Pengaturan Lokasi Kantor</h2>
                <p>Atur batas jangkauan GPS dan koordinat kantor yang diizinkan.</p>
              </div>
            </div>

            <div class="settings-grid">

              <!-- Settings Form Card -->
              <div class="card settings-form-card">
                <h3>Konfigurasi Pembatasan GPS</h3>
                <form id="settings-form" class="app-form">

                  <div class="form-group">
                    <label for="set-office-name">Nama Kantor / Lokasi</label>
                    <input type="text" id="set-office-name" class="input-field" placeholder="Kantor Pusat" required>
                  </div>

                  <!-- Toggles -->
                  <div class="settings-toggles">
                    <div class="toggle-group">
                      <div class="toggle-control">
                        <label class="switch">
                          <input type="checkbox" id="set-enable-gps">
                          <span class="slider round"></span>
                        </label>
                        <div class="toggle-label-text">
                          <span class="toggle-title">Batasi Berdasarkan GPS</span>
                          <span class="toggle-desc">Hanya izinkan absen jika berada di radius koordinat kantor.</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr class="form-divider">

                  <!-- GPS Config -->
                  <h4 class="settings-section-title"><i data-lucide="map-pin"></i> Koordinat Geolocation</h4>

                  <div class="form-row-2">
                    <div class="form-group">
                      <label for="set-office-lat">Latitude Kantor</label>
                      <input type="number" id="set-office-lat" step="any" class="input-field" placeholder="-6.200000" required>
                    </div>
                    <div class="form-group">
                      <label for="set-office-lng">Longitude Kantor</label>
                      <input type="number" id="set-office-lng" step="any" class="input-field" placeholder="106.816666" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="set-office-radius">Radius Akses (Meter)</label>
                    <input type="number" id="set-office-radius" class="input-field" placeholder="100" min="10" required>
                  </div>

                  <hr class="form-divider">

                  <!-- Work Schedule -->
                  <h4 class="settings-section-title"><i data-lucide="clock-3"></i> Jam Kerja</h4>

                  <div class="form-row-2">
                    <div class="form-group">
                      <label for="set-office-checkin-time">Jam Masuk</label>
                      <input type="time" id="set-office-checkin-time" class="input-field" required>
                    </div>
                    <div class="form-group">
                      <label for="set-office-checkout-time">Jam Pulang</label>
                      <input type="time" id="set-office-checkout-time" class="input-field" required>
                    </div>
                  </div>

                  <p class="field-hint">Jika absen masuk lewat jam masuk, keterangan akan menjadi <strong>Anda Telat</strong>. Jika absen pulang sebelum jam pulang, keterangan akan menjadi <strong>Pulang Cepat</strong>.</p>

                  <div class="helper-buttons">
                    <button type="button" id="btn-get-current-coords" class="btn-outline-info">
                      <i data-lucide="map-pin"></i> Pilih Titik di Peta
                    </button>
                  </div>

                  <div class="form-actions-settings">
                    <button type="submit" class="btn-primary btn-large">
                      <i data-lucide="save"></i> Simpan Semua Pengaturan
                    </button>
                  </div>

                </form>
              </div>

              <!-- Information Panel Card -->
              <div class="card info-settings-card">
                <h3>Status Koneksi Anda Saat Ini</h3>
                <div class="info-stat-list">
                  <div class="info-stat-item">
                    <div class="info-stat-icon">
                      <i data-lucide="map-pin"></i>
                    </div>
                    <div class="info-stat-details">
                      <p class="info-label">Koordinat Anda Sekarang:</p>
                      <p id="info-current-coords" class="info-value">Mencari lokasi...</p>
                    </div>
                  </div>

                  <div class="info-stat-item">
                    <div class="info-stat-icon">
                      <i data-lucide="navigation"></i>
                    </div>
                    <div class="info-stat-details">
                      <p class="info-label">Jarak ke Koordinat Kantor Terdaftar:</p>
                      <p id="info-current-distance" class="info-value">Menghitung...</p>
                    </div>
                  </div>

                  <div class="info-stat-item">
                    <div class="info-stat-icon">
                      <i data-lucide="clock-3"></i>
                    </div>
                    <div class="info-stat-details">
                      <p class="info-label">Jadwal Kerja:</p>
                      <p id="info-work-schedule" class="info-value">Mengambil jadwal...</p>
                    </div>
                  </div>
                </div>

                <div class="alert-info-box">
                  <h4><i data-lucide="info"></i> Tips Cara Pengetesan</h4>
                  <p>Jika ingin menyimulasikan pembatasan absensi di kantor:</p>
                  <ol>
                    <li>Aktifkan <strong>Batasi Berdasarkan GPS</strong>.</li>
                    <li>Klik tombol <strong>"Pilih Titik di Peta"</strong>.</li>
                    <li>Klik titik lokasi kantor di peta, lalu klik <strong>"Simpan Titik Ini"</strong>.</li>
                    <li>Terakhir, klik <strong>"Simpan Semua Pengaturan"</strong>.</li>
                  </ol>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </section>
    @endcan
  </main>

  <!-- Photo Preview Modal -->
  <div id="modal-photo" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <div class="modal-body-img">
        <img id="modal-expanded-img" src="" alt="Selfie Absensi">
      </div>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div id="modal-reset-password" class="modal">
    <div class="modal-content modal-reset-password">
      <span class="modal-close" id="close-reset-password">&times;</span>
      <h3>Password Sementara Karyawan</h3>
      <p class="location-picker-copy">Password berikut digunakan untuk login karyawan, lalu sistem akan memaksa ganti password baru.</p>

      <div class="reset-password-summary">
        <div>
          <span class="location-picker-label">Nama Karyawan</span>
          <strong id="reset-password-employee-name">-</strong>
        </div>
      </div>

      <div class="reset-password-code">
        <span>Password Sementara</span>
        <code id="reset-password-value">-</code>
      </div>

      <div class="location-picker-actions">
        <button type="button" id="btn-copy-reset-password" class="btn-secondary">
          Salin Password
        </button>
        <button type="button" id="btn-close-reset-password" class="btn-primary">
          Tutup
        </button>
      </div>
    </div>
  </div>

  <!-- Toast Notification System -->
  <div id="toast-container" class="toast-container"></div>

  <!-- Location Picker Modal -->
  <div id="modal-location-picker" class="modal">
      <div class="modal-content modal-location-picker">
      <span class="modal-close" id="close-location-picker">&times;</span>
      <h3>Pilih Titik Kantor</h3>
      <p class="location-picker-copy">Klik titik kantor di peta. Latitude dan longitude akan terisi otomatis.</p>

      <div class="location-picker-search">
        <input
          type="text"
          id="location-picker-search-input"
          class="input-field location-picker-search-input"
          placeholder="Cari alamat, kota, atau nama tempat..."
          autocomplete="off"
        >
        <button type="button" id="btn-search-location-picker" class="btn-primary location-picker-search-btn">
          Cari
        </button>
      </div>

      <div class="location-picker-meta">
        <div>
          <span class="location-picker-label">Latitude</span>
          <strong id="picked-lat">-</strong>
        </div>
        <div>
          <span class="location-picker-label">Longitude</span>
          <strong id="picked-lng">-</strong>
        </div>
      </div>

      <div
        id="location-picker-map"
        class="location-picker-map"
        aria-label="Google Maps"
      ></div>

      <p id="location-picker-status" class="location-picker-status">Memuat peta Google Maps...</p>

      <div class="location-picker-actions">
        <button type="button" id="btn-use-current-location" class="btn-secondary">
          Gunakan Lokasi Saya
        </button>
        <button type="button" id="btn-apply-picked-location" class="btn-primary">
          Simpan Titik Ini
        </button>
      </div>
    </div>
  </div>

  <!-- JavaScript Modules -->
  @cannot('access-admin')
  <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  @endcannot
  @can('access-admin')
  <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
  <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
  @if(config('services.google_maps.key'))
  <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initLocationPickerMap&v=weekly"></script>
  @endif
  @endcan
</body>
</html>
