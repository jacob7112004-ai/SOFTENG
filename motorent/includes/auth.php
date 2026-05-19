<?php
session_start();
function requireLogin() {
    if (!isset($_SESSION['staff_id'])) {
        header('Location: /motorent/login.php');
        exit;
    }
}
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function currentStaff() {
    return $_SESSION['staff_name'] ?? 'Staff';
}
function currentRole() {
    return $_SESSION['role'] ?? 'staff';
}
