<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}
// $adminUsername is available in all admin pages after this include
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
