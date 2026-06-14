<?php
session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $conn->query("UPDATE admin_users SET last_login = NOW() WHERE id = " . (int) $user['id']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Atharv Jewel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #dd2476 0%, #ff512f 100%); min-height: 100vh; }
        .card { border: none; border-radius: 1rem; }
        .btn-brand { background: linear-gradient(135deg, #dd2476, #ff512f); border: none; color: #fff; }
        .btn-brand:hover { opacity: .9; color: #fff; }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <div class="display-6 text-white mb-1">💎</div>
                <h3 class="text-white fw-bold">Atharv Jewel</h3>
                <p class="text-white-50">Admin Panel</p>
            </div>
            <div class="card shadow-lg p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-user text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-start-0 ps-0"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0" autocomplete="current-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 py-2 fw-semibold">
                        Sign In <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </form>
                <div class="text-center mt-3">
                    <a href="../index.html" class="text-muted small text-decoration-none">
                        <i class="fa fa-arrow-left me-1"></i>Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
