const db = require('./db');

console.log("=== Memulai Pengujian & Verifikasi Database ===");

// 1. Test Fetching Default Employees
const initialEmployees = db.getEmployees();
console.log(`[PASS] Berhasil membaca data karyawan. Ditemukan ${initialEmployees.length} karyawan default.`);

// 2. Test Adding a New Employee
const newEmpName = "Test Karyawan";
const newEmpNip = "20269999";
const newEmpPin = "4321";

const addedEmp = db.addEmployee({
  name: newEmpName,
  nip: newEmpNip,
  pin: newEmpPin
});

if (addedEmp && addedEmp.id && addedEmp.name === newEmpName) {
  console.log(`[PASS] Berhasil menambahkan karyawan baru: ${addedEmp.name} (ID: ${addedEmp.id})`);
} else {
  console.error(`[FAIL] Gagal menambahkan karyawan baru.`);
  process.exit(1);
}

// Verify employee is in list
const employeesAfterAdd = db.getEmployees();
const found = employeesAfterAdd.find(e => e.id === addedEmp.id);
if (found) {
  console.log(`[PASS] Karyawan baru terverifikasi ada di dalam database.`);
} else {
  console.error(`[FAIL] Karyawan baru tidak terdaftar dalam pencarian.`);
  process.exit(1);
}

// 3. Test Updating Employee
const updatedName = "Test Karyawan Updated";
const updated = db.updateEmployee(addedEmp.id, { name: updatedName });
if (updated && updated.name === updatedName) {
  console.log(`[PASS] Berhasil memperbarui data karyawan.`);
} else {
  console.error(`[FAIL] Gagal memperbarui data karyawan.`);
  process.exit(1);
}

// 4. Test Settings API
const settings = db.getSettings();
console.log(`[PASS] Berhasil mengambil pengaturan kantor default: "${settings.officeName}"`);

const newSettingsName = "Kantor Cabang Baru";
const updatedSettings = db.updateSettings({ officeName: newSettingsName });
if (updatedSettings && updatedSettings.officeName === newSettingsName) {
  console.log(`[PASS] Berhasil memperbarui pengaturan kantor.`);
} else {
  console.error(`[FAIL] Gagal memperbarui pengaturan kantor.`);
  process.exit(1);
}

// Reset settings name back to default for clean state
db.updateSettings({ officeName: "Kantor Pusat" });

// 5. Test Adding Attendance Log
const attRecord = db.addAttendance({
  employeeId: addedEmp.id,
  employeeName: addedEmp.name,
  date: "2026-08-08",
  type: "masuk",
  time: "08:00:00",
  selfieUrl: "uploads/selfies/test.jpg",
  coords: { lat: -6.200000, lng: 106.816666 },
  distance: 12,
  ip: "127.0.0.1",
  status: "Success"
});

if (attRecord && attRecord.id && attRecord.employeeName === addedEmp.name) {
  console.log(`[PASS] Berhasil menambahkan log absensi baru.`);
} else {
  console.error(`[FAIL] Gagal mencatat absensi.`);
  process.exit(1);
}

// 6. Test Fetching Attendance Logs
const logs = db.getAttendance();
const attFound = logs.find(log => log.id === attRecord.id);
if (attFound) {
  console.log(`[PASS] Log absensi baru terverifikasi ada di database.`);
} else {
  console.error(`[FAIL] Log absensi tidak ditemukan.`);
  process.exit(1);
}

// 7. Cleanup Test Employee
const deleteSuccess = db.deleteEmployee(addedEmp.id);
if (deleteSuccess) {
  console.log(`[PASS] Berhasil menghapus karyawan pengujian untuk membersihkan database.`);
} else {
  console.error(`[FAIL] Gagal menghapus karyawan pengujian.`);
  process.exit(1);
}

console.log("\n=== Seluruh Pengujian Database Berhasil! (100% OK) ===");
process.exit(0);
