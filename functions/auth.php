
<?php

// LOGIN USER
function loginUSER($pdo, $login, $password){

    // Find user by email or username
    $sql = "
        SELECT
            user_id,
            user_email,
            user_username,
            user_password,
            user_role
        FROM users
        WHERE user_email = :email
           OR user_username = :username
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':email' => $login,
        ':username' => $login
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        return false;
    }

    // Verify password
    if (!password_verify($password, $user['user_password'])) {
        return false;
    }

    // Check if account already has an active session
    if (hasActiveUserSession($pdo, $user['user_id'])) {
        return 'active_session';
    }

    // Regenerate PHP session ID for security
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    // Store user information in PHP session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['user_email'];
    $_SESSION['user_username'] = $user['user_username'];
    $_SESSION['user_role'] = $user['user_role'];

    // Set last activity
    $_SESSION['last_activity'] = time();

    // Create database session
    $session_id = startUserSession($pdo);

    // Check if database session was created
    if (!$session_id) {

        session_unset();
        session_destroy();

        return false;
    }

    // Save database session ID
    $_SESSION['session_id'] = $session_id;

    return true;
}


// REQUIRE LOGIN
function requireLogin(){

    if (!isset($_SESSION['user_id'])) {

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}


// REQUIRE USER ROLE
function requireRole($role){

    requireLogin();

    if ($_SESSION['user_role'] !== $role) {

        http_response_code(403);

        die('Access denied.');
    }
}

?>
