<?php

require_once 'includes/auth.php';
require_once 'includes/logger.php';
requireLogin();

// Read filter parameters from GET
$filters = [
    'action'      => $_GET['action']      ?? '',
    'entity_type' => $_GET['entity_type'] ?? '',
    'username'    => $_GET['username']    ?? '',
    'date_from'   => $_GET['date_from']   ?? '',
    'date_to'     => $_GET['date_to']     ?? '',
];

$logs = getActivityLogs($filters);

// Count by action type for summary badges
$db = getDB();
$counts = $db->query("
    SELECT action, COUNT(*) as cnt FROM activity_logs GROUP BY action
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<style>
  .log-stats { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
  .log-stat-pill {
    padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
  }
  .details-cell { max-width: 350px; font-size: 12px; color: var(--muted); white-space: pre-wrap; word-break: break-word; }
  .readonly-notice {
    background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3);
    color: var(--warning); padding: 10px 16px; border-radius: 8px;
    font-size: 13px; font-weight: 700; margin-bottom: 16px;
  }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="page-header">
    <div>
      <h2>Activity Logs</h2>
      <p class="subtitle">Full audit trail — every CRUD operation with the responsible user</p>
    </div>
  </div>

  <!-- READ-ONLY NOTICE -->
  <div class="readonly-notice">
    Activity logs are <strong>read-only</strong>. Editing or deleting log records is not permitted.
  </div>

  <!-- ACTION SUMMARY PILLS -->
  <div class="log-stats">
    <span class="log-stat-pill badge-create">CREATE: <?= $counts['CREATE'] ?? 0 ?></span>
    <span class="log-stat-pill badge-read">READ: <?= $counts['READ'] ?? 0 ?></span>
    <span class="log-stat-pill badge-update">UPDATE: <?= $counts['UPDATE'] ?? 0 ?></span>
    <span class="log-stat-pill badge-delete">DELETE: <?= $counts['DELETE'] ?? 0 ?></span>
    <span class="log-stat-pill" style="background:rgba(108,99,255,.15);color:var(--accent)">TOTAL: <?= array_sum($counts) ?></span>
  </div>

  <!-- FILTER FORM -->
  <div class="section-card">
    <form method="GET">
      <div class="search-bar" style="flex-wrap:wrap;gap:10px">
        <input type="text" name="username" value="<?= htmlspecialchars($filters['username']) ?>"
          placeholder="Filter by username..." style="width:180px;flex:none">

        <select name="action" style="width:140px;flex:none">
          <option value="">All Actions</option>
          <option value="CREATE" <?= $filters['action']==='CREATE'?'selected':'' ?>>CREATE</option>
          <option value="READ"   <?= $filters['action']==='READ'  ?'selected':'' ?>>READ</option>
          <option value="UPDATE" <?= $filters['action']==='UPDATE'?'selected':'' ?>>UPDATE</option>
          <option value="DELETE" <?= $filters['action']==='DELETE'?'selected':'' ?>>DELETE</option>
        </select>

        <select name="entity_type" style="width:160px;flex:none">
          <option value="">All Entities</option>
          <option value="Department" <?= $filters['entity_type']==='Department'?'selected':'' ?>>Department</option>
          <option value="Employee"   <?= $filters['entity_type']==='Employee'  ?'selected':'' ?>>Employee</option>
        </select>

        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"
          title="From Date" style="width:150px;flex:none">
        <input type="date" name="date_to"   value="<?= htmlspecialchars($filters['date_to']) ?>"
          title="To Date"   style="width:150px;flex:none">

        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="activity_logs.php" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <p style="color:var(--muted);font-size:13px;margin-top:8px">
      Showing <strong><?= count($logs) ?></strong> log record(s).
    </p>
  </div>

  <!-- LOGS TABLE -->
  <div class="section-card">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Timestamp</th>
          <th>Username</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Record</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="mono" style="font-size:12px"><?= $log['id'] ?></td>
          <td class="mono" style="font-size:12px;white-space:nowrap"><?= $log['performed_at'] ?></td>
          <td>
            <!-- This is the KEY column: identifies WHICH USER did the operation -->
            <code style="background:rgba(108,99,255,.15);color:var(--accent);padding:3px 8px;border-radius:4px">
              <?= htmlspecialchars($log['username']) ?>
            </code>
          </td>
          <td><span class="badge badge-<?= strtolower($log['action']) ?>"><?= $log['action'] ?></span></td>
          <td><?= $log['entity_type'] ?></td>
          <td style="font-size:13px;max-width:180px"><?= htmlspecialchars($log['entity_name']) ?></td>
          <td class="details-cell"><?= htmlspecialchars($log['details']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
          <tr>
            <td colspan="7" style="text-align:center;color:var(--muted);padding:40px">
              No activity logs found for the selected filters.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p style="color:var(--muted);font-size:12px;text-align:center;margin-top:8px">
    Logs are append-only. No edit or delete actions are available on this page.
  </p>
</main>
</body>
</html>
