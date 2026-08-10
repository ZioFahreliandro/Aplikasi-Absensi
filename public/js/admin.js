// Admin Panel Module
document.addEventListener('DOMContentLoaded', () => {
  // Navigation & Sub-tab management
  setupAdminTabs();
  
  // Set default month input filter to current month (YYYY-MM)
  const filterMonthInput = document.getElementById('filter-month');
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  filterMonthInput.value = `${year}-${month}`;
  
  // Listeners for Recap
  filterMonthInput.addEventListener('change', loadRecapData);
  document.getElementById('btn-export-csv').addEventListener('click', exportRecapCSV);
  
  // Listeners for Employee CRUD
  document.getElementById('employee-form').addEventListener('submit', saveEmployee);
  document.getElementById('btn-cancel-edit').addEventListener('click', resetEmployeeForm);
  
  // Listeners for Settings Form
  document.getElementById('settings-form').addEventListener('submit', saveSettings);
  document.getElementById('btn-get-current-coords').addEventListener('click', getCurrentCoordsForSettings);
  document.getElementById('btn-get-current-ip').addEventListener('click', getCurrentIpForSettings);
  
  // Modal Photo listener
  setupPhotoModal();
});

// Exposed function called when admin tab navigation is clicked
window.initAdminPanel = function() {
  loadRecapData();
  loadEmployeeData();
  loadSettingsData();
};

// Helper to get CSRF token from HTML head
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

// Admin Sidebar Sub-Tab Switcher
function setupAdminTabs() {
  const tabs = {
    'tab-recap': 'sub-view-recap',
    'tab-employees': 'sub-view-employees',
    'tab-settings': 'sub-view-settings'
  };
  
  Object.keys(tabs).forEach(tabId => {
    const tabBtn = document.getElementById(tabId);
    tabBtn.addEventListener('click', () => {
      // Remove active from all tabs & subviews
      Object.keys(tabs).forEach(id => {
        document.getElementById(id).classList.remove('active');
        document.getElementById(tabs[id]).classList.remove('active');
      });
      
      // Add active to current
      tabBtn.classList.add('active');
      document.getElementById(tabs[tabId]).classList.add('active');
      
      // Fetch fresh data based on tab
      if (tabId === 'tab-recap') loadRecapData();
      if (tabId === 'tab-employees') loadEmployeeData();
      if (tabId === 'tab-settings') loadSettingsData();
    });
  });
}

// ----------------------------------------------------
// 1. RECAP & REPORTS SECTION
// ----------------------------------------------------
let currentAttendanceLogs = [];

