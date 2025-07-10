<?php
require_once 'config.php';

// 获取所有链接并按分类分组，同时考虑分类的排序
$query = "
    SELECT l.*, COALESCE(c.sort_order, 999) as category_sort_order
    FROM links l
    LEFT JOIN categories c ON l.category = c.name
    ORDER BY category_sort_order ASC, l.category ASC, l.sort ASC, l.id DESC
";
$res = mysqli_query($conn, $query);
$grouped = [];

while ($row = mysqli_fetch_assoc($res)) {
  $grouped[$row['category']][] = $row;
}

// 分类图标映射函数
function getCategoryIcon($category) {
  $icons = [
    '开发工具' => '💻', '设计工具' => '🎨', '效率工具' => '⚡', '学习资源' => '📚',
    '娱乐' => '🎮', '社交' => '💬', '购物' => '🛒', '新闻' => '📰',
    '音乐' => '🎵', '视频' => '🎬', '图片' => '🖼️', '文档' => '📄',
    '云存储' => '☁️', '搜索' => '🔍', '翻译' => '🌐', '工具箱' => '🧰',
    '工具' => '🛠️', '个人' => '👤', '其他' => '📂'
  ];
  return $icons[$category] ?? '📂';
}

// 获取分类颜色主题
function getCategoryTheme($category) {
  $themes = [
    '开发工具' => 'blue', '设计工具' => 'purple', '效率工具' => 'green', '学习资源' => 'yellow',
    '娱乐' => 'pink', '社交' => 'indigo', '购物' => 'red', '新闻' => 'gray',
    '音乐' => 'purple', '视频' => 'red', '图片' => 'pink', '文档' => 'blue',
    '云存储' => 'cyan', '搜索' => 'orange', '翻译' => 'teal', '工具箱' => 'slate',
    '工具' => 'emerald', '个人' => 'violet', '其他' => 'gray'
  ];
  return $themes[$category] ?? 'gray';
}

// 获取网站图标URL
function getWebsiteIcon($url) {
  $domain = parse_url($url, PHP_URL_HOST);
  if ($domain) {
    return "https://icons.duckduckgo.com/ip3/{$domain}.ico";
  }
  return null;
}
?>

