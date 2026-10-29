<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();

$pageTitle = "Settings";
$pageDescription = "Personalize your AFTERCLASS workspace.";

$userId = (int) $_SESSION["USER_ID"];
$message = "";
$messageType = "success";

if (!isset($_SESSION["AFTERCLASS_SETTINGS"])) {
    $_SESSION["AFTERCLASS_SETTINGS"] = [
        "theme" => "light",
        "compact_mode" => "0"
    ];
}

$settings = &$_SESSION["AFTERCLASS_SETTINGS"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $theme = $_POST["theme"] ?? "light";
    $compactMode = isset($_POST["compact_mode"]) ? "1" : "0";

    if (!in_array($theme, ["light", "dark"], true)) {
        $message = "Choose a valid theme.";
        $messageType = "error";
    } else {
        $settings = [
            "theme" => $theme,
            "compact_mode" => $compactMode
        ];

        $message = "Your workspace preferences have been saved.";
    }
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="panel settings-panel">
    <div class="settings-heading">
        <div class="settings-icon">
            <span>✦</span>
        </div>

        <div>
            <h2>Workspace appearance</h2>
            <p>Make your workspace feel comfortable to use.</p>
        </div>
    </div>

    <form method="POST" action="settings.php">
        <div class="settings-option">
            <div class="settings-option-copy">
                <label for="theme" class="form-label">Color theme</label>
                <p>Choose the appearance of your workspace.</p>
            </div>

            <select class="form-control settings-select" id="theme" name="theme">
                <option
                    value="light"
                    <?= $settings["theme"] === "light" ? "selected" : "" ?>>
                    Light
                </option>

                <option
                    value="dark"
                    <?= $settings["theme"] === "dark" ? "selected" : "" ?>>
                    Dark
                </option>
            </select>
        </div>

        <div class="settings-option">
            <div class="settings-option-copy">
                <label for="compact_mode" class="form-label">
                    Compact layout
                </label>

                <p>Reduce spacing between cards and content.</p>
            </div>

            <label class="settings-toggle">
                <input
                    type="checkbox"
                    id="compact_mode"
                    name="compact_mode"
                    value="1"
                    <?= $settings["compact_mode"] === "1" ? "checked" : "" ?>>

                <span class="settings-toggle-track"></span>
            </label>
        </div>

        <div class="settings-actions">
            <button type="submit" class="btn btn-primary">
                Save preferences
            </button>
        </div>
    </form>
</section>

<section class="panel settings-info">
    <div>
        <h2>Account and security</h2>
        <p>
            Update your username, profile photo, or password from your profile.
        </p>
    </div>

    <a href="profile.php" class="btn btn-secondary">
        Open my profile
    </a>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>