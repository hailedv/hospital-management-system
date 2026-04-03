<?php
/**
 * Shared HTML head + top navbar
 * Usage: include '../includes/header.php';
 * Required vars before include:
 *   $page_title  - string
 *   $role_color  - CSS color string (e.g. '#28a745')
 *   $role_class  - btn class suffix (e.g. 'doctor')
 *   $back_path   - path prefix for assets (e.g. '../')
 */
$back_path = $back_path ?? '../';
$role_color = $role_color ?? '#2c5aa0';
$role_class = $role_class ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Hospital Management System') ?></title>
    <link rel="stylesheet" href="<?= $back_path ?>assets/css/style.css">
    <style>
        body { background: #f5f6fa; }
        .topbar {
            background: white;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border-radius: 0 0 10px 10px;
        }
        .topbar-brand { font-size: 1.3em; font-weight: 700; color: <?= $role_color ?>; }
        .topbar-user { color: #555; font-size: 0.9em; }
        .topbar-actions { display: flex; gap: 10px; align-items: center; }
        .topbar-actions a {
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 500;
            background: <?= $role_color ?>;
            color: white;
            transition: opacity 0.2s;
        }
        .topbar-actions a:hover { opacity: 0.85; }
        .topbar-actions a.logout { background: #dc3545; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-brand">🏥 Hospital Management System</div>
    <div class="topbar-user">
        <?php if (isset($_SESSION['full_name'])): ?>
            👤 <?= htmlspecialchars($_SESSION['full_name']) ?>
            &nbsp;|&nbsp;
            <span style="text-transform:capitalize"><?= htmlspecialchars($_SESSION['user_type'] ?? '') ?></span>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="<?= $back_path ?>logout.php" class="logout">🚪 Logout</a>
    </div>
</div>
<div class="container">
