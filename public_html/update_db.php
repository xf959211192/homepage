<?php
require_once 'config.php';

echo "<h2>数据库结构更新</h2>";

// 检查 sort 字段是否已存在
$check_sort = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'sort'");
if (mysqli_num_rows($check_sort) > 0) {
    echo "<p>✅ sort 字段已存在</p>";
} else {
    // 添加 sort 字段到 links 表
    $sql = "ALTER TABLE links ADD COLUMN sort INT DEFAULT 0";
    if (mysqli_query($conn, $sql)) {
        echo "<p>✅ 已添加 sort 字段</p>";
    } else {
        echo "<p>❌ 添加 sort 字段失败：" . mysqli_error($conn) . "</p>";
    }
}

// 检查 description 字段是否已存在
$check_desc = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'description'");
if (mysqli_num_rows($check_desc) > 0) {
    echo "<p>✅ description 字段已存在</p>";
} else {
    // 添加 description 字段到 links 表
    $sql = "ALTER TABLE links ADD COLUMN description TEXT";
    if (mysqli_query($conn, $sql)) {
        echo "<p>✅ 已添加 description 字段</p>";
    } else {
        echo "<p>❌ 添加 description 字段失败：" . mysqli_error($conn) . "</p>";
    }
}

// 检查 categories 表是否已存在
$check_categories_table = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if (mysqli_num_rows($check_categories_table) > 0) {
    echo "<p>✅ categories 表已存在</p>";
} else {
    // 创建 categories 表用于分类排序
    $sql = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if (mysqli_query($conn, $sql)) {
        echo "<p>✅ 已创建 categories 表</p>";

        // 从现有链接中提取分类并插入到 categories 表
        $existing_categories = mysqli_query($conn, "SELECT DISTINCT category FROM links WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $sort_order = 0;
        while ($cat_row = mysqli_fetch_assoc($existing_categories)) {
            $category_name = mysqli_real_escape_string($conn, $cat_row['category']);
            mysqli_query($conn, "INSERT INTO categories (name, sort_order) VALUES ('$category_name', $sort_order)");
            $sort_order += 10;
        }
        echo "<p>✅ 已导入现有分类到 categories 表</p>";
    } else {
        echo "<p>❌ 创建 categories 表失败：" . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='admin.php'>返回管理页面</a></p>";

mysqli_close($conn);
?>
