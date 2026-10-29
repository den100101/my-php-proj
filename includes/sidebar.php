<?php
$role = $_SESSION["USERTYPE"] ?? "Student";
$username = $_SESSION["USERNAME"] ?? "User";
$imagePath = $_SESSION["IMAGE_PATH"] ?? "";

$currentPage = basename($_SERVER["PHP_SELF"]);

$navigation = [
    [
        "label" => "Overview",
        "icon" => "◫",
        "url" => "dashboard.php",
        "roles" => ["Admin", "Registrar", "Department Head", "Dean", "Instructor", "Student"]
    ],
    [
        "label" => "Subjects",
        "icon" => "▤",
        "url" => "subjects.php",
        "roles" => ["Admin", "Registrar", "Department Head", "Dean", "Instructor", "Student"]
    ],
    [
        "label" => "Terms",
        "icon" => "◷",
        "url" => "term.php",
        "roles" => ["Admin", "Registrar"]
    ],
    [
        "label" => "School Year",
        "icon" => "▦",
        "url" => "schoolyear.php",
        "roles" => ["Admin", "Registrar"]
    ],
    [
        "label" => "Sections",
        "icon" => "▥",
        "url" => "section.php",
        "roles" => ["Admin", "Department Head", "Dean", "Instructor"]
    ],
    [
        "label" => "Year Levels",
        "icon" => "⇧",
        "url" => "yearlevel.php",
        "roles" => ["Admin", "Department Head", "Dean"]
    ],
    [
        "label" => "Instructors",
        "icon" => "♙",
        "url" => "instructors.php",
        "roles" => ["Admin", "Registrar", "Department Head", "Dean"]
    ],
    [
        "label" => "My Profile",
        "url" => "profile.php",
        "icon" => "user",
        "roles" => [
            "Admin",
            "Registrar",
            "Dean",
            "Department Head",
            "Instructor",
            "Student"
        ]
    ],
    [
        "label" => "Settings",
        "url" => "settings.php",
        "icon" => "settings",
        "roles" => [
            "Admin",
            "Registrar",
            "Dean",
            "Department Head",
            "Instructor",
            "Student"
        ]
    ],
];

$initial = strtoupper(substr($username, 0, 1));
?>

<aside class="sidebar">
    <a class="brand" href="dashboard.php">
        <div class="brand-mark">A.</div>
        <div>
            <div class="brand-name">AFTERCLASS</div>
            <div class="brand-tagline">Quietly ahead.</div>
        </div>
    </a>

    <div class="nav-label">Workspace</div>

    <nav class="sidebar-nav">
        <?php foreach ($navigation as $item): ?>
            <?php if (in_array($role, $item["roles"], true)): ?>
                <?php
                $isActive = $currentPage === $item["url"];
                ?>
                <a
                    class="nav-link <?= $isActive ? 'active' : '' ?>"
                    href="<?= htmlspecialchars($item["url"]) ?>"
                    <?= $isActive ? 'aria-current="page"' : '' ?>>

                    <span><?= htmlspecialchars($item["label"]) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="nav-link logout-link">
        <span class="nav-icon">↪</span>
        <span>Log out</span>
    </a>
    <div class="sidebar-profile">
        <div class="profile-avatar">
            <?php if ($imagePath !== ""): ?>
                <img
                    src="<?= htmlspecialchars($imagePath) ?>"
                    alt="Profile photo">
            <?php else: ?>
                <?= htmlspecialchars($initial) ?>
            <?php endif; ?>
        </div>


        <div class="profile-info">
            <div class="profile-name">
                <?= htmlspecialchars($username) ?>
            </div>
            <div class="profile-role">
                <?= htmlspecialchars($role) ?>
            </div>
        </div>
    </div>
</aside>