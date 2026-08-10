// Global Variables
let currentStream = null;

// DOM Elements
const btnClockIn = document.getElementById('btn-clock-in');
const btnClockOut = document.getElementById('btn-clock-out');
const liveClock = document.getElementById('live-clock');
const liveDate = document.getElementById('live-date');
const badgeGps = document.getElementById('badge-gps');
const badgeIp = document.getElementById('badge-ip');
const txtGpsDetails = document.getElementById('txt-gps-details');
const txtIpDetails = document.getElementById('txt-ip-details');
const videoWebcam = document.getElementById('webcam');
const canvasPhoto = document.getElementById('photo-canvas');
const cameraPlaceholder = document.getElementById('camera-placeholder');
const btnInitCamera = document.getElementById('btn-init-camera');
const cameraStatusBadge = document.getElementById('camera-status-badge');
const cameraWrapper = document.querySelector('.camera-circle-wrapper');

// Indonesian Day & Month Names
const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide Icons
  lucide.createIcons();
  
  // Start digital clock
  initClock();
  
  
  // Setup Camera listeners
  btnInitCamera.addEventListener('click', initCamera);
  
  // Setup Navigation
  setupNavigation();
  
  // Setup Attendance actions
  btnClockIn.addEventListener('click', () => submitAttendance('masuk'));
  btnClockOut.addEventListener('click', () => submitAttendance('pulang'));
});

