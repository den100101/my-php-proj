<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();

$pageTitle = "My Profile";
$pageDescription = "Your account, kept simple and organized.";

$message = "";
$messageType = "success";

$userId = (int) $_SESSION["USER_ID"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_profile") {
        $newUsername = trim($_POST["username"] ?? "");

        if (
            $newUsername === "" ||
            mb_strlen($newUsername) > 100
        ) {
            $message = "Enter a username with no more than 100 characters.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE tbl_account
                     SET USERNAME = ?
                     WHERE ID = ?"
                );

                $stmt->bind_param("si", $newUsername, $userId);
                $stmt->execute();
                $stmt->close();

                $_SESSION["USERNAME"] = $newUsername;

                $message = "Username updated successfully.";
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());

                $message = (int) $e->getCode() === 1062
                    ? "That username is already taken."
                    : "Unable to update your username.";

                $messageType = "error";
            }
        }
    }

    if ($action === "change_password") {
        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if (
            $currentPassword === "" ||
            strlen($newPassword) < 12 ||
            $newPassword !== $confirmPassword
        ) {
            $message = "Check your current password, ensure the new password has at least 12 characters, and confirm it correctly.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "SELECT PASSWORD FROM tbl_account WHERE ID = ? LIMIT 1"
            );

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $account = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (
                !$account ||
                !password_verify($currentPassword, $account["PASSWORD"])
            ) {
                $message = "Your current password is incorrect.";
                $messageType = "error";
            } else {
                try {
                    $passwordHash = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "UPDATE tbl_account SET PASSWORD = ? WHERE ID = ?"
                    );

                    $stmt->bind_param("si", $passwordHash, $userId);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Password changed successfully.";
                } catch (mysqli_sql_exception $e) {
                    error_log($e->getMessage());
                    $message = "Unable to change your password.";
                    $messageType = "error";
                }
            }
        }
    }

    if ($action === "upload_photo") {
        if (
            !isset($_FILES["photo"]) ||
            $_FILES["photo"]["error"] === UPLOAD_ERR_NO_FILE
        ) {
            $message = "Please select a photo.";
            $messageType = "error";
        } elseif ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
            $message = "The photo upload failed.";
            $messageType = "error";
        } elseif ($_FILES["photo"]["size"] > 2 * 1024 * 1024) {
            $message = "The photo must not exceed 2 MB.";
            $messageType = "error";
        } else {
            $file = $_FILES["photo"];
            $imageInfo = getimagesize($file["tmp_name"]);

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            $mimeType = $imageInfo["mime"] ?? "";

            if ($imageInfo === false || !isset($allowedTypes[$mimeType])) {
                $message = "Only valid JPG, PNG, and WEBP images are allowed.";
                $messageType = "error";
            } else {
                $imageDirectory = "/var/data/users/";

                if (
                    !is_dir($imageDirectory) &&
                    !mkdir($imageDirectory, 0755, true) &&
                    !is_dir($imageDirectory)
                ) {
                    $message = "Unable to prepare the image directory.";
                    $messageType = "error";
                } else {
                    $fileName = bin2hex(random_bytes(16))
                        . "."
                        . $allowedTypes[$mimeType];

                    $relativePath = "users/" . $fileName;
                    $destination = $imageDirectory . $fileName;

                    if (move_uploaded_file($file["tmp_name"], $destination)) {
                        try {
                            $stmt = $conn->prepare(
                                "UPDATE tbl_account SET IMAGE_PATH = ? WHERE ID = ?"
                            );

                            $stmt->bind_param("si", $relativePath, $userId);
                            $stmt->execute();
                            $stmt->close();

                            $_SESSION["IMAGE_PATH"] = $relativePath;

                            $message = "Profile photo updated successfully.";
                        } catch (mysqli_sql_exception $e) {
                            error_log($e->getMessage());

                            if (is_file($destination)) {
                                unlink($destination);
                            }

                            $message = "Unable to save the photo path. Check that IMAGE_PATH exists in tbl_account.";
                            $messageType = "error";
                        }
                    } else {
                        $message = "Unable to save the uploaded photo.";
                        $messageType = "error";
                    }
                }
            }
        }
    }
}

$stmt = $conn->prepare(
    "SELECT ID, USERNAME, USERTYPE, IMAGE_PATH
     FROM tbl_account
     WHERE ID = ?
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="profile-layout">
    <article class="panel profile-summary">
        <div class="profile-photo-large">
            <?php if (!empty($profile["IMAGE_PATH"])): ?>
                <img
                    src="<?= htmlspecialchars($profile["IMAGE_PATH"]) ?>"
                    alt="Profile photo">
            <?php else: ?>
                <span>
                    <?= htmlspecialchars(
                        strtoupper(substr($profile["USERNAME"], 0, 1))
                    ) ?>
                </span>
            <?php endif; ?>
        </div>

        <h2><?= htmlspecialchars($profile["USERNAME"]) ?></h2>

        <span class="badge badge-accent">
            <?= htmlspecialchars($profile["USERTYPE"]) ?>
        </span>

        <p class="profile-summary-note">
            Your AFTERCLASS account
        </p>

        <form
            method="POST"
            action="profile.php"
            enctype="multipart/form-data"
            class="profile-photo-form">
            <input type="hidden" name="action" value="upload_photo">

            <label class="form-label" for="photo">
                Update profile photo
            </label>

            <input
                class="form-control"
                type="file"
                id="photo"
                name="photo"
                accept=".jpg,.jpeg,.png,.webp"
                required>

            <p class="profile-help">JPG, PNG, or WEBP · Maximum 2 MB</p>

            <button class="btn btn-primary" type="submit">
                Upload photo
            </button>
        </form>
    </article>

    <div class="profile-details">
        <section class="panel profile-section">
            <div class="profile-section-heading">
                <div>
                    <h2>Account details</h2>
                    <p>Update the username associated with your account.</p>
                </div>
            </div>

            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label" for="username">
                        Username
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="username"
                        name="username"
                        maxlength="100"
                        value="<?= htmlspecialchars($profile["USERNAME"]) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Account role</label>

                    <input
                        class="form-control"
                        type="text"
                        value="<?= htmlspecialchars($profile["USERTYPE"]) ?>"
                        disabled>
                </div>

                <button class="btn btn-primary" type="submit">
                    Save changes
                </button>
            </form>
        </section>

        <section class="panel profile-section">
            <div class="profile-section-heading">
                <div>
                    <h2>Change password</h2>
                    <p>Use a unique password of at least 12 characters.</p>
                </div>
            </div>

            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label" for="current_password">
                        Current password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">
                        New password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="12"
                        autocomplete="new-password"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        Confirm new password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="12"
                        autocomplete="new-password"
                        required>
                </div>

                <button class="btn btn-primary" type="submit">
                    Change password
                </button>
            </form>
        </section>
    </div>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>