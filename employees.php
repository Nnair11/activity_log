<?php

require_once 'includes/auth.php';
require_once 'includes/logger.php';
requireLogin();

$db      = getDB();
$message = '';
$msgType = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE
    if ($action === 'create') {
        $deptId    = intval($_POST['dept_id']    ?? 0);
        $empNum    = trim($_POST['emp_number']   ?? '');
        $firstName = trim($_POST['first_name']   ?? '');
        $lastName  = trim($_POST['last_name']    ?? '');
        $email     = trim($_POST['email']        ?? '');
        $position  = trim($_POST['position']     ?? '');
        $salary    = floatval($_POST['salary']   ?? 0);
        $hireDate  = $_POST['hire_date']          ?? '';
        $status    = $_POST['status']             ?? 'Active';

        if (!$deptId || !$empNum || !$firstName || !$lastName || !$email || !$position || !$hireDate) {
            $message = 'All required fields must be filled.';
            $msgType = 'error';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO employees (dept_id, emp_number, first_name, last_name, email, position, salary, hire_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$deptId, $empNum, $firstName, $lastName, $email, $position, $salary, $hireDate, $status]);
                $newId = $db->lastInsertId();

                // Get dept name for the log
                $dept = $db->prepare("SELECT dept_name FROM departments WHERE id=?");
                $dept->execute([$deptId]);
                $deptName = $dept->fetchColumn();

                logActivity(
                    'CREATE', 'Employee', $newId,
                    "$empNum - $firstName $lastName",
                    currentUsername() . " created employee: #$empNum $firstName $lastName, Position='$position', Dept='$deptName', Salary=₱" . number_format($salary,2) . ", Status='$status'"
                );

                $message = "Employee '$firstName $lastName' created successfully.";
                $msgType = 'success';
            } catch (PDOException $e) {
                $message = 'Error: Employee number already exists.';
                $msgType = 'error';
            }
        }
    }

    // UPDATE
    if ($action === 'update') {
        $id        = intval($_POST['id']          ?? 0);
        $deptId    = intval($_POST['dept_id']     ?? 0);
        $empNum    = trim($_POST['emp_number']    ?? '');
        $firstName = trim($_POST['first_name']    ?? '');
        $lastName  = trim($_POST['last_name']     ?? '');
        $email     = trim($_POST['email']         ?? '');
        $position  = trim($_POST['position']      ?? '');
        $salary    = floatval($_POST['salary']    ?? 0);
        $hireDate  = $_POST['hire_date']           ?? '';
        $status    = $_POST['status']              ?? 'Active';

        // Fetch old data for diff
        $old = $db->prepare("SELECT e.*, d.dept_name FROM employees e LEFT JOIN departments d ON d.id=e.dept_id WHERE e.id=?");
        $old->execute([$id]);
        $oldRow = $old->fetch();

        // Get new dept name
        $dept = $db->prepare("SELECT dept_name FROM departments WHERE id=?");
        $dept->execute([$deptId]);
        $deptName = $dept->fetchColumn();

        $stmt = $db->prepare("
            UPDATE employees
            SET dept_id=?, emp_number=?, first_name=?, last_name=?, email=?, position=?, salary=?, hire_date=?, status=?
            WHERE id=?
        ");
        $stmt->execute([$deptId, $empNum, $firstName, $lastName, $email, $position, $salary, $hireDate, $status, $id]);

        $details = currentUsername() . " updated employee #$id. "
            . "Name: '{$oldRow['first_name']} {$oldRow['last_name']}' → '$firstName $lastName'. "
            . "Position: '{$oldRow['position']}' → '$position'. "
            . "Dept: '{$oldRow['dept_name']}' → '$deptName'. "
            . "Salary: ₱" . number_format($oldRow['salary'],2) . " → ₱" . number_format($salary,2) . ". "
            . "Status: '{$oldRow['status']}' → '$status'.";

        logActivity('UPDATE', 'Employee', $id, "$empNum - $firstName $lastName", $details);

        $message = 'Employee updated successfully.';
        $msgType = 'success';
    }

    // DELETE
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $row = $db->prepare("SELECT e.*, d.dept_name FROM employees e LEFT JOIN departments d ON d.id=e.dept_id WHERE e.id=?");
        $row->execute([$id]);
        $emp = $row->fetch();

        if ($emp) {
            $db->prepare("DELETE FROM employees WHERE id=?")->execute([$id]);
            logActivity(
                'DELETE', 'Employee', $id,
                "{$emp['emp_number']} - {$emp['first_name']} {$emp['last_name']}",
                currentUsername() . " deleted employee: #{$emp['emp_number']} {$emp['first_name']} {$emp['last_name']}, was in dept '{$emp['dept_name']}'"
            );
            $message = "Employee '{$emp['first_name']} {$emp['last_name']}' deleted.";
            $msgType = 'success';
        }
    }
}

