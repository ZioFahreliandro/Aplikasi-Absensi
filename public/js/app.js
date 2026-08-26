// Global Variables
let currentStream = null;
let pendingAttendanceType = null;
let officeSettings = {
  office_name: '',
  office_lat: null,
  office_lng: null,
  office_radius: 100,
  enable_gps: false,
};
let passwordChangeWatcher = null;
let currentCoords = {
  lat: null,
  lng: null,
};

// DOM Elements
const btnClockIn = document.getElementById('btn-clock-in');
const btnClockOut = document.getElementById('btn-clock-out');
const btnSubmitAttendance = document.getElementById('btn-submit-attendance');
const attendanceSelectionHelp = document.getElementById('attendance-selection-help');
const locationStatus = document.getElementById('location-status');
const liveClock = document.getElementById('live-clock');
const liveDate = document.getElementById('live-date');
const videoWebcam = document.getElementById('webcam');
const canvasPhoto = document.getElementById('photo-canvas');
const cameraPlaceholder = document.getElementById('camera-placeholder');
const btnInitCamera = document.getElementById('btn-init-camera');
const btnToggleCamera = document.getElementById('btn-toggle-camera');
const lateReasonGroup = document.getElementById('late-reason-group');
const lateReasonInput = document.getElementById('late-reason');
const cameraStatusBadge = document.getElementById('camera-status-badge');
const cameraWrapper = document.querySelector('.camera-circle-wrapper');

// Indonesian Day & Month Names
const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function getCurrentTimeString() {
  const now = new Date();
  const hh = String(now.getHours()).padStart(2, '0');
  const mm = String(now.getMinutes()).padStart(2, '0');
  const ss = String(now.getSeconds()).padStart(2, '0');
  return `${hh}:${mm}:${ss}`;
}

function normalizeTimeString(time) {
  const value = String(time || '').trim();
  if (!value) return '00:00:00';
  if (/^\d{2}:\d{2}$/.test(value)) return `${value}:00`;
  return value;
}

function isLateForCheckin() {
  if (pendingAttendanceType !== 'masuk') return false;
  if (!officeSettings || !officeSettings.office_checkin_time) return false;

  return getCurrentTimeString() > normalizeTimeString(officeSettings.office_checkin_time);
}

function updateLateReasonVisibility() {
  if (!lateReasonGroup || !lateReasonInput) return;

  const shouldShow = isLateForCheckin();
  lateReasonGroup.hidden = !shouldShow;

  if (!shouldShow) {
    lateReasonInput.value = '';
    if (pendingAttendanceType === 'masuk' && attendanceSelectionHelp) {
      attendanceSelectionHelp.textContent = 'Pilih Masuk atau Pulang dulu, lalu lanjut ambil foto.';
    }
  } else if (attendanceSelectionHelp) {
    attendanceSelectionHelp.textContent = 'Kamu terlambat masuk. Alasan di bawah ini opsional.';
  }

  refreshAttendanceButtonState();
}

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide Icons
  lucide.createIcons();

  // Start digital clock
  initClock();
  fetchSettings();
  startPasswordChangeWatcher();
  setLocationGateState(false);
  initLocationTracking();


  // Setup Camera listeners
  if (btnInitCamera) btnInitCamera.addEventListener('click', initCamera);
  if (btnToggleCamera) btnToggleCamera.addEventListener('click', toggleCamera);

  // Setup Navigation
  setupNavigation();

  // Setup Attendance actions
  if (btnClockIn) btnClockIn.addEventListener('click', () => selectAttendanceType('masuk'));
  if (btnClockOut) btnClockOut.addEventListener('click', () => selectAttendanceType('pulang'));
  if (lateReasonInput) lateReasonInput.addEventListener('input', refreshAttendanceButtonState);
  if (btnSubmitAttendance) btnSubmitAttendance.addEventListener('click', () => submitAttendance());
});

// Navigation between Kiosk & Admin View
function setupNavigation() {
  const navKiosk = document.getElementById('nav-kiosk');
  const navAdmin = document.getElementById('nav-admin');
  const viewKiosk = document.getElementById('view-kiosk');
  const viewAdmin = document.getElementById('view-admin');

  if (!navKiosk || !viewKiosk) return;

  navKiosk.addEventListener('click', () => {
    navKiosk.classList.add('active');
    if (navAdmin) navAdmin.classList.remove('active');
    viewKiosk.classList.add('active');
    if (viewAdmin) viewAdmin.classList.remove('active');
  });

  if (navAdmin && viewAdmin) {
    navAdmin.addEventListener('click', () => {
      navAdmin.classList.add('active');
      navKiosk.classList.remove('active');
      viewAdmin.classList.add('active');
      viewKiosk.classList.remove('active');

      // Stop camera stream when leaving kiosk to save CPU/battery
      stopCamera();

      // Trigger admin fetch
      if (window.initAdminPanel) {
        window.initAdminPanel();
      }
    });
  }
}

