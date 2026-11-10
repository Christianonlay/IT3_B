
<?php

require_once '../config/config.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {

    // Record logout activity
    logActivity(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['user_email'] ?? null,
        'logout',
        'success'
    );

    // End the database session first
    endUserSession($pdo);
}

// Clear all PHP session variables
$_SESSION = [];

// Delete PHP session cookie
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy PHP session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Redirect to login page
header('Location: ' . BASE_URL . '/index.php');
exit;

?>
