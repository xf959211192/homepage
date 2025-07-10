<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

// 处理AJAX请求
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_link':
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $url = mysqli_real_escape_string($conn, $_POST['url']);
            $category = mysqli_real_escape_string($conn, $_POST['category']);
            $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
            
            // URL格式化
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }
            
            $query = "INSERT INTO links (title, url, category, description) VALUES ('$title', '$url', '$category', '$description')";
            if (mysqli_query($conn, $query)) {
                echo json_encode(['success' => true, 'message' => '链接添加成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '添加失败']);
            }
            exit;
            
        case 'delete_link':
            $id = intval($_POST['id']);
            $query = "DELETE FROM links WHERE id = $id";
            if (mysqli_query($conn, $query)) {
                echo json_encode(['success' => true, 'message' => '链接删除成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '删除失败']);
            }
            exit;

        case 'update_category_order':
            if (isset($_POST['category_order']) && is_array($_POST['category_order'])) {
                foreach ($_POST['category_order'] as $category_name => $sort_order) {
                    $category_name = mysqli_real_escape_string($conn, $category_name);
                    $sort_order = intval($sort_order);

                    // 检查分类是否存在，不存在则插入
                    $check_query = "SELECT id FROM categories WHERE name = '$category_name'";
                    $check_result = mysqli_query($conn, $check_query);

                    if (mysqli_num_rows($check_result) > 0) {
                        // 更新排序
                        mysqli_query($conn, "UPDATE categories SET sort_order = $sort_order WHERE name = '$category_name'");
                    } else {
                        // 插入新分类
                        mysqli_query($conn, "INSERT INTO categories (name, sort_order) VALUES ('$category_name', $sort_order)");
                    }
                }
                echo json_encode(['success' => true, 'message' => '分类排序已更新']);
            } else {
                echo json_encode(['success' => false, 'message' => '更新失败']);
            }
            exit;

        case 'delete_category':
            $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

            // 检查该分类下是否还有链接
            $links_count_query = "SELECT COUNT(*) as count FROM links WHERE category = '$category_name'";
            $links_count_result = mysqli_query($conn, $links_count_query);
            $links_count = mysqli_fetch_assoc($links_count_result)['count'];

            if ($links_count > 0) {
                echo json_encode(['success' => false, 'message' => "无法删除分类「{$category_name}」，该分类下还有 {$links_count} 个链接！"]);
            } else {
                // 删除分类
                if (mysqli_query($conn, "DELETE FROM categories WHERE name = '$category_name'")) {
                    echo json_encode(['success' => true, 'message' => '分类已删除']);
                } else {
                    echo json_encode(['success' => false, 'message' => '删除失败']);
                }
            }
            exit;
    }
}

// 获取统计数据
$stats_query = "SELECT COUNT(*) as total_links, COUNT(DISTINCT category) as total_categories FROM links WHERE category IS NOT NULL AND category != ''";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// 获取分页数据
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$total_query = "SELECT COUNT(*) as total FROM links";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $per_page);

// 获取链接数据
$links_query = "SELECT * FROM links ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$links_result = mysqli_query($conn, $links_query);
$links = [];
while ($row = mysqli_fetch_assoc($links_result)) {
    $links[] = $row;
}

// 获取分类数据
$categories_query = "SELECT DISTINCT category FROM links WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row['category'];
}

// 获取分类管理数据
$managed_categories_query = "
    SELECT
        c.name,
        c.sort_order,
        COUNT(l.id) as link_count
    FROM categories c
    LEFT JOIN links l ON c.name = l.category
    GROUP BY c.name, c.sort_order
    ORDER BY c.sort_order ASC, c.name ASC
";
$managed_categories_result = mysqli_query($conn, $managed_categories_query);
$managed_categories = [];
while ($row = mysqli_fetch_assoc($managed_categories_result)) {
    $managed_categories[] = $row;
}

// 获取没有在categories表中的分类（从links表中）
$orphan_categories_query = "
    SELECT DISTINCT l.category, COUNT(l.id) as link_count
    FROM links l
    LEFT JOIN categories c ON l.category = c.name
    WHERE l.category IS NOT NULL
    AND l.category != ''
    AND c.name IS NULL
    GROUP BY l.category
    ORDER BY l.category
