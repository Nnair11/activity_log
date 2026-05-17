<?php
/**
 * homepage.php - Dashboard / Homepage
 * Author: Shan Alystair Punzalan | Section: UCOS 3-2
 *
 * Shows summary counts: departments, employees, recent logs.
 * Navigation to all modules.
 */

require_once 'includes/auth.php';
require_once 'includes/logger.php';
requireLogin();

$db = getDB();

// Summary counts
$deptCount = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$empCount  = $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$logCount  = $db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent 5 logs for dashboard preview
$recentLogs = $db->query("SELECT * FROM activity_logs ORDER BY performed_at DESC LIMIT 5")->fetchAll();

// Log the READ action on the homepage
logActivity('READ', 'Department', 0, 'Dashboard', currentUsername() . ' visited the dashboard.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="page-header">
    <div>
      <h2>Dashboard</h2>
      <p class="subtitle">Welcome back, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> &mdash; logged in as <code><?= htmlspecialchars(currentUsername()) ?></code></p>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-num"><?= $deptCount ?></div>
      <div class="stat-label">Departments</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $empCount ?></div>
      <div class="stat-label">Employees</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $logCount ?></div>
      <div class="stat-label">Activity Logs</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $userCount ?></div>
      <div class="stat-label">Users</div>
    </div>
  </div>

  <!-- RECENT ACTIVITY -->
  <div class="section-card">
    <div class="section-header">
      <h3>Recent Activity</h3>
      <a href="activity_logs.php" class="btn-link">View All →</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Record</th>
          <th>When</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentLogs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><code><?= htmlspecialchars($log['username']) ?></code></td>
          <td><span class="badge badge-<?= strtolower($log['action']) ?>"><?= $log['action'] ?></span></td>
          <td><?= $log['entity_type'] ?></td>
          <td><?= htmlspecialchars($log['entity_name']) ?></td>
          <td class="mono"><?= $log['performed_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentLogs): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--muted)">No activity yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- QUICK NAV -->
  <div class="quick-nav">
    <a class="qnav-card" href="departments.php">
      <span>Manage Departments</span>
    </a>
    <a class="qnav-card" href="employees.php">
      <span>Manage Employees</span>
    </a>
    <a class="qnav-card" href="search.php">
      <span>Search Records</span>
    </a>
    <a class="qnav-card" href="activity_logs.php">
      <span>Activity Logs</span>
    </a>
  </div>
</main>
</body>
</html>
