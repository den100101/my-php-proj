<?php
require_once __DIR__ . "/includes/auth.php";
requirePageAccess("Instructors");

$role = currentRole();

$pageTitle = "Instructors";
$pageDescription = "Manage instructor profiles and keep faculty information organized.";

$message = "";
$messageType = "success";

$imageDirectory = __DIR__ . "/images/users/";
$imageWebPath = "images/users/";

if (!is_dir($imageDirectory)) {
    mkdir($imageDirectory, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Add and delete instructors
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        requireRole(["Admin", "Registrar"]);

        $salutation = trim($_POST["salutation"] ?? "");
        $firstName = trim($_POST["firstname"] ?? "");
        $middleName = trim($_POST["middlename"] ?? "");
        $lastName = trim($_POST["lastname"] ?? "");

        $photoPath = "";
        $uploadedFile = $_FILES["photo"] ?? null;

        if (
            $firstName === "" ||
            $lastName === "" ||
            mb_strlen($firstName) > 100 ||
            mb_strlen($middleName) > 100 ||
            mb_strlen($lastName) > 100 ||
            mb_strlen($salutation) > 30
        ) {
            $message = "Enter a valid first name and last name. Check the field lengths.";
            $messageType = "error";
        } else {
            try {
                /*
                 * Process optional profile photo.
                 */
                if (
                    $uploadedFile &&
                    $uploadedFile["error"] !== UPLOAD_ERR_NO_FILE
                ) {
                    if ($uploadedFile["error"] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException("The photo upload failed.");
                    }

                    if ($uploadedFile["size"] > 2 * 1024 * 1024) {
                        throw new RuntimeException("The photo must not exceed 2 MB.");
                    }

                    $imageInfo = getimagesize($uploadedFile["tmp_name"]);

                    if ($imageInfo === false) {
                        throw new RuntimeException("Please upload a valid image.");
                    }

                    $allowedMimeTypes = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    $mimeType = $imageInfo["mime"] ?? "";

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        throw new RuntimeException(
                            "Only JPG, PNG, and WEBP photos are allowed."
                        );
                    }

                    $fileName = bin2hex(random_bytes(16))
                        . "."
                        . $allowedMimeTypes[$mimeType];

                    $destination = $imageDirectory . $fileName;

                    if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {
                        throw new RuntimeException("Unable to save the uploaded photo.");
                    }

                    $photoPath = $imageWebPath . $fileName;
                }

                $stmt = $conn->prepare(
                    "INSERT INTO tbl_instructors
                        (SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $salutation,
                    $firstName,
                    $middleName,
                    $lastName,
                    $photoPath
                );

                $stmt->execute();
                $stmt->close();

                $message = "Instructor added successfully.";
            } catch (Throwable $e) {
                error_log($e->getMessage());

                if ($photoPath !== "" && is_file(__DIR__ . "/" . $photoPath)) {
                    unlink(__DIR__ . "/" . $photoPath);
                }

                $message = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Unable to add the instructor. Check the tbl_instructors columns.";

                $messageType = "error";
            }
        }
    } elseif ($action === "delete") {
        requireRole(["Admin"]);

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {
            $message = "Invalid instructor selected.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "SELECT PHOTO FROM tbl_instructors WHERE ID = ?"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $instructor = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$instructor) {
                    $message = "Instructor not found.";
                    $messageType = "error";
                } else {
                    $stmt = $conn->prepare(
                        "DELETE FROM tbl_instructors WHERE ID = ?"
                    );
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();

                    $photo = $instructor["PHOTO"] ?? "";

                    if (
                        $photo !== "" &&
                        str_starts_with($photo, $imageWebPath)
                    ) {
                        $photoFile = __DIR__ . "/" . $photo;

                        if (is_file($photoFile)) {
                            unlink($photoFile);
                        }
                    }

                    $message = "Instructor deleted successfully.";
                }
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());
                $message = "Unable to delete this instructor. The record may be linked to other data.";
                $messageType = "error";
            }
        }
    } else {
        $message = "Invalid action.";
        $messageType = "error";
    }
}

