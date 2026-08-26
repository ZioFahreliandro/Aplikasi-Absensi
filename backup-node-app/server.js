const express = require('express');
const fs = require('fs');
const path = require('path');
const db = require('./db');

const app = express();
const PORT = process.env.PORT || 3000;

// Enable JSON parser with large limit (for base64 selfies)
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Serve static files from the 'public' directory
app.use(express.static(path.join(__dirname, 'public')));

// Ensure upload directory exists
const uploadDir = path.join(__dirname, 'public', 'uploads', 'selfies');
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir, { recursive: true });
}

// Haversine formula to calculate distance between two coordinates in meters
function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371e3; // Earth's radius in meters
  const radLat1 = (lat1 * Math.PI) / 180;
  const radLat2 = (lat2 * Math.PI) / 180;
  const diffLat = ((lat2 - lat1) * Math.PI) / 180;
  const diffLon = ((lon2 - lon1) * Math.PI) / 180;

  const a = Math.sin(diffLat / 2) * Math.sin(diffLat / 2) +
            Math.cos(radLat1) * Math.cos(radLat2) *
            Math.sin(diffLon / 2) * Math.sin(diffLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // distance in meters
}

// Helper to normalize client IP address
function getClientIp(req) {
  let ip = req.headers['x-forwarded-for'] || req.socket.remoteAddress || '';
  if (ip.startsWith('::ffff:')) {
    ip = ip.substring(7);
  }
  if (ip === '::1') {
    ip = '127.0.0.1';
  }
  return ip;
}

// ----------------------------------------------------
// API ROUTES
// ----------------------------------------------------

// 1. Get Client IP Address (useful for debugging and configuring the office IP)
app.get('/api/my-ip', (req, res) => {
  res.json({ ip: getClientIp(req) });
});

// 2. Employees API
app.get('/api/employees', (req, res) => {
  const employees = db.getEmployees();
  res.json(employees);
});

app.post('/api/employees', (req, res) => {
  const { name, nip, pin } = req.body;
  if (!name) {
    return res.status(400).json({ error: "Nama karyawan wajib diisi" });
  }
  const newEmp = db.addEmployee({ name, nip, pin });
  res.status(201).json(newEmp);
});

app.put('/api/employees/:id', (req, res) => {
  const { id } = req.params;
  const { name, nip, pin } = req.body;
  const updated = db.updateEmployee(id, { name, nip, pin });
  if (!updated) {
    return res.status(404).json({ error: "Karyawan tidak ditemukan" });
  }
  res.json(updated);
});

app.delete('/api/employees/:id', (req, res) => {
  const { id } = req.params;
  const success = db.deleteEmployee(id);
  if (!success) {
    return res.status(404).json({ error: "Karyawan tidak ditemukan" });
  }
  res.json({ message: "Karyawan berhasil dihapus" });
});

// 3. Settings API
app.get('/api/settings', (req, res) => {
  const settings = db.getSettings();
  res.json(settings);
});

app.post('/api/settings', (req, res) => {
  const updated = db.updateSettings(req.body);
  res.json(updated);
});

// 4. Attendance API
app.post('/api/attendance', (req, res) => {
  const { employeeId, type, selfie, coords, pin } = req.body;

  if (!employeeId || !type || !selfie || !pin) {
    return res.status(400).json({ error: "Data absensi tidak lengkap (employeeId, type, selfie, dan PIN wajib ada)" });
  }

  const employees = db.getEmployees();
  const employee = employees.find(e => e.id === employeeId);
  if (!employee) {
    return res.status(404).json({ error: "Karyawan tidak ditemukan" });
  }

  // Validation: Security PIN Match
  if (employee.pin !== pin) {
    return res.status(401).json({ error: "PIN yang dimasukkan salah!" });
  }

  const settings = db.getSettings();
  const clientIp = getClientIp(req);

  // Validation 1: IP Address restriction
  if (settings.enableIp) {
    if (settings.officeIp && clientIp !== settings.officeIp && settings.officeIp !== '127.0.0.1') {
      return res.status(403).json({
        error: `Akses ditolak. Absensi hanya dapat dilakukan dari jaringan kantor. IP Anda: ${clientIp}, IP terdaftar: ${settings.officeIp}`
      });
    }
  }

  // Validation 2: GPS Geolocation restriction
  let distance = null;
  if (settings.enableGps) {
    if (!coords || coords.lat === null || coords.lng === null) {
      return res.status(400).json({ error: "Izin lokasi (GPS) diperlukan untuk memvalidasi posisi Anda." });
    }

    distance = calculateDistance(
      coords.lat,
      coords.lng,
      settings.officeLat,
      settings.officeLng
    );

    if (distance > settings.officeRadius) {
      return res.status(403).json({
        error: `Anda berada di luar radius kantor. Jarak Anda: ${Math.round(distance)}m, Maksimal radius: ${settings.officeRadius}m`
      });
    }
  } else if (coords && coords.lat !== null && coords.lng !== null) {
    // Still calculate distance if coords provided but restriction disabled (for info)
    distance = calculateDistance(
      coords.lat,
      coords.lng,
      settings.officeLat,
      settings.officeLng
    );
  }

  // Process selfie image (Base64 -> File)
  let selfieUrl = '';
  try {
    const matches = selfie.match(/^data:([A-Za-z-+\/]+);base64,(.+)$/);
    if (!matches || matches.length !== 3) {
      return res.status(400).json({ error: "Format gambar tidak valid" });
    }

    const imageBuffer = Buffer.from(matches[2], 'base64');
    const filename = `selfie-${employeeId}-${Date.now()}.jpg`;
    const filepath = path.join(uploadDir, filename);

    fs.writeFileSync(filepath, imageBuffer);
    selfieUrl = `uploads/selfies/${filename}`;
  } catch (error) {
    console.error("Gagal menyimpan selfie:", error);
    return res.status(500).json({ error: "Gagal memproses gambar selfie di server" });
  }

  // Format local current date and time
  const now = new Date();
  // Adjust to local timezone (+07:00 as requested by the server's context but let's make it standard local time)
  const offset = now.getTimezoneOffset() * 60000;
  const localTime = new Date(now.getTime() - offset);

  const dateStr = localTime.toISOString().split('T')[0]; // YYYY-MM-DD
  const timeStr = now.toTimeString().split(' ')[0]; // HH:MM:SS

  // Save attendance log
  const record = db.addAttendance({
    employeeId: employee.id,
    employeeName: employee.name,
    date: dateStr,
    type, // 'masuk' or 'pulang'
    time: timeStr,
    selfieUrl,
    coords: coords || { lat: null, lng: null },
    distance: distance !== null ? Math.round(distance) : null,
    ip: clientIp,
    status: "Success"
  });

  res.status(201).json({
    message: `Absen ${type === 'masuk' ? 'masuk' : 'pulang'} berhasil absen!`,
    record
  });
});

// 5. Get Attendance Logs & Monthly Recap
app.get('/api/attendance', (req, res) => {
  const { month } = req.query; // format: YYYY-MM
  let logs = db.getAttendance();

  if (month) {
    logs = logs.filter(log => log.date.startsWith(month));
  }

  // Sort logs by date desc, then time desc
  logs.sort((a, b) => {
    const dateCompare = b.date.localeCompare(a.date);
    if (dateCompare !== 0) return dateCompare;
    return b.time.localeCompare(a.time);
  });

  res.json(logs);
});

// Start Server
app.listen(PORT, () => {
  console.log(`Server Absensi berjalan di http://localhost:${PORT}`);
});
