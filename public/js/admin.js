// Admin Panel Module
const MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const ADMIN_ACTIVE_TAB_KEY = 'absengo_admin_active_tab';
const EMPLOYEE_SEARCH_KEY = 'absengo_employee_search';

if (typeof window.showToast !== 'function') {
  window.showToast = function showToast(message, type = 'info') {
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
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }

    setTimeout(() => {
      toast.classList.add('show');
    }, 10);

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 4000);
  };
}

document.addEventListener('DOMContentLoaded', () => {
  // Navigation & Sub-tab management
  setupAdminTabs();
  restoreActiveAdminTab();
  
  // Set default month input filter to current month (YYYY-MM)
  const filterMonthInput = document.getElementById('filter-month');
  if (!filterMonthInput) {
    return;
  }
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  filterMonthInput.value = `${year}-${month}`;
  
  // Listeners for Recap
  filterMonthInput.addEventListener('change', loadRecapData);
  document.getElementById('btn-export-csv').addEventListener('click', exportRecapCSV);
  document.getElementById('btn-delete-today').addEventListener('click', deleteTodayAttendance);
  
  // Listeners for Employee CRUD
  document.getElementById('employee-form').addEventListener('submit', saveEmployee);
  document.getElementById('btn-cancel-edit').addEventListener('click', resetEmployeeForm);
  setupEmployeeSearch();
  
  // Listeners for Settings Form
  document.getElementById('settings-form').addEventListener('submit', saveSettings);
  document.getElementById('btn-get-current-coords').addEventListener('click', openLocationPicker);
  
  // Modal Photo listener
  setupPhotoModal();
  setupResetPasswordModal();
  setupLocationPicker();

  // Auto-load admin data when the dashboard opens for the first time.
  const adminView = document.getElementById('view-admin');
  if (adminView && adminView.classList.contains('active')) {
    window.initAdminPanel();
  }
});

// Exposed function called when admin tab navigation is clicked
window.initAdminPanel = function() {
  const activeTabId = sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY) || 'tab-recap';

  if (activeTabId === 'tab-employees') {
    loadEmployeeData();
  } else if (activeTabId === 'tab-settings') {
    loadSettingsData();
  } else {
    loadRecapData();
  }
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
    if (!tabBtn) return;
    tabBtn.addEventListener('click', () => {
      activateAdminTab(tabId, tabs);
    });
  });
}

function activateAdminTab(tabId, tabs = null) {
  const tabMap = tabs || {
    'tab-recap': 'sub-view-recap',
    'tab-employees': 'sub-view-employees',
    'tab-settings': 'sub-view-settings'
  };

  Object.keys(tabMap).forEach(id => {
    const tab = document.getElementById(id);
    const view = document.getElementById(tabMap[id]);
    if (tab) tab.classList.remove('active');
    if (view) view.classList.remove('active');
  });

  const activeTab = document.getElementById(tabId);
  const activeView = document.getElementById(tabMap[tabId]);
  if (activeTab) activeTab.classList.add('active');
  if (activeView) activeView.classList.add('active');

  sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY, tabId);

  if (tabId === 'tab-recap') loadRecapData();
  if (tabId === 'tab-employees') loadEmployeeData();
  if (tabId === 'tab-settings') loadSettingsData();
}

function restoreActiveAdminTab() {
  const tabId = sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY);
  if (!tabId) return;

  const tabBtn = document.getElementById(tabId);
  if (!tabBtn) return;

  tabBtn.click();
}

// ----------------------------------------------------
// 1. RECAP & REPORTS SECTION
// ----------------------------------------------------
let currentAttendanceLogs = [];