async function loadRecapData() {
  const monthFilter = document.getElementById('filter-month').value;
  const tbody = document.getElementById('recap-table-body');
  
  tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Memuat data absensi...</td></tr>`;
  
  try {
    const res = await fetch(`/api/attendance?month=${monthFilter}`);
    currentAttendanceLogs = await res.json();
    
    // Update Stats Overview
    updateStats(currentAttendanceLogs);
    
    if (currentAttendanceLogs.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Tidak ada rekaman absensi pada bulan ini.</td></tr>`;
      return;
    }
    
    tbody.innerHTML = '';
    currentAttendanceLogs.forEach(log => {
      const tr = document.createElement('tr');
      
      // Type Badge
      const typeBadge = log.type === 'masuk' 
        ? `<span class="indicator-badge success" style="padding:0.2rem 0.5rem">Masuk</span>`
        : `<span class="indicator-badge danger" style="padding:0.2rem 0.5rem; background:rgba(239,68,68,0.15)">Pulang</span>`;
      
      // Verification Status Badge
      let statusBadge = '';
      if (log.status === 'Success') {
        statusBadge = `<span class="indicator-badge success" style="font-size:0.75rem; padding:0.2rem 0.5rem"><i data-lucide="check" style="width:12px;height:12px"></i> Sukses</span>`;
      } else {
        statusBadge = `<span class="indicator-badge danger" style="font-size:0.75rem; padding:0.2rem 0.5rem"><i data-lucide="x" style="width:12px;height:12px"></i> Gagal</span>`;
      }
      
      // GPS Distance display
      // Laravel maps SQLite double/float fields. Sometimes JSON decodes them as numbers or null
      const latitude = log.latitude;
      const longitude = log.longitude;
      const gpsDisplay = (latitude !== null && latitude !== undefined)
        ? `<span title="Lat: ${latitude}, Lng: ${longitude}">
             ${log.distance !== null ? `${log.distance} meter` : 'Terdeteksi'}
           </span>`
        : `<span class="text-muted">Tidak ada GPS</span>`;
      
      tr.innerHTML = `
        <td><strong>${log.employee_name}</strong></td>
        <td>${formatIndoDate(log.date)}</td>
        <td><code style="font-size:0.95rem; font-weight:600">${log.time}</code></td>
        <td>${typeBadge}</td>
        <td>
          <img src="${log.selfie_url}" class="table-selfie-thumb" alt="Selfie ${log.employee_name}">
        </td>
        <td>${gpsDisplay}</td>
        <td><code>${log.ip_address || '-'}</code></td>
        <td>${statusBadge}</td>
      `;
      
      // Bind click event on selfie thumbnail
      tr.querySelector('.table-selfie-thumb').addEventListener('click', () => {
        openModal(log);
      });
      
      tbody.appendChild(tr);
    });
    
    lucide.createIcons();
  } catch (error) {
    console.error("Gagal mengambil rekap absensi:", error);
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Gagal memuat data absensi dari server.</td></tr>`;
  }
}

// Update Recap Quick Dashboard stats cards
async function updateStats(logs) {
  // 1. Total active employees
  try {
    const res = await fetch('/api/employees');
    const employees = await res.json();
    document.getElementById('stat-total-emp').textContent = employees.length;
  } catch (e) {
    console.error(e);
  }
  
  // Get today's logs (in local client date timezone)
  const todayStr = new Date().toISOString().split('T')[0];
  // Log date field in Laravel is YYYY-MM-DD
  const todayLogs = logs.filter(log => log.date === todayStr);
  
  // 2. Present Today (Distinct employees clocked-in today)
  const presentTodayIds = new Set(
    todayLogs.filter(log => log.type === 'masuk').map(log => log.employee_id)
  );
  document.getElementById('stat-present-today').textContent = presentTodayIds.size;
  
  // 3. Outside radius logs count today
  let officeRadius = 100;
  try {
    const res = await fetch('/api/settings');
    const settings = await res.json();
    officeRadius = settings.office_radius || 100;
  } catch (e) {}
  
  const outsideCount = todayLogs.filter(log => log.distance !== null && log.distance > officeRadius).length;
  document.getElementById('stat-outside-today').textContent = outsideCount;
}

// Photo Preview Modal Controller
function setupPhotoModal() {
  const modal = document.getElementById('modal-photo');
  const closeBtn = document.querySelector('.modal-close');
  
  closeBtn.addEventListener('click', () => {
    modal.classList.remove('active');
  });
  
  // Close on outside click
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });
}

function openModal(log) {
  const modal = document.getElementById('modal-photo');
  const img = document.getElementById('modal-expanded-img');
  const details = document.getElementById('modal-photo-details');
  
  img.src = log.selfie_url;
  
  const formattedCoords = (log.latitude !== null && log.latitude !== undefined) 
    ? `${log.latitude}, ${log.longitude}` 
    : 'Tidak Ada GPS';
    
  const formattedDistance = log.distance !== null ? `${log.distance} meter dari kantor` : 'Jarak tidak diketahui';
  
  details.innerHTML = `
    <div>Karyawan: <span>${log.employee_name}</span></div>
    <div>Tanggal/Waktu: <span>${formatIndoDate(log.date)} / ${log.time}</span></div>
    <div>Tipe Absen: <span>Absen ${log.type === 'masuk' ? 'Masuk' : 'Pulang'}</span></div>
    <div>IP Koneksi: <span>${log.ip_address || 'Localhost'}</span></div>
    <div>Lokasi GPS: <span>${formattedCoords} (${formattedDistance})</span></div>
    <div>Status Kehadiran: <span style="color:var(--success)">${log.status}</span></div>
  `;
  
  modal.classList.add('active');
}

// Generate & Export CSV File
function exportRecapCSV() {
  if (currentAttendanceLogs.length === 0) {
    window.showToast("Tidak ada data absensi untuk diekspor pada bulan ini", "error");
    return;
  }
  
  const monthFilter = document.getElementById('filter-month').value;
  
  // CSV Headers
  let csvContent = "Nama Karyawan,Tanggal,Jam,Tipe Absen,Jarak (Meter),IP Address,Latitude,Longitude,Status\n";
  
  // CSV Rows
  currentAttendanceLogs.forEach(log => {
    const name = `"${log.employee_name.replace(/"/g, '""')}"`;
    const date = log.date;
    const time = log.time;
    const type = log.type === 'masuk' ? 'Masuk' : 'Pulang';
    const distance = log.distance !== null ? log.distance : '-';
    const ip = log.ip_address || '-';
    const lat = log.latitude !== null ? log.latitude : '-';
    const lng = log.longitude !== null ? log.longitude : '-';
    const status = log.status;
    
    csvContent += `${name},${date},${time},${type},${distance},${ip},${lat},${lng},${status}\n`;
  });
  
  // Create download link
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", `Rekap_Absensi_${monthFilter}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  window.showToast(`Berhasil mengekspor rekap ${monthFilter} ke CSV`, "success");
}

