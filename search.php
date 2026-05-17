<?php

require_once 'includes/auth.php';
require_once 'includes/logger.php';
requireLogin();

$db = getDB();

$query       = trim($_GET['q']    ?? '');
$searchType  = $_GET['type']      ?? 'both';   // both | dept | emp
$deptFilter  = intval($_GET['dept'] ?? 0);

$depts = [];
$emps  = [];

if ($query !== '') {
    $like = '%' . $query . '%';

    // Search DEPARTMENTS (Parent)
    if ($searchType === 'both' || $searchType === 'dept') {
        $stmt = $db->prepare("
            SELECT d.*, COUNT(e.id) AS emp_count
            FROM departments d
            LEFT JOIN employees e ON e.dept_id = d.id
            WHERE d.dept_code LIKE ?
               OR d.dept_name LIKE ?
               OR d.location  LIKE ?
            GROUP BY d.id
            ORDER BY d.dept_code
        ");
        $stmt->execute([$like, $like, $like]);
        $depts = $stmt->fetchAll();
    }

    // Search EMPLOYEES (Child)
    if ($searchType === 'both' || $searchType === 'emp') {
        $empSql = "
            SELECT e.*, d.dept_name, d.dept_code
            FROM employees e
            LEFT JOIN departments d ON d.id = e.dept_id
            WHERE (e.emp_number LIKE ?
               OR  e.first_name LIKE ?
               OR  e.last_name  LIKE ?
               OR  e.email      LIKE ?
               OR  e.position   LIKE ?
               OR  CONCAT(e.first_name,' ',e.last_name) LIKE ?)
        ";
        $params = [$like, $like, $like, $like, $like, $like];

        // Optional dept filter
        if ($deptFilter > 0) {
            $empSql .= " AND e.dept_id = ?";
            $params[] = $deptFilter;
        }
        $empSql .= " ORDER BY d.dept_code, e.emp_number";

        $stmt = $db->prepare($empSql);
        $stmt->execute($params);
        $emps = $stmt->fetchAll();
    }

    // Log the search as a READ
    logActivity(
        'READ',
        $searchType === 'emp' ? 'Employee' : 'Department',
        0,
        "Search: \"$query\"",
        currentUsername() . " searched for '$query' (type: $searchType). Found " . count($depts) . " dept(s), " . count($emps) . " employee(s)."
    );
}

// All departments for filter dropdown
$allDepts = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="page-header">
    <div>
      <h2>Search Records</h2>
      <p class="subtitle">Search across both departments and employees</p>
    </div>
  </div>

  <!-- SEARCH FORM -->
  <div class="section-card">
    <form method="GET">
      <div class="search-bar">
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>"
          placeholder="Search by name, code, position, email..."
          style="flex:2">

        <select name="type">
          <option value="both" <?= $searchType==='both' ? 'selected':'' ?>>All Records</option>
          <option value="dept" <?= $searchType==='dept' ? 'selected':'' ?>>Departments Only</option>
          <option value="emp"  <?= $searchType==='emp'  ? 'selected':'' ?>>Employees Only</option>
        </select>

        <?php if ($searchType !== 'dept'): ?>
        <select name="dept">
          <option value="0">All Departments</option>
          <?php foreach ($allDepts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id'] ? 'selected':'' ?>>
              [<?= htmlspecialchars($d['dept_code']) ?>] <?= htmlspecialchars($d['dept_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($query): ?>
          <a href="search.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($query): ?>
      <p style="color:var(--muted);font-size:13px">
        Showing results for <strong>"<?= htmlspecialchars($query) ?>"</strong>
        — <?= count($depts) ?> department(s), <?= count($emps) ?> employee(s) found.
      </p>
    <?php else: ?>
      <p style="color:var(--muted);font-size:13px">Enter a keyword above to search.</p>
    <?php endif; ?>
  </div>

  <!-- DEPARTMENTS RESULTS -->
  <?php if ($query && ($searchType === 'both' || $searchType === 'dept')): ?>
  <div class="section-card">
    <div class="section-header">
      <h3>Departments <span style="color:var(--muted);font-size:13px">(<?= count($depts) ?> result<?= count($depts) !== 1 ? 's' : '' ?>)</span></h3>
      <a href="departments.php" class="btn-link">Manage →</a>
    </div>
    <?php if ($depts): ?>
    <table class="data-table">
      <thead>
        <tr><th>ID</th><th>Code</th><th>Name</th><th>Location</th><th>Budget</th><th>Employees</th></tr>
      </thead>
      <tbody>
        <?php foreach ($depts as $d): ?>
        <tr>
          <td class="mono"><?= $d['id'] ?></td>
          <td><strong><?= htmlspecialchars($d['dept_code']) ?></strong></td>
          <td><?= htmlspecialchars($d['dept_name']) ?></td>
          <td><?= htmlspecialchars($d['location']) ?></td>
          <td class="mono">₱<?= number_format($d['budget'],2) ?></td>
          <td><?= $d['emp_count'] ?> emp</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p style="color:var(--muted);text-align:center;padding:20px">No departments match "<?= htmlspecialchars($query) ?>".</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- EMPLOYEES RESULTS -->
  <?php if ($query && ($searchType === 'both' || $searchType === 'emp')): ?>
  <div class="section-card">
    <div class="section-header">
      <h3>Employees <span style="color:var(--muted);font-size:13px">(<?= count($emps) ?> result<?= count($emps) !== 1 ? 's' : '' ?>)</span></h3>
      <a href="employees.php" class="btn-link">Manage →</a>
    </div>
    <?php if ($emps): ?>
    <table class="data-table">
      <thead>
        <tr><th>Emp #</th><th>Name</th><th>Department</th><th>Position</th><th>Salary</th><th>Hire Date</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($emps as $e): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($e['emp_number']) ?></td>
          <td>
            <strong><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></strong><br>
            <small style="color:var(--muted)"><?= htmlspecialchars($e['email']) ?></small>
          </td>
          <td><span class="badge badge-active"><?= htmlspecialchars($e['dept_code']) ?></span> <?= htmlspecialchars($e['dept_name']) ?></td>
          <td><?= htmlspecialchars($e['position']) ?></td>
          <td class="mono">₱<?= number_format($e['salary'],2) ?></td>
          <td class="mono"><?= $e['hire_date'] ?></td>
          <td><span class="badge badge-<?= strtolower(str_replace(' ','-',$e['status'])) ?>"><?= $e['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p style="color:var(--muted);text-align:center;padding:20px">No employees match "<?= htmlspecialchars($query) ?>".</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</main>
</body>
</html>
