<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION["USER_ID"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}

exit();