/*
|--------------------------------------------------------------------------
| Search instructors
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$instructors = [];

try {
    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt = $conn->prepare(
            "SELECT ID, SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO
             FROM tbl_instructors
             WHERE SALUTATION LIKE ?
                OR FIRSTNAME LIKE ?
                OR MIDDLENAME LIKE ?
                OR LASTNAME LIKE ?
             ORDER BY LASTNAME ASC, FIRSTNAME ASC"
        );

        $stmt->bind_param(
            "ssss",
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm
        );

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }

        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT ID, SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO
             FROM tbl_instructors
             ORDER BY LASTNAME ASC, FIRSTNAME ASC"
        );

        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $message = "Unable to load instructors. Check your tbl_instructors columns.";
    $messageType = "error";
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card-grid instructor-stats">
    <article class="card">
        <div class="stat-label">Instructors found</div>

        <div class="stat-value">
            <?= number_format(count($instructors)) ?>
        </div>

        <div class="stat-note">
            <?= $search !== "" ? "Matching search results" : "Faculty directory" ?>
        </div>
    </article>

    <article class="card">
        <div class="stat-label">Your access</div>

        <div class="stat-value instructor-access">
            <?= in_array($role, ["Admin", "Registrar"], true)
                ? "Manage"
                : "View only" ?>
        </div>

        <div class="stat-note">
            <?= $role === "Admin"
                ? "Add and remove instructor profiles"
                : ($role === "Registrar"
                    ? "Add and view instructor profiles"
                    : "Search and view instructor profiles") ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="table-toolbar">
        <form
            method="GET"
            action="instructors.php"
            class="instructor-search-form">
            <input
                class="search-input"
                type="search"
                name="search"
                placeholder="Search by instructor name..."
                value="<?= htmlspecialchars($search) ?>"
                aria-label="Search instructors">

            <button class="btn btn-outline" type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a class="btn btn-outline" href="instructors.php">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if (in_array($role, ["Admin", "Registrar"], true)): ?>
            <button
                class="btn btn-primary"
                type="button"
                id="showInstructorForm">
                + Add instructor
            </button>
        <?php endif; ?>
    </div>

    <?php if (in_array($role, ["Admin", "Registrar"], true)): ?>
        <div class="instructor-add-area" id="instructorAddArea" hidden>
            <form
                method="POST"
                action="instructors.php"
                enctype="multipart/form-data"
                class="instructor-add-form">
                <input type="hidden" name="action" value="add">

                <div class="instructor-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="salutation">
                            Salutation
                        </label>

                        <select
                            class="form-control"
                            id="salutation"
                            name="salutation">
                            <option value="">Select salutation</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="firstname">
                            First name
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="firstname"
                            name="firstname"
                            maxlength="100"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="middlename">
                            Middle name
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="middlename"
                            name="middlename"
                            maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="lastname">
                            Last name
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="lastname"
                            name="lastname"
                            maxlength="100"
                            required>
                    </div>
                </div>

                <div class="form-group instructor-photo-field">
                    <label class="form-label" for="photo">
                        Profile photo
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        id="photo"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp">

                    <span class="instructor-help">
                        Optional. JPG, PNG, or WEBP. Maximum 2 MB.
                    </span>
                </div>

                <div class="form-actions">
                    <button
                        class="btn btn-outline"
                        type="button"
                        id="cancelInstructorForm">
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Save instructor
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>ID</th>
                    <th>Instructor name</th>

                    <?php if ($role === "Admin"): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($instructors)): ?>
                    <?php foreach ($instructors as $instructor): ?>
                        <?php
                        $photo = $instructor["PHOTO"] ?? "";

                        $fullName = trim(
                            ($instructor["SALUTATION"] ?? "") . " " .
                                ($instructor["FIRSTNAME"] ?? "") . " " .
                                ($instructor["MIDDLENAME"] ?? "") . " " .
                                ($instructor["LASTNAME"] ?? "")
                        );
                        ?>

                        <tr>
                            <td>
                                <div class="instructor-table-avatar">
                                    <?php if ($photo !== ""): ?>
                                        <img
                                            src="<?= htmlspecialchars($photo) ?>"
                                            alt="<?= htmlspecialchars($fullName) ?>">
                                    <?php else: ?>
                                        <span>
                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr($instructor["FIRSTNAME"], 0, 1) .
                                                        substr($instructor["LASTNAME"], 0, 1)
                                                )
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td><?= (int) $instructor["ID"] ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($fullName) ?>
                                </strong>
                            </td>

                            <?php if ($role === "Admin"): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="instructors.php"
                                        onsubmit="return confirm('Delete this instructor? This action cannot be undone.');">
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $instructor["ID"] ?>">

                                        <button
                                            class="btn btn-danger"
                                            type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $role === "Admin" ? 4 : 3 ?>">
                            <div class="empty-state">
                                <div class="empty-state-title">
                                    No instructors found
                                </div>

                                <p>
                                    <?= $search !== ""
                                        ? "Try searching for another name."
                                        : "Instructor profiles will appear here." ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (in_array($role, ["Admin", "Registrar"], true)): ?>
    <script>
        const instructorAddArea = document.getElementById("instructorAddArea");
        const showInstructorForm = document.getElementById("showInstructorForm");
        const cancelInstructorForm = document.getElementById("cancelInstructorForm");

        showInstructorForm.addEventListener("click", () => {
            instructorAddArea.hidden = !instructorAddArea.hidden;

            if (!instructorAddArea.hidden) {
                document.getElementById("firstname").focus();
            }
        });

        cancelInstructorForm.addEventListener("click", () => {
            instructorAddArea.hidden = true;
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>