function escapeHtml(value) {
  const text = value === null || value === undefined ? '' : String(value);
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getAttendanceSummary(log) {
  const note = log.attendance_note || 'Tepat Waktu';
  const reason = (log.late_reason || '').trim();

  if (reason && note !== 'Tepat Waktu') {
    return `${note} - ${reason}`;
  }

  return note;
}

async function loadRecapData() {
  const monthFilter = document.getElementById('filter-month').value;
  const tbody = document.getElementById('recap-table-body');
  
  tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Memuat data absensi...</td></tr>`;
  
  try {
    const res = await fetch(`/api/attendance?month=${encodeURIComponent(monthFilter)}&t=${Date.now()}`, {
      cache: 'no-store',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!res.ok) {
      const responseText = await res.text();
      let message = `Server mengembalikan ${res.status}`;

      try {
        const parsed = JSON.parse(responseText);
        message = parsed.error || parsed.message || message;
      } catch (parseError) {
        if (responseText.toLowerCase().includes('<!doctype html') || responseText.toLowerCase().includes('<html')) {
          message = 'Server mengembalikan halaman HTML, biasanya karena sesi login habis atau ada error di backend.';
        }
      }

      throw new Error(message);
    }

    currentAttendanceLogs = await res.json();
    
    // Update Stats Overview
    updateStats(currentAttendanceLogs);
    
    if (currentAttendanceLogs.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Tidak ada rekaman absensi pada bulan ini.</td></tr>`;
      return;
    }
    
    tbody.innerHTML = '';
    currentAttendanceLogs.forEach(log => {
      const tr = document.createElement('tr');
      
      // Type Badge
      const typeBadge = log.type === 'masuk' 
        ? `<span class="indicator-badge success" style="padding:0.2rem 0.5rem">Masuk</span>`
        : `<span class="indicator-badge danger" style="padding:0.2rem 0.5rem; background:rgba(239,68,68,0.15)">Pulang</span>`;

      // Attendance Note Badge
      const note = log.attendance_note || 'Tepat Waktu';
      const noteBadge = note === 'Tepat Waktu'
        ? `<span class="indicator-badge success" style="padding:0.2rem 0.5rem">Tepat Waktu</span>`
        : `<span class="indicator-badge warning" style="padding:0.2rem 0.5rem">${escapeHtml(note)}</span>`;
      const lateReason = (log.late_reason || '').trim();
      
      tr.innerHTML = `
        <td><strong>${escapeHtml(log.employee_name)}</strong></td>
        <td>${escapeHtml(formatIndoDate(log.date))}</td>
        <td><code style="font-size:0.95rem; font-weight:600">${escapeHtml(log.time)}</code></td>
        <td>${typeBadge}</td>
        <td>${noteBadge}</td>
        <td>${lateReason ? `<span class="recap-reason">${escapeHtml(lateReason)}</span>` : '<span class="text-muted">-</span>'}</td>
        <td>
          <img src="${escapeHtml(log.selfie_url)}" class="table-selfie-thumb" alt="Selfie ${escapeHtml(log.employee_name)}">
        </td>
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
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat data absensi dari server: ${escapeHtml(error.message)}</td></tr>`;
  }
}

// Update Recap Quick Dashboard stats cards
async function updateStats(logs) {
  // 1. Total active employees
  try {
    const res = await fetch(`/api/employees?t=${Date.now()}`, {
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
      },
    });
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
    const res = await fetch(`/api/settings?t=${Date.now()}`, {
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
      },
    });
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
  if (!modal || !closeBtn) return;
  
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

function setupResetPasswordModal() {
  const modal = document.getElementById('modal-reset-password');
  const closeBtn = document.getElementById('close-reset-password');
  const closeActionBtn = document.getElementById('btn-close-reset-password');
  const copyBtn = document.getElementById('btn-copy-reset-password');

  if (!modal) return;

  const closeModal = () => {
    modal.classList.remove('active');
  };

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (closeActionBtn) closeActionBtn.addEventListener('click', closeModal);
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const code = document.getElementById('reset-password-value')?.textContent || '';
      if (!code || code === '-') return;

      try {
        await navigator.clipboard.writeText(code);
        window.showToast('Password sementara disalin ke clipboard.', 'success');
      } catch (error) {
        console.error(error);
        window.showToast('Gagal menyalin password sementara.', 'error');
      }
    });
  }

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });
}

