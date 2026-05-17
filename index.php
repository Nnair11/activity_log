<?php


require_once 'includes/auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

$message     = '';
$messageType = '';
$activeTab   = 'login';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {

        // REGISTRATION
        if ($_POST['action'] === 'register') {
            $activeTab = 'register';
            $username  = trim($_POST['username'] ?? '');
            $password  = $_POST['password'] ?? '';
            $fullName  = trim($_POST['full_name'] ?? '');

            if (!$username || !$password || !$fullName) {
                $message     = 'All fields are required.';
                $messageType = 'error';
            } elseif (strlen($password) < 6) {
                $message     = 'Password must be at least 6 characters.';
                $messageType = 'error';
            } else {
                $result      = registerUser($username, $password, $fullName);
                $message     = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                if ($result['success']) $activeTab = 'login';
            }
        }

        // LOGIN
        if ($_POST['action'] === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                $message     = 'Username and password are required.';
                $messageType = 'error';
            } else {
                $result = loginUser($username, $password);
                if ($result['success']) {
                    header('Location: homepage.php');
                    exit;
                }
                $message     = $result['message'];
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Dept-Employee Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0d0f17;
    --surface: #151824;
    --border: #252a3a;
    --accent: #6c63ff;
    --accent2: #ff6584;
    --text: #e2e8f0;
    --muted: #64748b;
    --success: #10b981;
    --error: #ef4444;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Syne', sans-serif;
    min-height: 100vh;
    display: grid;
    place-items: center;
    background-image: radial-gradient(ellipse at 20% 50%, rgba(108,99,255,.12) 0%, transparent 60%),
                      radial-gradient(ellipse at 80% 20%, rgba(255,101,132,.08) 0%, transparent 50%);
  }
  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    width: 420px;
    max-width: 95vw;
    padding: 40px 36px;
    box-shadow: 0 24px 64px rgba(0,0,0,.5);
  }
  .logo {
    text-align: center;
    margin-bottom: 28px;
  }
  .logo h1 {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.5px;
  }
  .logo span { color: var(--accent); }
  .logo p { font-size: 12px; color: var(--muted); margin-top: 4px; font-family: 'JetBrains Mono', monospace; }
  .tabs { display: flex; gap: 4px; background: var(--bg); border-radius: 8px; padding: 4px; margin-bottom: 24px; }
  .tab {
    flex: 1; padding: 8px; border: none; background: transparent;
    color: var(--muted); font-family: 'Syne', sans-serif; font-size: 14px;
    font-weight: 700; border-radius: 6px; cursor: pointer; transition: all .2s;
  }
  .tab.active { background: var(--accent); color: #fff; }
  .form-group { margin-bottom: 16px; }
  label { display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 6px; letter-spacing: .8px; text-transform: uppercase; }
  input[type=text], input[type=password] {
    width: 100%; padding: 11px 14px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text);
    font-family: 'JetBrains Mono', monospace; font-size: 14px;
    transition: border-color .2s;
  }
  input:focus { outline: none; border-color: var(--accent); }
  .btn {
    width: 100%; padding: 12px;
    background: var(--accent); border: none; border-radius: 8px;
    color: #fff; font-family: 'Syne', sans-serif; font-size: 15px;
    font-weight: 800; cursor: pointer; margin-top: 8px;
    transition: opacity .2s, transform .1s;
  }
  .btn:hover { opacity: .9; transform: translateY(-1px); }
  .alert {
    padding: 12px 14px; border-radius: 8px; font-size: 13px;
    margin-bottom: 16px; font-weight: 700;
  }
  .alert.success { background: rgba(16,185,129,.15); color: var(--success); border: 1px solid rgba(16,185,129,.3); }
  .alert.error   { background: rgba(239,68,68,.15);  color: var(--error);   border: 1px solid rgba(239,68,68,.3); }
  .pane { display: none; }
  .pane.active { display: block; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>Act<span>&bull;</span>Logs</h1>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="tabs">
    <button class="tab <?= $activeTab === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">Login</button>
    <button class="tab <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
  </div>

  <!-- LOGIN FORM -->
  <div class="pane <?= $activeTab === 'login' ? 'active' : '' ?>" id="pane-login">
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn">Sign In →</button>
    </form>
  </div>

  <!-- REGISTRATION FORM -->
  <div class="pane <?= $activeTab === 'register' ? 'active' : '' ?>" id="pane-register">
    <form method="POST">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
      </div>
      <div class="form-group">
        <label>Username <small style="color:var(--muted)">(must be unique)</small></label>
        <input type="text" name="username" placeholder="Choose a username" required>
      </div>
      <div class="form-group">
        <label>Password <small style="color:var(--muted)">(min. 6 chars)</small></label>
        <input type="password" name="password" placeholder="Choose a password" required>
      </div>
      <button type="submit" class="btn">Create Account →</button>
    </form>
  </div>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab').forEach((t,i) => {
    t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
  document.getElementById('pane-login').classList.toggle('active', tab === 'login');
  document.getElementById('pane-register').classList.toggle('active', tab === 'register');
}
</script>
</body>
</html>
