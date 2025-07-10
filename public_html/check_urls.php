<?php
require_once 'config.php';

echo "<h2>检查数据库中的URL格式</h2>";

$query = "SELECT id, title, url FROM links ORDER BY id DESC LIMIT 10";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>标题</th><th>URL</th><th>URL格式</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $url = $row['url'];
        $format = '';
        
        if (strpos($url, 'http://') === 0) {
            $format = 'HTTP完整URL';
        } elseif (strpos($url, 'https://') === 0) {
            $format = 'HTTPS完整URL';
        } elseif (strpos($url, '//') === 0) {
            $format = '协议相对URL';
        } elseif (strpos($url, '/') === 0) {
            $format = '绝对路径';
        } else {
            $format = '相对路径或其他';
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($url) . "</td>";
        echo "<td>" . $format . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>数据库中暂无数据</p>";
}

echo "<p><a href='admin.php'>返回管理页面</a></p>";

mysqli_close($conn);
?>