<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>晓风的个人主页 - 优质工具与资源导航</title>
    <meta name="description" content="晓风的个人主页，精选优质工具和资源导航，提升工作效率。业余开发者，热爱技术分享。">
    <meta name="keywords" content="晓风,个人主页,工具导航,资源分享,开发工具,效率工具">
    <meta property="og:title" content="晓风的个人主页">
    <meta property="og:description" content="精选优质工具和资源导航，提升工作效率">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://xf17.top">
    <link rel="canonical" href="https://xf17.top">
    <link rel="icon" type="image/jpeg" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
    <link rel="apple-touch-icon" href="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-gentle': 'bounceGentle 2s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(100%)' },
                            '100%': { transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* 自定义滚动条 */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* 深色模式 */
        [data-theme="dark"] { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f1f5f9; }
        [data-theme="dark"] .bg-white { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(10px); }
        [data-theme="dark"] .bg-gray-50 { background: #0f172a; }
        [data-theme="dark"] .text-gray-900 { color: #f1f5f9; }
        [data-theme="dark"] .text-gray-700 { color: #cbd5e1; }
        [data-theme="dark"] .text-gray-500 { color: #94a3b8; }
        [data-theme="dark"] .border { border-color: rgba(71, 85, 105, 0.3); }
        [data-theme="dark"] .border-gray-200 { border-color: rgba(71, 85, 105, 0.3); }
        [data-theme="dark"] .hover\:bg-gray-100:hover { background: rgba(71, 85, 105, 0.3); }
        [data-theme="dark"] ::-webkit-scrollbar-track { background: #1e293b; }
        [data-theme="dark"] ::-webkit-scrollbar-thumb { background: #475569; }
        
        /* 玻璃态效果 */
        .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        [data-theme="dark"] .glass { background: rgba(30, 41, 59, 0.3); border: 1px solid rgba(71, 85, 105, 0.3); }
        
        /* 卡片悬停效果 */
        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        
        /* 渐变背景 */
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .gradient-text { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        

        
        /* 网站图标样式 */
        .website-icon-container {
            transition: all 0.3s ease;
        }

        .website-icon-container:hover {
            transform: scale(1.1);
        }

        .website-icon {
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        /* 图标加载失败时的样式 */
        .fallback-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* 左侧导航样式 */
        .category-filter.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .category-filter:not(.active):hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .category-filter:not(.active):hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* 移动端导航动画 */
        #mobileNavContent {
            transition: all 0.3s ease-in-out;
        }

        #navToggleIcon {
            transition: transform 0.3s ease-in-out;
        }

        /* 移动端优化 */
        @media (max-width: 768px) {
            .mobile-padding { padding: 1rem; }
            .mobile-text { font-size: 0.875rem; }
        }

        /* 移动端左侧导航 */
        @media (max-width: 1024px) {
            .category-filter {
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
            }

            /* 移动端导航内容 */
            #mobileNavContent {
                position: static;
                box-shadow: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 transition-all duration-300">
    <!-- 顶部导航栏 -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <!-- Logo和标题 -->
                <div class="flex items-center space-x-2">
                    <img src="https://i.miji.bid/2025/06/21/b84f06ec3e6ba07e119d3a37df2b512b.jpeg"
                         class="w-8 h-8 rounded-full ring-2 ring-white/20" alt="晓风头像">
                    <div class="hidden sm:block">
                        <h1 class="text-base font-bold gradient-text">晓风的个人主页</h1>
                        <p class="text-xs text-gray-500">精选工具 · 提升效率</p>
                    </div>
                </div>

                <!-- 右侧按钮 -->
                <div class="flex items-center space-x-1">
                    <button onclick="toggleTheme()" class="p-1.5 rounded-full glass hover:bg-white/20 transition-all" title="切换主题">
                        <span class="theme-icon text-base">🌓</span>
                    </button>
                    <button onclick="window.open('admin.php', '_blank')" class="p-1.5 rounded-full glass hover:bg-white/20 transition-all" title="后台管理">
                        <span class="text-base">⚙️</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容区域 -->
    <main class="pt-16 pb-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">


            <!-- 主要布局：左侧导航 + 右侧内容 -->
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- 移动端导航切换按钮 -->
                <div class="lg:hidden mb-4">
                    <button onclick="toggleMobileNav()" class="w-full flex items-center justify-between px-4 py-3 bg-white rounded-xl shadow-sm border">
                        <span class="text-sm font-semibold text-gray-900">分类导航</span>
                        <svg id="navToggleIcon" class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- 左侧分类导航 -->
                <aside class="lg:w-64 flex-shrink-0">
                    <div class="sticky top-20">
                        <div id="mobileNavContent" class="bg-white rounded-xl p-4 shadow-sm border lg:block hidden">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3 hidden lg:block">分类导航</h3>
                            <div class="space-y-1">
                                <button onclick="showAllCategories()" class="category-filter active w-full text-left px-3 py-2 rounded-lg glass text-sm font-medium transition-all hover:bg-gray-50" data-category="all">
                                    🌟 全部
                                </button>
                                <?php foreach ($grouped as $category => $links): ?>
                                    <button onclick="filterCategory('<?= htmlspecialchars($category) ?>')" class="category-filter w-full text-left px-3 py-2 rounded-lg glass text-sm font-medium transition-all hover:bg-gray-50" data-category="<?= htmlspecialchars($category) ?>">
                                        <?= getCategoryIcon($category) ?> <?= htmlspecialchars($category ?: '其他') ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 导航栏延伸内容 -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="space-y-3">
                                    <!-- 联系信息 -->
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-2">联系作者</div>
                                        <a href="mailto:xf17@foxmail.com" class="inline-flex items-center space-x-2 px-3 py-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="font-mono text-xs text-gray-700">xf17@foxmail.com</span>
                                        </a>
                                    </div>

                                    <!-- 快捷操作 -->
                                    <div class="space-y-2">
                                        <button onclick="window.open('admin.php', '_blank')" class="w-full flex items-center space-x-2 px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                            <span class="text-base">⚙️</span>
                                            <span>后台管理</span>
                                        </button>
                                        <button onclick="toggleTheme()" class="w-full flex items-center space-x-2 px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                            <span class="theme-icon text-base">🌓</span>
                                            <span>切换主题</span>
                                        </button>
                                    </div>

                                    <!-- 统计信息 -->
                                    <div class="text-center pt-3 border-t border-gray-200">
                                        <div class="text-xs text-gray-500 mb-2">网站统计</div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-gray-50 rounded-lg p-2">
                                                <div class="font-semibold text-gray-900"><?= array_sum(array_map('count', $grouped)) ?></div>
                                                <div class="text-gray-500">工具</div>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-2">
                                                <div class="font-semibold text-gray-900"><?= count($grouped) ?></div>
                                                <div class="text-gray-500">分类</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- 右侧工具卡片区域 -->
                <div class="flex-1">
                    <!-- 工具卡片区域 -->
            <div id="toolsContainer" class="space-y-8">
                <?php if (empty($grouped)): ?>
                    <div class="text-center py-16">
                        <div class="text-5xl mb-3 animate-bounce-gentle">📭</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">暂无导航链接</h3>
                        <p class="text-sm text-gray-500">管理员还没有添加任何链接</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped as $category => $links): ?>
                        <section class="category-section animate-fade-in" data-category="<?= htmlspecialchars($category) ?>">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl gradient-bg flex items-center justify-center text-white text-lg">
                                        <?= getCategoryIcon($category) ?>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($category ?: '其他') ?></h3>
                                        <p class="text-xs text-gray-500"><?= count($links) ?> 个工具</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <?php foreach ($links as $link): ?>
                                    <div class="tool-card bg-white rounded-xl p-4 card-hover border"
                                         data-url="<?= htmlspecialchars($link['url']) ?>">

                                        <div class="flex items-start justify-between mb-3">
                                            <div class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center overflow-hidden website-icon-container">
                                                <?php
                                                $iconUrl = getWebsiteIcon($link['url']);
                                                if ($iconUrl): ?>
                                                    <img src="<?= htmlspecialchars($iconUrl) ?>"
                                                         alt="<?= htmlspecialchars($link['title']) ?> 图标"
                                                         class="w-6 h-6 object-contain website-icon"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="w-full h-full fallback-icon flex items-center justify-center text-white font-bold text-sm" style="display: none;">
                                                        <?= strtoupper(substr($link['title'], 0, 1)) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-full h-full fallback-icon flex items-center justify-center text-white font-bold text-sm">
                                                        <?= strtoupper(substr($link['title'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex space-x-1">
                                                <button onclick="addToFavorites('<?= htmlspecialchars($link['title']) ?>', '<?= htmlspecialchars($link['url']) ?>')"
                                                        class="p-1 rounded-md hover:bg-gray-100 transition-colors" title="收藏">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <h4 class="text-base font-semibold text-gray-900 mb-2 line-clamp-1"><?= htmlspecialchars($link['title']) ?></h4>

                                        <?php if (!empty($link['description'])): ?>
                                            <p class="text-xs text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($link['description']) ?></p>
                                        <?php endif; ?>

                                        <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
                                            <span class="font-mono text-xs truncate"><?= parse_url($link['url'], PHP_URL_HOST) ?></span>
                                            <span class="px-1.5 py-0.5 bg-gray-100 rounded text-xs"><?= htmlspecialchars($category) ?></span>
                                        </div>

                                        <div class="flex space-x-2">
                                            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                                               class="flex-1 text-center py-2 px-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all font-medium text-xs">
                                                访问
                                            </a>
                                            <button onclick="copyToClipboard('<?= htmlspecialchars($link['url']) ?>', this)"
                                                    class="copy-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-xs">
                                                复制
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 返回顶部按钮 -->
    <button id="backToTop" onclick="scrollToTop()"
            class="fixed bottom-4 right-4 w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all transform hover:scale-110 opacity-0 pointer-events-none">
        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>



    <script>
        // 主题切换功能
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // 更新所有主题图标
            const icons = document.querySelectorAll('.theme-icon');
            icons.forEach(icon => {
                icon.textContent = newTheme === 'dark' ? '☀️' : '�';
            });
        }

        // 初始化主题
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // 更新所有主题图标
            const icons = document.querySelectorAll('.theme-icon');
            icons.forEach(icon => {
                icon.textContent = savedTheme === 'dark' ? '☀️' : '�';
            });
        }



        // 分类筛选功能
        function filterCategory(category) {
            const sections = document.querySelectorAll('.category-section');
            const filterButtons = document.querySelectorAll('.category-filter');

            // 更新按钮状态
            filterButtons.forEach(btn => {
                btn.classList.remove('active', 'bg-blue-500', 'text-white');
                btn.classList.add('glass', 'hover:bg-gray-50');
            });

            const activeButton = document.querySelector(`[data-category="${category}"]`);
            if (activeButton) {
                activeButton.classList.add('active', 'bg-blue-500', 'text-white');
                activeButton.classList.remove('glass', 'hover:bg-gray-50');
            }

            // 显示/隐藏分类
            sections.forEach(section => {
                if (section.dataset.category === category) {
                    section.style.display = 'block';
                    section.classList.add('animate-fade-in');
                } else {
                    section.style.display = 'none';
                    section.classList.remove('animate-fade-in');
                }
            });
        }

        function showAllCategories() {
            const sections = document.querySelectorAll('.category-section');
            const filterButtons = document.querySelectorAll('.category-filter');

            // 更新按钮状态
            filterButtons.forEach(btn => {
                btn.classList.remove('active', 'bg-blue-500', 'text-white');
                btn.classList.add('glass', 'hover:bg-gray-50');
            });

            const allButton = document.querySelector('[data-category="all"]');
            if (allButton) {
                allButton.classList.add('active', 'bg-blue-500', 'text-white');
                allButton.classList.remove('glass', 'hover:bg-gray-50');
            }

            // 显示所有分类
            sections.forEach(section => {
                section.style.display = 'block';
                section.classList.add('animate-fade-in');
            });
        }

        // 复制功能
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '已复制!';
                button.classList.add('bg-green-500', 'text-white');
                button.classList.remove('bg-gray-100', 'text-gray-700');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-500', 'text-white');
                    button.classList.add('bg-gray-100', 'text-gray-700');
                }, 2000);
            }).catch(() => {
                // 降级方案
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                const originalText = button.textContent;
                button.textContent = '已复制!';
                button.classList.add('bg-green-500', 'text-white');
                button.classList.remove('bg-gray-100', 'text-gray-700');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-500', 'text-white');
                    button.classList.add('bg-gray-100', 'text-gray-700');
                }, 2000);
            });
        }

        // 收藏功能
        function addToFavorites(title, url) {
            let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
            const favorite = { title, url, addedAt: new Date().toISOString() };

            if (!favorites.some(fav => fav.url === url)) {
                favorites.push(favorite);
                localStorage.setItem('favorites', JSON.stringify(favorites));

                // 显示提示
                showToast(`已添加 "${title}" 到收藏夹`);
            } else {
                showToast(`"${title}" 已在收藏夹中`);
            }
        }

        // 显示提示消息
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-20 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-up';
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // 返回顶部功能
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // 监听滚动显示返回顶部按钮
        function initScrollButton() {
            const backToTopButton = document.getElementById('backToTop');

            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.remove('opacity-0', 'pointer-events-none');
                    backToTopButton.classList.add('opacity-100');
                } else {
                    backToTopButton.classList.add('opacity-0', 'pointer-events-none');
                    backToTopButton.classList.remove('opacity-100');
                }
            });
        }



        // 移动端导航切换
        function toggleMobileNav() {
            const navContent = document.getElementById('mobileNavContent');
            const toggleIcon = document.getElementById('navToggleIcon');

            navContent.classList.toggle('hidden');
            navContent.classList.toggle('block');

            // 旋转箭头图标
            if (navContent.classList.contains('hidden')) {
                toggleIcon.style.transform = 'rotate(0deg)';
            } else {
                toggleIcon.style.transform = 'rotate(180deg)';
            }
        }

        // 修复主题图标函数
        function fixThemeIcons() {
            const icons = document.querySelectorAll('.theme-icon');
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            icons.forEach(icon => {
                icon.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
            });
        }

        // 重写主题切换函数以确保图标正确显示
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // 立即更新图标
            fixThemeIcons();
        }

        // 重写初始化主题函数
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // 立即更新图标
            fixThemeIcons();
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            // 额外的图标修复，确保显示正确
            setTimeout(fixThemeIcons, 100);
            initScrollButton();

            // 添加卡片进入动画
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.tool-card').forEach(card => {
                observer.observe(card);
            });

            // 监听窗口大小变化，自动显示/隐藏移动端导航
            window.addEventListener('resize', function() {
                const navContent = document.getElementById('mobileNavContent');
                const toggleIcon = document.getElementById('navToggleIcon');

                if (window.innerWidth >= 1024) { // lg断点
                    navContent.classList.remove('hidden');
                    navContent.classList.add('block');
                    toggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    navContent.classList.add('hidden');
                    navContent.classList.remove('block');
                    toggleIcon.style.transform = 'rotate(0deg)';
                }
            });
        });
    </script>
</body>
</html>
