<?php
require_once 'config.php';
$res = mysqli_query($conn, "SELECT * FROM links");
$links = [];
while ($row = mysqli_fetch_assoc($res)) $links[] = $row;
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="links.json"');
echo json_encode($links, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>
