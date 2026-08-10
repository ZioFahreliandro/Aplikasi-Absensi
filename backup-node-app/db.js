const fs = require('fs');
const path = require('path');

const DB_PATH = path.join(__dirname, 'data', 'db.json');

// Default Database Structure
const defaultDb = {
  employees: [
    { id: "emp-1", name: "Budi Santoso", nip: "19920801", pin: "1234" },
    { id: "emp-2", name: "Siti Rahma", nip: "19950412", pin: "5678" },
    { id: "emp-3", name: "Joko Widodo", nip: "19901130", pin: "0000" }
  ],
  attendance: [],
  settings: {
    officeName: "Kantor Pusat",
    officeLat: -6.200000,
    officeLng: 106.816666,
    officeRadius: 100, // in meters
    officeIp: "127.0.0.1",
    enableGps: false, // disabled by default for ease of testing
    enableIp: false  // disabled by default for ease of testing
  }
};

// Initialize DB if not exists
function initDb() {
  const dir = path.dirname(DB_PATH);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  
  if (!fs.existsSync(DB_PATH)) {
    fs.writeFileSync(DB_PATH, JSON.stringify(defaultDb, null, 2), 'utf-8');
  }
}

// Read database
function readDb() {
  initDb();
  try {
    const data = fs.readFileSync(DB_PATH, 'utf-8');
    return JSON.parse(data);
  } catch (error) {
    console.error("Failed to read database, resetting to default:", error);
    return defaultDb;
  }
}

// Write database
function writeDb(data) {
  initDb();
  try {
    fs.writeFileSync(DB_PATH, JSON.stringify(data, null, 2), 'utf-8');
    return true;
  } catch (error) {
    console.error("Failed to write database:", error);
    return false;
  }
}

// Helper to generate unique ID
function generateId(prefix = 'id') {
  return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).substr(2, 4)}`;
}

const db = {
  // Employees CRUD
  getEmployees: () => {
    return readDb().employees;
  },
  
  addEmployee: (employee) => {
    const data = readDb();
    const newEmp = {
      id: generateId('emp'),
      name: employee.name,
      nip: employee.nip || '',
      pin: employee.pin || '1234'
    };
    data.employees.push(newEmp);
    writeDb(data);
    return newEmp;
  },
  
  updateEmployee: (id, updatedData) => {
    const data = readDb();
    const idx = data.employees.findIndex(e => e.id === id);
    if (idx !== -1) {
      data.employees[idx] = { ...data.employees[idx], ...updatedData, id }; // keep same id
      writeDb(data);
      return data.employees[idx];
    }
    return null;
  },
  
  deleteEmployee: (id) => {
    const data = readDb();
    const initialLength = data.employees.length;
    data.employees = data.employees.filter(e => e.id !== id);
    // Also clean up or flag attendance records? Typically we keep attendance records, but reference them.
    writeDb(data);
    return data.employees.length < initialLength;
  },
  
  // Attendance Transactions
  getAttendance: () => {
    return readDb().attendance;
  },
  
  addAttendance: (record) => {
    const data = readDb();
    const newRecord = {
      id: generateId('att'),
      employeeId: record.employeeId,
      employeeName: record.employeeName, // cached for easier reporting
      date: record.date, // YYYY-MM-DD
      type: record.type, // 'masuk' or 'pulang'
      time: record.time, // HH:MM:SS
      selfieUrl: record.selfieUrl,
      coords: record.coords || { lat: null, lng: null },
      distance: record.distance || null,
      ip: record.ip || '',
      status: record.status || 'Success'
    };
    data.attendance.push(newRecord);
    writeDb(data);
    return newRecord;
  },
  
  // Settings CRUD
  getSettings: () => {
    return readDb().settings;
  },
  
  updateSettings: (newSettings) => {
    const data = readDb();
    data.settings = { ...data.settings, ...newSettings };
    writeDb(data);
    return data.settings;
  }
};

module.exports = db;
