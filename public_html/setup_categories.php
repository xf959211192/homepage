<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
  header("Location: login.php");
  exit;
}

require_once 'config.php';

$message = '';
$error = '';

// 处理设置请求
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['setup'])) {
    try {
        // 检查 categories 表是否已存在
        $check_categories_table = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
        if (mysqli_num_rows($check_categories_table) == 0) {
            // 创建 categories 表
            $sql = "CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("创建 categories 表失败：" . mysqli_error($conn));
            }
        }
        
        // 从现有链接中提取分类并插入到 categories 表
        $existing_categories = mysqli_query($conn, "SELECT DISTINCT category FROM links WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $sort_order = 10;
        $added_count = 0;
        
        while ($cat_row = mysqli_fetch_assoc($existing_categories)) {
            $category_name = mysqli_real_escape_string($conn, $cat_row['category']);
            
            // 检查分类是否已存在
            $check_query = "SELECT id FROM categories WHERE name = '$category_name'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) == 0) {
                mysqli_query($conn, "INSERT INTO categories (name, sort_order) VALUES ('$category_name', $sort_order)");
                $sort_order += 10;
                $added_count++;
            }
        }
        
        $message = "✅ 分类管理设置完成！共添加了 {$added_count} 个分类到管理中。";
        
    } catch (Exception $e) {
        $error = "❌ 设置失败：" . $e->getMessage();
    }
}

// 获取当前状态
$categories_table_exists = false;
$check_categories_table = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if (mysqli_num_rows($check_categories_table) > 0) {
    $categories_table_exists = true;
}

// 获取链接中的分类数量
$links_categories_query = "SELECT DISTINCT category FROM links WHERE category IS NOT NULL AND category != ''";
$links_categories_result = mysqli_query($conn, $links_categories_query);
$links_categories_count = mysqli_num_rows($links_categories_result);

// 获取已管理的分类数量
$managed_categories_count = 0;
if ($categories_table_exists) {
    $managed_query = "SELECT COUNT(*) as count FROM categories";
    $managed_result = mysqli_query($conn, $managed_query);
    $managed_categories_count = mysqli_fetch_assoc($managed_result)['count'];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理设置 - 晓风的个人主页</title>
    <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
    
    <!-- 引入Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 引入Google Fonts - Inter字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-2xl mx-auto px-4">
            <!-- 页面标题 -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">⚙️ 分类管理设置</h1>
                        <p class="text-gray-600 mt-2">初始化分类排序功能</p>
                    </div>
                    <a href="admin.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        ← 返回管理页
                    </a>
                </div>
            </div>

            <!-- 消息提示 -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- 当前状态 -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">📊 当前状态</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="font-medium text-gray-900">Categories 表</span>
                            <p class="text-sm text-gray-600">用于存储分类排序信息</p>
                        </div>
                        <div class="text-right">
                            <?php if ($categories_table_exists): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ 已创建
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ❌ 未创建
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="font-medium text-gray-900">链接中的分类</span>
                            <p class="text-sm text-gray-600">从现有链接中发现的分类数量</p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-semibold text-gray-900"><?= $links_categories_count ?></span>
                            <p class="text-xs text-gray-500">个分类</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="font-medium text-gray-900">已管理的分类</span>
                            <p class="text-sm text-gray-600">已添加到分类管理中的数量</p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-semibold text-gray-900"><?= $managed_categories_count ?></span>
                            <p class="text-xs text-gray-500">个分类</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 设置操作 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">🚀 初始化设置</h2>
                
                <?php if (!$categories_table_exists || $managed_categories_count < $links_categories_count): ?>
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="font-medium text-blue-900 mb-2">将要执行的操作：</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <?php if (!$categories_table_exists): ?>
                                    <li>• 创建 categories 数据表</li>
                                <?php endif; ?>
                                <li>• 将现有分类添加到分类管理中</li>
                                <li>• 设置默认排序顺序</li>
                                <li>• 启用分类排序功能</li>
                            </ul>
                        </div>
                        
                        <form method="post">
                            <input type="hidden" name="setup" value="1">
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                                    onclick="return confirm('确定要初始化分类管理功能吗？这将创建新的数据表并导入现有分类。')">
                                🚀 开始设置
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-6xl mb-4">✅</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">设置已完成</h3>
                        <p class="text-gray-600 mb-6">分类管理功能已经设置完成，您可以开始管理分类排序了。</p>
                        <div class="space-x-4">
                            <a href="category_manager.php" 
                               class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold">
                                📂 管理分类排序
                            </a>
                            <a href="admin.php" 
                               class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-semibold">
                                ← 返回管理页
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 说明信息 -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-medium text-yellow-800 mb-2">💡 功能说明</h3>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• 分类排序功能可以让您自定义前台页面中分类的显示顺序</li>
                    <li>• 数字越小的分类会显示在越前面的位置</li>
                    <li>• 支持拖拽调整分类顺序，操作简单直观</li>
                    <li>• 新添加的分类会自动加入到分类管理中</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