// Date formatter utility
function formatIndoDate(dateStr) {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  
  const d = parseInt(parts[2], 10);
  const m = parseInt(parts[1], 10) - 1;
  const y = parts[0];
  
  return `${d} ${MONTHS[m]} ${y}`;
}

// ----------------------------------------------------
// 2. EMPLOYEE CRUD SECTION
// ----------------------------------------------------
async function loadEmployeeData() {
  const tbody = document.getElementById('employee-table-body');
  tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Memuat karyawan...</td></tr>`;
  
  try {
    const res = await fetch('/api/employees');
    const employees = await res.json();
    
    if (employees.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Belum ada karyawan terdaftar.</td></tr>`;
      return;
    }
    
    tbody.innerHTML = '';
    employees.forEach(emp => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><code>${emp.nip || '-'}</code></td>
        <td><strong>${emp.name}</strong></td>
        <td><code>••••</code></td>
        <td>
          <button class="btn-primary btn-edit" data-id="${emp.id}">Edit</button>
          <button class="btn-danger" data-id="${emp.id}">Hapus</button>
        </td>
      `;
      
      // Bind Edit Action
      tr.querySelector('.btn-edit').addEventListener('click', () => {
        editEmployee(emp);
      });
      
      // Bind Delete Action
      tr.querySelector('.btn-danger').addEventListener('click', () => {
        deleteEmployee(emp.id, emp.name);
      });
      
      tbody.appendChild(tr);
    });
  } catch (error) {
    console.error("Gagal memuat karyawan:", error);
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Gagal memuat karyawan dari server.</td></tr>`;
  }
}

// Save Employee Form Handler (Create or Update)
async function saveEmployee(e) {
  e.preventDefault();
  
  const id = document.getElementById('employee-id').value;
  const name = document.getElementById('emp-name').value.trim();
  const nip = document.getElementById('emp-nip').value.trim();
  const pin = document.getElementById('emp-pin').value.trim();
  
  if (!name || !nip || !pin) {
    window.showToast("Semua data input karyawan wajib diisi!", "error");
    return;
  }
  
  if (pin.length !== 4 || isNaN(pin)) {
    window.showToast("PIN wajib berisi 4 digit angka!", "error");
    return;
  }
  
  const url = id ? `/api/employees/${id}` : '/api/employees';
  const method = id ? 'PUT' : 'POST';
  
  try {
    const res = await fetch(url, {
      method,
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
      },
      body: JSON.stringify({ name, nip, pin })
    });
    
    if (res.ok) {
      window.showToast(`Karyawan berhasil ${id ? 'diperbarui' : 'ditambahkan'}!`, "success");
      resetEmployeeForm();
      loadEmployeeData();
      
      // Sync Kiosk employee dropdown
      if (window.fetchEmployees) window.fetchEmployees();
    } else {
      const data = await res.json();
      window.showToast(data.error || "Gagal menyimpan data karyawan", "error");
    }
  } catch (error) {
    console.error(error);
    window.showToast("Gagal menyimpan karyawan ke server", "error");
  }
}

function editEmployee(emp) {
  document.getElementById('employee-id').value = emp.id;
  document.getElementById('emp-name').value = emp.name;
  document.getElementById('emp-nip').value = emp.nip;
  document.getElementById('emp-pin').value = emp.pin; // show raw pin for edit convenience
  
  document.getElementById('employee-form-title').textContent = "Edit Data Karyawan";
  document.getElementById('btn-submit-employee').innerHTML = `<i data-lucide="save"></i> Perbarui Karyawan`;
  document.getElementById('btn-cancel-edit').style.display = "inline-flex";
  lucide.createIcons();
}

function resetEmployeeForm() {
  document.getElementById('employee-id').value = '';
  document.getElementById('emp-name').value = '';
  document.getElementById('emp-nip').value = '';
  document.getElementById('emp-pin').value = '';
  
  document.getElementById('employee-form-title').textContent = "Tambah Karyawan Baru";
  document.getElementById('btn-submit-employee').innerHTML = `<i data-lucide="plus-circle"></i> Simpan Karyawan`;
  document.getElementById('btn-cancel-edit').style.display = "none";
  lucide.createIcons();
}

