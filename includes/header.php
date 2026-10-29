<?php
if (!isset($pageTitle)) {
    $pageTitle = "Overview";
}

if (!isset($pageDescription)) {
    $pageDescription = "Your academic workspace, organized.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?> | AFTERCLASS
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<?php
$workspaceSettings = $_SESSION["AFTERCLASS_SETTINGS"] ?? [
    "theme" => "light",
    "compact_mode" => "0"
];

$workspaceTheme = ($workspaceSettings["theme"] ?? "light") === "dark"
    ? "dark"
    : "light";

$compactMode = ($workspaceSettings["compact_mode"] ?? "0") === "1";
?>

<body class="theme-<?= htmlspecialchars($workspaceTheme) ?><?= $compactMode ? ' compact-mode' : '' ?>">
    <div class="app-layout">
        <?php require __DIR__ . "/sidebar.php"; ?>

        <main class="main-content">
            <div class="page-container">
                <header class="page-header">
                    <div>
                        <div class="page-eyebrow">Your academic workspace</div>
                        <h1 class="page-title">
                            <?= htmlspecialchars($pageTitle) ?>
                        </h1>
                        <p class="page-description">
                            <?= htmlspecialchars($pageDescription) ?>
                        </p>
                    </div>
                </header>