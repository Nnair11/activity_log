<?php

require_once 'includes/auth.php';
require_once 'includes/logger.php';
requireLogin();

$db      = getDB();
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE a new department
    if ($action === 'create') {
        $deptCode = trim($_POST['dept_code'] ?? '');
        $deptName = trim($_POST['dept_name'] ?? '');
        $location = trim($_POST['location']  ?? '');
        $budget   = floatval($_POST['budget'] ?? 0);

        if (!$deptCode || !$deptName) {
            $message = 'Department Code and Name are required.';
            $msgType = 'error';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO departments (dept_code, dept_name, location, budget)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$deptCode, $deptName, $location, $budget]);
                $newId = $db->lastInsertId();

                // LOG THE CREATE ACTION
                logActivity(
                    'CREATE',
                    'Department',
                    $newId,
                    "$deptCode - $deptName",
                    currentUsername() . " created a new department: Code='$deptCode', Name='$deptName', Location='$location', Budget=₱" . number_format($budget, 2)
                );

                $message = "Department '$deptName' created successfully.";
                $msgType = 'success';
            } catch (PDOException $e) {
                $message = 'Error: Department code already exists.';
                $msgType = 'error';
            }
        }
    }

    // UPDATE a department
    if ($action === 'update') {
        $id       = intval($_POST['id']        ?? 0);
        $deptCode = trim($_POST['dept_code']   ?? '');
        $deptName = trim($_POST['dept_name']   ?? '');
        $location = trim($_POST['location']    ?? '');
        $budget   = floatval($_POST['budget']  ?? 0);

        // Fetch old values to log the diff
        $old = $db->prepare("SELECT * FROM departments WHERE id = ?");
        $old->execute([$id]);
        $oldRow = $old->fetch();

        try {
            $stmt = $db->prepare("
                UPDATE departments
                SET dept_code=?, dept_name=?, location=?, budget=?
                WHERE id=?
            ");
            $stmt->execute([$deptCode, $deptName, $location, $budget, $id]);

            // LOG THE UPDATE ACTION with before/after details
            $details = currentUsername() . " updated department #$id. "
                . "Code: '{$oldRow['dept_code']}' → '$deptCode'. "
                . "Name: '{$oldRow['dept_name']}' → '$deptName'. "
                . "Location: '{$oldRow['location']}' → '$location'. "
                . "Budget: ₱" . number_format($oldRow['budget'],2) . " → ₱" . number_format($budget,2);

            logActivity('UPDATE', 'Department', $id, "$deptCode - $deptName", $details);

            $message = "Department updated successfully.";
            $msgType = 'success';
        } catch (PDOException $e) {
            $message = 'Update failed: ' . $e->getMessage();
            $msgType = 'error';
        }
    }

    // DELETE a department
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        $row = $db->prepare("SELECT * FROM departments WHERE id = ?");
        $row->execute([$id]);
        $dept = $row->fetch();

        if ($dept) {
            $db->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);

            // LOG THE DELETE ACTION
            logActivity(
                'DELETE',
                'Department',
                $id,
                "{$dept['dept_code']} - {$dept['dept_name']}",
                currentUsername() . " deleted department: Code='{$dept['dept_code']}', Name='{$dept['dept_name']}'"
            );

            $message = "Department '{$dept['dept_name']}' deleted.";
            $msgType = 'success';
        }
    }
}

// ── Fetch all departments (with employee count) ──────────────
$departments = $db->query("
    SELECT d.*, COUNT(e.id) AS emp_count
    FROM departments d
    LEFT JOIN employees e ON e.dept_id = d.id
    GROUP BY d.id
    ORDER BY d.id ASC
")->fetchAll();

// Log the READ
logActivity('READ', 'Department', 0, 'Departments List', currentUsername() . ' viewed the departments list.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <div class="page-header">
    <div>
      <h2>Departments</h2>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ New Department</button>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="section-card">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Code</th>
          <th>Department Name</th>
          <th>Location</th>
          <th>Budget (₱)</th>
          <th>Employees</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $dept): ?>
        <tr>
          <td class="mono"><?= $dept['id'] ?></td>
          <td><strong><?= htmlspecialchars($dept['dept_code']) ?></strong></td>
          <td><?= htmlspecialchars($dept['dept_name']) ?></td>
          <td><?= htmlspecialchars($dept['location']) ?></td>
          <td class="mono">₱<?= number_format($dept['budget'], 2) ?></td>
          <td><span class="badge badge-active"><?= $dept['emp_count'] ?> emp</span></td>
          <td class="mono" style="font-size:12px"><?= substr($dept['created_at'],0,10) ?></td>
          <td>
            <button class="btn btn-secondary btn-sm"
              onclick="openEdit(<?= htmlspecialchars(json_encode($dept)) ?>)">Edit</button>
            <button class="btn btn-danger btn-sm"
              onclick="openDelete(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['dept_name'], ENT_QUOTES) ?>')">Del</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$departments): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">No departments found. Add one above.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3>Create Department</h3>
      <button class="modal-close" onclick="closeModal('modal-create')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-grid">
        <div class="form-group">
          <label>Department Code *</label>
          <input type="text" name="dept_code" placeholder="e.g. CSIT" required>
        </div>
        <div class="form-group">
          <label>Department Name *</label>
          <input type="text" name="dept_name" placeholder="e.g. Computer Science & IT" required>
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="location" placeholder="e.g. Building A, Floor 2">
        </div>
        <div class="form-group">
          <label>Budget (₱)</label>
          <input type="number" name="budget" placeholder="0.00" step="0.01" min="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Department</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Department</h3>
      <button class="modal-close" onclick="closeModal('modal-edit')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="form-grid">
        <div class="form-group">
          <label>Department Code *</label>
          <input type="text" name="dept_code" id="edit-code" required>
        </div>
        <div class="form-group">
          <label>Department Name *</label>
          <input type="text" name="dept_name" id="edit-name" required>
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="location" id="edit-location">
        </div>
        <div class="form-group">
          <label>Budget (₱)</label>
          <input type="number" name="budget" id="edit-budget" step="0.01" min="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="width:400px">
    <div class="modal-header">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeModal('modal-delete')">×</button>
    </div>
    <p style="color:var(--muted);margin-bottom:20px">
      Are you sure you want to delete <strong id="delete-name"></strong>?
      All associated employees will also be removed.
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
function openEdit(dept) {
  document.getElementById('edit-id').value       = dept.id;
  document.getElementById('edit-code').value     = dept.dept_code;
  document.getElementById('edit-name').value     = dept.dept_name;
  document.getElementById('edit-location').value = dept.location;
  document.getElementById('edit-budget').value   = dept.budget;
  openModal('modal-edit');
}
function openDelete(id, name) {
  document.getElementById('delete-id').value    = id;
  document.getElementById('delete-name').textContent = name;
  openModal('modal-delete');
}
</script>
</body>
</html>