async function deleteEmployee(id, name) {
  if (!confirm(`Apakah Anda yakin ingin menghapus karyawan "${name}"? Semua data absensi karyawan tersebut tetap tersimpan di arsip.`)) {
    return;
  }
  
  try {
    const res = await fetch(`/api/employees/${id}`, { 
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken()
      }
    });
    if (res.ok) {
      window.showToast(`Karyawan "${name}" berhasil dihapus`, "success");
      loadEmployeeData();
      
      // Sync Kiosk employee dropdown
      if (window.fetchEmployees) window.fetchEmployees();
    } else {
      window.showToast("Gagal menghapus karyawan", "error");
    }
  } catch (error) {
    console.error(error);
    window.showToast("Kesalahan jaringan saat menghapus karyawan", "error");
  }
}

// ----------------------------------------------------
// 3. SETTINGS SECTION
// ----------------------------------------------------
async function loadSettingsData() {
  try {
    const res = await fetch('/api/settings');
    const settings = await res.json();
    
    // Set fields (Laravel returns snake_case columns)
    document.getElementById('set-office-name').value = settings.office_name;
    document.getElementById('set-enable-gps').checked = settings.enable_gps;
    document.getElementById('set-enable-ip').checked = settings.enable_ip;
    document.getElementById('set-office-lat').value = settings.office_lat;
    document.getElementById('set-office-lng').value = settings.office_lng;
    document.getElementById('set-office-radius').value = settings.office_radius;
    document.getElementById('set-office-ip').value = settings.office_ip;
    
    // Also trigger update in app.js variables
    if (window.fetchSettings) window.fetchSettings();
  } catch (error) {
    console.error("Gagal memuat pengaturan:", error);
    window.showToast("Gagal memuat data pengaturan dari server", "error");
  }
}

async function saveSettings(e) {
  e.preventDefault();
  
  const officeName = document.getElementById('set-office-name').value.trim();
  const enableGps = document.getElementById('set-enable-gps').checked;
  const enableIp = document.getElementById('set-enable-ip').checked;
  const officeLat = parseFloat(document.getElementById('set-office-lat').value);
  const officeLng = parseFloat(document.getElementById('set-office-lng').value);
  const officeRadius = parseInt(document.getElementById('set-office-radius').value, 10);
  const officeIp = document.getElementById('set-office-ip').value.trim();
  
  const payload = {
    officeName,
    enableGps,
    enableIp,
    officeLat,
    officeLng,
    officeRadius,
    officeIp
  };
  
  try {
    const res = await fetch('/api/settings', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
      },
      body: JSON.stringify(payload)
    });
    
    if (res.ok) {
      window.showToast("Semua pengaturan berhasil disimpan!", "success");
      
      // Trigger app.js to reload settings immediately
      if (window.fetchSettings) window.fetchSettings();
      // Reload admin panel configs
      loadSettingsData();
    } else {
      window.showToast("Gagal menyimpan pengaturan", "error");
    }
  } catch (error) {
    console.error(error);
    window.showToast("Kesalahan jaringan saat menyimpan pengaturan", "error");
  }
}

// Auto-fill Settings Coordinates with current GPS coords
function getCurrentCoordsForSettings() {
  if (!navigator.geolocation) {
    window.showToast("Geolocation tidak didukung oleh browser Anda", "error");
    return;
  }
  
  window.showToast("Mengambil lokasi GPS Anda...", "info");
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      document.getElementById('set-office-lat').value = position.coords.latitude.toFixed(6);
      document.getElementById('set-office-lng').value = position.coords.longitude.toFixed(6);
      window.showToast("Berhasil mengambil koordinat Anda saat ini!", "success");
    },
    (error) => {
      console.error(error);
      window.showToast(`Gagal mengambil koordinat: ${error.message}`, "error");
    },
    { enableHighAccuracy: true }
  );
}

// Auto-fill Settings IP Address with client's IP detected by server
async function getCurrentIpForSettings() {
  window.showToast("Mendeteksi IP Anda dari server...", "info");
  try {
    const res = await fetch('/api/my-ip');
    const data = await res.json();
    
    if (data.ip) {
      document.getElementById('set-office-ip').value = data.ip;
      window.showToast(`Berhasil mendeteksi IP Anda: ${data.ip}`, "success");
    } else {
      window.showToast("Gagal mendeteksi IP", "error");
    }
  } catch (error) {
    console.error(error);
    window.showToast("Gagal terhubung ke API pendeteksi IP", "error");
  }
}