function openResetPasswordModal(name, password) {
  const modal = document.getElementById('modal-reset-password');
  const nameEl = document.getElementById('reset-password-employee-name');
  const passwordEl = document.getElementById('reset-password-value');

  if (!modal || !nameEl || !passwordEl) return;

  nameEl.textContent = name || '-';
  passwordEl.textContent = password || '-';
  modal.classList.add('active');
}

function openModal(log) {
  const modal = document.getElementById('modal-photo');
  const img = document.getElementById('modal-expanded-img');
  
  img.src = log.selfie_url;
  
  modal.classList.add('active');
}

// Generate & Export Spreadsheet Table
function exportRecapCSV() {
  if (currentAttendanceLogs.length === 0) {
    window.showToast("Tidak ada data absensi untuk diekspor pada bulan ini", "error");
    return;
  }
  
  const monthFilter = document.getElementById('filter-month').value;

  const rows = currentAttendanceLogs.map((log) => `
    <tr>
      <td>${escapeHtml(log.employee_name || '-')}</td>
      <td>${escapeHtml(log.date || '-')}</td>
      <td>${escapeHtml(log.time || '-')}</td>
      <td>${escapeHtml(log.type === 'masuk' ? 'Masuk' : 'Pulang')}</td>
      <td>${escapeHtml(log.attendance_note || 'Tepat Waktu')}</td>
      <td>${escapeHtml((log.late_reason || '').trim() || '-')}</td>
      <td>${escapeHtml(log.status || '-')}</td>
    </tr>
  `).join('');

  const htmlTable = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        body {
          font-family: Arial, sans-serif;
          background: #fff;
          color: #111827;
        }
        table {
          border-collapse: collapse;
          width: 100%;
        }
        th, td {
          border: 1px solid #000;
          padding: 6px 10px;
          font-size: 12px;
          text-align: left;
          vertical-align: top;
        }
        th {
          font-weight: 700;
          background: #f3f4f6;
        }
      </style>
    </head>
    <body>
      <table>
        <thead>
          <tr>
            <th>nama karyawan</th>
            <th>Tanggal</th>
            <th>jam</th>
            <th>Tipe absen</th>
            <th>Keterangan</th>
            <th>Alasan</th>
            <th>status</th>
          </tr>
        </thead>
        <tbody>
          ${rows}
        </tbody>
      </table>
    </body>
    </html>
  `;

  const blob = new Blob(['\uFEFF' + htmlTable], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", `Rekap_Absensi_${monthFilter}.xls`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  window.showToast(`Berhasil mengekspor rekap ${monthFilter} ke tabel Excel`, "success");
}

async function deleteTodayAttendance() {
  const confirmed = confirm('Apakah Anda yakin ingin menghapus semua rekapan absensi hari ini? Tindakan ini tidak bisa dibatalkan.');
  if (!confirmed) return;

  try {
    const res = await fetch(`/api/attendance/today?t=${Date.now()}`, {
      cache: 'no-store',
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken()
      }
    });

    const data = await res.json();

    if (res.ok) {
      window.showToast(data.message || 'Rekapan hari ini berhasil dihapus', 'success');
      loadRecapData();
    } else {
      window.showToast(data.error || 'Gagal menghapus rekapan hari ini', 'error');
    }
  } catch (error) {
    console.error(error);
    window.showToast('Kesalahan jaringan saat menghapus rekapan hari ini', 'error');
  }
}

// Date formatter utility
function formatIndoDate(dateStr) {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  
  const d = parseInt(parts[2], 10);
  const m = parseInt(parts[1], 10) - 1;
  const y = parts[0];

  if (!MONTHS[m]) {
    return dateStr;
  }

  return `${d} ${MONTHS[m]} ${y}`;
}

function normalizeDateForInput(dateValue) {
  if (!dateValue) return '';

  const parsed = String(dateValue).split('T')[0];
  if (/^\d{4}-\d{2}-\d{2}$/.test(parsed)) {
    return parsed;
  }

  return '';
}

function formatBirthDateForSearch(dateValue) {
  const normalized = normalizeDateForInput(dateValue);
  if (!normalized) return '';

  return `${normalized} ${formatIndoDate(normalized)}`;
}

// ----------------------------------------------------
// 2. EMPLOYEE CRUD SECTION
// ----------------------------------------------------
let currentEmployees = [];
let employeeSearchTerm = '';

async function loadEmployeeData() {
  const tbody = document.getElementById('employee-table-body');
  tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Memuat karyawan...</td></tr>`;
  
  try {
    const res = await fetch(`/api/employees?t=${Date.now()}`, {
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
      },
    });
    const employees = await res.json();
    currentEmployees = Array.isArray(employees) ? employees : [];
    renderEmployeeTable();
  } catch (error) {
    console.error("Gagal memuat karyawan:", error);
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Gagal memuat karyawan dari server.</td></tr>`;
  }
}

function setupEmployeeSearch() {
  const searchInput = document.getElementById('employee-search');
  if (!searchInput) return;

  employeeSearchTerm = (searchInput.value || sessionStorage.getItem(EMPLOYEE_SEARCH_KEY) || '').trim();
  searchInput.value = employeeSearchTerm;

  searchInput.addEventListener('input', (event) => {
    employeeSearchTerm = event.target.value.trim();
    sessionStorage.setItem(EMPLOYEE_SEARCH_KEY, employeeSearchTerm);
    renderEmployeeTable();
  });

  renderEmployeeTable();
}

function getFilteredEmployees() {
  const term = employeeSearchTerm.toLowerCase();
  const filtered = !term
    ? currentEmployees.slice()
    : currentEmployees.filter((emp) => {
      return [emp.name, emp.nip, formatBirthDateForSearch(emp.birth_date)]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(term));
    });

  return filtered.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'id'));
}

function renderEmployeeTable() {
  const tbody = document.getElementById('employee-table-body');
  if (!tbody) return;

  if (currentEmployees.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Belum ada karyawan terdaftar.</td></tr>`;
    return;
  }

  const filteredEmployees = getFilteredEmployees();

  if (filteredEmployees.length === 0) {
    const term = escapeHtml(employeeSearchTerm);
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="text-center text-muted">
          Tidak ditemukan karyawan untuk pencarian "${term}".
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = '';
  filteredEmployees.forEach(emp => {
    tbody.appendChild(createEmployeeRow(emp));
  });
}

