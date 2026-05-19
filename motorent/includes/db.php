<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'motorent');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#a32d2d;background:#fcebeb;border-radius:8px;margin:40px auto;max-width:500px">
        <strong>Database connection failed.</strong><br>
        Make sure XAMPP MySQL is running and you have imported <code>motorent.sql</code>.<br><br>
        Error: ' . $conn->connect_error . '</div>');
}
$conn->set_charset("utf8mb4");
