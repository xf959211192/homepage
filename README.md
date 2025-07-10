# 晓风的个人主页

一个简洁美观的个人导航主页，支持链接分类管理、拖拽排序、导入导出等功能。

## 🌟 功能特色

- **🎨 现代化界面设计** - 响应式布局，支持深色/浅色主题切换
- **📂 分类管理** - 支持自定义分类，每个分类都有独特的图标和颜色主题
- **🔧 链接管理** - 添加、编辑、删除链接，支持拖拽排序
- **📱 响应式设计** - 完美适配桌面端和移动端
- **🔍 智能搜索** - 实时搜索链接标题和描述
- **📊 管理后台** - 完整的后台管理系统
- **📤 导入导出** - 支持JSON格式的数据导入导出
- **🔒 安全认证** - 管理员登录保护
- **🌐 网站图标** - 自动获取网站favicon

## 🚀 快速开始

### 环境要求

- PHP 7.4+
- MySQL 5.7+ 或 MariaDB 10.2+
- Web服务器 (Apache/Nginx)

### 安装步骤

1. **克隆项目**
   ```bash
   git clone <repository-url>
   cd homepage
   ```

2. **配置数据库**
   ```bash
   # 复制配置文件
   cp public_html/config.php.example public_html/config.php
   
   # 编辑配置文件，填入数据库信息
   nano public_html/config.php
   ```

3. **创建数据库表**
   
   执行以下SQL创建必要的数据表：
   
   ```sql
   -- 创建链接表
   CREATE TABLE `links` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `title` varchar(255) NOT NULL,
     `url` text NOT NULL,
     `category` varchar(100) DEFAULT '其他',
     `description` text,
     `sort` int(11) DEFAULT 0,
     `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   
   -- 创建分类表
   CREATE TABLE `categories` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `name` varchar(100) NOT NULL,
     `sort_order` int(11) DEFAULT 0,
     `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`),
     UNIQUE KEY `name` (`name`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

4. **设置Web服务器**
   
   将 `public_html` 目录设置为网站根目录，或者将其内容复制到现有的网站根目录。

5. **访问网站**
   
   - 前台页面：`http://yourdomain.com/`
   - 管理后台：`http://yourdomain.com/admin.php`
   - 默认管理员账号：`admin` / `your_password`（请在config.php中修改）

## 📁 项目结构

```
homepage/
├── public_html/
│   ├── index.php          # 主页面
│   ├── admin.php          # 管理后台
│   ├── login.php          # 登录页面
│   ├── auth.php           # 认证处理
│   ├── config.php         # 配置文件
│   ├── config.php.example # 配置文件模板
│   ├── edit.php           # 编辑链接
│   ├── import.php         # 数据导入
│   ├── export.php         # 数据导出
│   ├── check_urls.php     # URL检查工具
│   ├── fix_urls.php       # URL修复工具
│   ├── setup_categories.php # 分类设置
│   ├── update_db.php      # 数据库更新
│   └── assets/            # 静态资源
└── README.md              # 项目说明
```

## ⚙️ 配置说明

### 数据库配置

编辑 `public_html/config.php` 文件：

```php
// 数据库连接配置
$host = 'localhost';        // 数据库主机
$username = 'your_username'; // 数据库用户名
$password = 'your_password'; // 数据库密码
$database = 'your_database'; // 数据库名称

// 管理员认证配置
$admin_username = 'admin';          // 管理员用户名
$admin_password = 'your_password';  // 管理员密码

// 网站配置
$site_title = '晓风的个人主页';
$site_description = '精选优质工具和资源导航，提升工作效率';
$site_url = 'https://yourdomain.com';
```

### 分类配置

系统预设了多种分类，每个分类都有对应的图标和颜色主题：

- 💻 开发工具 (蓝色)
- 🎨 设计工具 (紫色)  
- ⚡ 效率工具 (绿色)
- 📚 学习资源 (黄色)
- 🎮 娱乐 (粉色)
- 💬 社交 (靛蓝)
- 🛒 购物 (红色)
- 📰 新闻 (灰色)

## 🔧 使用说明

### 添加链接

1. 访问管理后台 `/admin.php`
2. 点击"添加新链接"按钮
3. 填写链接信息（标题、URL、分类、描述）
4. 点击保存

### 管理分类

1. 在管理后台可以创建新分类
2. 支持拖拽调整分类显示顺序
3. 每个分类会自动分配图标和颜色主题

### 导入导出

- **导出**：在管理后台点击"导出数据"按钮，下载JSON格式的备份文件
- **导入**：访问 `/import.php`，上传JSON格式的数据文件

## 🛠️ 维护工具

项目包含了一些实用的维护工具：

- `check_urls.php` - 检查链接可用性
- `fix_urls.php` - 批量修复URL格式
- `setup_categories.php` - 初始化分类数据
- `update_db.php` - 数据库结构更新

## 🎨 自定义主题

可以通过修改CSS变量来自定义主题颜色：

```css
:root {
  --primary-color: #3b82f6;
  --secondary-color: #64748b;
  --background-color: #ffffff;
  --text-color: #1f2937;
}
```

## 📝 更新日志

### v1.0.0
- 初始版本发布
- 基础链接管理功能
- 响应式设计
- 管理后台
- 导入导出功能

## 🤝 贡献

欢迎提交Issue和Pull Request来改进这个项目。

## 📄 许可证

本项目采用 MIT 许可证。

## 📞 联系方式

如有问题或建议，请通过以下方式联系：

- Email: your_email@example.com
- GitHub: [您的GitHub用户名]

---

**享受使用晓风的个人主页！** 🎉