function createEmployeeRow(emp) {
  const tr = document.createElement('tr');
  tr.dataset.employeeId = emp.id;
  const passwordStatus = emp.must_change_password
    ? '<span class="indicator-badge warning" style="margin-top:0.35rem;">Wajib ganti password</span>'
    : '<span class="indicator-badge success" style="margin-top:0.35rem;">Password aktif</span>';
  const birthDate = formatIndoDate(emp.birth_date);

  tr.innerHTML = `
    <td><code>${emp.nip || '-'}</code></td>
    <td><code>${birthDate || '-'}</code></td>
    <td>
      <strong>${emp.name}</strong>
      <div>${passwordStatus}</div>
    </td>
    <td>
      <button class="btn-primary btn-edit" data-id="${emp.id}">Edit</button>
      <button class="btn-reset" data-id="${emp.id}">Reset Password</button>
      <button class="btn-danger" data-id="${emp.id}">Hapus</button>
    </td>
  `;

  tr.querySelector('.btn-edit').addEventListener('click', () => {
    editEmployee(emp);
  });

  tr.querySelector('.btn-reset').addEventListener('click', () => {
    resetEmployeePassword(emp.id, emp.name);
  });

  tr.querySelector('.btn-danger').addEventListener('click', () => {
    deleteEmployee(emp.id, emp.name);
  });

  return tr;
}