";
$orphan_result = mysqli_query($conn, $orphan_categories_query);
$orphan_categories = [];
while ($row = mysqli_fetch_assoc($orphan_result)) {
    $orphan_categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 晓风的个人主页</title>
    <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* 简洁的动画效果 */
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* 简洁的悬停效果 */
        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* 加载动画 */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* 导航激活状态 */
        .nav-active {
            background-color: #2563eb;
            color: white;
        }

        /* 简洁的表格样式 */
        .table-row:hover {
            background-color: #f8fafc;
        }

        /* 侧边栏样式 */
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }

        .sidebar-overlay {
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body class="h-full font-sans antialiased bg-gray-50" x-data="adminApp()" x-init="init()">

    <!-- 移动端菜单按钮 -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-3 bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- 移动端遮罩层 -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 sidebar-overlay"
         x-cloak></div>

    <!-- 左侧边栏 -->
    <div class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 sidebar"
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <!-- Logo 区域 -->
        <div class="flex items-center justify-center h-16 px-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg"
                     class="w-8 h-8 rounded-lg" alt="Logo">
                <h1 class="text-lg font-bold text-gray-900">管理后台</h1>
            </div>
        </div>

        <!-- 用户信息 -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <img src="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg"
                         class="w-10 h-10 rounded-full ring-2 ring-blue-100" alt="用户头像">
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">晓风</p>
                    <p class="text-xs text-gray-500 truncate">管理员</p>
                </div>
            </div>
        </div>

        <!-- 导航菜单 -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                主要功能
            </div>

            <template x-for="item in navigation" :key="item.id">
                <button @click="setActiveView(item.id)"
                        class="w-full group flex items-center px-3 py-2 text-sm font-medium rounded-lg text-left transition-colors"
                        :class="activeView === item.id ? 'nav-active text-white' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'">
                    <span x-html="item.icon" class="mr-3 h-5 w-5 flex-shrink-0"></span>
                    <span x-text="item.name"></span>
                </button>
            </template>


        </nav>

        <!-- 底部操作 -->
        <div class="px-4 py-4 border-t border-gray-200 space-y-1">
            <a href="index.php" class="group flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:text-gray-900 hover:bg-gray-100">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                返回首页
            </a>
            <a href="logout.php" class="group flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:text-red-900 hover:bg-red-50">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                退出登录
            </a>
        </div>
    </div>

    <!-- 通知组件 -->
    <div x-show="notification.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 max-w-sm w-full z-50"
         x-cloak>
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg x-show="notification.type === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg x-show="notification.type === 'error'" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900" x-text="notification.message"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button @click="hideNotification()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 主内容区域 -->
    <div class="lg:pl-72">
        <!-- 顶部导航栏 -->
        <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                <div class="flex flex-1 items-center">
                    <h1 class="text-xl font-semibold text-gray-900" x-text="currentPageTitle"></h1>
                </div>
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- 分页信息 -->
                    <div class="hidden lg:flex lg:items-center lg:gap-x-2">
                        <span class="text-sm text-gray-500">第 <?= $page ?> 页，共 <?= $total_pages ?> 页</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 页面内容 -->
        <main class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <!-- 仪表盘视图 -->
            <div x-show="activeView === 'dashboard'" x-transition class="fade-in">
                <!-- 页面标题 -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">仪表盘</h1>
                    <p class="mt-1 text-sm text-gray-600">欢迎回来，查看您的数据概览</p>
                </div>

                <!-- 统计卡片 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">总链接数</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_links'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">分类数量</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_categories'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">当前页面</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $page ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">总页数</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_pages ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 快速操作 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">添加链接</h3>
                            <p class="text-sm text-gray-600 mb-4">快速添加新的链接到您的收藏</p>
                            <button @click="setActiveView('add')" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                开始添加
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">分类管理</h3>
                            <p class="text-sm text-gray-600 mb-4">调整分类显示顺序</p>
                            <button @click="setActiveView('categories')" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                                管理分类
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-6 hover-lift transition-all">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">导出数据</h3>
                            <p class="text-sm text-gray-600 mb-4">备份所有链接数据</p>
                            <a href="export.php" class="block w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition-colors">
                                立即导出
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 添加链接视图 -->
            <div x-show="activeView === 'add'" x-transition class="fade-in">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">添加链接</h1>
                    <p class="mt-1 text-sm text-gray-600">添加新的链接到您的收藏夹</p>
                </div>

                <div class="max-w-2xl">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">链接标题</label>
                                <input type="text" id="title" x-model="form.title" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">链接地址</label>
                                <input type="url" id="url" x-model="form.url" required
                                       placeholder="https://example.com"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">分类</label>
                                <select id="category" x-model="form.category" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">描述（可选）</label>
                                <textarea id="description" x-model="form.description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="resetForm()"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                    重置
                                </button>
                                <button type="submit" :disabled="loading"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center">
                                    <span x-show="!loading">添加链接</span>
                                    <span x-show="loading" class="flex items-center">
                                        <div class="loading mr-2"></div>
                                        添加中...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 管理链接视图 -->
            <div x-show="activeView === 'manage'" x-transition class="fade-in">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">管理链接</h1>
                    <p class="mt-1 text-sm text-gray-600">查看、编辑和删除您的链接</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分类</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">链接</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($links as $link): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($link['title']) ?></div>
                                        <?php if (!empty($link['description'])): ?>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($link['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($link['category']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-sm truncate max-w-xs block">
                                            <?= htmlspecialchars($link['url']) ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="edit.php?id=<?= $link['id'] ?>"
                                               class="text-blue-600 hover:text-blue-800">编辑</a>
                                            <button @click="deleteLink(<?= $link['id'] ?>)"
                                                    class="text-red-600 hover:text-red-800">删除</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <button @click="goToPage(<?= $page - 1 ?>)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">上一页</button>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                    <button @click="goToPage(<?= $page + 1 ?>)" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">下一页</button>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        显示第 <span class="font-medium"><?= ($page - 1) * $per_page + 1 ?></span> 到
                                        <span class="font-medium"><?= min($page * $per_page, $total_rows) ?></span> 条，
                                        共 <span class="font-medium"><?= $total_rows ?></span> 条记录
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <button @click="goToPage(<?= $i ?>)"
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                                <?= $i ?>
                                            </button>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 分类管理视图 -->
            <div x-show="activeView === 'categories'" x-transition class="fade-in">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">分类管理</h1>
                    <p class="mt-1 text-sm text-gray-600">拖拽调整分类显示顺序，数字越小越靠前</p>
                </div>

                <!-- 分类排序管理 -->
                <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">🎯 调整分类顺序</h2>

                    <?php if (empty($managed_categories)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <div class="text-4xl mb-2">📭</div>
                            <p>暂无分类数据</p>
                        </div>
                    <?php else: ?>
                        <div id="sortable-categories" class="space-y-3">
                            <?php foreach ($managed_categories as $index => $category): ?>
                                <div class="category-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-move hover:bg-gray-100 transition-colors"
                                     data-category="<?= htmlspecialchars($category['name']) ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="drag-handle mr-3 text-xl text-gray-400">⋮⋮</span>
                                            <div>
                                                <span class="font-medium text-gray-900"><?= htmlspecialchars($category['name']) ?></span>
                                                <span class="ml-2 text-sm text-gray-500">(<?= $category['link_count'] ?> 个链接)</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <input type="number"
                                                   class="category-order-input w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                                                   value="<?= $category['sort_order'] ?>"
                                                   data-category="<?= htmlspecialchars($category['name']) ?>"
                                                   min="0" step="10">
                                            <?php if ($category['link_count'] == 0): ?>
                                                <button @click="deleteCategory('<?= htmlspecialchars($category['name']) ?>')"
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    删除
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button @click="saveCategoryOrder()"
                                    :disabled="loading"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center">
                                <span x-show="!loading">💾 保存排序</span>
                                <span x-show="loading" class="flex items-center">
                                    <div class="loading mr-2"></div>
                                    保存中...
                                </span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 未管理的分类 -->
                <?php if (!empty($orphan_categories)): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-yellow-800 mb-4">⚠️ 未管理的分类</h3>
                        <p class="text-yellow-700 mb-4">以下分类存在于链接中，但未在分类管理中设置排序：</p>
                        <div class="space-y-2">
                            <?php foreach ($orphan_categories as $orphan): ?>
                                <div class="flex items-center justify-between bg-white p-3 rounded border">
                                    <span class="font-medium"><?= htmlspecialchars($orphan['category']) ?></span>
                                    <span class="text-sm text-gray-500"><?= $orphan['link_count'] ?> 个链接</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4">
                            <button @click="addOrphanCategories()"
                                    :disabled="loading"
                                    class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition-colors disabled:opacity-50">
                                📥 添加到分类管理
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 数据管理视图 -->
            <div x-show="activeView === 'data'" x-transition class="fade-in">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">数据管理</h1>
                    <p class="mt-1 text-sm text-gray-600">导入导出数据，管理系统设置</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- 导入数据 -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">导入数据</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">从JSON文件导入链接数据</p>
                        <form action="import.php" method="post" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <input type="file" name="json" accept=".json" required
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                导入数据
                            </button>
                        </form>
                    </div>

                    <!-- 导出数据 -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">导出数据</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">将所有链接数据导出为JSON格式</p>
                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">当前数据量</span>
                                    <span class="font-medium text-gray-900"><?= $stats['total_links'] ?> 条链接</span>
                                </div>
                                <div class="flex justify-between items-center text-sm mt-2">
                                    <span class="text-gray-600">分类数量</span>
                                    <span class="font-medium text-gray-900"><?= $stats['total_categories'] ?> 个分类</span>
                                </div>
                            </div>
                            <a href="export.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors text-center">
                                导出所有链接
                            </a>
                        </div>
                    </div>

                    <!-- 系统工具 -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg border border-gray-200 p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">系统工具</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button @click="setActiveView('categories')"
                                        class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors w-full text-left">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">分类管理</div>
                                        <div class="text-xs text-gray-500">调整分类排序</div>
                                    </div>
                                </button>

                                <a href="update_db.php" target="_blank"
                                   class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">数据库更新</div>
                                        <div class="text-xs text-gray-500">更新数据库结构</div>
                                    </div>
                                </a>

                                <a href="test_fix.php" target="_blank"
                                   class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">功能测试</div>
                                        <div class="text-xs text-gray-500">测试系统功能</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function adminApp() {
            return {
                // 状态管理
                activeView: 'dashboard',
                sidebarOpen: false,
                loading: false,

                // 通知系统
                notification: {
                    show: false,
                    type: 'success',
                    message: ''
                },

                // 表单数据
                form: {
                    title: '',
                    url: '',
                    category: '',
                    description: ''
                },

                // 导航配置
                navigation: [
                    {
                        id: 'dashboard',
                        name: '仪表盘',
                        icon: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path></svg>'
                    },
                    {
                        id: 'add',
                        name: '添加链接',
                        icon: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>'
                    },
                    {
                        id: 'manage',
                        name: '管理链接',
                        icon: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>'
                    },
                    {
                        id: 'categories',
                        name: '分类管理',
                        icon: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>'
                    },
                    {
                        id: 'data',
                        name: '数据管理',
                        icon: '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'
                    }
                ],

                // 计算属性
                get currentPageTitle() {
                    const titles = {
                        'dashboard': '仪表盘',
                        'add': '添加链接',
                        'manage': '管理链接',
                        'categories': '分类管理',
                        'data': '数据管理'
                    };
                    return titles[this.activeView] || '管理后台';
                },

                // 初始化
                init() {
                    // 检查URL参数来确定当前视图
                    const urlParams = new URLSearchParams(window.location.search);
                    const page = urlParams.get('page');

                    // 如果有分页参数，说明用户在管理链接页面
                    if (page) {
                        this.activeView = 'manage';
                    } else {
                        // 检查是否有保存的视图状态
                        const savedView = sessionStorage.getItem('activeView');
                        if (savedView && ['dashboard', 'add', 'manage', 'categories', 'data'].includes(savedView)) {
                            this.activeView = savedView;
                        }
                    }

                    // 监听窗口大小变化
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) { // lg断点
                            this.sidebarOpen = false;
                        }
                    });

                    // 监听键盘事件
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            this.sidebarOpen = false;
                            this.hideNotification();
                        }
                    });
                },

                // 设置活动视图
                setActiveView(view) {
                    this.activeView = view;
                    this.sidebarOpen = false;

                    // 保存当前视图状态到 sessionStorage
                    sessionStorage.setItem('activeView', view);

                    // 如果切换到管理链接页面，更新URL但不刷新页面
                    if (view === 'manage') {
                        const currentUrl = new URL(window.location);
                        if (!currentUrl.searchParams.has('page')) {
                            currentUrl.searchParams.set('page', '1');
                            window.history.replaceState({}, '', currentUrl);
                        }
                    } else {
                        // 如果不是管理页面，清除分页参数
                        const currentUrl = new URL(window.location);
                        if (currentUrl.searchParams.has('page')) {
                            currentUrl.searchParams.delete('page');
                            window.history.replaceState({}, '', currentUrl);
                        }
                    }

                    // 如果切换到分类管理页面，初始化拖拽排序
                    if (view === 'categories') {
                        this.$nextTick(() => {
                            this.initSortable();
                        });
                    }
                },

                // 显示通知
                showNotification(type, message) {
                    this.notification = {
                        show: true,
                        type: type,
                        message: message
                    };

                    // 3秒后自动隐藏
                    setTimeout(() => {
                        this.hideNotification();
                    }, 3000);
                },

                // 隐藏通知
                hideNotification() {
                    this.notification.show = false;
                },

                // 重置表单
                resetForm() {
                    this.form = {
                        title: '',
                        url: '',
                        category: '',
                        description: ''
                    };
                },

                // 提交表单
                async submitForm() {
                    if (this.loading) return;

                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'add_link');
                        formData.append('title', this.form.title);
                        formData.append('url', this.form.url);
                        formData.append('category', this.form.category);
                        formData.append('description', this.form.description);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showNotification('success', result.message);
                            this.resetForm();
                            // 1.5秒后跳转到管理页面
                            setTimeout(() => {
                                this.setActiveView('manage');
                                // 跳转到管理页面的第一页
                                window.location.href = '?page=1';
                            }, 1500);
                        } else {
                            this.showNotification('error', result.message);
                        }
                    } catch (error) {
                        this.showNotification('error', '网络错误，请重试');
                        console.error('Error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                // 分页跳转
                goToPage(page) {
                    // 保持在管理视图
                    this.activeView = 'manage';
                    sessionStorage.setItem('activeView', 'manage');

                    // 跳转到指定页面
                    window.location.href = `?page=${page}`;
                },

                // 删除链接
                async deleteLink(id) {
                    if (!confirm('确定要删除这个链接吗？此操作不可撤销！')) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_link');
                        formData.append('id', id);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showNotification('success', result.message);
                            // 1秒后刷新页面
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            this.showNotification('error', result.message);
                        }
                    } catch (error) {
                        this.showNotification('error', '删除失败，请重试');
                        console.error('Error:', error);
                    }
                },

                // 初始化拖拽排序
                initSortable() {
                    const sortableElement = document.getElementById('sortable-categories');
                    if (sortableElement && typeof Sortable !== 'undefined') {
                        new Sortable(sortableElement, {
                            handle: '.drag-handle',
                            animation: 150,
                            onEnd: () => {
                                this.updateSortOrder();
                            }
                        });
                    }
                },

                // 更新排序数字
                updateSortOrder() {
                    const items = document.querySelectorAll('#sortable-categories .category-item');
                    items.forEach((item, index) => {
                        const input = item.querySelector('.category-order-input');
                        if (input) {
                            input.value = (index + 1) * 10;
                        }
                    });
                },

                // 保存分类排序
                async saveCategoryOrder() {
                    if (this.loading) return;

                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'update_category_order');

                        // 收集所有分类的排序数据
                        const orderInputs = document.querySelectorAll('.category-order-input');
                        const categoryOrder = {};

                        orderInputs.forEach(input => {
                            const categoryName = input.dataset.category;
                            const sortOrder = parseInt(input.value) || 0;
                            categoryOrder[categoryName] = sortOrder;
                        });

                        // 将分类排序数据添加到表单
                        Object.keys(categoryOrder).forEach(categoryName => {
                            formData.append(`category_order[${categoryName}]`, categoryOrder[categoryName]);
                        });

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showNotification('success', result.message);
                            // 1.5秒后刷新页面以更新数据
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            this.showNotification('error', result.message);
                        }
                    } catch (error) {
                        this.showNotification('error', '网络错误，请重试');
                        console.error('Error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                // 删除分类
                async deleteCategory(categoryName) {
                    if (!confirm(`确定要删除分类「${categoryName}」吗？`)) {
                        return;
                    }

                    if (this.loading) return;

                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_category');
                        formData.append('category_name', categoryName);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showNotification('success', result.message);
                            // 1.5秒后刷新页面以更新数据
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            this.showNotification('error', result.message);
                        }
                    } catch (error) {
                        this.showNotification('error', '网络错误，请重试');
                        console.error('Error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                // 添加孤立分类到管理中
                async addOrphanCategories() {
                    if (!confirm('确定要将所有未管理的分类添加到分类管理中吗？')) {
                        return;
                    }

                    if (this.loading) return;

                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'update_category_order');

                        // 添加现有分类的排序
                        const orderInputs = document.querySelectorAll('.category-order-input');
                        orderInputs.forEach(input => {
                            const categoryName = input.dataset.category;
                            const sortOrder = parseInt(input.value) || 0;
                            formData.append(`category_order[${categoryName}]`, sortOrder);
                        });

                        // 添加孤立分类
                        const orphanCategories = <?= json_encode($orphan_categories) ?>;
                        const maxOrder = Math.max(...Array.from(orderInputs).map(input => parseInt(input.value) || 0), 0);
                        let nextOrder = maxOrder + 10;

                        orphanCategories.forEach(orphan => {
                            formData.append(`category_order[${orphan.category}]`, nextOrder);
                            nextOrder += 10;
                        });

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showNotification('success', result.message);
                            // 1.5秒后刷新页面以更新数据
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            this.showNotification('error', result.message);
                        }
                    } catch (error) {
                        this.showNotification('error', '网络错误，请重试');
                        console.error('Error:', error);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>

    <!-- 引入SortableJS用于拖拽排序 -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</body>
</html>
