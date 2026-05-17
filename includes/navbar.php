<?php

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
  <div class="nav-brand">
    <span>Act<strong>&bull;</strong>Logs</span>
  </div>
  <div class="nav-links">
    <a href="homepage.php"      class="<?= $currentPage === 'homepage.php'      ? 'active' : '' ?>">Dashboard</a>
    <a href="departments.php"   class="<?= $currentPage === 'departments.php'   ? 'active' : '' ?>">Departments</a>
    <a href="employees.php"     class="<?= $currentPage === 'employees.php'     ? 'active' : '' ?>">Employees</a>
    <a href="search.php"        class="<?= $currentPage === 'search.php'        ? 'active' : '' ?>">Search</a>
    <a href="activity_logs.php" class="<?= $currentPage === 'activity_logs.php' ? 'active' : '' ?>">Activity Logs</a>
  </div>
  <div class="nav-user">
    <span class="nav-username"><?= htmlspecialchars(currentUsername()) ?></span>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</nav>