function upsertEmployeeRow(emp) {
  const tbody = document.getElementById('employee-table-body');
  if (!tbody) return;

  currentEmployees = currentEmployees.filter((item) => item.id !== emp.id);
  currentEmployees.push(emp);

  renderEmployeeTable();
}

// Save Employee Form Handler (Create or Update)
async function saveEmployee(e) {
  e.preventDefault();
  
  const id = document.getElementById('employee-id').value;
  const name = document.getElementById('emp-name').value.trim();
  const nip = document.getElementById('emp-nip').value.trim();
  const birthDate = document.getElementById('emp-birth-date').value;
  const isEditing = Boolean(id);
  
  if (!name || !nip || !birthDate) {
    window.showToast("Semua data input karyawan wajib diisi!", "error");
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
      body: JSON.stringify({ name, nip, birth_date: birthDate })
    });
    
    if (res.ok) {
      const savedEmployee = await res.json();
      window.showToast(`Karyawan berhasil ${id ? 'diperbarui' : 'ditambahkan'}!`, "success");
      upsertEmployeeRow(savedEmployee);
      resetEmployeeForm();
      
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
  document.getElementById('emp-birth-date').value = normalizeDateForInput(emp.birth_date);
  document.getElementById('employee-password-group').style.display = 'none';
  
  document.getElementById('employee-form-title').textContent = "Edit Data Karyawan";
  document.getElementById('btn-submit-employee').innerHTML = `<i data-lucide="save"></i> Perbarui Karyawan`;
  document.getElementById('btn-cancel-edit').style.display = "inline-flex";
  lucide.createIcons();
}

function resetEmployeeForm() {
  document.getElementById('employee-id').value = '';
  document.getElementById('emp-name').value = '';
  document.getElementById('emp-nip').value = '';
  document.getElementById('emp-birth-date').value = '';
  document.getElementById('employee-password-group').style.display = 'block';
  
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
      currentEmployees = currentEmployees.filter((emp) => String(emp.id) !== String(id));
      renderEmployeeTable();
      
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

async function resetEmployeePassword(id, name) {
  const confirmed = confirm(`Reset password untuk "${name}"? Sistem akan membuat password sementara baru dan karyawan akan diminta ganti password saat login berikutnya.`);
  if (!confirmed) return;

  try {
    const res = await fetch(`/api/employees/${id}/reset-password`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    });

    const data = await res.json();

    if (res.ok) {
      const temporaryPassword = data.temporary_password || '-';
      window.showToast(`Password "${name}" berhasil di-reset.`, 'success');
      upsertEmployeeRow(data.employee || { id, name });
      openResetPasswordModal(name, temporaryPassword);
    } else {
      window.showToast(data.error || 'Gagal reset password karyawan', 'error');
    }
  } catch (error) {
    console.error(error);
    window.showToast('Kesalahan jaringan saat reset password karyawan', 'error');
  }
}

// ----------------------------------------------------
// 3. SETTINGS SECTION
// ----------------------------------------------------
let locationPickerSelection = {
  lat: null,
  lng: null,
};

let locationPickerMap = null;
let locationPickerMarker = null;

function buildGoogleMapsBrowseUrl(lat, lng) {
  return `https://www.google.com/maps?q=${lat},${lng}`;
}

function buildGoogleMapsSearchUrl(lat, lng) {
  if (lat === null || lng === null || Number.isNaN(Number(lat)) || Number.isNaN(Number(lng))) {
    return null;
  }

  const formattedLat = Number(lat).toFixed(6);
  const formattedLng = Number(lng).toFixed(6);
  return `https://www.google.com/maps/search/?api=1&query=${formattedLat},${formattedLng}`;
}

