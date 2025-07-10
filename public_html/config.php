<?php
$host = "mysql15.serv00.com";
$db = "m11669_xf17_links";
$user = "m11669_xf17";
$pass = "Hu959211192";

$conn = mysqli_connect($host, $user, $pass, $db);

// 检查连接是否成功
if (!$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}

// 设置字符集为 UTF-8
mysqli_set_charset($conn, 'utf8mb4');
?>
