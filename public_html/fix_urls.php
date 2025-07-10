<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';

// URL格式化函数
function formatUrl($url) {
  $url = trim($url);
  
  if (preg_match('/^https?:\/\//', $url)) {
    return $url;
  }
  
  if (strpos($url, '//') === 0) {
    return 'https:' . $url;
  }
  
  if (!empty($url)) {
    return 'https://' . $url;
  }
  
  return $url;
}

$fixed_count = 0;
$total_count = 0;

if (isset($_POST['fix_urls'])) {
  // 获取所有链接
  $query = "SELECT id, url FROM links";
  $result = mysqli_query($conn, $query);
  
  while ($row = mysqli_fetch_assoc($result)) {
    $total_count++;
    $original_url = $row['url'];
    $formatted_url = formatUrl($original_url);
    
    if ($original_url !== $formatted_url) {
      $fixed_count++;
      $escaped_url = mysqli_real_escape_string($conn, $formatted_url);
      $update_query = "UPDATE links SET url = '$escaped_url' WHERE id = " . $row['id'];
      mysqli_query($conn, $update_query);
    }
  }
}

// 获取需要修复的URL列表
$preview_query = "SELECT id, title, url FROM links";
$preview_result = mysqli_query($conn, $preview_query);
$need_fix = [];

while ($row = mysqli_fetch_assoc($preview_result)) {
  $original = $row['url'];
  $formatted = formatUrl($original);
  if ($original !== $formatted) {
    $need_fix[] = [
      'id' => $row['id'],
      'title' => $row['title'],
      'original' => $original,
      'formatted' => $formatted
    ];
  }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>URL格式修复工具</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      color: #1a202c;
      padding: 20px;
      line-height: 1.6;
    }
    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #e2e8f0;
    }
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      text-decoration: none;
      display: inline-block;
      margin: 5px;
    }
    .btn-primary { background: #3182ce; color: white; }
    .btn-success { background: #38a169; color: white; }
    .btn-secondary { background: #e2e8f0; color: #4a5568; }
    .btn:hover { transform: translateY(-1px); }
    .alert {
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
    .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }
    th {
      background: #f8fafc;
      font-weight: 600;
    }
    .url-original { color: #dc2626; }
    .url-formatted { color: #059669; }
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }
    .stat-card {
      background: #f8fafc;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: #3182ce;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🔗 URL格式修复工具</h1>
      <p>检查并修复数据库中的URL格式，确保所有链接都是完整的独立URL</p>
    </div>

    <?php if (isset($_POST['fix_urls'])): ?>
      <div class="alert alert-success">
        <strong>✅ 修复完成！</strong><br>
        总共检查了 <?= $total_count ?> 个链接，修复了 <?= $fixed_count ?> 个URL格式问题。
      </div>
    <?php endif; ?>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-number"><?= count($need_fix) ?></div>
        <div>需要修复的URL</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= mysqli_num_rows(mysqli_query($conn, "SELECT COUNT(*) as total FROM links")) ?></div>
        <div>总链接数</div>
      </div>
    </div>

    <?php if (count($need_fix) > 0): ?>
      <div class="alert alert-warning">
        <strong>⚠️ 发现需要修复的URL</strong><br>
        以下链接的URL格式不完整，建议进行修复：
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>标题</th>
            <th>当前URL</th>
            <th>修复后URL</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($need_fix as $item): ?>
            <tr>
              <td><?= $item['id'] ?></td>
              <td><?= htmlspecialchars($item['title']) ?></td>
              <td class="url-original"><?= htmlspecialchars($item['original']) ?></td>
              <td class="url-formatted"><?= htmlspecialchars($item['formatted']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <form method="post" style="text-align: center; margin: 30px 0;">
        <button type="submit" name="fix_urls" class="btn btn-primary" onclick="return confirm('确定要修复这些URL吗？此操作不可撤销！')">
          🔧 立即修复所有URL
        </button>
      </form>
    <?php else: ?>
      <div class="alert alert-success">
        <strong>✅ 所有URL格式正确</strong><br>
        数据库中的所有链接都已经是完整的独立URL格式，无需修复。
      </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
      <a href="admin.php" class="btn btn-secondary">← 返回管理页面</a>
      <a href="check_urls.php" class="btn btn-secondary">📊 查看URL详情</a>
    </div>
  </div>
</body>
</html>
