<?php
require_once __DIR__ . "/includes/auth.php";
requirePageAccess("Subjects");

$role = currentRole();
$username = $_SESSION["USERNAME"] ?? "User";

$canManage = in_array(
    $role,
    ["Admin", "Registrar", "Department Head", "Dean"],
    true
);

$pageTitle = "Subjects";
$pageDescription = "Explore and manage your academic course catalog.";

$message = "";
$messageType = "success";

/*
|--------------------------------------------------------------------------
| Handle POST actions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$canManage) {
        http_response_code(403);
        exit("403 Forbidden — You cannot modify subjects.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $courseCode = strtoupper(trim($_POST["course_code"] ?? ""));
        $courseName = trim($_POST["course_name"] ?? "");
        $units = filter_var(
            $_POST["units"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $courseCode === "" ||
            $courseName === "" ||
            $units === false ||
            $units < 1 ||
            $units > 10
        ) {
            $message = "Enter a course code, subject name, and valid units (1–10).";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO tbl_course
                    (COURSE_CODE, COURSE_NAME, UNITS)
                    VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssi",
                    $courseCode,
                    $courseName,
                    $units
                );

                $stmt->execute();
                $stmt->close();

                $message = "Subject added successfully.";
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());

                if ((int) $e->getCode() === 1062) {
                    $message = "That course code already exists.";
                } else {
                    $message = "Unable to add the subject. Check your database columns.";
                }

                $messageType = "error";
            }
        }
    } elseif ($action === "delete") {
        if ($role !== "Admin") {
            http_response_code(403);
            exit("403 Forbidden — Only Admin can delete subjects.");
        }

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {
            $message = "Invalid subject selected.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM tbl_course WHERE ID = ?"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Subject deleted successfully.";
                } else {
                    $message = "Subject not found.";
                    $messageType = "error";
                }

                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());
                $message = "Unable to delete this subject. It may be linked to other records.";
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
| Load subjects
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$subjects = [];

try {
    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt = $conn->prepare(
            "SELECT ID, COURSE_CODE, COURSE_NAME, UNITS
             FROM tbl_course
             WHERE COURSE_CODE LIKE ?
                OR COURSE_NAME LIKE ?
             ORDER BY COURSE_CODE ASC"
        );

        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }

        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT ID, COURSE_CODE, COURSE_NAME, UNITS
             FROM tbl_course
             ORDER BY COURSE_CODE ASC"
        );

        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $message = "Unable to load subjects. Please check your tbl_course column names.";
    $messageType = "error";
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card-grid subject-stats">
    <article class="card">
        <div class="stat-label">Total subjects</div>
        <div class="stat-value"><?= number_format(count($subjects)) ?></div>
        <div class="stat-note">
            <?= $search !== "" ? "Matching search results" : "Displayed records" ?>
        </div>
    </article>

    <article class="card">
        <div class="stat-label">Your access</div>
        <div class="stat-value subject-access">
            <?= $canManage ? "Manage" : "View" ?>
        </div>
        <div class="stat-note">
            <?= $canManage ? "You can add subjects" : "Read-only access" ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="table-toolbar">
        <form method="GET" action="subjects.php" class="subject-search-form">
            <input
                class="search-input"
                type="search"
                name="search"
                placeholder="Search course code or subject..."
                value="<?= htmlspecialchars($search) ?>"
                aria-label="Search subjects">

            <button class="btn btn-outline" type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a class="btn btn-outline" href="subjects.php">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if ($canManage): ?>
            <button
                class="btn btn-primary"
                type="button"
                id="showAddForm">
                + Add subject
            </button>
        <?php endif; ?>
    </div>

    <?php if ($canManage): ?>
        <div class="subject-add-area" id="addSubjectArea" hidden>
            <form method="POST" action="subjects.php" class="subject-add-form">
                <input type="hidden" name="action" value="add">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="course_code">
                            Course code
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="course_code"
                            name="course_code"
                            maxlength="50"
                            placeholder="e.g. IT101"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="course_name">
                            Subject name
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="course_name"
                            name="course_name"
                            maxlength="255"
                            placeholder="e.g. Introduction to Computing"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="units">
                            Units
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            id="units"
                            name="units"
                            min="1"
                            max="10"
                            value="3"
                            required>
                    </div>
                </div>

                <div class="form-actions">
                    <button
                        class="btn btn-outline"
                        type="button"
                        id="cancelAddForm">
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Save subject
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Course code</th>
                    <th>Subject name</th>
                    <th>Units</th>

                    <?php if ($role === "Admin"): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (count($subjects) > 0): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td>
                                <?= (int) $subject["ID"] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($subject["COURSE_CODE"]) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($subject["COURSE_NAME"]) ?>
                            </td>

                            <td>
                                <span class="badge badge-accent">
                                    <?= (int) $subject["UNITS"] ?> units
                                </span>
                            </td>

                            <?php if ($role === "Admin"): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="subjects.php"
                                        onsubmit="return confirm('Delete this subject? This action cannot be undone.');">
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $subject["ID"] ?>">

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
                        <td colspan="<?= $role === "Admin" ? 5 : 4 ?>">
                            <div class="empty-state">
                                <div class="empty-state-title">
                                    No subjects found
                                </div>

                                <p>
                                    <?= $search !== ""
                                        ? "Try another search term."
                                        : "Subjects will appear here once records are added." ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    const addArea = document.getElementById("addSubjectArea");
    const showAddForm = document.getElementById("showAddForm");
    const cancelAddForm = document.getElementById("cancelAddForm");

    if (showAddForm && addArea) {
        showAddForm.addEventListener("click", () => {
            addArea.hidden = !addArea.hidden;

            if (!addArea.hidden) {
                document.getElementById("course_code").focus();
            }
        });
    }

    if (cancelAddForm && addArea) {
        cancelAddForm.addEventListener("click", () => {
            addArea.hidden = true;
        });
    }
</script>

<?php require __DIR__ . "/includes/footer.php"; ?>