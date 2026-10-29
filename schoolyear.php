<?php
require_once __DIR__ . "/includes/auth.php";
requirePageAccess("School Year");

$role = currentRole();

$pageTitle = "School Years";
$pageDescription = "Keep academic years organized and accessible.";

$message = "";
$messageType = "success";

/*
|--------------------------------------------------------------------------
| Handle add and delete
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireRole(["Admin", "Registrar"]);

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $schoolYear = trim($_POST["school_year"] ?? "");

        if (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
            $message = "Enter a school year in YYYY-YYYY format, such as 2026-2027.";
            $messageType = "error";
        } else {
            [$startYear, $endYear] = array_map(
                "intval",
                explode("-", $schoolYear)
            );

            if ($endYear !== $startYear + 1) {
                $message = "The ending year must be one year after the starting year.";
                $messageType = "error";
            } else {
                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO tbl_schoolyear (SCHOOL_YEAR) VALUES (?)"
                    );

                    $stmt->bind_param("s", $schoolYear);
                    $stmt->execute();
                    $stmt->close();

                    $message = "School year added successfully.";
                } catch (mysqli_sql_exception $e) {
                    error_log($e->getMessage());

                    if ((int) $e->getCode() === 1062) {
                        $message = "That school year already exists.";
                    } else {
                        $message = "Unable to add the school year. Check your database columns.";
                    }

                    $messageType = "error";
                }
            }
        }
    } elseif ($action === "delete") {
        if ($role !== "Admin") {
            http_response_code(403);
            exit("403 Forbidden — Only Admin can delete school years.");
        }

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {
            $message = "Invalid school year selected.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM tbl_schoolyear WHERE ID = ?"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "School year deleted successfully.";
                } else {
                    $message = "School year not found.";
                    $messageType = "error";
                }

                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());
                $message = "Unable to delete this school year. It may be linked to other records.";
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
| Search and retrieve school years
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$schoolYears = [];

try {
    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt = $conn->prepare(
            "SELECT ID, SCHOOL_YEAR
             FROM tbl_schoolyear
             WHERE SCHOOL_YEAR LIKE ?
             ORDER BY SCHOOL_YEAR DESC"
        );

        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $schoolYears[] = $row;
        }

        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT ID, SCHOOL_YEAR
             FROM tbl_schoolyear
             ORDER BY SCHOOL_YEAR DESC"
        );

        while ($row = $result->fetch_assoc()) {
            $schoolYears[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $message = "Unable to load school years. Check your tbl_schoolyear columns.";
    $messageType = "error";
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card-grid schoolyear-stats">
    <article class="card">
        <div class="stat-label">Total school years</div>

        <div class="stat-value">
            <?= number_format(count($schoolYears)) ?>
        </div>

        <div class="stat-note">
            <?= $search !== "" ? "Matching search results" : "Displayed records" ?>
        </div>
    </article>

    <article class="card">
        <div class="stat-label">Your access</div>

        <div class="stat-value schoolyear-access">Manage</div>

        <div class="stat-note">
            <?= $role === "Admin"
                ? "Full school year management"
                : "Add and view school years" ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="table-toolbar">
        <form
            method="GET"
            action="schoolyear.php"
            class="schoolyear-search-form">
            <input
                class="search-input"
                type="search"
                name="search"
                placeholder="Search school years..."
                value="<?= htmlspecialchars($search) ?>"
                aria-label="Search school years">

            <button class="btn btn-outline" type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a class="btn btn-outline" href="schoolyear.php">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <button
            class="btn btn-primary"
            type="button"
            id="showSchoolYearForm">
            + Add school year
        </button>
    </div>

    <div class="schoolyear-add-area" id="schoolYearAddArea" hidden>
        <form method="POST" action="schoolyear.php" class="schoolyear-add-form">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label class="form-label" for="school_year">
                    School year
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="school_year"
                    name="school_year"
                    placeholder="2026-2027"
                    pattern="[0-9]{4}-[0-9]{4}"
                    maxlength="9"
                    title="Use the format YYYY-YYYY, for example 2026-2027."
                    required>

                <span class="schoolyear-help">
                    Use the format YYYY-YYYY.
                </span>
            </div>

            <div class="form-actions">
                <button
                    class="btn btn-outline"
                    type="button"
                    id="cancelSchoolYearForm">
                    Cancel
                </button>

                <button class="btn btn-primary" type="submit">
                    Save school year
                </button>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>School year</th>
                    <th>Format</th>

                    <?php if ($role === "Admin"): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($schoolYears)): ?>
                    <?php foreach ($schoolYears as $schoolYear): ?>
                        <tr>
                            <td><?= (int) $schoolYear["ID"] ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($schoolYear["SCHOOL_YEAR"]) ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge badge-accent">
                                    Academic year
                                </span>
                            </td>

                            <?php if ($role === "Admin"): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="schoolyear.php"
                                        onsubmit="return confirm('Delete this school year? This action cannot be undone.');">
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $schoolYear["ID"] ?>">

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
                                    No school years found
                                </div>

                                <p>
                                    <?= $search !== ""
                                        ? "Try another search term."
                                        : "Add a school year to start organizing academic records." ?>
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
    const schoolYearAddArea = document.getElementById("schoolYearAddArea");
    const showSchoolYearForm = document.getElementById("showSchoolYearForm");
    const cancelSchoolYearForm = document.getElementById("cancelSchoolYearForm");

    showSchoolYearForm.addEventListener("click", () => {
        schoolYearAddArea.hidden = !schoolYearAddArea.hidden;

        if (!schoolYearAddArea.hidden) {
            document.getElementById("school_year").focus();
        }
    });

    cancelSchoolYearForm.addEventListener("click", () => {
        schoolYearAddArea.hidden = true;
    });
</script>

<?php require __DIR__ . "/includes/footer.php"; ?>