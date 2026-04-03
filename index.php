<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        /* ── Navbar ── */
        .navbar {
            background: white;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand { font-size: 1.4em; font-weight: 700; color: #2c5aa0; }
        .navbar-brand span { color: #28a745; }
        .navbar-actions { display: flex; gap: 12px; }
        .btn {
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-primary { background: #2c5aa0; color: white; }
        .btn-primary:hover { background: #1e3d6f; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-outline { background: transparent; color: #2c5aa0; border: 2px solid #2c5aa0; }
        .btn-outline:hover { background: #2c5aa0; color: white; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 60%, #764ba2 100%);
            color: white;
            padding: 80px 40px;
            text-align: center;
        }
        .hero-icon { font-size: 5em; margin-bottom: 20px; }
        .hero h1 { font-size: 3em; margin-bottom: 16px; font-weight: 700; }
        .hero p { font-size: 1.2em; opacity: 0.9; max-width: 600px; margin: 0 auto 36px; line-height: 1.6; }
        .hero-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .btn-hero-primary { background: white; color: #2c5aa0; padding: 14px 32px; font-size: 1.05em; border-radius: 30px; }
        .btn-hero-primary:hover { background: #f0f4ff; }
        .btn-hero-outline { background: transparent; color: white; border: 2px solid white; padding: 14px 32px; font-size: 1.05em; border-radius: 30px; }
        .btn-hero-outline:hover { background: rgba(255,255,255,0.15); }

        /* ── Stats bar ── */
        .stats-bar {
            background: white;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .stat-item {
            padding: 28px 20px;
            text-align: center;
            border-right: 1px solid #eee;
        }
        .stat-item:last-child { border-right: none; }
        .stat-num { font-size: 2.2em; font-weight: 700; color: #2c5aa0; }
        .stat-lbl { color: #666; font-size: 0.9em; margin-top: 4px; }

        /* ── Section ── */
        .section { padding: 70px 40px; max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 50px; }
        .section-title h2 { font-size: 2em; color: #2c5aa0; margin-bottom: 10px; }
        .section-title p { color: #666; font-size: 1.05em; }

        /* ── Features grid ── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            border-top: 4px solid transparent;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .feature-card.blue  { border-top-color: #2c5aa0; }
        .feature-card.green { border-top-color: #28a745; }
        .feature-card.purple{ border-top-color: #9c27b0; }
        .feature-card.orange{ border-top-color: #ff9800; }
        .feature-card.pink  { border-top-color: #e91e63; }
        .feature-card.teal  { border-top-color: #009688; }
        .feature-card.red   { border-top-color: #dc3545; }
        .feature-card.slate { border-top-color: #607d8b; }
        .feature-icon { font-size: 2.5em; margin-bottom: 14px; }
        .feature-card h3 { font-size: 1.15em; margin-bottom: 10px; color: #222; }
        .feature-card p { color: #666; font-size: 0.92em; line-height: 1.6; }
        .feature-tags { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 6px; }
        .tag {
            background: #f0f4ff;
            color: #2c5aa0;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78em;
            font-weight: 500;
        }

        /* ── Roles section ── */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }
        .role-card {
            background: white;
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            text-decoration: none;
            color: #333;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .role-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .role-icon { font-size: 2.5em; margin-bottom: 10px; }
        .role-card h4 { font-size: 0.95em; font-weight: 600; margin-bottom: 4px; }
        .role-card p { font-size: 0.8em; color: #888; }

        /* ── CTA ── */
        .cta {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 70px 40px;
        }
        .cta h2 { font-size: 2em; margin-bottom: 14px; }
        .cta p { font-size: 1.1em; opacity: 0.9; margin-bottom: 30px; }
        .btn-cta { background: white; color: #28a745; padding: 14px 36px; font-size: 1.05em; border-radius: 30px; font-weight: 700; }
        .btn-cta:hover { background: #f0fff4; }

        /* ── Footer ── */
        footer {
            background: #1e3d6f;
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 24px;
            font-size: 0.9em;
        }
        footer a { color: rgba(255,255,255,0.9); text-decoration: none; margin: 0 10px; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2em; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .section { padding: 50px 20px; }
            .navbar { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">🏥 Hospital <span>MS</span></div>
    <div class="navbar-actions">
        <a href="login.php" class="btn btn-outline">Staff Login</a>
        <a href="patient/register.php" class="btn btn-success">Patient Register</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-icon">🏥</div>
    <h1>Hospital Management System</h1>
    <p>A complete digital solution for managing patients, staff, appointments, prescriptions, billing, and more — all in one place.</p>
    <div class="hero-actions">
        <a href="login.php" class="btn btn-hero-primary">🔑 Staff Login</a>
        <a href="patient/register.php" class="btn btn-hero-outline">👤 Patient Register</a>
    </div>
</section>

<!-- Stats bar -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-num">7+</div>
        <div class="stat-lbl">User Roles</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">20+</div>
        <div class="stat-lbl">Modules</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">100%</div>
        <div class="stat-lbl">Web Based</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">24/7</div>
        <div class="stat-lbl">Access</div>
    </div>
</div>

<!-- Main Features -->
<div class="section">
    <div class="section-title">
        <h2>Core Features</h2>
        <p>Everything a modern hospital needs to operate efficiently</p>
    </div>
    <div class="features-grid">

        <div class="feature-card blue">
            <div class="feature-icon">👨‍⚕️</div>
            <h3>Doctor Portal</h3>
            <p>Manage appointments, write prescriptions, view patient history, send referrals, and access medical records.</p>
            <div class="feature-tags">
                <span class="tag">Appointments</span>
                <span class="tag">Prescriptions</span>
                <span class="tag">Referrals</span>
                <span class="tag">Medical Records</span>
            </div>
        </div>

        <div class="feature-card pink">
            <div class="feature-icon">👩‍⚕️</div>
            <h3>Nurse Dashboard</h3>
            <p>Record patient vitals, administer medications, manage assigned patients, and add nursing notes.</p>
            <div class="feature-tags">
                <span class="tag">Vitals</span>
                <span class="tag">Medications</span>
                <span class="tag">Patient Notes</span>
            </div>
        </div>

        <div class="feature-card purple">
            <div class="feature-icon">💊</div>
            <h3>Pharmacy Management</h3>
            <p>Dispense medicines from prescriptions, manage stock levels, track expiry dates, and generate inventory reports.</p>
            <div class="feature-tags">
                <span class="tag">Dispensing</span>
                <span class="tag">Stock Control</span>
                <span class="tag">Expiry Alerts</span>
            </div>
        </div>

        <div class="feature-card slate">
            <div class="feature-icon">💰</div>
            <h3>Billing & Accounting</h3>
            <p>Generate patient bills, process payments, manage insurance claims, and produce financial reports.</p>
            <div class="feature-tags">
                <span class="tag">Billing</span>
                <span class="tag">Payments</span>
                <span class="tag">Insurance</span>
                <span class="tag">Reports</span>
            </div>
        </div>

        <div class="feature-card orange">
            <div class="feature-icon">🧑‍💼</div>
            <h3>Receptionist System</h3>
            <p>Register new patients, book appointments with available doctors, and search patient records quickly.</p>
            <div class="feature-tags">
                <span class="tag">Patient Registration</span>
                <span class="tag">Appointments</span>
                <span class="tag">Search</span>
            </div>
        </div>

        <div class="feature-card red">
            <div class="feature-icon">🔑</div>
            <h3>Admin Control</h3>
            <p>Add and manage all staff members, view system-wide reports, and control user access and permissions.</p>
            <div class="feature-tags">
                <span class="tag">Staff Management</span>
                <span class="tag">User Control</span>
                <span class="tag">Reports</span>
            </div>
        </div>

        <div class="feature-card green">
            <div class="feature-icon">👤</div>
            <h3>Patient Portal</h3>
            <p>Patients can view their appointments, bills, prescriptions, and update their personal profile.</p>
            <div class="feature-tags">
                <span class="tag">Appointments</span>
                <span class="tag">Bills</span>
                <span class="tag">Profile</span>
            </div>
        </div>

        <div class="feature-card teal">
            <div class="feature-icon">🔬</div>
            <h3>Laboratory Services</h3>
            <p>Process lab test requests, record results, and make them available to doctors and patients.</p>
            <div class="feature-tags">
                <span class="tag">Test Requests</span>
                <span class="tag">Results</span>
                <span class="tag">Reports</span>
            </div>
        </div>

    </div>
</div>

<!-- Roles -->
<div style="background:white;padding:70px 40px;">
    <div style="max-width:1200px;margin:0 auto;">
        <div class="section-title">
            <h2>Who Uses This System?</h2>
            <p>Each role has a dedicated dashboard tailored to their workflow</p>
        </div>
        <div class="roles-grid">
            <a href="login.php" class="role-card">
                <div class="role-icon">🔑</div>
                <h4>Admin</h4>
                <p>Full system control</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">👨‍⚕️</div>
                <h4>Doctor</h4>
                <p>Patient care</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">👩‍⚕️</div>
                <h4>Nurse</h4>
                <p>Vitals & care</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">🧑‍💼</div>
                <h4>Receptionist</h4>
                <p>Registration & booking</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">💊</div>
                <h4>Pharmacist</h4>
                <p>Medicine dispensing</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">💰</div>
                <h4>Accountant</h4>
                <p>Billing & finance</p>
            </a>
            <a href="login.php" class="role-card">
                <div class="role-icon">🔬</div>
                <h4>Lab Tech</h4>
                <p>Test processing</p>
            </a>
            <a href="patient/register.php" class="role-card">
                <div class="role-icon">👤</div>
                <h4>Patient</h4>
                <p>Self-service portal</p>
            </a>
        </div>
    </div>
</div>

<!-- CTA -->
<section class="cta">
    <h2>Ready to Get Started?</h2>
    <p>Log in with your staff credentials or register as a patient to access the system.</p>
    <a href="login.php" class="btn btn-cta">Login Now →</a>
</section>

<!-- Footer -->
<footer>
    <p>
        &copy; <?= date('Y') ?> Hospital Management System &nbsp;|&nbsp;
        <a href="login.php">Staff Login</a>
        <a href="patient/register.php">Patient Register</a>
    </p>
</footer>

</body>
</html>
