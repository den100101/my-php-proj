<?php
require_once __DIR__ . "/includes/auth.php";
requirePageAccess("Section");

$role = currentRole();

$pageTitle = "Sections";
$pageDescription = "Keep class sections clear, organized, and easy to find.";

$message = "";
$messageType = "success";

/*
|--------------------------------------------------------------------------
| Handle form submissions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        requireRole(["Admin"]);

        $sectionName = trim($_POST["section_name"] ?? "");

        if ($sectionName === "" || mb_strlen($sectionName) > 100) {
            $message = "Enter a section name with no more than 100 characters.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO tbl_section (SECTION_NAME) VALUES (?)"
                );

                $stmt->bind_param("s", $sectionName);
                $stmt->execute();
                $stmt->close();

                $message = "Section added successfully.";
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());

                if ((int) $e->getCode() === 1062) {
                    $message = "That section already exists.";
                } else {
                    $message = "Unable to add the section. Check your database columns.";
                }

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
            $message = "Invalid section selected.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare(
                    "DELETE FROM tbl_section WHERE ID = ?"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Section deleted successfully.";
                } else {
                    $message = "Section not found.";
                    $messageType = "error";
                }

                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                error_log($e->getMessage());
                $message = "Unable to delete this section. It may be linked to other records.";
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
| Search and retrieve sections
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$sections = [];

try {
    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt = $conn->prepare(
            "SELECT ID, SECTION_NAME
             FROM tbl_section
             WHERE SECTION_NAME LIKE ?
             ORDER BY SECTION_NAME ASC"
        );

        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }

        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT ID, SECTION_NAME
             FROM tbl_section
             ORDER BY SECTION_NAME ASC"
        );

        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $message = "Unable to load sections. Check your tbl_section columns.";
    $messageType = "error";
}

require __DIR__ . "/includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $messageType === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card-grid section-stats">
    <article class="card">
        <div class="stat-label">Sections found</div>

        <div class="stat-value">
            <?= number_format(count($sections)) ?>
        </div>

        <div class="stat-note">
            <?= $search !== "" ? "Matching search results" : "Available sections" ?>
        </div>
    </article>

    <article class="card">
        <div class="stat-label">Your access</div>

        <div class="stat-value section-access">
            <?= $role === "Admin" ? "Full access" : "View only" ?>
        </div>

        <div class="stat-note">
            <?= $role === "Admin"
                ? "Add and remove sections"
                : "Search and view section records" ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="table-toolbar">
        <form
            method="GET"
            action="section.php"
            class="section-search-form">
            <input
                class="search-input"
                type="search"
                name="search"
                placeholder="Search sections..."
                value="<?= htmlspecialchars($search) ?>"
                aria-label="Search sections">

            <button class="btn btn-outline" type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a class="btn btn-outline" href="section.php">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if ($role === "Admin"): ?>
            <button
                class="btn btn-primary"
                type="button"
                id="showSectionForm">
                + Add section
            </button>
        <?php endif; ?>
    </div>

    <?php if ($role === "Admin"): ?>
        <div class="section-add-area" id="sectionAddArea" hidden>
            <form method="POST" action="section.php" class="section-add-form">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="section_name">
                        Section name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="section_name"
                        name="section_name"
                        placeholder="e.g. BSIT 4A"
                        maxlength="100"
                        required>
                </div>

                <div class="form-actions">
                    <button
                        class="btn btn-outline"
                        type="button"
                        id="cancelSectionForm">
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Save section
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
                    <th>Section name</th>
                    <th>Record type</th>

                    <?php if ($role === "Admin"): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><?= (int) $section["ID"] ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($section["SECTION_NAME"]) ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge badge-accent">
                                    Class section
                                </span>
                            </td>

                            <?php if ($role === "Admin"): ?>
                                <td>
                                    <form
                                        method="POST"
                                        action="section.php"
                                        onsubmit="return confirm('Delete this section? This action cannot be undone.');">
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $section["ID"] ?>">

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
                                    No sections found
                                </div>

                                <p>
                                    <?= $search !== ""
                                        ? "Try another search term."
                                        : "There are no section records to display yet." ?>
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
        const sectionAddArea = document.getElementById("sectionAddArea");
        const showSectionForm = document.getElementById("showSectionForm");
        const cancelSectionForm = document.getElementById("cancelSectionForm");

        showSectionForm.addEventListener("click", () => {
            sectionAddArea.hidden = !sectionAddArea.hidden;

            if (!sectionAddArea.hidden) {
                document.getElementById("section_name").focus();
            }
        });

        cancelSectionForm.addEventListener("click", () => {
            sectionAddArea.hidden = true;
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>