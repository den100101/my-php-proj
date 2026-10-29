
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https")
    );

    ini_set("session.use_strict_mode", "1");
    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_secure", $isHttps ? "1" : "0");
    ini_set("session.cookie_samesite", "Lax");

    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$isProduction = getenv("RENDER") !== false
    || strtolower(getenv("APP_ENV") ?: "") === "production";

$dbHost = getenv("DB_HOST") ?: "";
$dbUser = getenv("DB_USER") ?: "";
$dbPassword = getenv("DB_PASSWORD") ?: "";
$dbName = getenv("DB_NAME") ?: "db_website";
$dbPort = (int) (getenv("DB_PORT") ?: 3306);
$dbSslCa = getenv("DB_SSL_CA") ?: "";

try {
    if ($isProduction && ($dbHost === "" || $dbUser === "" || $dbPassword === "")) {
        throw new RuntimeException("Required production database environment variables are missing.");
    }

    // Local development fallback.
    if (!$isProduction && $dbHost === "") {
        $dbHost = "localhost";
        $dbUser = "root";
        $dbPassword = "";
    }

    $conn = mysqli_init();

    if (!$conn) {
        throw new RuntimeException("Unable to initialize MySQL.");
    }

    if ($dbSslCa !== "") {
        if (!is_readable($dbSslCa)) {
            throw new RuntimeException("The configured database SSL CA file is unavailable.");
        }

        $conn->ssl_set(null, null, $dbSslCa, null, null);

        $conn->real_connect(
            $dbHost,
            $dbUser,
            $dbPassword,
            $dbName,
            $dbPort,
            null,
            MYSQLI_CLIENT_SSL
        );
    } else {
        if ($isProduction) {
            throw new RuntimeException("DB_SSL_CA must be configured in production.");
        }

        $conn->real_connect(
            $dbHost,
            $dbUser,
            $dbPassword,
            $dbName,
            $dbPort
        );
    }

    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    error_log("Database connection failed: " . $e->getMessage());

    http_response_code(500);
    exit("Unable to connect to the database. Please try again later.");
}
