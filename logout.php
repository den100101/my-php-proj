<?php
require_once __DIR__ . "/config/db.php";

// Clear the current session.
$_SESSION = [];

// Remove the session cookie.
if (ini_get("session.use_cookies")) {
    $cookieParams = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $cookieParams["path"],
        $cookieParams["domain"],
        $cookieParams["secure"],
        $cookieParams["httponly"]
    );
}

// Destroy the session.
session_destroy();

// Redirect to login.
header("Location: login.php");
exit();
