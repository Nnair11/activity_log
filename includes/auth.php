<?php

require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current visitor is logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Returns the logged-in user's ID, or null if not logged in.
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Returns the logged-in user's username, or null if not logged in.
 */
function currentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

/**
 * Attempt to register a new user.
 * Returns ['success' => bool, 'message' => string]
 */
function registerUser(string $username, string $password, string $fullName): array {
    $db = getDB();

    // Check for duplicate username (requirement: no duplicate usernames)
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => "Username '$username' is already taken. Please choose another."];
    }

    // Hash password securely with bcrypt
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashed, $fullName]);

    return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
}

/**
 * Attempt to log in a user.
 * Returns ['success' => bool, 'message' => string]
 */
function loginUser(string $username, string $password): array {
    $db = getDB();

    $stmt = $db->prepare("SELECT id, username, password, full_name FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Store user info in session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];

    return ['success' => true, 'message' => 'Login successful!'];
}

/**
 * Log out the current user by destroying the session.
 */
function logoutUser(): void {
    session_destroy();
    header('Location: index.php');
    exit;
}
