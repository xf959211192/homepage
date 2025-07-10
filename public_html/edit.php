<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}
require_once 'config.php';

// 设置字符编码
header('Content-Type: text/html; charset=UTF-8');
mysqli_set_charset($conn, 'utf8mb4');

$id = intval($_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM links WHERE id=$id");
$link = mysqli_fetch_assoc($res);

// 检查 sort 字段是否存在
$check_sort = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'sort'");
$has_sort = mysqli_num_rows($check_sort) > 0;

// 获取现有分类（按排序显示）
$categories_query = "
    SELECT DISTINCT l.category, COALESCE(c.sort_order, 999) as sort_order
    FROM links l
    LEFT JOIN categories c ON l.category = c.name
    WHERE l.category IS NOT NULL AND l.category != ''
    ORDER BY sort_order ASC, l.category ASC
";
$categories_result = mysqli_query($conn, $categories_query);
$existing_categories = [];
while ($cat_row = mysqli_fetch_assoc($categories_result)) {
    $existing_categories[] = $cat_row['category'];
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = mysqli_real_escape_string($conn, $_POST['title']);
  $url = formatUrl($_POST['url']); // 格式化URL
  $url = mysqli_real_escape_string($conn, $url);
  $category = mysqli_real_escape_string($conn, $_POST['category']);
  $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
  $sort = intval($_POST['sort'] ?? 0);

  // 检查字段是否存在
  $check_sort = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'sort'");
  $has_sort = mysqli_num_rows($check_sort) > 0;
  $check_desc = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'description'");
  $has_desc = mysqli_num_rows($check_desc) > 0;

  // 构建更新SQL
  if ($has_sort && $has_desc) {
    mysqli_query($conn, "UPDATE links SET title='$title', url='$url', category='$category', description='$description', sort=$sort WHERE id=$id");
  } elseif ($has_sort) {
    mysqli_query($conn, "UPDATE links SET title='$title', url='$url', category='$category', sort=$sort WHERE id=$id");
  } elseif ($has_desc) {
    mysqli_query($conn, "UPDATE links SET title='$title', url='$url', category='$category', description='$description' WHERE id=$id");
  } else {
    mysqli_query($conn, "UPDATE links SET title='$title', url='$url', category='$category' WHERE id=$id");
  }
  header("Location: admin.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>编辑链接 - 晓风的个人主页</title>
  <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
  <link rel="apple-touch-icon" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
  <style>
    :root {
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --bg-card: #ffffff;
      --text-primary: #1a202c;
      --text-secondary: #4a5568;
      --text-muted: #718096;
      --accent: #3182ce;
      --accent-hover: #2c5aa0;
      --success: #38a169;
      --border: #e2e8f0;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    [data-theme="dark"] {
      --bg-primary: #1a202c;
      --bg-secondary: #2d3748;
      --bg-card: #2d3748;
      --text-primary: #f7fafc;
      --text-secondary: #e2e8f0;
      --text-muted: #a0aec0;
      --accent: #63b3ed;
      --accent-hover: #4299e1;
      --success: #68d391;
      --border: #4a5568;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
      --gradient: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .edit-container {
      background: var(--bg-card);
      border-radius: 16px;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border);
      width: 100%;
      max-width: 500px;
      overflow: hidden;
    }

    .edit-header {
      background: var(--gradient);
      color: white;
      padding: 25px;
      text-align: center;
    }

    .edit-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .edit-header p {
      opacity: 0.9;
      font-size: 0.9rem;
    }

    .edit-content {
      padding: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-weight: 500;
      color: var(--text-secondary);
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    .form-input {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--border);
      border-radius: 8px;
      background: var(--bg-primary);
      color: var(--text-primary);
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 500;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
    }

    .btn-primary {
      background: var(--accent);
      color: white;
    }

    .btn-primary:hover {
      background: var(--accent-hover);
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: var(--bg-primary);
      color: var(--text-secondary);
      border: 2px solid var(--border);
      margin-top: 10px;
    }

    .btn-secondary:hover {
      background: var(--border);
      transform: translateY(-1px);
    }

    .actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 25px;
    }

    .theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--bg-card);
      border: 2px solid var(--border);
      border-radius: 50px;
      padding: 10px 15px;
      cursor: pointer;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      z-index: 1000;
      color: var(--text-primary);
      font-size: 1rem;
    }

    .theme-toggle:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    @media (max-width: 480px) {
      .edit-container {
        margin: 10px;
      }

      .edit-content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="theme-toggle" onclick="toggleTheme()" title="切换主题">
    <span class="theme-icon">🌓</span>
  </div>

  <div class="edit-container">
    <div class="edit-header">
      <h2>✏️ 编辑链接</h2>
      <p>修改链接信息</p>
    </div>

    <div class="edit-content">
      <form method="post">
        <div class="form-group">
          <label class="form-label">标题 *</label>
          <input name="title" class="form-input" value="<?= htmlspecialchars($link['title']) ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label">链接地址 *</label>
          <input name="url" class="form-input" value="<?= htmlspecialchars($link['url']) ?>" required onblur="formatUrlInput(this)" />
        </div>

        <div class="form-group">
          <label class="form-label">工具简介</label>
          <textarea name="description" class="form-input" placeholder="简单描述这个工具的功能和特点..." rows="3" style="resize: vertical; height: auto;"><?= htmlspecialchars($link['description'] ?? '') ?></textarea>
        </div>



        <div class="form-group">
          <label class="form-label">分类</label>
          <div style="display: flex; gap: 10px; align-items: end;">
            <select class="form-input" style="flex: 1;" onchange="handleCategorySelect(this)">
              <option value="">选择现有分类</option>
              <?php foreach ($existing_categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $link['category'] ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
              <?php endforeach; ?>
              <option value="__new__">+ 创建新分类</option>
            </select>
            <input name="category" class="form-input" value="<?= htmlspecialchars($link['category']) ?>" placeholder="或输入新分类" style="flex: 1;" id="categoryInput" />
          </div>
        </div>

        <?php if ($has_sort): ?>
        <div class="form-group">
          <label class="form-label">排序</label>
          <input name="sort" type="number" class="form-input" value="<?= $link['sort'] ?? 0 ?>" placeholder="数字越小越靠前" />
        </div>
        <?php endif; ?>

        <div class="actions">
          <button type="submit" class="btn btn-primary">
            <span>💾</span> 保存修改
          </button>
          <a href="admin.php" class="btn btn-secondary">
            <span>↩️</span> 返回管理页
          </a>
        </div>
      </form>
    </div>
  </div>

<script>
  // 主题切换功能
  function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('admin-theme', newTheme);

    // 更新图标
    const icon = document.querySelector('.theme-icon');
    icon.textContent = newTheme === 'dark' ? '☀️' : '🌓';
  }

  // 初始化主题
  function initTheme() {
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    const icon = document.querySelector('.theme-icon');
    if (icon) {
      icon.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
    }
  }

  // 切换 emoji 选择器显示
  function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.style.display = picker.style.display === 'none' ? 'grid' : 'none';
  }

  // 选择 emoji
  function selectEmoji(emoji) {
    const iconInput = document.getElementById('iconInput');
    iconInput.value = emoji;

    // 隐藏选择器
    document.getElementById('emojiPicker').style.display = 'none';

    // 添加视觉反馈
    iconInput.style.background = 'var(--success)';
    iconInput.style.color = 'white';

    setTimeout(() => {
      iconInput.style.background = '';
      iconInput.style.color = '';
    }, 1000);
  }

  // 处理分类选择
  function handleCategorySelect(select) {
    const categoryInput = document.getElementById('categoryInput');

    if (select.value === '__new__') {
      categoryInput.focus();
      categoryInput.placeholder = '请输入新分类名称';
      categoryInput.value = '';
      select.value = '';
    } else if (select.value) {
      categoryInput.value = select.value;
    }
  }



  // URL格式化函数
  function formatUrlInput(input) {
    let url = input.value.trim();

    if (!url) return;

    if (/^https?:\/\//.test(url)) {
      return;
    }

    if (url.startsWith('//')) {
      input.value = 'https:' + url;
      return;
    }

    if (url && !url.includes('://')) {
      input.value = 'https://' + url;
    }
  }



  // 页面加载完成后初始化
  document.addEventListener('DOMContentLoaded', function() {
    initTheme();

    // 添加加载动画
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s ease';
    setTimeout(() => {
      document.body.style.opacity = '1';
    }, 100);

    // 点击页面其他地方关闭 emoji 选择器
    document.addEventListener('click', function(e) {
      const picker = document.getElementById('emojiPicker');
      const toggleBtn = e.target.closest('button[onclick="toggleEmojiPicker()"]');
      const emojiBtn = e.target.closest('.emoji-btn');

      if (!toggleBtn && !emojiBtn && picker && !picker.contains(e.target)) {
        picker.style.display = 'none';
      }
    });
  });

  // 表单提交确认
  document.querySelector('form').addEventListener('submit', function(e) {
    const title = this.querySelector('[name="title"]').value;
    if (!confirm(`确定要保存对「${title}」的修改吗？`)) {
      e.preventDefault();
    }
  });
</script>
</body>
</html>
