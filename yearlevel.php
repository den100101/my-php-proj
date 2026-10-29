<?php
require_once __DIR__ . "/includes/auth.php";
requirePageAccess("Year Level");

$role = currentRole();

$pageTitle = "Year Levels";
$pageDescription = "Organize academic levels in one clear workspace.";

$message = "";
$messageType = "success";

/*
|--------------------------------------------------------------------------
| Handle add and delete
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";


    if ($action === "add") {
        requireRole(["Admin"]);

        $yearLevel = trim($_POST["year_level"] ?? "");
        $yearLevelName = trim($_POST["yearlevel_name"] ?? "");

        if (
            $yearLevel === "" ||
            mb_strlen($yearLevel) > 50 ||
            $yearLevelName === "" ||
            mb_strlen($yearLevelName) > 100
        ) {
            $message = "Complete both fields using the allowed character limits.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO tbl_yearlevel (YEAR_LEVEL, YEARLEVEL_NAME)
                     VALUES (?, ?)"
                );

                $stmt->bind_param("ss", $yearLevel, $yearLevelName);
                $stmt->execute();
                $stmt->close();

                $message = "Year level added successfully.";
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());

                if ((int) $e->getCode() === 1062) {
                    $message = "That year level already exists.";
                } else {
                    $message = "Unable to add the year level. Check your database constraints.";
                }

                $messageType = "error";
            }
        }
    }
}
/*
|--------------------------------------------------------------------------
| Search and retrieve year levels
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$yearLevels = [];

try {
    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt = $conn->prepare(
            "SELECT ID, YEAR_LEVEL, YEARLEVEL_NAME
             FROM tbl_yearlevel
             WHERE YEAR_LEVEL LIKE ?
                OR YEARLEVEL_NAME LIKE ?
             ORDER BY ID ASC"
        );

        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $yearLevels[] = $row;
        }

        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT ID, YEAR_LEVEL, YEARLEVEL_NAME
             FROM tbl_yearlevel
             ORDER BY ID ASC"
        );

        while ($row = $result->fetch_assoc()) {
            $yearLevels[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $message = "Unable to load year levels. Check your tbl_yearlevel columns.";
    $messageType = "error";
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card-grid yearlevel-stats">
    <article class="card">
        <div class="stat-label">Year levels found</div>

        <div class="stat-value">
            <?= number_format(count($yearLevels)) ?>
        </div>

        <div class="stat-note">
            <?= $search !== "" ? "Matching search results" : "Available academic levels" ?>
        </div>
    </article>

    <article class="card">
        <div class="stat-label">Your access</div>

        <div class="stat-value yearlevel-access">
            <?= $role === "Admin" ? "Full access" : "View only" ?>
        </div>

        <div class="stat-note">
            <?= $role === "Admin"
                ? "Add and remove year levels"
                : "Search and view academic levels" ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="table-toolbar">
        <form
            method="GET"
            action="yearlevel.php"
            class="yearlevel-search-form">
            <input
                class="search-input"
                type="search"
                name="search"
                placeholder="Search year levels..."
                value="<?= htmlspecialchars($search) ?>"
                aria-label="Search year levels">

            <button class="btn btn-outline" type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a class="btn btn-outline" href="yearlevel.php">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if ($role === "Admin"): ?>
            <button
                class="btn btn-primary"
                type="button"
                id="showYearLevelForm">
                + Add year level
            </button>
        <?php endif; ?>
    </div>

    <?php if ($role === "Admin"): ?>
        <div class="yearlevel-add-area" id="yearLevelAddArea" hidden>
            <form method="POST" action="yearlevel.php" class="yearlevel-add-form">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="year_level">
                        Year level code
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="year_level"
                        name="year_level"
                        placeholder="e.g. 1, 2, 3, 4"
                        maxlength="50"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="yearlevel_name">
                        Year level name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="yearlevel_name"
                        name="yearlevel_name"
                        placeholder="e.g. First Year"
                        maxlength="100"
                        required>
                </div>

                <p class="yearlevel-help">
                    Use a consistent code and name for each academic level.
                </p>

                <div class="form-actions">
                    <button
                        class="btn btn-outline"
                        type="button"
                        id="cancelYearLevelForm">
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Save year level
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
                    <th>Year level code</th>
                    <th>Year level name</th>

                    <?php if ($role === "Admin"): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($yearLevels)): ?>
                    <?php foreach ($yearLevels as $yearLevel): ?>
                        <tr>
                            <td><?= (int) $yearLevel["ID"] ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($yearLevel["YEAR_LEVEL"]) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($yearLevel["YEARLEVEL_NAME"]) ?>
                            </td>

                            <?php if ($role === "Admin"): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="yearlevel.php"
                                        onsubmit="return confirm('Delete this year level? This action cannot be undone.');">
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $yearLevel["ID"] ?>">

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
                                    No year levels found
                                </div>

                                <p>
                                    <?= $search !== ""
                                        ? "Try another search term."
                                        : "There are no academic levels to display yet." ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($role === "Admin"): ?>
    <script>
        const yearLevelAddArea = document.getElementById("yearLevelAddArea");
        const showYearLevelForm = document.getElementById("showYearLevelForm");
        const cancelYearLevelForm = document.getElementById("cancelYearLevelForm");

        showYearLevelForm.addEventListener("click", () => {
            yearLevelAddArea.hidden = !yearLevelAddArea.hidden;

            if (!yearLevelAddArea.hidden) {
                document.getElementById("year_level").focus();
            }
        });

        cancelYearLevelForm.addEventListener("click", () => {
            yearLevelAddArea.hidden = true;
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>