function updateLocationPickerStatus(message) {
  const status = document.getElementById('location-picker-status');
  if (status) {
    status.textContent = message;
  }
}

function ensureLocationPickerMap() {
  if (!window.google || !google.maps) {
    updateLocationPickerStatus('Google Maps belum dimuat. Isi GOOGLE_MAPS_API_KEY di .env dulu.');
    return false;
  }

  const mapElement = document.getElementById('location-picker-map');
  if (!mapElement) return false;

  const fallbackPosition = { lat: -6.200000, lng: 106.816666 };
  const initialPosition =
    locationPickerSelection.lat !== null && locationPickerSelection.lng !== null
      ? { lat: locationPickerSelection.lat, lng: locationPickerSelection.lng }
      : fallbackPosition;

  if (!locationPickerMap) {
    locationPickerMap = new google.maps.Map(mapElement, {
      center: initialPosition,
      zoom: 16,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
      clickableIcons: true,
    });

    locationPickerMarker = new google.maps.Marker({
      position: initialPosition,
      map: locationPickerMap,
      draggable: true,
    });

    locationPickerMap.addListener('click', (event) => {
      if (!event.latLng) return;
      setPickedLocation(event.latLng.lat(), event.latLng.lng(), true);
    });

    locationPickerMarker.addListener('dragend', (event) => {
      if (!event.latLng) return;
      setPickedLocation(event.latLng.lat(), event.latLng.lng(), true);
    });
  } else {
    google.maps.event.trigger(locationPickerMap, 'resize');
    locationPickerMap.setCenter(initialPosition);
  }

  if (locationPickerMarker) {
    locationPickerMarker.setPosition(initialPosition);
  }

  updateLocationPickerStatus('Klik titik di peta untuk memilih lokasi kantor.');
  return true;
}

window.initLocationPickerMap = function initLocationPickerMap() {
  ensureLocationPickerMap();
};

async function loadSettingsData() {
  try {
    const res = await fetch(`/api/settings?t=${Date.now()}`, {
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
      },
    });
    const settings = await res.json();

    // Set fields (Laravel returns snake_case columns)
    document.getElementById('set-office-name').value = settings.office_name;
    document.getElementById('set-enable-gps').checked = settings.enable_gps;
    document.getElementById('set-office-lat').value = settings.office_lat;
    document.getElementById('set-office-lng').value = settings.office_lng;
    document.getElementById('set-office-radius').value = settings.office_radius;
    document.getElementById('set-office-checkin-time').value = normalizeTimeForInput(settings.office_checkin_time || '08:00:00');
    document.getElementById('set-office-checkout-time').value = normalizeTimeForInput(settings.office_checkout_time || '17:00:00');

    const scheduleEl = document.getElementById('info-work-schedule');
    if (scheduleEl) {
      scheduleEl.textContent = `${normalizeTimeForInput(settings.office_checkin_time || '08:00:00')} - ${normalizeTimeForInput(settings.office_checkout_time || '17:00:00')}`;
    }

    const lat = parseFloat(settings.office_lat);
    const lng = parseFloat(settings.office_lng);
    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
      setPickedLocation(lat, lng, false);
    }
  } catch (error) {
    console.error('Gagal memuat pengaturan:', error);
    window.showToast('Gagal memuat data pengaturan dari server', 'error');
  }
}

