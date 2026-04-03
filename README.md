# 🏥 Hospital Management System

A full-featured web-based Hospital Management System built with **PHP**, **MySQL**, and vanilla **JavaScript**. Designed to manage all hospital operations including patients, staff, appointments, prescriptions, billing, pharmacy, and laboratory services.

---

## 🚀 Features

| Module | Description |
|---|---|
| 🔑 Admin | Manage staff, users, and system-wide reports |
| 👨‍⚕️ Doctor | Appointments, prescriptions, medical records, referrals |
| 👩‍⚕️ Nurse | Patient vitals, medications, nursing notes |
| 🧑‍💼 Receptionist | Patient registration, appointment booking |
| 💊 Pharmacist | Dispense medicines, stock control, expiry alerts |
| 💰 Accountant | Billing, payments, insurance claims, financial reports |
| 🔬 Lab Technician | Process tests, record and share results |
| 👤 Patient | View appointments, bills, prescriptions, profile |

---

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: Apache (XAMPP recommended for local)

---

## ⚙️ Installation

### Requirements
- XAMPP (or any Apache + PHP + MySQL stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/your-username/hospital-management-system.git
```

**2. Move to your web server directory**
```
C:\xampp\htdocs\hospital\
```

**3. Set up the database**
- Open `http://localhost/phpmyadmin`
- Create a database named `hospital_management_system`
- Import `database/hospital.sql`

**4. Configure database connection**
```bash
cp config/db.example.php config/db.php
```
Then edit `config/db.php` with your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital_management_system');
```

**5. Open in browser**
```
http://localhost/hospital/
```

---

## 🔐 Default Login Credentials

> All accounts use password: `123456`

| Username | Role |
|---|---|
| admin | Administrator |
| doctor1 | Doctor |
| nurse1 | Nurse |
| receptionist1 | Receptionist |
| pharmacist1 | Pharmacist |
| accountant1 | Accountant |
| labtech1 | Lab Technician |

---

## 📁 Project Structure

```
hospital/
├── admin/              # Admin module
├── doctor/             # Doctor module
├── nurse/              # Nurse module
├── receptionist/       # Receptionist module
├── pharmacist/         # Pharmacist module
├── accountant/         # Accountant module
├── lab_technician/     # Lab technician module
├── patient/            # Patient portal
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/
│   ├── db.php          # (not committed — copy from db.example.php)
│   └── db.example.php
├── database/
│   └── hospital.sql    # Full database schema + seed data
├── includes/
│   ├── header.php
│   └── footer.php
├── index.php
├── login.php
├── logout.php
└── README.md
```

---

## 📸 Screenshots

> Add screenshots of your dashboards here.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

## 👤 Author

Built with ❤️ for healthcare management.
