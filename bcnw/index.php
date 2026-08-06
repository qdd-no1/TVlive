
<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// ================= 配置区域 =================
$admin_pwd = "123456";    // 默认密码
$storage_dir = "./cloud_file";
$share_dir = "./share_temp";
$zip_temp = "./zip_temp";
$pwd_config_file = "./pwd.config.txt";
$max_upload_size = 50 * 1024 * 1024; // 最大上传文件大小 50MB

// 创建存储目录
foreach ([$storage_dir, $share_dir, $zip_temp] as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0755, true);
}

// 读取自定义密码
if (file_exists($pwd_config_file)) $admin_pwd = trim(file_get_contents($pwd_config_file));

// ================= 工具函数 =================

// 文件大小格式化
function formatSize($b) {
    if ($b < 1024) return $b . " B";
    if ($b < 1048576) return round($b / 1024, 2) . " KB";
    return round($b / 1048576, 2) . " MB";
}

// 文件类型判断
function isImage($ext) { return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']); }
function isVideo($ext) { return in_array(strtolower($ext), ['mp4', 'mov', 'webm']); }
function isPdf($ext) { return strtolower($ext) === 'pdf'; }
function isOffice($ext) { return in_array(strtolower($ext), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']); }

// 安全文件名过滤非法字符
function safeFileName($name) {
    $name = mb_convert_encoding($name, 'UTF-8', mb_detect_encoding($name));
    return preg_replace('/[<>:"\/\\|?*]/', '_', $name);
}

// 安全路径：清除../防止目录穿越
function safePath($path) {
    $path = preg_replace('/\.\.\//', '', $path);
    $path = trim($path, '/');
    return $path;
}

// 获取文件图标 SVG
function getFileIcon($ext, $is_dir) {
    if ($is_dir) return '<svg class="icon icon-dir" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>';
    if (isImage($ext)) return '<svg class="icon icon-img" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
    if (isVideo($ext)) return '<svg class="icon icon-video" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
    if (isPdf($ext)) return '<svg class="icon icon-pdf" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
    if (isOffice($ext)) return '<svg class="icon icon-doc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
    return '<svg class="icon icon-file" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
}

// 遍历所有子目录
function scanAllDirs($base, $rel = "") {
    $res = [];
    $basePath = $base . ($rel ? "/" . $rel : "");
    if (!is_dir($basePath)) return [];
    $dh = opendir($basePath);
    while ($f = readdir($dh)) {
        if ($f == '.' || $f == '..') continue;
        $full = $basePath . "/" . $f;
        if (is_dir($full)) {
            $subRel = $rel ? ($rel . "/" . $f) : $f;
            $res[] = $subRel;
            $res = array_merge($res, scanAllDirs($base, $subRel));
        }
    }
    closedir($dh);
    return $res;
}

// 全局搜索函数
function globalSearchFiles($baseDir, $keyword, $relPath = "") {
    global $_SESSION;
    if (!isset($_SESSION['login']) || !$_SESSION['login']) return [];
    $result = [];
    $currentFull = rtrim($baseDir, "/") . ($relPath ? "/" . $relPath : "");
    if (!is_dir($currentFull)) return [];
    $dh = opendir($currentFull);
    while ($f = readdir($dh)) {
        if ($f == '.' || $f == '..') continue;
        $fileFull = $currentFull . "/" . $f;
        $subRel = ($relPath === "") ? $f : $relPath . "/" . $f;
        if (stripos($f, $keyword) !== false) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $result[] = [
                "full_rel" => $subRel,
                "name" => $f,
                "parent_dir" => $relPath,
                "is_dir" => is_dir($fileFull),
                "size" => is_dir($fileFull) ? 0 : filesize($fileFull),
                "mtime" => date("Y-m-d H:i", filemtime($fileFull)),
                "ext" => $ext
            ];
        }
        if (is_dir($fileFull)) {
            $childList = globalSearchFiles($baseDir, $keyword, $subRel);
            $result = array_merge($result, $childList);
        }
    }
    closedir($dh);
    return $result;
}

// 文件夹打包工具
function zipFolder($src, $zipFile) {
    if (!class_exists("ZipArchive")) return false;
    $zip = new ZipArchive();
    if (!$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) return false;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $f) {
        $rel = substr($f->getRealPath(), strlen(realpath($src)) + 1);
        $f->isDir() ? $zip->addEmptyDir($rel) : $zip->addFile($f, $rel);
    }
    $zip->close();
    return true;
}

// ================= 业务逻辑处理 =================

$login_err = "";
$msg = "";
$current_dir = isset($_GET['dir']) ? safePath($_GET['dir']) : "";
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";

// 登录处理
if (isset($_POST['login'])) {
    $pwd = trim($_POST['pwd']);
    if ($pwd === $admin_pwd) $_SESSION['login'] = true;
    else $login_err = "密码错误";
}

// 退出登录
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 未登录展示登录页
if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>白菜内部网盘 - 登录</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
    .login-card { width: 90%; max-width: 360px; padding: 40px 30px; background: rgba(255, 255, 255, 0.95); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px); }
    h2 { text-align: center; margin-bottom: 24px; color: #333; font-weight: 600; }
    .err { color: #e53e3e; text-align: center; margin-bottom: 16px; font-size: 14px; }
    input[type="password"] { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; margin-bottom: 20px; transition: border-color 0.3s; outline: none; }
    input[type="password"]:focus { border-color: #2563eb; }
    button { width: 100%; padding: 14px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; transition: background 0.3s; }
    button:hover { background: #1d4ed8; }
    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #888; }
</style>
</head>
<body>
<div class="login-card">
    <h2>🥬 白菜内部网盘</h2>
    <?php if ($login_err): ?><div class="err"><?= htmlspecialchars($login_err) ?></div><?php endif; ?>
    <form method="post">
        <input type="password" name="pwd" placeholder="请输入访问密码" required autofocus>
        <button type="submit" name="login">立即登录</button>
    </form>
    <div class="footer">安全加密存储 · 仅限内部使用</div>
</div>
</body>
</html>
    <?php
    exit;
}

// --- 已登录逻辑 ---

// 处理文件上传
if (isset($_FILES['file_upload']) && !empty($_FILES['file_upload']['name'][0])) {
    $upload_dir = $storage_dir . ($current_dir ? "/" . $current_dir : "");
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $count = count($_FILES['file_upload']['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['file_upload']['error'][$i] !== UPLOAD_ERR_OK) continue;
        
        $tmp_name = $_FILES['file_upload']['tmp_name'][$i];
        $orig_name = $_FILES['file_upload']['name'][$i];
        $safe_name = safeFileName($orig_name);
        
        // 防止同名覆盖，添加时间戳
        $ext = pathinfo($safe_name, PATHINFO_EXTENSION);
        $name_no_ext = pathinfo($safe_name, PATHINFO_FILENAME);
        $final_name = $name_no_ext . "_" . time() . ($ext ? "." . $ext : "");
        
        $target_path = $upload_dir . "/" . $final_name;
        
        if ($_FILES['file_upload']['size'][$i] > $max_upload_size) {
            $msg .= "<div class='toast error'>文件 {$safe_name} 超过大小限制 (50MB)</div>";
            continue;
        }
        
        if (move_uploaded_file($tmp_name, $target_path)) {
            $msg .= "<div class='toast success'>{$safe_name} 上传成功</div>";
        } else {
            $msg .= "<div class='toast error'>{$safe_name} 上传失败</div>";
        }
    }
}

// 处理文件删除
if (isset($_GET['delete'])) {
    $del_path_rel = safePath($_GET['delete']);
    $del_path_full = $storage_dir . "/" . $del_path_rel;
    
    // 安全检查：确保路径在 storage_dir 内
    $real_base = realpath($storage_dir);
    $real_target = realpath($del_path_full);
    
    if ($real_target && strpos($real_target, $real_base) === 0) {
        if (is_dir($del_path_full)) {
            // 递归删除目录
            $it = new RecursiveDirectoryIterator($del_path_full, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) rmdir($file->getRealPath());
                else unlink($file->getRealPath());
            }
            rmdir($del_path_full);
            $msg = "<div class='toast success'>文件夹已删除</div>";
        } else {
            unlink($del_path_full);
            $msg = "<div class='toast success'>文件已删除</div>";
        }
    } else {
        $msg = "<div class='toast error'>非法操作</div>";
    }
    
    // 删除后重定向到当前目录，防止刷新重复提交
    header("Location: index.php?dir=" . urlencode($current_dir) . "&msg=" . urlencode($msg));
    exit;
}

// 获取当前目录文件列表
$file_list = [];
$scan_path = $storage_dir . ($current_dir ? "/" . $current_dir : "");
if (is_dir($scan_path)) {
    $dh = opendir($scan_path);
    while ($f = readdir($dh)) {
        if ($f == '.' || $f == '..') continue;
        $full = $scan_path . "/" . $f;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        $file_list[] = [
            "name" => $f,
            "is_dir" => is_dir($full),
            "size" => is_dir($full) ? 0 : filesize($full),
            "mtime" => date("Y-m-d H:i", filemtime($full)),
            "ext" => $ext,
            "rel_path" => ($current_dir ? $current_dir . "/" : "") . $f
        ];
    }
    closedir($dh);
    // 排序：文件夹在前，文件在后，按名称排序
    usort($file_list, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

// 如果有搜索关键词，则显示搜索结果
$is_searching = !empty($search_keyword);
if ($is_searching) {
    $file_list = globalSearchFiles($storage_dir, $search_keyword);
}

// 面包屑导航生成
$breadcrumbs = [];
if ($current_dir) {
    $parts = explode("/", $current_dir);
    $path_acc = "";
    foreach ($parts as $part) {
        $path_acc .= ($path_acc ? "/" : "") . $part;
        $breadcrumbs[] = ["name" => $part, "path" => $path_acc];
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>白菜内部网盘</title>
<style>
    :root { --primary: #2563eb; --bg: #f8fafc; --card-bg: #ffffff; --text: #1e293b; --text-light: #64748b; --border: #e2e8f0; --danger: #ef4444; --success: #10b981; }
    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; padding-bottom: 80px; }
    
    /* Header */
    header { background: var(--card-bg); padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center; }
    .brand { font-weight: 700; font-size: 18px; color: var(--primary); display: flex; align-items: center; gap: 8px; }
    .user-actions a { color: var(--text-light); text-decoration: none; font-size: 14px; margin-left: 15px; }
    
    /* Breadcrumbs & Search */
    .controls { padding: 15px 20px; max-width: 1200px; margin: 0 auto; }
    .breadcrumbs { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 15px; font-size: 14px; color: var(--text-light); }
    .breadcrumbs a { color: var(--primary); text-decoration: none; }
    .breadcrumbs span { color: var(--text-light); }
    
    .search-bar { display: flex; gap: 10px; margin-bottom: 15px; }
    .search-bar input { flex: 1; padding: 10px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; }
    .search-bar button { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
    
    /* Upload Area */
    .upload-area { background: #eff6ff; border: 2px dashed #bfdbfe; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px; transition: all 0.3s; cursor: pointer; position: relative; }
    .upload-area:hover { background: #dbeafe; border-color: var(--primary); }
    .upload-area input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .upload-icon { font-size: 24px; margin-bottom: 8px; display: block; }
    .upload-text { font-size: 14px; color: var(--primary); font-weight: 500; }
    .upload-hint { font-size: 12px; color: var(--text-light); margin-top: 4px; }

    /* File List */
    .file-list { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .file-item { display: flex; align-items: center; background: var(--card-bg); padding: 12px 15px; margin-bottom: 10px; border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: transform 0.2s; border: 1px solid transparent; }
    .file-item:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-color: #bfdbfe; }
    
    .file-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
    .file-icon svg { width: 24px; height: 24px; }
    .icon-dir { color: #f59e0b; }
    .icon-img { color: #10b981; }
    .icon-video { color: #8b5cf6; }
    .icon-pdf { color: #ef4444; }
    .icon-doc { color: #3b82f6; }
    .icon-file { color: #94a3b8; }
    
    .file-info { flex: 1; min-width: 0; }
    .file-name { font-weight: 500; font-size: 15px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; text-decoration: none; margin-bottom: 2px; }
    .file-meta { font-size: 12px; color: var(--text-light); display: flex; gap: 10px; }
    
    .file-actions { display: flex; gap: 10px; margin-left: 10px; }
    .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-download { background: #eff6ff; color: var(--primary); }
    .btn-delete { background: #fef2f2; color: var(--danger); }
    .btn-delete:hover { background: #fee2e2; }
    
    /* Toast Messages */
    .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
    .toast { padding: 12px 20px; border-radius: 8px; color: white; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s ease-out; pointer-events: auto; max-width: 300px; }
    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    /* Mobile Optimization */
    @media (max-width: 600px) {
        .file-item { padding: 10px; }
        .file-icon { width: 32px; height: 32px; margin-right: 10px; }
        .file-icon svg { width: 20px; height: 20px; }
        .file-name { font-size: 14px; }
        .file-meta { font-size: 11px; }
        .btn-action { padding: 4px 8px; font-size: 11px; }
        .upload-area { padding: 15px; }
        .upload-text { font-size: 13px; }
    }
</style>
</head>
<body>

<!-- 消息提示 -->
<div class="toast-container" id="toastContainer">
    <?php 
    if (isset($_GET['msg'])) {
        echo $_GET['msg'];
    } elseif ($msg) {
        echo $msg;
    }
    ?>
</div>

<header>
    <div class="brand">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg>
        白菜网盘
    </div>
    <div class="user-actions">
        <a href="?logout=1">退出</a>
    </div>
</header>

<div class="controls">
    <!-- 面包屑 -->
    <div class="breadcrumbs">
        <a href="?dir=">🏠 根目录</a>
        <?php foreach ($breadcrumbs as $crumb): ?>
            <span>/</span>
            <a href="?dir=<?= urlencode($crumb['path']) ?>"><?= htmlspecialchars($crumb['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- 搜索 -->
    <form class="search-bar" method="get">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($current_dir) ?>">
        <input type="text" name="search" placeholder="搜索文件名..." value="<?= htmlspecialchars($search_keyword) ?>">
        <button type="submit">搜索</button>
        <?php if ($is_searching): ?>
            <a href="?dir=<?= urlencode($current_dir) ?>" style="padding: 10px; color: #666; text-decoration: none;">取消</a>
        <?php endif; ?>
    </form>

    <!-- 上传区域 -->
    <?php if (!$is_searching): ?>
    <div class="upload-area">
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <input type="file" name="file_upload[]" multiple onchange="document.getElementById('uploadForm').submit()">
            <span class="upload-icon">☁️</span>
            <div class="upload-text">点击或拖拽文件至此上传</div>
            <div class="upload-hint">支持多文件，单文件最大2048MB</div>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="file-list">
    <?php if (empty($file_list)): ?>
        <div style="text-align: center; padding: 40px; color: #999;">
            <p>暂无文件</p>
        </div>
    <?php else: ?>
        <?php foreach ($file_list as $file): ?>
            <div class="file-item">
                <div class="file-icon">
                    <?= getFileIcon($file['ext'], $file['is_dir']) ?>
                </div>
                <div class="file-info">
                    <?php if ($file['is_dir']): ?>
                        <a href="?dir=<?= urlencode($file['rel_path']) ?>" class="file-name"><?= htmlspecialchars($file['name']) ?></a>
                        <div class="file-meta">文件夹 · <?= $file['mtime'] ?></div>
                    <?php else: ?>
                        <a href="<?= $storage_dir . "/" . $file['rel_path'] ?>" target="_blank" class="file-name"><?= htmlspecialchars($file['name']) ?></a>
                        <div class="file-meta"><?= formatSize($file['size']) ?> · <?= $file['mtime'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="file-actions">
                    <?php if (!$file['is_dir']): ?>
                        <a href="<?= $storage_dir . "/" . $file['rel_path'] ?>" download class="btn-action btn-download">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            下载
                        </a>
                    <?php endif; ?>
                    <a href="?delete=<?= urlencode($file['rel_path']) ?>&dir=<?= urlencode($current_dir) ?>" 
                       class="btn-action btn-delete" 
                       onclick="return confirm('确定要删除 <?= htmlspecialchars($file['name']) ?> 吗？此操作不可恢复！')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        删除
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // 自动消失的 Toast
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(t => {
            t.style.opacity = '0';
            t.style.transform = 'translateY(-20px)';
            t.style.transition = 'all 0.5s';
            setTimeout(() => t.remove(), 500);
        });
    }, 3000);
</script>

</body>
</html>
