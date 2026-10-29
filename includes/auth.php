
<?php

require_once __DIR__ . "/../config/db.php";

function requireLogin(): void
{
    if (
        empty($_SESSION["USER_ID"]) ||
        empty($_SESSION["USERNAME"]) ||
        empty($_SESSION["USERTYPE"])
    ) {
        header("Location: login.php");
        exit();
    }
}

function currentRole(): string
{
    return $_SESSION["USERTYPE"] ?? "";
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    if (
        currentRole() !== "Admin" &&
        !in_array(currentRole(), $allowedRoles, true)
    ) {
        http_response_code(403);

        exit("403 Forbidden — You do not have permission to access this page.");
    }
}

function requirePageAccess(string $page): void
{
    $permissions = [
        "Subjects" => [
            "Admin",
            "Registrar",
            "Dean",
            "Department Head",
            "Instructor",
            "Student"
        ],

        "Term" => [
            "Admin",
            "Registrar"
        ],

        "School Year" => [
            "Admin",
            "Registrar"
        ],

        "Section" => [
            "Admin",
            "Dean",
            "Department Head",
            "Instructor"
        ],

        "Year Level" => [
            "Admin",
            "Dean",
            "Department Head"
        ],

        "Instructors" => [
            "Admin",
            "Registrar",
            "Dean",
            "Department Head"
        ]
    ];

    if (!isset($permissions[$page])) {
        http_response_code(404);
        exit("404 — Page not found.");
    }

    requireRole($permissions[$page]);
}
