<?php
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
  header("Location: admin.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>后台登录 - 晓风的个人主页</title>
  <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
  <link rel="apple-touch-icon" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
  <style>
    :root {
      --bg-primary: #f8fafc;
      --bg-card: #ffffff;
      --text-primary: #1a202c;
      --text-secondary: #4a5568;
      --text-muted: #718096;
      --accent: #3182ce;
      --accent-hover: #2c5aa0;
      --danger: #e53e3e;
      --border: #e2e8f0;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    [data-theme="dark"] {
      --bg-primary: #1a202c;
      --bg-card: #2d3748;
      --text-primary: #f7fafc;
      --text-secondary: #e2e8f0;
      --text-muted: #a0aec0;
      --accent: #63b3ed;
      --accent-hover: #4299e1;
      --danger: #fc8181;
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
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: var(--gradient);
      opacity: 0.05;
      z-index: -1;
    }

    .login-container {
      background: var(--bg-card);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border);
      width: 100%;
      max-width: 400px;
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

    .login-header {
      background: var(--gradient);
      color: white;
      padding: 40px 30px;
      text-align: center;
    }

    .login-header h2 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .login-header p {
      opacity: 0.9;
      font-size: 0.95rem;
    }

    .login-content {
      padding: 40px 30px;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
      background: rgba(229, 62, 62, 0.1);
      color: var(--danger);
      border: 1px solid rgba(229, 62, 62, 0.2);
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-label {
      display: block;
      font-weight: 500;
      color: var(--text-secondary);
      margin-bottom: 8px;
      font-size: 0.9rem;
    }

    .form-input {
      width: 100%;
      padding: 15px;
      border: 2px solid var(--border);
      border-radius: 10px;
      background: var(--bg-primary);
      color: var(--text-primary);
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
      transform: translateY(-1px);
    }

    .login-btn {
      width: 100%;
      padding: 15px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .login-btn:hover {
      background: var(--accent-hover);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    .theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--bg-card);
      border: 2px solid var(--border);
      border-radius: 50px;
      padding: 12px 16px;
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

    .footer {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
      color: var(--text-muted);
      font-size: 0.85rem;
    }

    @media (max-width: 480px) {
      .login-container {
        margin: 10px;
        border-radius: 16px;
      }

      .login-header {
        padding: 30px 20px;
      }

      .login-content {
        padding: 30px 20px;
      }

      .theme-toggle {
        top: 15px;
        right: 15px;
        padding: 10px 14px;
      }
    }

    /* 加载动画 */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-container {
      animation: fadeInUp 0.6s ease;
    }
  </style>
</head>
<body>
  <div class="theme-toggle" onclick="toggleTheme()" title="切换主题">
    <span class="theme-icon">🌓</span>
  </div>

  <div class="login-container">
    <div class="login-header">
      <h2>🔐 后台登录</h2>
      <p>晓风的个人主页管理系统</p>
    </div>

    <div class="login-content">
      <?php if (isset($_GET['error'])): ?>
        <div class="alert">
          <span>⚠️</span>
          用户名或密码错误，请重试！
        </div>
      <?php endif; ?>

      <form method="post" action="auth.php">
        <div class="form-group">
          <label class="form-label">用户名</label>
          <input type="text" name="username" class="form-input" placeholder="请输入用户名" required autofocus />
        </div>

        <div class="form-group">
          <label class="form-label">密码</label>
          <input type="password" name="password" class="form-input" placeholder="请输入密码" required />
        </div>

        <button type="submit" class="login-btn">
          <span>🚀</span> 立即登录
        </button>
      </form>

      <div class="footer">
        <p>© 2024 晓风的个人主页 - 安全登录</p>
      </div>
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

  // 页面加载完成后初始化
  document.addEventListener('DOMContentLoaded', function() {
    initTheme();

    // 表单提交时的加载状态
    document.querySelector('form').addEventListener('submit', function() {
      const btn = document.querySelector('.login-btn');
      btn.innerHTML = '<span>⏳</span> 登录中...';
      btn.disabled = true;
    });
  });
</script>
</body>
</html>
