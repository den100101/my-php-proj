<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();

$pageTitle = "Overview";
$pageDescription = "A clear view of your academic workspace.";

$role = currentRole();
$username = $_SESSION["USERNAME"] ?? "User";

$firstName = preg_split('/\s+/', trim($username))[0] ?? "there";
$hour = (int) date("G");

if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

$stats = [
    [
        "label" => "Subjects",
        "table" => "tbl_course",
        "icon" => "▤"
    ],
    [
        "label" => "Terms",
        "table" => "tbl_term",
        "icon" => "◷"
    ],
    [
        "label" => "School Years",
        "table" => "tbl_schoolyear",
        "icon" => "▦"
    ],
    [
        "label" => "Sections",
        "table" => "tbl_section",
        "icon" => "▥"
    ]
];

$counts = [];

foreach ($stats as $stat) {
    $table = $stat["table"];

    try {
        // Table names come only from the fixed list above.
        $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
        $counts[$table] = (int) $result->fetch_assoc()["total"];
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $counts[$table] = null;
    }
}

require __DIR__ . "/includes/header.php";
?>

<section class="welcome-panel">
    <div>
        <div class="page-eyebrow">Your space. Your progress.</div>

        <h2>
            <?= htmlspecialchars($greeting) ?>,
            <?= htmlspecialchars($firstName) ?>.
        </h2>

        <p>
            Stay focused on what matters. Your academic workspace
            is ready when you are.
        </p>
    </div>

    <div class="welcome-mark" aria-hidden="true">A.</div>
</section>

<section class="dashboard-section">
    <div class="section-heading">
        <div>
            <h2>At a glance</h2>
            <p>Your workspace, organized in one place.</p>
        </div>

        <span class="role-chip">
            <?= htmlspecialchars($role) ?>
        </span>
    </div>

    <div class="card-grid">
        <?php foreach ($stats as $stat): ?>
            <article class="card dashboard-stat">
                <div class="stat-top">
                    <span class="stat-label">
                        <?= htmlspecialchars($stat["label"]) ?>
                    </span>

                    <span class="stat-icon" aria-hidden="true">
                        <?= htmlspecialchars($stat["icon"]) ?>
                    </span>
                </div>

                <div class="stat-value">
                    <?php
                    $count = $counts[$stat["table"]];
                    echo $count === null
                        ? "—"
                        : number_format($count);
                    ?>
                </div>

                <div class="stat-note">Records in your system</div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-heading">
        <div>
            <h2>Quick access</h2>
            <p>Go straight to what you need.</p>
        </div>
    </div>

    <div class="quick-access-grid">
        <?php
        $quickLinks = [
            [
                "title" => "Subjects",
                "description" => "Browse and manage courses.",
                "url" => "subjects.php",
                "icon" => "▤",
                "roles" => [
                    "Admin",
                    "Registrar",
                    "Department Head",
                    "Dean",
                    "Instructor",
                    "Student"
                ]
            ],
            [
                "title" => "Terms",
                "description" => "Manage academic terms.",
                "url" => "term.php",
                "icon" => "◷",
                "roles" => ["Admin", "Registrar"]
            ],
            [
                "title" => "School Years",
                "description" => "Organize academic years.",
                "url" => "schoolyear.php",
                "icon" => "▦",
                "roles" => ["Admin", "Registrar"]
            ],
            [
                "title" => "Sections",
                "description" => "View and manage sections.",
                "url" => "section.php",
                "icon" => "▥",
                "roles" => [
                    "Admin",
                    "Department Head",
                    "Dean",
                    "Instructor"
                ]
            ],
            [
                "title" => "Year Levels",
                "description" => "Manage year-level records.",
                "url" => "yearlevel.php",
                "icon" => "⇧",
                "roles" => ["Admin", "Department Head", "Dean"]
            ],
            [
                "title" => "Instructors",
                "description" => "View instructor information.",
                "url" => "instructors.php",
                "icon" => "♙",
                "roles" => [
                    "Admin",
                    "Registrar",
                    "Department Head",
                    "Dean"
                ]
            ]
        ];
        ?>

        <?php foreach ($quickLinks as $link): ?>
            <?php if (in_array($role, $link["roles"], true)): ?>
                <a
                    class="quick-link"
                    href="<?= htmlspecialchars($link["url"]) ?>">
                    <span class="quick-link-icon" aria-hidden="true">
                        <?= htmlspecialchars($link["icon"]) ?>
                    </span>

                    <span class="quick-link-content">
                        <strong>
                            <?= htmlspecialchars($link["title"]) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($link["description"]) ?>
                        </span>
                    </span>

                    <span class="quick-link-arrow" aria-hidden="true">
                        →
                    </span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-note">
    <div class="note-symbol" aria-hidden="true">✳</div>

    <div>
        <strong>Quiet progress is still progress.</strong>
        <p>
            Keep your workspace organized and take things one step
            at a time. Consistency adds up.
        </p>
    </div>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>