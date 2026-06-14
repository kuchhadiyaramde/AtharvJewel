<?php
/**
 * One-time setup wizard — creates the database, tables, seed data, and admin user.
 * DELETE this file from your server after running it.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Connect WITHOUT selecting a database so we can CREATE DATABASE
$conn = new mysqli(
    $_POST['db_host'] ?? 'localhost',
    $_POST['db_user'] ?? 'root',
    $_POST['db_pass'] ?? '',
);

$step = 'form';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    $dbHost     = trim($_POST['db_host'] ?? 'localhost');
    $dbUser     = trim($_POST['db_user'] ?? 'root');
    $dbPass     = $_POST['db_pass'] ?? '';
    $adminUser  = trim($_POST['admin_username'] ?? '');
    $adminPass  = $_POST['admin_password'] ?? '';
    $adminConf  = $_POST['admin_confirm'] ?? '';

    // Validate
    if (!$adminUser || !$adminPass) {
        $messages[] = ['type' => 'danger', 'text' => 'Admin username and password are required.'];
    } elseif ($adminPass !== $adminConf) {
        $messages[] = ['type' => 'danger', 'text' => 'Passwords do not match.'];
    } elseif (strlen($adminPass) < 8) {
        $messages[] = ['type' => 'danger', 'text' => 'Password must be at least 8 characters.'];
    } else {
        // Connect to MySQL
        $conn = new mysqli($dbHost, $dbUser, $dbPass);
        if ($conn->connect_error) {
            $messages[] = ['type' => 'danger', 'text' => 'MySQL connection failed: ' . $conn->connect_error];
        } else {
            $conn->set_charset('utf8mb4');

            // Run SQL file
            $sql = file_get_contents(__DIR__ . '/database.sql');
            if ($conn->multi_query($sql)) {
                do { $conn->store_result(); } while ($conn->next_result());
            }
            if ($conn->error) {
                $messages[] = ['type' => 'warning', 'text' => 'DB setup note: ' . $conn->error];
            }

            // Select DB
            $conn->select_db('atharv_jewel');

            // Create admin user
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
            $stmt->bind_param('ss', $adminUser, $hash);
            if ($stmt->execute()) {
                // Update config.php
                $configContent = "<?php\ndefine('DB_HOST', " . var_export($dbHost, true) . ");\ndefine('DB_USER', " . var_export($dbUser, true) . ");\ndefine('DB_PASS', " . var_export($dbPass, true) . ");\ndefine('DB_NAME', 'atharv_jewel');\n\nfunction getDBConnection(): mysqli {\n    static \$conn = null;\n    if (\$conn === null) {\n        \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n        if (\$conn->connect_error) {\n            error_log('MySQL connection error: ' . \$conn->connect_error);\n            http_response_code(500);\n            exit('Database unavailable. Check config.php.');\n        }\n        \$conn->set_charset('utf8mb4');\n    }\n    return \$conn;\n}\n";
                file_put_contents(__DIR__ . '/config.php', $configContent);
                $messages[] = ['type' => 'success', 'text' => 'Setup complete! Admin user <strong>' . htmlspecialchars($adminUser) . '</strong> created.'];
                $step = 'done';
            } else {
                $messages[] = ['type' => 'danger', 'text' => 'Failed to create admin user: ' . $stmt->error];
            }
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atharv Jewel — Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: linear-gradient(135deg, #dd2476, #ff512f); min-height: 100vh; }
        .card { border: none; border-radius: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mb-4">
                <h2 class="text-white fw-bold">Atharv Jewel</h2>
                <p class="text-white-50">One-Time Setup Wizard</p>
            </div>
            <div class="card shadow-lg p-4">

                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
                <?php endforeach; ?>

                <?php if ($step === 'done'): ?>
                    <div class="text-center py-3">
                        <div class="display-4 text-success mb-3">✓</div>
                        <h4>Setup Successful</h4>
                        <p class="text-muted">Your Atharv Jewel site is ready.</p>
                        <div class="alert alert-warning text-start">
                            <strong>Security:</strong> Delete <code>setup.php</code> from your server now.
                        </div>
                        <a href="index.html" class="btn btn-outline-secondary me-2">View Site</a>
                        <a href="admin/" class="btn btn-danger">Go to Admin Panel</a>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <h6 class="text-uppercase text-muted mb-3 small fw-bold">Database Connection</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small">MySQL Host</label>
                                <input type="text" name="db_host" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Port</label>
                                <input type="text" class="form-control form-control-sm" value="3306" disabled>
                            </div>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label small">MySQL User</label>
                                <input type="text" name="db_user" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">MySQL Password</label>
                                <input type="password" name="db_pass" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3 small fw-bold">Admin Account</h6>
                        <div class="mb-3">
                            <label class="form-label small">Username</label>
                            <input type="text" name="admin_username" class="form-control form-control-sm" required
                                   value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Password <span class="text-muted">(min 8 chars)</span></label>
                            <input type="password" name="admin_password" class="form-control form-control-sm" required minlength="8">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Confirm Password</label>
                            <input type="password" name="admin_confirm" class="form-control form-control-sm" required>
                        </div>

                        <button type="submit" name="setup" class="btn btn-danger w-100">Run Setup</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
</body>
</html>
