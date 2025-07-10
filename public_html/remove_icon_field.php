<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';

$message = '';
$error = '';

if (isset($_POST['remove_icon'])) {
  try {
    // 检查icon字段是否存在
    $check_query = "SHOW COLUMNS FROM links LIKE 'icon'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
      // 删除icon字段
      $drop_query = "ALTER TABLE links DROP COLUMN icon";
      if (mysqli_query($conn, $drop_query)) {
        $message = "✅ 成功移除icon字段！";
      } else {
        $error = "❌ 移除icon字段失败: " . mysqli_error($conn);
      }
    } else {
      $message = "ℹ️ icon字段已经不存在了";
    }
  } catch (Exception $e) {
    $error = "❌ 操作失败: " . $e->getMessage();
  }
}

// 检查当前数据库结构
$columns_query = "SHOW COLUMNS FROM links";
$columns_result = mysqli_query($conn, $columns_query);
$columns = [];
while ($row = mysqli_fetch_assoc($columns_result)) {
  $columns[] = $row;
}

$has_icon = false;
foreach ($columns as $column) {
  if ($column['Field'] === 'icon') {
    $has_icon = true;
    break;
  }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>移除图标字段 - 晓风的个人主页</title>
  <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      color: #1a202c;
      padding: 20px;
      line-height: 1.6;
    }
    .container {
      max-width: 800px;
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
    .btn-danger { background: #dc2626; color: white; }
    .btn-secondary { background: #e2e8f0; color: #4a5568; }
    .btn:hover { transform: translateY(-1px); }
    .alert {
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
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
    .warning-box {
      background: #fef3c7;
      border: 1px solid #fcd34d;
      color: #92400e;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🗑️ 移除图标字段</h1>
      <p>从数据库中完全移除icon字段</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      <strong>ℹ️ 当前数据库状态</strong><br>
      Icon字段状态: <?= $has_icon ? '✅ 存在' : '❌ 不存在' ?>
    </div>

    <h3>📋 当前数据库结构</h3>
    <table>
      <thead>
        <tr>
          <th>字段名</th>
          <th>类型</th>
          <th>是否为空</th>
          <th>默认值</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($columns as $column): ?>
          <tr <?= $column['Field'] === 'icon' ? 'style="background: #fee2e2;"' : '' ?>>
            <td><?= htmlspecialchars($column['Field']) ?></td>
            <td><?= htmlspecialchars($column['Type']) ?></td>
            <td><?= $column['Null'] === 'YES' ? '是' : '否' ?></td>
            <td><?= htmlspecialchars($column['Default'] ?? 'NULL') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($has_icon): ?>
      <div class="warning-box">
        <h4>⚠️ 重要提醒</h4>
        <ul>
          <li>此操作将永久删除数据库中的icon字段</li>
          <li>所有现有的图标数据将丢失</li>
          <li>此操作不可撤销，请确保已备份数据</li>
          <li>建议先导出数据作为备份</li>
        </ul>
      </div>

      <form method="post" style="text-align: center; margin: 30px 0;">
        <button type="submit" name="remove_icon" class="btn btn-danger" 
                onclick="return confirm('确定要删除icon字段吗？此操作不可撤销！\n\n建议先备份数据库。')">
          🗑️ 确认删除icon字段
        </button>
      </form>
    <?php else: ?>
      <div class="alert alert-success">
        <strong>✅ 图标字段已移除</strong><br>
        数据库中已经没有icon字段了，清理工作已完成。
      </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
      <a href="admin.php" class="btn btn-secondary">← 返回管理页面</a>
      <a href="export.php" class="btn btn-secondary">📥 导出备份</a>
    </div>
  </div>
</body>
</html>