// Digital Clock
function initClock() {
  if (!liveClock || !liveDate) return;

  function updateTime() {
    const now = new Date();

    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');

    liveClock.textContent = `${hh}:${mm}:${ss}`;

    const dayName = DAYS[now.getDay()];
    const date = now.getDate();
    const monthName = MONTHS[now.getMonth()];
    const year = now.getFullYear();

    liveDate.textContent = `${dayName}, ${date} ${monthName} ${year}`;
    updateLateReasonVisibility();
  }

  updateTime();
  setInterval(updateTime, 1000);
}

async function fetchSettings() {
  try {
    const res = await fetch('/api/settings');
    if (!res.ok) return;
    officeSettings = await res.json();
    updateLateReasonVisibility();
  } catch (error) {
    console.error("Gagal mengambil pengaturan kantor:", error);
  }
}

function initLocationTracking() {
  if (!navigator.geolocation) {
    setLocationGateState(false, 'Perangkat ini belum mendukung lokasi. Coba pakai browser atau ponsel yang mendukung GPS.');
    return;
  }

  navigator.geolocation.watchPosition(
    (position) => {
      currentCoords.lat = position.coords.latitude;
      currentCoords.lng = position.coords.longitude;
      setLocationGateState(true);
    },
    (error) => {
      console.warn("GPS tidak aktif atau ditolak:", error);
      currentCoords.lat = null;
      currentCoords.lng = null;
      setLocationGateState(false, 'Lokasi belum aktif. Silakan nyalakan GPS dan beri izin akses lokasi dulu.');
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

function hasActiveLocation() {
  return currentCoords.lat !== null && currentCoords.lng !== null;
}

function refreshAttendanceButtonState() {
  if (btnClockIn) btnClockIn.disabled = !hasActiveLocation();
  if (btnClockOut) btnClockOut.disabled = !hasActiveLocation();

  if (btnSubmitAttendance) {
    btnSubmitAttendance.disabled = !hasActiveLocation() || !pendingAttendanceType;
  }
}

function setLocationGateState(isActive, message) {
  refreshAttendanceButtonState();

  if (locationStatus) {
    locationStatus.textContent = message || (
      isActive
        ? 'Lokasi aktif. Kamu sudah bisa pilih Masuk atau Pulang.'
        : 'Lokasi belum aktif. Silakan nyalakan GPS dan beri izin akses lokasi dulu.'
    );
  }
}

function getDistance(lat1, lon1, lat2, lon2) {
  const R = 6371e3;
  const radLat1 = (lat1 * Math.PI) / 180;
  const radLat2 = (lat2 * Math.PI) / 180;
  const diffLat = ((lat2 - lat1) * Math.PI) / 180;
  const diffLon = ((lon2 - lon1) * Math.PI) / 180;

  const a = Math.sin(diffLat / 2) * Math.sin(diffLat / 2) +
            Math.cos(radLat1) * Math.cos(radLat2) *
            Math.sin(diffLon / 2) * Math.sin(diffLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

// Start Webcam Stream
async function initCamera() {
  stopCamera();

  try {
    if (cameraStatusBadge) {
      cameraStatusBadge.textContent = "INITIALIZING...";
      cameraStatusBadge.className = "badge-status offline";
    }

    const constraints = {
      video: {
        width: { ideal: 640 },
        height: { ideal: 640 },
        facingMode: 'user'
      },
      audio: false
    };

    currentStream = await navigator.mediaDevices.getUserMedia(constraints);
    if (videoWebcam) videoWebcam.srcObject = currentStream;

    // Update UI states
    if (cameraPlaceholder) cameraPlaceholder.style.display = 'none';
    if (cameraStatusBadge) {
      cameraStatusBadge.textContent = "ONLINE";
      cameraStatusBadge.className = "badge-status online";
    }
    if (cameraWrapper) cameraWrapper.classList.add('active');
    if (btnInitCamera) btnInitCamera.hidden = true;
    if (btnToggleCamera) {
      btnToggleCamera.hidden = false;
      btnToggleCamera.innerHTML = '<i data-lucide="power"></i> Matikan Kamera';
    }
    lucide.createIcons();

  } catch (error) {
    console.error("Gagal membuka kamera:", error);
    if (cameraStatusBadge) {
      cameraStatusBadge.textContent = "OFFLINE";
      cameraStatusBadge.className = "badge-status offline";
    }
    if (cameraPlaceholder) cameraPlaceholder.style.display = 'flex';
    if (cameraWrapper) cameraWrapper.classList.remove('active');

    showToast("Izin kamera ditolak atau tidak ada kamera terdeteksi.", "error");
  }
}

function toggleCamera() {
  if (currentStream) {
    stopCamera();
    return;
  }

  initCamera();
}

// Stop Webcam Stream
function stopCamera() {
  if (currentStream) {
    currentStream.getTracks().forEach(track => track.stop());
    currentStream = null;
  }
  if (videoWebcam) videoWebcam.srcObject = null;
  if (cameraPlaceholder) cameraPlaceholder.style.display = 'flex';
  if (cameraStatusBadge) {
    cameraStatusBadge.textContent = "OFFLINE";
    cameraStatusBadge.className = "badge-status offline";
  }
  if (cameraWrapper) cameraWrapper.classList.remove('active', 'ready');
  if (btnInitCamera) {
    btnInitCamera.hidden = false;
    btnInitCamera.innerHTML = '<i data-lucide="video"></i> Aktifkan Kamera';
  }
  if (btnToggleCamera) {
    btnToggleCamera.hidden = true;
    btnToggleCamera.innerHTML = '<i data-lucide="power"></i> Matikan Kamera';
  }
}

function selectAttendanceType(type) {
  if (!hasActiveLocation()) {
    showToast('Silakan aktifkan GPS dan izinkan akses lokasi terlebih dahulu.', 'error');
    return;
  }

  pendingAttendanceType = type;

  if (btnClockIn) btnClockIn.classList.toggle('active', type === 'masuk');
  if (btnClockOut) btnClockOut.classList.toggle('active', type === 'pulang');

  if (btnSubmitAttendance) {
    btnSubmitAttendance.disabled = false;
    btnSubmitAttendance.innerHTML = `<i data-lucide="camera"></i> ${type === 'masuk' ? 'Ambil Foto & Simpan Masuk' : 'Ambil Foto & Simpan Pulang'}`;
    lucide.createIcons();
  }

  if (attendanceSelectionHelp) {
    attendanceSelectionHelp.textContent = `Aksi terpilih: ${type === 'masuk' ? 'Masuk' : 'Pulang'}. Tekan tombol foto untuk melanjutkan.`;
  }

  updateLateReasonVisibility();
  refreshAttendanceButtonState();

  showToast(`Aksi ${type === 'masuk' ? 'Masuk' : 'Pulang'} dipilih. Silakan ambil foto untuk mengirim absensi.`, 'info');
}

function resetAttendanceSelection() {
  pendingAttendanceType = null;
  if (btnClockIn) btnClockIn.classList.remove('active');
  if (btnClockOut) btnClockOut.classList.remove('active');
  if (lateReasonInput) lateReasonInput.value = '';
  if (lateReasonGroup) lateReasonGroup.hidden = true;

  if (btnSubmitAttendance) {
    btnSubmitAttendance.disabled = true;
    btnSubmitAttendance.innerHTML = '<i data-lucide="camera"></i> Ambil Foto & Kirim';
    lucide.createIcons();
  }

  if (attendanceSelectionHelp) {
    attendanceSelectionHelp.textContent = 'Pilih Masuk atau Pulang dulu, lalu lanjut ambil foto.';
  }

  refreshAttendanceButtonState();
}

// Capture Image from Webcam
function captureSelfie() {
  if (!currentStream || !canvasPhoto || !videoWebcam) return null;

  // Set canvas size matching the webcam feed proportions
  canvasPhoto.width = videoWebcam.videoWidth || 640;
  canvasPhoto.height = videoWebcam.videoHeight || 640;

  const ctx = canvasPhoto.getContext('2d');

  // Draw the video frame to canvas
  // Since video is mirrored, we also mirror the canvas before drawing to get correct image orientation
  ctx.translate(canvasPhoto.width, 0);
  ctx.scale(-1, 1);
  ctx.drawImage(videoWebcam, 0, 0, canvasPhoto.width, canvasPhoto.height);
  ctx.setTransform(1, 0, 0, 1, 0, 0); // reset scale/translate

  // Export as Base64 JPEG
  return canvasPhoto.toDataURL('image/jpeg', 0.85);
}

// Submit Attendance Transaction
async function submitAttendance(type = pendingAttendanceType) {
  if (!type) {
    showToast("Silakan pilih Masuk atau Pulang terlebih dahulu.", "info");
    return;
  }

  if (!officeSettings.office_name) {
    await fetchSettings();
  }

  if (!hasActiveLocation()) {
    showToast("Silakan nyalakan GPS dan izinkan lokasi sebelum absen.", "error");
    return;
  }

  // Basic validation
  if (!currentStream) {
    showToast("Silakan aktifkan kamera dan izinkan akses webcam!", "error");
    return;
  }

  if (officeSettings.enable_gps) {
    if (
      currentCoords.lat === null ||
      currentCoords.lng === null ||
      officeSettings.office_lat === null ||
      officeSettings.office_lng === null
    ) {
      showToast("Lokasi belum aktif. Silakan beri izin akses lokasi terlebih dahulu.", "error");
      return;
    }

    const distance = getDistance(
      currentCoords.lat,
      currentCoords.lng,
      officeSettings.office_lat,
      officeSettings.office_lng
    );

    if (distance > officeSettings.office_radius) {
      showToast(`Anda masih ${Math.round(distance)} meter dari kantor. Radius maksimal ${officeSettings.office_radius} meter.`, "error");
      return;
    }
  }

  const lateReason = lateReasonGroup && !lateReasonGroup.hidden
    ? (lateReasonInput?.value || '').trim()
    : '';

  // Capture image
  const selfieBase64 = captureSelfie();
  if (!selfieBase64) {
    showToast("Gagal mengambil gambar selfie. Coba lagi.", "error");
    return;
  }

  // Disable buttons to prevent double submission
  if (btnClockIn) btnClockIn.disabled = true;
  if (btnClockOut) btnClockOut.disabled = true;

  try {
    const payload = {
      type,
      selfie: selfieBase64,
      latitude: currentCoords.lat,
      longitude: currentCoords.lng,
      late_reason: lateReason
    };

    // Fetch Laravel CSRF Token from Meta Tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const res = await fetch('/api/attendance', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (res.ok) {
      showToast(data.message, "success");
      resetAttendanceSelection();
      // Play a quick success visual effect by adding green flash to camera
      if (cameraWrapper) {
        cameraWrapper.style.borderColor = "var(--success)";
        setTimeout(() => {
          cameraWrapper.style.borderColor = "";
        }, 1000);
      }
    } else {
      showToast(data.error || "Gagal melakukan absensi", "error");
    }
  } catch (error) {
    console.error("Gagal melakukan absensi:", error);
    showToast("Terjadi kesalahan jaringan saat mengirim absensi", "error");
  } finally {
    if (btnClockIn) btnClockIn.disabled = false;
    if (btnClockOut) btnClockOut.disabled = false;
  }
}

// Toast Notifications Helper
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;

  let iconName = 'info';
  if (type === 'success') iconName = 'check-circle';
  if (type === 'error') iconName = 'alert-triangle';

  toast.innerHTML = `
    <div class="toast-icon">
      <i data-lucide="${iconName}"></i>
    </div>
    <div class="toast-message">${message}</div>
  `;

  container.appendChild(toast);
  lucide.createIcons();

  // Animation delay to slide in
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);

  // Auto remove after 4 seconds
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 4000);
}

// Export toast function globally for use in other files
window.showToast = showToast;
window.fetchSettings = fetchSettings;

async function fetchEmployeePasswordStatus() {
  try {
    const res = await fetch('/api/employee/password-status', {
      cache: 'no-store',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!res.ok) return null;

    return await res.json();
  } catch (error) {
    console.error('Gagal memeriksa status password karyawan:', error);
    return null;
  }
}

function startPasswordChangeWatcher() {
  if (passwordChangeWatcher || window.location.pathname === '/force-password-change') {
    return;
  }

  const checkAndRedirect = async () => {
    const status = await fetchEmployeePasswordStatus();
    if (!status || !status.must_change_password) {
      return;
    }

    window.location.replace(status.redirect_url || '/force-password-change');
  };

  checkAndRedirect();
  passwordChangeWatcher = setInterval(checkAndRedirect, 10000);
}
