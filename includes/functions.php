<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/** Redirect helper */
function redirect($url) {
    header("Location: $url");
    exit;
}

/** Basic string sanitizer for output */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Set a one-time flash message */
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve & clear the flash message */
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Is a user currently logged in? */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/** Get current logged-in user's basic info from session */
function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['user_name'],
        'role'   => $_SESSION['user_role'],
        'status' => $_SESSION['user_status'],
    ];
}

/**
 * Guard a page so only specific roles can access it.
 * $login_path lets each page pass the correct relative path back to login.php
 * (e.g. 'login.php' from root, '../login.php' from a subfolder).
 */
function require_role($allowed_roles = [], $login_path = 'login.php', $home_path = 'index.php') {
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to continue.');
        redirect($login_path);
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles, true)) {
        set_flash('error', 'You do not have access to that page.');
        redirect($home_path);
    }
}

/** Simple required-field validator. Returns array of error messages. */
function validate_required($fields) {
    $errors = [];
    foreach ($fields as $label => $value) {
        if ($value === null || trim((string)$value) === '') {
            $errors[] = "$label is required.";
        }
    }
    return $errors;
}

/** Validate a Bangladeshi-style phone number (11 digits, starts with 01) */
function is_valid_phone($phone) {
    return (bool) preg_match('/^01[0-9]{9}$/', $phone);
}
