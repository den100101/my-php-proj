<?php
require_once __DIR__ . "/config/db.php";

if (!empty($_SESSION["USER_ID"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT ID, USERNAME, PASSWORD, USERTYPE, IMAGE_PATH
                 FROM tbl_account
                 WHERE USERNAME = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $account = $result->fetch_assoc();
            $stmt->close();

            if (
                $account &&
                password_verify($password, $account["PASSWORD"])
            ) {
                session_regenerate_id(true);

                $_SESSION["USER_ID"] = $account["ID"];
                $_SESSION["USERNAME"] = $account["USERNAME"];
                $_SESSION["USERTYPE"] = $account["USERTYPE"];
                $_SESSION["IMAGE_PATH"] = $account["IMAGE_PATH"] ?? "";

                header("Location: dashboard.php");
                exit();
            }

            $error = "Invalid username or password.";
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $error = "Unable to sign in right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | AFTERCLASS</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 72px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--text-soft);
            font-size: 11px;
            font-weight: 650;
        }

        .login-brand-link {
            display: inline-flex;
        }

        .login-error {
            margin-bottom: 20px;
        }

        .login-hint {
            margin-top: 24px;
            color: var(--text-soft);
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="login-page">

        <section class="login-showcase">

            <a class="brand login-brand-link" href="login.php">
                <div class="brand-mark">A.</div>

                <div>
                    <div class="brand-name">AFTERCLASS</div>
                    <div class="brand-tagline">Quietly ahead.</div>
                </div>
            </a>

            <div class="login-message">
                <div class="page-eyebrow">A space for progress</div>

                <h1>Make your mark.<br>On your terms.</h1>

                <p>
                    Your academic workspace, designed to keep things
                    organized, focused, and moving forward. Less clutter.
                    More progress.
                </p>
            </div>

            <div class="login-quote">
                Consistency speaks louder than noise.
            </div>

        </section>

        <section class="login-form-side">

            <form class="login-form" method="POST" action="login.php">

                <div class="page-eyebrow">Welcome back</div>

                <h2>Sign in to continue.</h2>

                <p class="login-description">
                    Enter your account details to access your workspace.
                </p>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-error login-error" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="username">
                        Username
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($username) ?>"
                        maxlength="30"
                        autocomplete="username"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                    </label>

                    <div class="password-wrapper">
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required>

                        <button
                            class="password-toggle"
                            type="button"
                            id="togglePassword"
                            aria-controls="password"
                            aria-pressed="false">
                            Show
                        </button>
                    </div>
                </div>

                <button
                    class="btn btn-primary login-submit"
                    type="submit">
                    Sign in
                    <span aria-hidden="true">→</span>
                </button>

                <div class="login-hint">
                    Authorized accounts only.
                </div>

            </form>

        </section>

    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function() {
            const showPassword = passwordInput.type === "password";

            passwordInput.type = showPassword ? "text" : "password";
            togglePassword.textContent = showPassword ? "Hide" : "Show";
            togglePassword.setAttribute("aria-pressed", String(showPassword));
        });
    </script>

</body>

</html>