<?php
session_start();
if (!isset($_SESSION['logged_in'])) die("未登录");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json'])) {
  $json = file_get_contents($_FILES['json']['tmp_name']);
  $data = json_decode($json, true);

  if (is_array($data)) {
    // 检查数据库字段是否存在
    $check_sort = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'sort'");
    $has_sort = mysqli_num_rows($check_sort) > 0;
    $check_desc = mysqli_query($conn, "SHOW COLUMNS FROM links LIKE 'description'");
    $has_desc = mysqli_num_rows($check_desc) > 0;

    // 检查 categories 表是否存在
    $check_categories_table = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
    $has_categories_table = mysqli_num_rows($check_categories_table) > 0;

    // 收集所有新分类
    $new_categories = [];

    foreach ($data as $row) {
      $title = mysqli_real_escape_string($conn, $row['title']);
      $url = formatUrl($row['url']); // 格式化URL
      $url = mysqli_real_escape_string($conn, $url);
      $cat = mysqli_real_escape_string($conn, $row['category']);
      $description = mysqli_real_escape_string($conn, $row['description'] ?? '');
      $sort = intval($row['sort'] ?? 0);

      // 收集分类
      if (!empty($cat) && !in_array($cat, $new_categories)) {
        $new_categories[] = $cat;
      }

      // 根据数据库字段构建SQL
      if ($has_sort && $has_desc) {
        mysqli_query($conn, "INSERT INTO links (title, url, category, description, sort) VALUES ('$title','$url','$cat','$description',$sort)");
      } elseif ($has_sort) {
        mysqli_query($conn, "INSERT INTO links (title, url, category, sort) VALUES ('$title','$url','$cat',$sort)");
      } elseif ($has_desc) {
        mysqli_query($conn, "INSERT INTO links (title, url, category, description) VALUES ('$title','$url','$cat','$description')");
      } else {
        mysqli_query($conn, "INSERT INTO links (title, url, category) VALUES ('$title','$url','$cat')");
      }
    }

    // 如果 categories 表存在，添加新分类到管理中
    if ($has_categories_table && !empty($new_categories)) {
      // 获取当前最大排序值
      $max_sort_query = "SELECT MAX(sort_order) as max_sort FROM categories";
      $max_sort_result = mysqli_query($conn, $max_sort_query);
      $max_sort = mysqli_fetch_assoc($max_sort_result)['max_sort'] ?? 0;
      $next_sort = $max_sort + 10;

      foreach ($new_categories as $category) {
        // 检查分类是否已存在
        $check_query = "SELECT id FROM categories WHERE name = '$category'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) == 0) {
          mysqli_query($conn, "INSERT INTO categories (name, sort_order) VALUES ('$category', $next_sort)");
          $next_sort += 10;
        }
      }
    }
  }
  header("Location: admin.php?import=1");
  exit;
}
?>
