<?php
session_start();

$valid_user = "admin";
$valid_pass = "xf2025top";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// 简单验证（可扩展为数据库验证）
if ($username === $valid_user && $password === $valid_pass) {
    $_SESSION['logged_in'] = true;
    header("Location: admin.php");
    exit;
} else {
    // 登录失败，跳回登录页
    header("Location: login.php?error=1");
    exit;
}
?>