// ── Fetch employees with department name ─────────────────────
$employees = $db->query("
    SELECT e.*, d.dept_name, d.dept_code
    FROM employees e
    LEFT JOIN departments d ON d.id = e.dept_id
    ORDER BY CAST(SUBSTRING_INDEX(e.emp_number, '-', -1) AS UNSIGNED) ASC
")->fetchAll();

// ── Fetch departments for dropdowns ─────────────────────────
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code")->fetchAll();

logActivity('READ', 'Employee', 0, 'Employees List', currentUsername() . ' viewed the employees list.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employees | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="page-header">
    <div>
      <h2>Employees</h2>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ New Employee</button>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="section-card">
    <table class="data-table">
      <thead>
        <tr>
          <th>Emp #</th>
          <th>Name</th>
          <th>Department</th>
          <th>Position</th>
          <th>Salary</th>
          <th>Hire Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $emp): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($emp['emp_number']) ?></td>
          <td><strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong><br>
              <small style="color:var(--muted)"><?= htmlspecialchars($emp['email']) ?></small></td>
          <td><span class="badge badge-active"><?= htmlspecialchars($emp['dept_code']) ?></span> <?= htmlspecialchars($emp['dept_name']) ?></td>
          <td><?= htmlspecialchars($emp['position']) ?></td>
          <td class="mono">₱<?= number_format($emp['salary'], 2) ?></td>
          <td class="mono"><?= $emp['hire_date'] ?></td>
          <td>
            <span class="badge badge-<?= strtolower(str_replace(' ','-',$emp['status'])) ?>">
              <?= $emp['status'] ?>
            </span>
          </td>
          <td>
            <button class="btn btn-secondary btn-sm"
              onclick="openEdit(<?= htmlspecialchars(json_encode($emp)) ?>)">Edit</button>
            <button class="btn btn-danger btn-sm"
              onclick="openDelete(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['first_name'].' '.$emp['last_name'], ENT_QUOTES) ?>')">Del</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">No employees found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php
// Reusable department options HTML
$deptOptions = '';
foreach ($departments as $d) {
    $deptOptions .= "<option value='{$d['id']}'>[{$d['dept_code']}] {$d['dept_name']}</option>";
}
?>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3>Add Employee</h3>
      <button class="modal-close" onclick="closeModal('modal-create')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label>Department *</label>
        <select name="dept_id" required>
          <option value="">-- Select Department --</option>
          <?= $deptOptions ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Employee Number *</label>
          <input type="text" name="emp_number" placeholder="e.g. EMP-005" required>
        </div>
        <div class="form-group">
          <label>Position *</label>
          <input type="text" name="position" placeholder="e.g. Developer" required>
        </div>
        <div class="form-group">
          <label>First Name *</label>
          <input type="text" name="first_name" required>
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <input type="text" name="last_name" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="text" name="email" placeholder="user@company.com" required>
        </div>
        <div class="form-group">
          <label>Salary (₱)</label>
          <input type="number" name="salary" placeholder="0.00" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label>Hire Date *</label>
          <input type="date" name="hire_date" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Employee</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="form-group">
        <label>Department *</label>
        <select name="dept_id" id="edit-dept" required>
          <option value="">-- Select Department --</option>
          <?= $deptOptions ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Employee Number *</label>
          <input type="text" name="emp_number" id="edit-empnum" required>
        </div>
        <div class="form-group">
          <label>Position *</label>
          <input type="text" name="position" id="edit-position" required>
        </div>
        <div class="form-group">
          <label>First Name *</label>
          <input type="text" name="first_name" id="edit-fname" required>
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <input type="text" name="last_name" id="edit-lname" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="text" name="email" id="edit-email" required>
        </div>
        <div class="form-group">
          <label>Salary (₱)</label>
          <input type="number" name="salary" id="edit-salary" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label>Hire Date *</label>
          <input type="date" name="hire_date" id="edit-hiredate" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit-status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="width:400px">
    <div class="modal-header">
      <h3>Delete Employee</h3>
      <button class="modal-close" onclick="closeModal('modal-delete')">×</button>
    </div>
    <p style="color:var(--muted);margin-bottom:20px">
      Delete <strong id="delete-name"></strong>? This cannot be undone.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete-id">
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-delete')">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/modal.js"></script>
<script>
function openEdit(emp) {
  document.getElementById('edit-id').value        = emp.id;
  document.getElementById('edit-empnum').value    = emp.emp_number;
  document.getElementById('edit-fname').value     = emp.first_name;
  document.getElementById('edit-lname').value     = emp.last_name;
  document.getElementById('edit-email').value     = emp.email;
  document.getElementById('edit-position').value  = emp.position;
  document.getElementById('edit-salary').value    = emp.salary;
  document.getElementById('edit-hiredate').value  = emp.hire_date;
  document.getElementById('edit-dept').value      = emp.dept_id;
  document.getElementById('edit-status').value    = emp.status;
  openModal('modal-edit');
}
function openDelete(id, name) {
  document.getElementById('delete-id').value      = id;
  document.getElementById('delete-name').textContent = name;
  openModal('modal-delete');
}
</script>
</body>
</html>