// Navigation between Kiosk & Admin View
function setupNavigation() {
  const navKiosk = document.getElementById('nav-kiosk');
  const navAdmin = document.getElementById('nav-admin');
  const viewKiosk = document.getElementById('view-kiosk');
  const viewAdmin = document.getElementById('view-admin');
  
  navKiosk.addEventListener('click', () => {
    navKiosk.classList.add('active');
    if (navAdmin) navAdmin.classList.remove('active');
    viewKiosk.classList.add('active');
    if (viewAdmin) viewAdmin.classList.remove('active');
    
    // Auto start camera when going to Kiosk if permissions granted before
    if (!currentStream) {
      initCamera();
    }
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
  }
  
  updateTime();
  setInterval(updateTime, 1000);
}

// Fetch settings from API
async function fetchSettings() {
  try {
    const res = await fetch('/api/settings');
    officeSettings = await res.json();
    
    // Trigger validation update if variables are available
    updateValidationStatus();
  } catch (error) {
    console.error("Gagal mengambil pengaturan kantor:", error);
  }
}

// Detect Client Public Network IP
async function detectNetwork() {
  try {
    const res = await fetch('/api/my-ip');
    const data = await res.json();
    currentIp = data.ip;
    
    // Set UI panel IP
    const infoCurrentIp = document.getElementById('info-current-ip');
    if (infoCurrentIp) infoCurrentIp.textContent = currentIp;
    
    updateValidationStatus();
  } catch (error) {
    console.error("Gagal mendeteksi IP:", error);
    txtIpDetails.innerHTML = `<i data-lucide="alert-circle"></i> Gagal terhubung ke deteksi IP server.`;
    lucide.createIcons();
  }
}

// Start Webcam Stream
async function initCamera() {
  stopCamera();
  
  try {
    cameraStatusBadge.textContent = "INITIALIZING...";
    cameraStatusBadge.className = "badge-status offline";
    
    const constraints = {
      video: {
        width: { ideal: 640 },
        height: { ideal: 640 },
        facingMode: 'user'
      },
      audio: false
    };
    
    currentStream = await navigator.mediaDevices.getUserMedia(constraints);
    videoWebcam.srcObject = currentStream;
    
    // Update UI states
    cameraPlaceholder.style.display = 'none';
    cameraStatusBadge.textContent = "ONLINE";
    cameraStatusBadge.className = "badge-status online";
    cameraWrapper.classList.add('active');
    
  } catch (error) {
    console.error("Gagal membuka kamera:", error);
    cameraStatusBadge.textContent = "OFFLINE";
    cameraStatusBadge.className = "badge-status offline";
    cameraPlaceholder.style.display = 'flex';
    cameraWrapper.classList.remove('active');
    
    showToast("Izin kamera ditolak atau tidak ada kamera terdeteksi.", "error");
  }
}

// Stop Webcam Stream
function stopCamera() {
  if (currentStream) {
    currentStream.getTracks().forEach(track => track.stop());
    currentStream = null;
  }
  videoWebcam.srcObject = null;
  cameraPlaceholder.style.display = 'flex';
  cameraStatusBadge.textContent = "OFFLINE";
  cameraStatusBadge.className = "badge-status offline";
  cameraWrapper.classList.remove('active', 'ready');
}

// GPS Geolocation Tracking
function initLocationTracking() {
  if (!navigator.geolocation) {
    txtGpsDetails.innerHTML = `<i data-lucide="alert-triangle"></i> GPS tidak didukung di browser ini.`;
    lucide.createIcons();
    return;
  }
  
  // Watch position for continuous updates
  navigator.geolocation.watchPosition(
    (position) => {
      userCoords.lat = position.coords.latitude;
      userCoords.lng = position.coords.longitude;
      
      // Update info panel in admin view if present
      const infoCurrentCoords = document.getElementById('info-current-coords');
      if (infoCurrentCoords) {
        infoCurrentCoords.textContent = `${userCoords.lat.toFixed(6)}, ${userCoords.lng.toFixed(6)}`;
      }
      
      updateValidationStatus();
    },
    (error) => {
      console.warn("Kesalahan GPS Geolocation:", error);
      txtGpsDetails.innerHTML = `<i data-lucide="alert-triangle"></i> Gagal mendapat GPS: ${error.message}`;
      
      const infoCurrentCoords = document.getElementById('info-current-coords');
      if (infoCurrentCoords) {
        infoCurrentCoords.textContent = "Izin Lokasi Ditolak";
      }
      
      lucide.createIcons();
      updateValidationStatus();
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

// Haversine client-side helper to display distance
function getDistance(lat1, lon1, lat2, lon2) {
  const R = 6371e3; // Earth's radius in meters
  const radLat1 = (lat1 * Math.PI) / 180;
  const radLat2 = (lat2 * Math.PI) / 180;
  const diffLat = ((lat2 - lat1) * Math.PI) / 180;
  const diffLon = ((lon2 - lon1) * Math.PI) / 180;

  const a = Math.sin(diffLat / 2) * Math.sin(diffLat / 2) +
            Math.cos(radLat1) * Math.cos(radLat2) *
            Math.sin(diffLon / 2) * Math.sin(diffLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // in meters
}

// Update the validation statuses for GPS & IP in real-time
function updateValidationStatus() {
  if (!officeSettings.office_name) return; // settings not loaded yet (Laravel format is snake_case: office_name)
  
  let gpsValid = true;
  let ipValid = true;
  
  // 1. Geolocation Check
  if (officeSettings.enable_gps) {
    if (userCoords.lat === null || userCoords.lng === null) {
      gpsValid = false;
      badgeGps.className = "indicator-badge danger";
      badgeGps.innerHTML = `<span class="pulse-dot"></span> GPS: Di Luar Jangkauan`;
      txtGpsDetails.innerHTML = `<i data-lucide="map-pin"></i> Mengambil data GPS Anda...`;
    } else {
      const distance = getDistance(
        userCoords.lat, 
        userCoords.lng, 
        officeSettings.office_lat, 
        officeSettings.office_lng
      );
      const roundedDist = Math.round(distance);
      
      // Update settings info page status if open
      const infoCurrentDistance = document.getElementById('info-current-distance');
      if (infoCurrentDistance) {
        infoCurrentDistance.textContent = `${roundedDist} meter`;
      }
      
      if (distance <= officeSettings.office_radius) {
        badgeGps.className = "indicator-badge success";
        badgeGps.innerHTML = `<span class="pulse-dot"></span> GPS: Di Dalam Area`;
        txtGpsDetails.innerHTML = `<i data-lucide="map-pin"></i> Jarak ke kantor: <strong>${roundedDist}m</strong> (Batas: ${officeSettings.office_radius}m)`;
      } else {
        gpsValid = false;
        badgeGps.className = "indicator-badge danger";
        badgeGps.innerHTML = `<span class="pulse-dot"></span> GPS: Di Luar Jangkauan`;
        txtGpsDetails.innerHTML = `<i data-lucide="map-pin"></i> Jarak Anda: <strong>${roundedDist}m</strong> (Batas: ${officeSettings.office_radius}m)`;
      }
    }
  } else {
    // GPS disabled in settings
    badgeGps.className = "indicator-badge success";
    badgeGps.innerHTML = `<span class="pulse-dot"></span> GPS: Bebas Akses`;
    
    if (userCoords.lat !== null) {
      const distance = getDistance(
        userCoords.lat, 
        userCoords.lng, 
        officeSettings.office_lat, 
        officeSettings.office_lng
      );
      txtGpsDetails.innerHTML = `<i data-lucide="map-pin"></i> Jarak Anda: ${Math.round(distance)}m (GPS dinonaktifkan)`;
    } else {
      txtGpsDetails.innerHTML = `<i data-lucide="map-pin"></i> Lokasi GPS aktif (opsional)`;
    }
  }
  
  // 2. IP Jaringan Check
  if (officeSettings.enable_ip) {
    if (!currentIp) {
      ipValid = false;
      badgeIp.className = "indicator-badge danger";
      badgeIp.innerHTML = `<span class="pulse-dot"></span> IP Jaringan: Tidak Valid`;
      txtIpDetails.innerHTML = `<i data-lucide="globe"></i> Mendeteksi IP Anda...`;
    } else if (currentIp === officeSettings.office_ip || officeSettings.office_ip === '127.0.0.1') {
      badgeIp.className = "indicator-badge success";
      badgeIp.innerHTML = `<span class="pulse-dot"></span> IP Jaringan: Valid`;
      txtIpDetails.innerHTML = `<i data-lucide="globe"></i> Terhubung ke jaringan kantor (IP: ${currentIp})`;
    } else {
      ipValid = false;
      badgeIp.className = "indicator-badge danger";
      badgeIp.innerHTML = `<span class="pulse-dot"></span> IP Jaringan: Tidak Valid`;
      txtIpDetails.innerHTML = `<i data-lucide="globe"></i> IP Anda: ${currentIp} (Harus IP Kantor: ${officeSettings.office_ip})`;
    }
  } else {
    // IP disabled in settings
    badgeIp.className = "indicator-badge success";
    badgeIp.innerHTML = `<span class="pulse-dot"></span> IP Jaringan: Bebas Akses`;
    txtIpDetails.innerHTML = `<i data-lucide="globe"></i> IP Anda: ${currentIp || '127.0.0.1'} (IP dinonaktifkan)`;
  }
  
  // If camera is active and constraints are satisfied, apply glowing state
  if (currentStream && gpsValid && ipValid) {
    cameraWrapper.classList.add('ready');
  } else {
    cameraWrapper.classList.remove('ready');
  }
  
  lucide.createIcons();
}

// Capture Image from Webcam
function captureSelfie() {
  if (!currentStream) return null;
  
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
async function submitAttendance(type) {
  // Basic validation
  if (!currentStream) {
    showToast("Silakan aktifkan kamera dan izinkan akses webcam!", "error");
    return;
  }
  
  // Capture image
  const selfieBase64 = captureSelfie();
  if (!selfieBase64) {
    showToast("Gagal mengambil gambar selfie. Coba lagi.", "error");
    return;
  }
  
  // Disable buttons to prevent double submission
  btnClockIn.disabled = true;
  btnClockOut.disabled = true;
  
  try {
    const payload = {
      type,
      selfie: selfieBase64
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
      // Play a quick success visual effect by adding green flash to camera
      cameraWrapper.style.borderColor = "var(--success)";
      setTimeout(() => {
        cameraWrapper.style.borderColor = "";
      }, 1000);
    } else {
      showToast(data.error || "Gagal melakukan absensi", "error");
    }
  } catch (error) {
    console.error("Gagal melakukan absensi:", error);
    showToast("Terjadi kesalahan jaringan saat mengirim absensi", "error");
  } finally {
    btnClockIn.disabled = false;
    btnClockOut.disabled = false;
  }
}

// Toast Notifications Helper
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
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