async function saveSettings(e) {
  e.preventDefault();

  const officeName = document.getElementById('set-office-name').value.trim();
  const enableGps = document.getElementById('set-enable-gps').checked;
  const officeLat = parseFloat(document.getElementById('set-office-lat').value);
  const officeLng = parseFloat(document.getElementById('set-office-lng').value);
  const officeRadius = parseInt(document.getElementById('set-office-radius').value, 10);
  const officeCheckinTime = normalizeTimeForSave(document.getElementById('set-office-checkin-time').value);
  const officeCheckoutTime = normalizeTimeForSave(document.getElementById('set-office-checkout-time').value);

  const payload = {
    officeName,
    enableGps,
    officeLat,
    officeLng,
    officeRadius,
    officeCheckinTime,
    officeCheckoutTime,
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
      window.showToast('Semua pengaturan berhasil disimpan!', 'success');
      document.getElementById('set-office-name').value = officeName;
      document.getElementById('set-enable-gps').checked = enableGps;
      document.getElementById('set-office-lat').value = Number(officeLat).toFixed(6);
      document.getElementById('set-office-lng').value = Number(officeLng).toFixed(6);
      document.getElementById('set-office-radius').value = officeRadius;
      document.getElementById('set-office-checkin-time').value = officeCheckinTime.slice(0, 5);
      document.getElementById('set-office-checkout-time').value = officeCheckoutTime.slice(0, 5);

      const scheduleEl = document.getElementById('info-work-schedule');
      if (scheduleEl) {
        scheduleEl.textContent = `${officeCheckinTime.slice(0, 5)} - ${officeCheckoutTime.slice(0, 5)}`;
      }

      if (window.fetchSettings) window.fetchSettings();
      sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY, 'tab-settings');
      window.location.reload();
    } else {
      window.showToast('Gagal menyimpan pengaturan', 'error');
    }
  } catch (error) {
    console.error(error);
    window.showToast('Kesalahan jaringan saat menyimpan pengaturan', 'error');
  }
}

function normalizeTimeForInput(timeValue) {
  if (!timeValue) return '';
  return timeValue.length >= 5 ? timeValue.slice(0, 5) : timeValue;
}

function normalizeTimeForSave(timeValue) {
  if (!timeValue) return '00:00:00';
  return timeValue.length === 5 ? `${timeValue}:00` : timeValue;
}

function setupLocationPicker() {
  const modal = document.getElementById('modal-location-picker');
  const closeBtn = document.getElementById('close-location-picker');
  const useCurrentBtn = document.getElementById('btn-use-current-location');
  const applyBtn = document.getElementById('btn-apply-picked-location');
  const latInput = document.getElementById('set-office-lat');
  const lngInput = document.getElementById('set-office-lng');

  if (!modal || !closeBtn || !useCurrentBtn || !applyBtn || !latInput || !lngInput) return;

  closeBtn.addEventListener('click', closeLocationPicker);
  useCurrentBtn.addEventListener('click', centerPickerOnCurrentLocation);
  applyBtn.addEventListener('click', applyPickedLocation);
  latInput.addEventListener('input', syncPickerFromInputs);
  lngInput.addEventListener('input', syncPickerFromInputs);

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeLocationPicker();
    }
  });
}

function openLocationPicker() {
  const modal = document.getElementById('modal-location-picker');
  if (!modal) return;

  modal.classList.add('active');
  const latInput = document.getElementById('set-office-lat');
  const lngInput = document.getElementById('set-office-lng');
  const lat = parseFloat(latInput.value);
  const lng = parseFloat(lngInput.value);

  if (Number.isNaN(lat) || Number.isNaN(lng)) {
    setPickedLocation(-6.200000, 106.816666, true);
  } else {
    setPickedLocation(lat, lng, false);
  }

  updateLocationPickerStatus('Klik titik di peta untuk memilih lokasi kantor.');
  requestAnimationFrame(() => {
    if (ensureLocationPickerMap()) {
      google.maps.event.trigger(locationPickerMap, 'resize');
      if (locationPickerSelection.lat !== null && locationPickerSelection.lng !== null) {
        locationPickerMap.setCenter({
          lat: locationPickerSelection.lat,
          lng: locationPickerSelection.lng,
        });
      }
    }
  });
}

function openGoogleMapsDirectly() {
  const latInput = document.getElementById('set-office-lat');
  const lngInput = document.getElementById('set-office-lng');
  const lat = parseFloat(latInput?.value);
  const lng = parseFloat(lngInput?.value);

  const url = buildGoogleMapsSearchUrl(
    Number.isNaN(lat) ? null : lat,
    Number.isNaN(lng) ? null : lng
  );

  if (!url) {
    window.showToast('Isi koordinat latitude dan longitude dulu supaya Google Maps membuka titik yang tepat.', 'error');
    return;
  }

  window.open(url, '_blank', 'noopener,noreferrer');
  window.showToast('Google Maps dibuka di tab baru pada titik koordinat yang dipilih.', 'success');
}

function closeLocationPicker() {
  const modal = document.getElementById('modal-location-picker');
  if (modal) modal.classList.remove('active');
}

function syncPickerFromInputs() {
  const lat = parseFloat(document.getElementById('set-office-lat').value);
  const lng = parseFloat(document.getElementById('set-office-lng').value);

  if (Number.isNaN(lat) || Number.isNaN(lng)) return;

  setPickedLocation(lat, lng, false);
}

function updatePickedLocationPreview(lat, lng) {
  const pickedLat = document.getElementById('picked-lat');
  const pickedLng = document.getElementById('picked-lng');
  const openMapsBtn = document.getElementById('btn-open-google-maps');

  const formattedLat = Number(lat).toFixed(6);
  const formattedLng = Number(lng).toFixed(6);

  if (pickedLat) pickedLat.textContent = formattedLat;
  if (pickedLng) pickedLng.textContent = formattedLng;
  if (openMapsBtn) openMapsBtn.href = buildGoogleMapsBrowseUrl(formattedLat, formattedLng);
}

function setPickedLocation(lat, lng, syncInputs = true) {
  locationPickerSelection.lat = Number(lat);
  locationPickerSelection.lng = Number(lng);

  updatePickedLocationPreview(lat, lng);

  if (syncInputs) {
    const latInput = document.getElementById('set-office-lat');
    const lngInput = document.getElementById('set-office-lng');
    if (latInput) latInput.value = Number(lat).toFixed(6);
    if (lngInput) lngInput.value = Number(lng).toFixed(6);
  }

  if (locationPickerMap && locationPickerMarker) {
    const position = {
      lat: Number(lat),
      lng: Number(lng),
    };
    locationPickerMarker.setPosition(position);
    locationPickerMap.setCenter(position);
  }

  updateLocationPickerStatus(`Titik dipilih: ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`);
}

function centerPickerOnCurrentLocation() {
  if (!navigator.geolocation) {
    window.showToast('Geolocation tidak didukung oleh browser Anda', 'error');
    return;
  }

  window.showToast('Mengambil lokasi GPS Anda...', 'info');

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      setPickedLocation(lat, lng, true);
      ensureLocationPickerMap();
      window.showToast('Koordinat GPS Anda sudah dipakai.', 'success');
      updateLocationPickerStatus('Titik GPS kamu sudah diisi ke form. Klik Simpan Titik Ini untuk menyimpannya.');
    },
    (error) => {
      console.error(error);
      window.showToast(`Gagal mengambil koordinat: ${error.message}`, 'error');
      updateLocationPickerStatus(`Gagal mengambil GPS: ${error.message}`);
    },
    { enableHighAccuracy: true }
  );
}

function applyPickedLocation() {
  if (locationPickerSelection.lat === null || locationPickerSelection.lng === null) {
    window.showToast('Pilih titik lokasi di peta terlebih dahulu.', 'error');
    return;
  }

  const latInput = document.getElementById('set-office-lat');
  const lngInput = document.getElementById('set-office-lng');
  if (latInput) latInput.value = Number(locationPickerSelection.lat).toFixed(6);
  if (lngInput) lngInput.value = Number(locationPickerSelection.lng).toFixed(6);
  closeLocationPicker();
  window.showToast('Koordinat kantor sudah diisi dari peta.', 'success');
}
