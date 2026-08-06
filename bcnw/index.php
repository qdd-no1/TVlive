<?php
session_start();
error_reporting(0);
ini_set('display_errors',0);
$admin_pwd = "123456";    // 登录密码，自行修改，因为没有数据库，只能少量人员使用或共用
$storage_dir = "./cloud_file";
$share_dir = "./share_temp";
$zip_temp = "./zip_temp";
$pwd_config_file = "./pwd.config.txt";

// 创建存储目录
foreach([$storage_dir, $share_dir, $zip_temp] as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0755, true);
}
// 读取自定义密码
if (file_exists($pwd_config_file)) $admin_pwd = trim(file_get_contents($pwd_config_file));

// 文件大小格式化
function formatSize($b){
    if($b<1024)return $b." B";
    if($b<1048576)return round($b/1024,2)." KB";
    return round($b/1048576,2)." MB";
}

// 文件类型判断
function isImage($ext){return in_array(strtolower($ext),['jpg','jpeg','png','gif','webp','bmp']);}
function isVideo($ext){return in_array(strtolower($ext),['mp4','mov','webm']);}
function isPdf($ext){return strtolower($ext) === 'pdf';}
function isOffice($ext){return in_array(strtolower($ext),['doc','docx','xls','xlsx','ppt','pptx']);}

// 安全文件名过滤非法字符
function safeFileName($name){
    $name = mb_convert_encoding($name,'UTF-8',mb_detect_encoding($name));
    return preg_replace('/[<>:"\/\\|?*]/','_',$name);
}

// 安全路径：清除../防止目录穿越
function safePath($path){
    $path = preg_replace('/\.\.\//','',$path);
    $path = trim($path, '/');
    return $path;
}

// 遍历所有子目录（移动文件下拉框使用）
function scanAllDirs($base,$rel=""){
    $res=[];
    $basePath = $base.($rel?"/".$rel:"");
    if(!is_dir($basePath))return [];
    $dh=opendir($basePath);
    while($f=readdir($dh)){
        if($f=='.'||$f=='..')continue;
        $full=$basePath."/".$f;
        if(is_dir($full)){
            $subRel = $rel?($rel."/".$f):$f;
            $res[]=$subRel;
            $res = array_merge($res,scanAllDirs($base,$subRel));
        }
    }
    closedir($dh);
    return $res;
}

// 【修复版全局搜索函数】修复路径、增加安全过滤、session权限校验前置
function globalSearchFiles($baseDir, $keyword, $relPath = ""){
    global $_SESSION;
    // 未登录直接返回空
    if(!isset($_SESSION['login']) || !$_SESSION['login']) return [];
    $result = [];
    $currentFull = rtrim($baseDir,"/") . ($relPath ? "/".$relPath : "");
    if(!is_dir($currentFull)) return [];
    $dh = opendir($currentFull);
    while($f = readdir($dh)){
        if($f == '.' || $f == '..') continue;
        $fileFull = $currentFull . "/" . $f;
        $subRel = ($relPath === "") ? $f : $relPath."/".$f;
        // 关键词匹配
        if(stripos($f, $keyword) !== false){
            $ext = strtolower(pathinfo($f,PATHINFO_EXTENSION));
            $result[] = [
                "full_rel" => $subRel,
                "name" => $f,
                "parent_dir" => $relPath,
                "is_dir" => is_dir($fileFull),
                "size" => is_dir($fileFull) ? 0 : filesize($fileFull),
                "mtime" => date("Y-m-d H:i",filemtime($fileFull)),
                "ext" => $ext
            ];
        }
        // 递归子目录
        if(is_dir($fileFull)){
            $childList = globalSearchFiles($baseDir, $keyword, $subRel);
            $result = array_merge($result, $childList);
        }
    }
    closedir($dh);
    return $result;
}

// 文件夹打包工具
function zipFolder($src,$zipFile){
    if(!class_exists("ZipArchive"))return false;
    $zip=new ZipArchive();
    if(!$zip->open($zipFile,ZipArchive::CREATE|ZipArchive::OVERWRITE))return false;
    $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach($files as $f){
        $rel = substr($f->getRealPath(),strlen(realpath($src))+1);
        $f->isDir()?$zip->addEmptyDir($rel):$zip->addFile($f,$rel);
    }
    $zip->close();
    return true;
}
// 登录处理
$login_err = "";
if(isset($_POST['login'])){
    $pwd = trim($_POST['pwd']);
    if($pwd === $admin_pwd)$_SESSION['login']=true;
    else $login_err="密码错误";
}
// 退出登录
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit;
}
// 未登录展示登录页
if(!isset($_SESSION['login'])||!$_SESSION['login']){
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>白菜内部网盘</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:system-ui}
body{
display:flex;justify-content:center;align-items:center;height:100vh;
background: url("./login_bg.jpg") no-repeat center center;
background-size: cover;
background-color: rgba(0,0,0.4);
background-blend-mode: multiply;
}
.card{
width:340px;padding:35px;background:rgba(255,255,255,0.92);
border-radius:12px;box-shadow:0 4px 20px #00000033;
backdrop-filter: blur(8px);
-webkit-backdrop-filter: blur(8px);
}
h2{text-align:center;margin-bottom:24px;color:#222}
.err{color:#e53e3e;text-align:center;margin-bottom:16px}
input{width:100%;padding:13px;border:1px solid #d0d7e3;border-radius:8px;font-size:16px;margin-bottom:16px}
button{width:100%;padding:13px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:16px}
</style>
</head>
<body>
<div class="card">
    <h2>白菜内部网盘</h2>
    <?php if($login_err):?><div class="err"><?php echo $login_err;?></div><?php endif;?>
    <form method="post">
        <input type="password" name="pwd" placeholder="访问密码" required>
        <button name="login">登录</button>
    </form>
</body>
</html>
<?php exit;}


// 当前目录、排序参数
$current_folder = isset($_GET['dir'])?safePath($_GET['dir']):"";
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortorder = isset($_GET['sortorder']) ? $_GET['sortorder'] : '';
$allow_sortby = ['name','mtime'];
$allow_order = ['asc','desc'];
if(!in_array($sortby,$allow_sortby)) $sortby = '';
if(!in_array($sortorder,$allow_order)) $sortorder = '';
$real_folder = $storage_dir."/".$current_folder;
$siteRoot = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), "/") . "/";

// ==========【修复后的搜索接口】==========
// 统一优先接收FormData表单POST，废弃json解析分支（原BUG根源）
if(isset($_POST['global_search'])){
    header("Content-Type:application/json;charset=utf-8");
    // 安全过滤关键词
    $kw = trim($_POST['kw'] ?? '');
    $kw = safePath($kw);
    if(empty($kw)){
        echo json_encode(["list"=>[]],JSON_UNESCAPED_UNICODE);
        exit;
    }
    $resultList = globalSearchFiles($storage_dir, $kw);
    echo json_encode(["list"=>$resultList],JSON_UNESCAPED_UNICODE);
    exit;
}

// AJAX 创建分享
if(isset($_POST['ajax_create_share'])){
    header("Content-Type:application/json;charset=utf-8");
    $shareFile = safePath($_POST['share_file']);
    $isDirShare = isset($_POST['is_dir_share']) && $_POST['is_dir_share'] === '1';
    $exp = (int)$_POST['share_expire'];
    $pwd = trim($_POST['share_pwd']);
    $token = md5(uniqid().time());
    $expireTime = $exp === 0 ? 0 : time() + $exp * 3600;
    $data = json_encode([
        "token"=>$token,
        "path"=>$current_folder,
        "file"=>$shareFile,
        "is_dir"=>$isDirShare,
        "pwd"=>$pwd,
        "expire"=>$expireTime,
        "create_time"=>time()
    ],JSON_UNESCAPED_UNICODE);
    file_put_contents($share_dir."/".$token.".txt",$data);
    $shareUrl = $siteRoot."index.php?share=".$token;
    echo json_encode([
        "code"=>1,
        "url"=>$shareUrl,
        "pwd"=>$pwd,
        "token"=>$token
    ],JSON_UNESCAPED_UNICODE);
    exit;
}

// AJAX 获取分享列表
if(isset($_POST['ajax_get_share_list'])){
    header("Content-Type:application/json;charset=utf-8");
    $list = [];
    $dh = opendir($share_dir);
    while($f = readdir($dh)){
        if($f == '.' || $f == '..') continue;
        if(pathinfo($f,PATHINFO_EXTENSION) !== 'txt') continue;
        $token = pathinfo($f,PATHINFO_FILENAME);
        $filePath = $share_dir."/".$f;
        $raw = file_get_contents($filePath);
        $info = json_decode($raw,true);
        if(!is_array($info)) continue;
        $now = time();
        $isExpire = ($info['expire'] !== 0) && ($now > $info['expire']);
        $shareUrl = $siteRoot."index.php?share=".$token;
        $list[] = [
            "token"=>$token,
            "url"=>$shareUrl,
            "name"=>$info['file'],
            "parent_path"=>$info['path'],
            "is_dir"=>$info['is_dir'],
            "pwd"=>$info['pwd'],
            "expire"=>$info['expire'],
            "create_time"=>$info['create_time'],
            "is_expire"=>$isExpire,
            "expire_text"=>$info['expire']===0?"永久有效":date("Y-m-d H:i",$info['expire'])
        ];
    }
    closedir($dh);
    usort($list,function($a,$b){
        return $b['create_time'] - $a['create_time'];
    });
    echo json_encode(["list"=>$list],JSON_UNESCAPED_UNICODE);
    exit;
}

// AJAX 删除分享
if(isset($_POST['ajax_del_share'])){
    header("Content-Type:application/json;charset=utf-8");
    $token = safePath($_POST['token']);
    $file = $share_dir."/".$token.".txt";
    if(file_exists($file)){
        unlink($file);
        echo json_encode(["code"=>1,"msg"=>"删除成功"]);
    }else{
        echo json_encode(["code"=>0,"msg"=>"分享不存在"]);
    }
    exit;
}

// 文件流预览（图片/视频/PDF）
if(isset($_GET['stream'])){
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET,OPTIONS");
    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
    $fileRel = safePath($_GET['stream']);
    $full = $storage_dir . "/" . $fileRel;
    if(!file_exists($full))die("文件不存在");
    $ext = strtolower(pathinfo($full,PATHINFO_EXTENSION));
    if(isImage($ext))header("Content-Type:image/*");
    elseif(isVideo($ext))header("Content-Type:video/*");
    elseif(isPdf($ext))header("Content-Type:application/pdf");
    else header("Content-Type:application/octet-stream");
    readfile($full);
    exit;
}

// AJAX 创建文件夹
if(isset($_POST['ajax_mkdir'])){
    header("Content-Type:application/json;charset=utf-8");
    $target_sub = trim($_POST['target_dir'] ?? '');
    $target_sub = safePath($target_sub);
    if(empty($target_sub)){
        echo json_encode(["code"=>0,"msg"=>"目录名称不能为空"]);
        exit;
    }
    $full_mk = rtrim($real_folder, '/') . '/' . $target_sub;
    $storageReal = realpath($storage_dir);
    $parentReal = realpath(rtrim($real_folder, '/'));
    if(!$parentReal || strpos($parentReal, $storageReal) !== 0){
        echo json_encode(["code"=>0,"msg"=>"非法目录路径"]);
        exit;
    }
    if(!file_exists($full_mk)){
        $res = mkdir($full_mk, 0755, true);
        if($res){
            echo json_encode(["code"=>1,"msg"=>"目录创建成功"]);
        }else{
            echo json_encode(["code"=>0,"msg"=>"目录创建失败，服务器权限不足"]);
        }
    }else{
        echo json_encode(["code"=>1,"msg"=>"目录已存在"]);
    }
    exit;
}

// AJAX 分片/拖拽上传
if(isset($_POST['ajax_upload'])){
    header("Content-Type:application/json;charset=utf-8");
    if(empty($_FILES['file'])){
        echo json_encode(["code"=>0,"msg"=>"未检测到文件"]);
        exit;
    }
    $file = $_FILES['file'];
    $oriName = $file['name'];
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    if($fileSize <= 0){
        echo json_encode(["code"=>0,"msg"=>"文件为空"]);
        exit;
    }
    $safeName = safeFileName($oriName);
    $relPath = trim($_POST['relative_path'] ?? '');
    $relPath = safePath($relPath);
    if(!empty($relPath)){
        $subDir = dirname($relPath);
        $targetDir = rtrim($real_folder, '/') . '/' . $subDir;
        if(!file_exists($targetDir)){
            mkdir($targetDir, 0755, true);
        }
        $saveFull = $targetDir . '/' . $safeName;
    }else{
        $saveFull = rtrim($real_folder, '/') . '/' . $safeName;
    }
    if(move_uploaded_file($tmpPath, $saveFull)){
        echo json_encode(["code"=>1,"msg"=>"上传成功"]);
    }else{
        echo json_encode(["code"=>0,"msg"=>"服务器写入失败，检查目录权限0755"]);
    }
    exit;
}

// 表单批量上传文件
if(!empty($_FILES['upfiles'])){
    $files = $_FILES['upfiles'];
    $cnt = count($files['name']);
    for($i=0;$i<$cnt;$i++){
        $fn = safeFileName($files['name'][$i]);
        $tmp = $files['tmp_name'][$i];
        $sz = $files['size'][$i];
        if($sz<=0)continue;
        move_uploaded_file($tmp,$real_folder."/".$fn);
    }
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 修改后台登录密码
if(isset($_POST['change_pwd'])){
    $p1 = trim($_POST['new_pwd1']);
    $p2 = trim($_POST['new_pwd2']);
    if($p1!==$p2){echo "<script>alert('两次密码不一致');history.back()</script>";exit;}
    file_put_contents($pwd_config_file,$p1);
    echo "<script>alert('密码修改成功，请重新登录');location.href='?logout=1'</script>";exit;
}

// 新建文件夹表单
if(isset($_POST['new_folder'])){
    $rawName = trim($_POST['folder_name']);
    $name = safeFileName($rawName);
    if(empty($name)){
        echo "<script>alert('文件夹名称不能为空！');history.back();</script>";
        exit;
    }
    $newDir = $real_folder."/".$name;
    if(file_exists($newDir)){
        echo "<script>alert('同名文件夹已存在！');history.back();</script>";
    }
    mkdir($newDir,0755,true);
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 单文件/文件夹删除
if(isset($_GET['del'])){
    $target = safePath($_GET['del']);
    $delPath = $real_folder."/".$target;
    if(is_dir($delPath)){
        $di = new RecursiveDirectoryIterator($delPath,RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di,RecursiveIteratorIterator::CHILD_FIRST);
        foreach($ri as $f)unlink($f);
        rmdir($delPath);
    }elseif(file_exists($delPath))unlink($delPath);
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 批量删除
if(isset($_POST['batch_del']) && !empty($_POST['batch_list'])){
    $list = $_POST['batch_list'];
    foreach($list as $item){
        $item = safePath($item);
        $p = $real_folder."/".$item;
        $realBase = realpath($storage_dir);
        $realTarget = realpath($p);
        if(!$realTarget || strpos($realTarget, $realBase) !== 0){
            continue;
        }
        if(is_dir($p)){
            $di = new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS);
            $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($ri as $fileObj){
                $filePath = $fileObj->getRealPath();
                if(is_dir($filePath)){
                    rmdir($filePath);
                }else{
                    unlink($filePath);
                }
            }
            rmdir($p);
        }elseif(file_exists($p)){
            unlink($p);
        }
    }
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 重命名
if(isset($_POST['rename'])){
    $old = safePath($_POST['old_name']);
    $new = safeFileName(trim($_POST['new_name']));
    $oldP = $real_folder."/".$old;
    $newP = $real_folder."/".$new;
    if(file_exists($oldP)&&!file_exists($newP))rename($oldP,$newP);
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 移动文件/文件夹
if(isset($_POST['move_file'])){
    $src = safePath($_POST['src_name']);
    $dstDir = safePath($_POST['dst_dir']);
    $srcP = $real_folder."/".$src;
    $dstP = $storage_dir.($dstDir?"/".$dstDir:"")."/".$src;
    if(file_exists($srcP)&&!file_exists($dstP))rename($srcP,$dstP);
    $redirectUrl = "index.php?dir=".urlencode($current_folder);
    if($sortby)$redirectUrl.="&sortby=".urlencode($sortby);
    if($sortorder)$redirectUrl.="&sortorder=".urlencode($sortorder);
    header("Location: ".$redirectUrl);exit;
}

// 打包文件夹下载
if(isset($_GET['zip'])){
    $zipName = safePath($_GET['zip']);
    $srcDir = $real_folder."/".$zipName;
    $zipFile = $zip_temp."/".uniqid().".zip";
    if(zipFolder($srcDir,$zipFile)){
        header("Content-Type:application/zip;charset=utf-8");
        header("Content-Disposition:attachment;filename=".rawurlencode($zipName).".zip");
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }
    die("打包失败，请开启ZipArchive");
}

// 单文件下载
if(isset($_GET['download'])){
    $fn = safePath($_GET['download']);
    $full = $real_folder."/".$fn;
    if(!file_exists($full))die("文件不存在");
    header("Content-Type:application/octet-stream;charset=utf-8");
    header("Content-Disposition:attachment;filename=".rawurlencode($fn));
    readfile($full);exit;
}

// 分享提取密码页面
if(isset($_GET['share'])){
    $tk = safePath($_GET['share']);
    $sf = $share_dir."/".$tk.".txt";
    if(!file_exists($sf))die("链接失效");
    $data = json_decode(file_get_contents($sf),true);
    if($data['expire'] != 0 && time() > $data['expire']){
        unlink($sf);
        die("已过期");
    }
    if(!isset($_POST['share_pwd_check'])){
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>输入提取密码</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:system-ui}
body{display:flex;justify-content:center;align-items:center;height:100vh;background:#f0f4f8}
.box{width:320px;padding:30px;background:#fff;border-radius:10px;box-shadow:0 4px 16px #ddd}
h3{text-align:center;margin-bottom:20px;color:#222}
input{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;margin-bottom:16px;font-size:16px}
button{width:100%;padding:12px;background:#2563eb;color:#fff;border:none;border-radius:6px}
.err{color:red;text-align:center;margin-bottom:10}
</style>
</head>
<body>
<div class="box">
    <h3>文件提取密码</h3>
    <?php if(isset($_GET['err']))echo '<div class="err">密码错误</div>';?>
    <form method="post">
        <input type="password" name="share_pwd_check" placeholder="密码" required>
        <button>确认访问</button>
    </form>
</body>
</html>
<?php exit;}
    if($_POST['share_pwd_check']!==$data['pwd']){
        header("Location:?share=$tk&err=1");
        exit;
    }
    $targetFullDir = trim($data['path'],'/');
    if($targetFullDir !== '') $targetFullDir .= '/';
    $targetFullDir .= $data['file'];
    if($data['is_dir']){
        $jumpUrl = "index.php?dir=".urlencode($targetFullDir);
        echo "<script>location.href='".$jumpUrl."'</script>";
        exit;
    }else{
        $downP = $storage_dir.($data['path']?"/".$data['path']:"")."/".$data['file'];
        header("Content-Type:application/octet-stream;charset=utf-8");
        header("Content-Disposition:attachment;filename=".rawurlencode($data['file']));
        readfile($downP);
        exit;
    }
}

// 读取当前目录文件列表
$list = [];
if(is_dir($real_folder)){
    $dh = opendir($real_folder);
    while($f=readdir($dh)){
        if($f=='.'||$f=='..')continue;
        $full = $real_folder."/".$f;
        $ext = strtolower(pathinfo($full,PATHINFO_EXTENSION));
        $list[] = [
            "name"=>$f,
            "is_dir"=>is_dir($full),
            "size"=>is_dir($full)?0:filesize($full),
            "mtime"=>date("Y-m-d H:i",filemtime($full)),
            "ext"=>$ext
        ];
    }
    closedir($dh);
}

// 文件列表排序
usort($list,function($a,$b) use ($sortby,$sortorder){
    $dirA = $a['is_dir'] ? 1 : 0;
    $dirB = $b['is_dir'] ? 1 : 0;
    if($dirA != $dirB){
        return $dirB - $dirA;
    }
    if(empty($sortby)){
        return strcmp($a['name'],$b['name']);
    }
    if($sortby == 'name'){
        $cmp = strcmp($a['name'],$b['name']);
        return $sortorder == 'desc' ? -$cmp : $cmp;
    }
    if($sortby == 'mtime'){
        $t1 = strtotime($a['mtime']);
        $t2 = strtotime($b['mtime']);
        if($t1 == $t2) return 0;
        $cmp = $t1 > $t2 ? 1 : -1;
        return $sortorder == 'desc' ? -$cmp : $cmp;
    }
    return strcmp($a['name'],$b['name']);
});

// 获取全部目录（移动文件下拉）
$allDirs = scanAllDirs($storage_dir);

// 面包屑导航
$crumbs = [];
$temp = "";
$crumbs[] = ["name"=>"根目录","url"=>"index.php"];
$seg = explode("/",$current_folder);
foreach($seg as $s){
    if(trim($s)=="")continue;
    $temp .= ($temp?"/":"").$s;
    $crumbs[] = ["name"=>$s,"url"=>"index.php?dir=".urlencode($temp)];
}

// 排序链接生成函数
function buildSortUrl($targetSortBy,$currentDir,$currSortBy,$currSortOrder){
    $params = [];
    if($currentDir)$params['dir'] = $currentDir;
    if($targetSortBy == $currSortBy){
        $newOrder = ($currSortOrder == 'asc') ? 'desc' : 'asc';
    }else{
        $newOrder = 'asc';
    }
    $params['sortby'] = $targetSortBy;
    $params['sortorder'] = $newOrder;
    $qs = http_build_query($params);
    return "index.php?".$qs;
}
function getSortArrow($field,$currSortBy,$currSortOrder){
    if($currSortBy != $field) return '';
    return $currSortOrder == 'asc' ? ' ▲' : ' ▼';
}
$url_name_sort = buildSortUrl('name',$current_folder,$sortby,$sortorder);
$url_mtime_sort = buildSortUrl('mtime',$current_folder,$sortby,$sortorder);
$arrow_name = getSortArrow('name',$sortby,$sortorder);
$arrow_mtime = getSortArrow('mtime',$sortby,$sortorder);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta http-equiv="Access-Control-Allow-Origin" content="*">
<title>白菜内部网盘</title>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.4.120/build/pdf.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:system-ui}
body{background:#f5f7fa;padding:24px}
.top{display:flex;justify-content:space-between;align-items:center;background:#2563eb;color:#fff;padding:16px 22px;border-radius:10px;margin-bottom:16px}
.top-right a{color:#fff;text-decoration:none;padding:8px 14px;border-radius:6px;margin-left:8px}
.logout{background:#dc2626}
.pwd-btn{background:#0891b2}
.share-manage-btn{background:#047857}
.search-bar{background:#fff;padding:14px;border-radius:10px;margin-bottom:16px;display:flex;gap:10px}
#search-input{flex:1;padding:10px;border:1px solid #ddd;border-radius:8px}
#search-btn{padding:10px 20px;background:#2563eb;color:#fff;border:none;border-radius:8px}
.upload-area{background:#fff;padding:22px;border-radius:10px;box-shadow:0 1px 8px #e2e8f0;margin-bottom:16px}
.upload-area h3{margin-bottom:12px;color:#333}
.upload-tip{color:#999;font-size:13px;margin:8px 0}
.upload-row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
#drop-box{border:2px dashed #2563eb;padding:20px;border-radius:8px;width:100%;margin:10px 0;text-align:center;color:#2563eb}
#progress-box{display:none;margin-top:10px}
#progress-bar{height:10px;background:#eee;border-radius:5px}
#progress-inner{height:10px;width:0%;background:#2563eb;border-radius:5px}
table{width:100%;background:#fff;border-radius:10px;box-shadow:0 1px 8px #e2e8f0;border-collapse:collapse;overflow:hidden}
th,td{padding:14px 16px;text-align:left;border-bottom:1px solid #eee}
th{background:#f1f5f9}
th a{color:#111;text-decoration:none;display:block;}
th a:hover{color:#2563eb;}
.op a{text-decoration:none;padding:6px 9px;border-radius:6px;color:#fff;font-size:12px;margin-right:5px}
.dir{background:#059669}
.down{background:#2563eb}
.pre-img{background:#9333ea}
.pre-video{background:#ea580c}
.pre-pdf{background:#d97706}
.pre-office{background:#0284c7}
.share{background:#ca8a04}
.rename{background:#6366f1}
.move{background:#047857}
.zip{background:#0891b2}
.del{background:#dc2626}
.empty{text-align:center;padding:50px;color:#777}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0.85);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-content{background:#fff;padding:24px;border-radius:10px;width:95%;max-width:800px;max-height:95%;overflow-y:auto;display:flex;flex-direction:column}
.close{position:absolute;top:20px;right:30px;color:#fff;font-size:34px;cursor:pointer;z-index:1000}
.img-modal img{max-width:90%;max-height:90vh}
video{max-width:90%;max-height:90vh}
#pdf-wrap{flex:1;overflow:auto;display:flex;justify-content:center;align-items:flex-start;background:#eee;padding:10px;min-height:400px;}
#pdf-canvas{display:block;box-shadow:0 0 8px #999;background:#fff;}
.page-bar{display:flex;gap:10px;margin:10px 0;justify-content:center}
.page-bar button{padding:6px 14px}
input,select{padding:9px;border:1px solid #ddd;border-radius:6px;margin:8px 0;width:100%}
button{padding:10px 18px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer}
.batch-del-btn{background:#dc2626;margin-bottom:10px}
.search-path-tip{color:#666;font-size:12px;margin-top:4px}
.copy-sm{padding:4px 8px;font-size:12px;margin-left:6px}
.del-sm{background:#dc2626;padding:4px;font-size:12}
.expire-tag{color:#dc2626;font-weight:bold}
.normal-tag{color:#059669}
</style>
</head>
<body>
<div class="top">
    <h1>白菜内部网盘</h1>
    <div class="top-right">
        <!-- <a href="javascript:openShareManageModal()" class="share-manage-btn">分享管理</a>
        <a href="javascript:openPwdModal()" class="pwd-btn">修改密码</a> -->
        <a href="?logout=1" class="logout">退出登录</a>
    </div>
</div>
<div class="search-bar">
    <input type="text" id="search-input" placeholder="全局搜索所有目录文件名/文件夹名">
    <button id="search-btn">搜索</button>
</div>
<div style="margin-bottom:16px;background:#fff;padding:12px;border-radius:8px">
    <?php foreach($crumbs as $cr): ?>
        <a href="<?php echo $cr['url'] ?>"><?php echo htmlspecialchars($cr['name']) ?></a>
        <?php if(end($crumbs)!==$cr): ?> / <?php endif; ?>
    <?php endforeach; ?>
</div>
<div class="upload-area">
    <h3>文件/文件夹上传管理</h3>
    <div class="upload-tip">✅ 批量多文件夹上传：Ctrl多选文件夹拖拽；<br>⚠️ 按钮单次仅选1个文件夹，可多次追加</div>
    <div id="drop-box">拖拽多个文件夹/文件批量上传</div>
    <div class="upload-row">
        <input type="file" id="file-input" multiple hidden>
        <button onclick="document.getElementById('file-input').click()">选择多个文件</button>
        <input type="file" id="multi-folder-input" multiple hidden webkitdirectory directory>
        <!-- <button onclick="document.getElementById('multi-folder-input').click()">选择文件夹（单次1个）</button> -->
        <input type="file" id="folder-input" multiple hidden webkitdirectory directory>
        <button onclick="document.getElementById('folder-input').click()">上传单个文件夹</button>----------------------------
        
        <form method="post" style="display:flex;gap:8px;align-items:center;margin:0;">
            <input type="text" name="folder_name" placeholder="新建文件夹" required style="margin:0;">
            <button type="submit" name="new_folder">新建</button>
        </form>
    </div>
    <div id="progress-box">
        <div id="progress-bar"><div id="progress-inner"></div></div>
        <span id="progress-text">0%</span>
    </div>
</div>
<form method="post" id="batchForm">
    <input type="hidden" name="batch_del" value="1">
    <button type="submit" class="batch-del-btn" onclick="return confirm('确定批量删除？')">批量删除选中</button>
<table id="file-table">
    <tr>
        <th><input type="checkbox" id="checkAll">全选</th>
        <th><a href="<?php echo $url_name_sort; ?>">名称<?php echo $arrow_name; ?></a></th>
        <th>大小</th>
        <th><a href="<?php echo $url_mtime_sort; ?>">修改时间<?php echo $arrow_mtime; ?></a></th>
        <th>操作</th>
    </tr>
    <?php if(empty($list)): ?>
    <tr><td colspan="5" class="empty">目录为空</td></tr>
    <?php else:foreach($list as $item): ?>
    <tr data-name="<?php echo htmlspecialchars(strtolower($item['name'])) ?>">
        <td><input class="batch-check" name="batch_list[]" value="<?php echo htmlspecialchars($item['name']) ?>" type="checkbox"></td>
        <td>
            <?php if($item['is_dir']): ?>
                <a href="index.php?dir=<?php echo urlencode($current_folder.($current_folder?"/":"").$item['name']) ?>">📁 <?php echo htmlspecialchars($item['name']) ?></a>
            <?php else: ?>
                📄 <?php echo htmlspecialchars($item['name']) ?>
            <?php endif; ?>
        </td>
        <td><?php echo $item['is_dir']?"文件夹":formatSize($item['size']) ?></td>
        <td><?php echo $item['mtime'] ?></td>
        <td class="op">
            <?php if($item['is_dir']): ?>
                <!-- <a href="index.php?dir=<?php echo urlencode($current_folder.($current_folder."/".$item['name'])) ?>" class="dir">进入</a> -->
                <a href="javascript:openZip('<?php echo htmlspecialchars($item['name']) ?>')" class="zip">打包</a>
                <a href="javascript:openShare('<?php echo htmlspecialchars($item['name']) ?>',1)" class="share">分享文件夹</a>
            <?php else: ?>
                <a href="?download=<?php echo urlencode($current_folder."/".$item['name']) ?>" class="down">下载</a>
                <?php if(isImage($item['ext'])): ?>
                    <a href="javascript:openImg('<?php echo rawurlencode($current_folder."/".$item['name']) ?>')" class="pre-img">看图</a>
                <?php endif; ?>
                <?php if(isVideo($item['ext'])): ?>
                    <a href="javascript:openVideo('<?php echo rawurlencode($current_folder."/".$item['name']) ?>')" class="pre-video">播放</a>
                <?php endif; ?>
                <?php if(isPdf($item['ext'])): ?>
                    <a href="javascript:openPdf('<?php echo rawurlencode($current_folder."/".$item['name']) ?>')" class="pre-pdf">预览PDF</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="javascript:openRename('<?php echo htmlspecialchars($item['name']) ?>')" class="rename">重命名</a>
            <a href="javascript:openMove('<?php echo htmlspecialchars($item['name']) ?>')" class="move">移动</a>
            <!-- <a href="?del=<?php echo urlencode($item['name']) ?>&dir=<?php echo urlencode($current_folder) ?>" class="del" onclick="return confirm('删除？')">删除</a> -->
        </td>
    </tr>
    <?php endforeach;endif; ?>
</table>
<!-- 全局搜索结果表格 -->
<table id="search-result-table" style="display:none;margin-top:16px;">
    <thead>
        <tr>
            <th>全局搜索结果</th>
            <th>大小</th>
            <th>所在目录</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody id="search-result-body"></tbody>
</table>
<!-- 弹窗模块 -->
<div class="modal" id="share-manage-modal"><span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <h3>全部分享管理</h3>
        <div style="margin:10px 0">
            <button id="refresh-share-list">刷新列表</button>
        </div>
        <table id="share-list-table" style="width:100%;margin-top:10px">
            <thead>
                <tr>
                    <th>分享对象</th>
                    <th>类型</th>
                    <th>链接</th>
                    <th>提取密码</th>
                    <th>有效期</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="share-list-body">
                <tr><td colspan="6" class="empty">暂无分享记录</td></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="modal img-modal" id="img-modal"><span class="close" onclick="closeAll()">×</span><img id="img-show"></div>
<div class="modal" id="video-modal"><span class="close" onclick="closeAll()"><video id="video-show" controls></video></div>
<div class="modal" id="pdf-modal">
    <span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <div class="page-bar">
            <button id="pdfPrev">上一页</button>
            <span id="pdfPageInfo">1/1</span>
            <button id="pdfNext">下一页</button>
        </div>
        <div id="pdf-wrap">
            <canvas id="pdf-canvas"></canvas>
        </div>
    </div>
</div>
<div class="modal" id="pwd-modal"><span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <h3>修改登录密码</h3>
        <form method="post">
            <input type="password" name="new_pwd1" placeholder="新密码" required>
            <input type="password" name="new_pwd2" placeholder="确认密码" required>
            <button type="submit" name="change_pwd">确认修改</button>
        </form>
    </div>
</div>
<div class="modal" id="share-modal"><span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <h3>生成带密码分享</h3>
        <div>
            <input type="hidden" name="share_file" id="share-file-name">
            <input type="hidden" name="is-dir-share" id="is-dir-share" value="0">
            <label>提取密码</label>
            <input id="share-pwd-input" value="<?php echo substr(md5(time()),0,6) ?>" required>
            <label>有效期</label>
            <select id="share-expire-select">
                <option value="0">永久有效</option>
                <option value="1">1小时</option>
                <option value="6">6小时</option>
                <option value="24">24小时</option>
                <option value="72">3天</option>
                <option value="168">7天</option>
            </select>
            <br>
            <button id="btn-generate-share">生成链接</button>
        </div>
        <div style="margin-top:16px;border-top:1px solid #eee;padding-top:16px">
            <div>分享链接：<input readonly id="share-url-box" style="width:100%"></div>
            <div style="margin-top:8px">提取密码：<input readonly id="share-pwd-box" style="width:100%"></div>
            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
                <button onclick="copyShareUrl()">一键复制链接</button>
                <button onclick="copySharePwd()">一键复制密码</button>
                <button onclick="copyAllShare()">复制链接+密码</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="rename-modal"><span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <h3>重命名</h3>
        <form method="post">
            <input type="hidden" name="old_name" id="rename-old">
            <input id="rename-new" name="new_name" required>
            <button name="rename">确认</button>
        </form>
    </div>
</div>
<div class="modal" id="move-modal"><span class="close" onclick="closeAll()">×</span>
    <div class="modal-content">
        <h3>移动文件</h3>
        <form method="post">
            <input type="hidden" name="src_name" id="move-src">
            <select name="dst_dir">
                <option value="">根目录</option>
                <?php foreach($allDirs as $d): ?>
                <option value="<?php echo htmlspecialchars($d) ?>"><?php echo htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
            <button name="move_file">确认移动</button>
        </form>
    </div>
</div>


<script>
const pdfjsLib = window['pdfjs-dist/build/pdf'];
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.4.120/build/pdf.worker.min.js';
let pdfDoc = null, pdfCurPage = 1;
let renderLock = false;
const currentDir = "<?php echo htmlspecialchars($current_folder) ?>";
const siteRoot = "<?php echo $siteRoot ?>";
let currentShareUrl = "";
let currentSharePwd = "";
function closeAll(){
    document.querySelectorAll('.modal').forEach(m=>m.style.display='none');
    const video = document.getElementById('video-show');
    video.pause();
    video.src = '';
    document.getElementById('img-show').src = '';
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0,0,canvas.width,canvas.height);
    if(pdfDoc){
        pdfDoc.destroy();
        pdfDoc = null;
    }
    pdfCurPage = 1;
}
function openImg(rel){
    const src = `${siteRoot}index.php?stream=${encodeURIComponent(rel)}`;
    document.getElementById('img-show').src = src;
    document.getElementById('img-modal').style.display='flex';
}
function openVideo(rel){
    const src = `${siteRoot}index.php?stream=${encodeURIComponent(rel)}`;
    document.getElementById('video-show').src = src;
    document.getElementById('video-modal').style.display='flex';
}
async function renderPdf(page){
    if(renderLock) return;
    renderLock = true;
    try{
        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0,0,canvas.width,canvas.height);
        const pageObj = await pdfDoc.getPage(page);
        const origViewport = pageObj.getViewport({scale:1});
        const wrap = document.getElementById('pdf-wrap');
        const maxWidth = Math.max(wrap.clientWidth - 40, 300);
        const scale = maxWidth / origViewport.width;
        const view = pageObj.getViewport({scale:scale});
        canvas.width = view.width;
        canvas.height = view.height;
        await pageObj.render({canvasContext: ctx, viewport: view}).promise;
        document.getElementById('pdfPageInfo').innerText = `${page} / ${pdfDoc.numPages}`;
    }catch(err){
        console.error("PDF渲染失败：",err);
        alert("PDF渲染失败："+err.message);
    }finally{
        renderLock = false;
    }
}
async function openPdf(rel){
    closeAll();
    const modal = document.getElementById('pdf-modal');
    modal.style.display = 'flex';
    setTimeout(async ()=>{
        try{
            const url = `${siteRoot}index.php?stream=${encodeURIComponent(rel)}`;
            const loadingTask = pdfjsLib.getDocument(url);
            pdfDoc = await loadingTask.promise;
            pdfCurPage = 1;
            await renderPdf(pdfCurPage);
        }catch(err){
            console.error("加载PDF失败：",err);
            alert("加载PDF文件失败："+err.message);
            closeAll();
        }
    },200);
}
document.getElementById('pdfPrev').onclick = async ()=>{
    if(!pdfDoc||pdfCurPage<=1||renderLock)return;
    pdfCurPage--;await renderPdf(pdfCurPage);
}
document.getElementById('pdfNext').onclick = async ()=>{
    if(!pdfDoc||pdfCurPage>=pdfDoc.numPages||renderLock)return;
    pdfCurPage++;await renderPdf(pdfCurPage);
}
function openPwdModal(){document.getElementById('pwd-modal').style.display='flex';}
function openShare(name, isDir=0){
    document.getElementById('share-file-name').value=name;
    document.getElementById('is-dir-share').value = isDir;
    document.getElementById('share-modal').style.display='flex';
    currentShareUrl = "";
    currentSharePwd = "";
    document.getElementById('share-url-box').value = "";
    document.getElementById('share-pwd-box').value = "";
}
function openShareManageModal(){
    document.getElementById('share-manage-modal').style.display='flex';
    loadShareList();
}
async function loadShareList(){
    const tbody = document.getElementById('share-list-body');
    tbody.innerHTML = '<tr><td colspan="6" class="empty">加载中...</td></tr>';
    const fd = new FormData();
    fd.append("ajax_get_share_list","1");
    const res = await fetch(location.href,{method:"POST",body:fd});
    const json = await res.json();
    const list = json.list;
    if(list.length === 0){
        tbody.innerHTML = '<tr><td colspan="6" class="empty">暂无分享记录</td>';
        return;
    }
    let html = "";
    list.forEach(item=>{
        const typeText = item.is_dir ? "文件夹" : "文件";
        const expireClass = item.is_expire ? "expire-tag" : "normal-tag";
        let copyText = `分享链接：${item.url}\n密码：${item.pwd}`;
        html += `<tr>
            <td>${item.name}</td>
            <td>${typeText}</td>
            <td style="max-width:220;overflow:hidden;text-overflow:ellipsis">${item.url}<button class="copy-sm" onclick="copySingle('${encodeURIComponent(copyText)}')">复制</button></td>
            <td>${item.pwd}</td>
            <td class="${expireClass}">${item.expire_text} ${item.is_expire?"(已过期)":""}</td>
            <td><button class="del-sm" onclick="delShare('${item.token}')">删除</button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}
function copySingle(txtEnc){
    const txt = decodeURIComponent(txt);
    const temp = document.createElement('textarea');
    temp.value = txt;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert("已复制链接+密码");
}
async function delShare(token){
    if(!confirm("确定删除该分享链接？")) return;
    const fd = new FormData();
    fd.append("ajax_del_share","1");
    fd.append("token",token);
    const res = await fetch(location.href,{method:"POST",body:fd});
    const json = await res.json();
    if(json.code ===1){
        alert("删除成功");
        loadShareList();
    }else{
        alert(json.msg);
    }
}
document.getElementById("refresh-share-list").onclick = loadShareList;
document.getElementById("btn-generate-share").onclick = async function(){
    const shareFile = document.getElementById("share-file-name").value;
    const isDir = document.getElementById("is-dir-share").value;
    const pwd = document.getElementById("share-pwd-input").value.trim();
    const expire = document.getElementById("share-expire-select").value;
    if(!pwd){
        alert("请填写提取密码");
        return;
    }
    const fd = new FormData();
    fd.append("ajax_create_share", "1");
    fd.append("share_file", shareFile);
    fd.append("is_dir_share", isDir);
    fd.append("share_pwd", pwd);
    fd.append("share_expire", expire);
    const res = await fetch(location.href, {method:"POST",body:fd});
    const json = await res.json();
    if(json.code === 1){
        currentShareUrl = json.url;
        currentSharePwd = json.pwd;
        document.getElementById('share-url-box').value = currentShareUrl;
        document.getElementById('share-pwd-box').value = currentSharePwd;
        alert("分享创建成功！");
    }else{
        alert("创建失败");
    }
}
function copyShareUrl(){
    const input = document.getElementById('share-url-box');
    input.select();
    document.execCommand('copy');
    alert('链接复制成功');
}
function copySharePwd(){
    const input = document.getElementById('share-pwd-box');
    input.select();
    document.execCommand('copy');
    alert('密码复制成功');
}
function copyAllShare(){
    const text = `分享链接：${currentShareUrl}\n提取密码：${currentSharePwd}`;
    const temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert('链接+密码已复制');
}
function openRename(name){document.getElementById('rename-old').value=name;document.getElementById('rename-new').value=name;document.getElementById('rename-modal').style.display='flex';}
function openMove(name){document.getElementById('move-src').value=name;document.getElementById('move-modal').style.display='flex';}
function openZip(name){location.href=`?zip=${encodeURIComponent(name)}&dir=${encodeURIComponent(currentDir)}`;}

// ==========【修复后全局搜索JS】==========
async function doGlobalSearch(){
    const kwInput = document.getElementById('search-input');
    const kw = kwInput.value.trim();
    const resultTable = document.getElementById('search-result-table');
    const resultBody = document.getElementById('search-result-body');
    const originTable = document.getElementById('file-table');
    resultBody.innerHTML = '<tr><td colspan="4" class="empty">正在搜索...</td></tr>';
    if(!kw){
        resultTable.style.display = 'none';
        originTable.style.display = 'table';
        return;
    }
    try{
        // 修复1：请求地址仅使用站点根index.php，不带GET参数
        const reqUrl = siteRoot + "index.php";
        const fd = new FormData();
        fd.append('global_search','1');
        fd.append('kw', kw);
        const resp = await fetch(reqUrl, {
            method: 'POST',
            body: fd
        });
        const data = await resp.json();
        console.log("搜索关键词：", kw, "返回结果：", data);
        const list = data.list || [];
        originTable.style.display = 'none';
        resultTable.style.display = 'table';
        resultBody.innerHTML = "";
        if(list.length === 0){
            resultBody.innerHTML = `<tr><td colspan="4" class="empty">未找到匹配文件/文件夹</td>`;
            return;
        }
        list.forEach(item=>{
            let nameHtml = item.is_dir ? `📁 ${item.name}` : `📄 ${item.name}`;
            let sizeText = item.is_dir ? "文件夹" : formatSizeJs(item.size);
            let parentPath = item.parent_dir || "根目录";
            let opsHtml = "";
            // 修复2：路径拼接修正，full_rel是根目录完整相对路径
            if(item.is_dir){
                opsHtml += `<a href="index.php?dir=${encodeURIComponent(item.full_rel)}" class="dir">进入目录</a>`;
            }else{
                // 修复3：下载参数传递完整相对路径，后端download接口兼容完整路径
                opsHtml += `<a href="index.php?download=${encodeURIComponent(item.full_rel)}" class="down">下载</a>`;
                const ext = item.ext;
                if(['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) opsHtml += `<a href="javascript:openImg('${item.full_rel}')" class="pre-img">看图</a>`;
                if(['mp4','mov','webm'].includes(ext)) opsHtml += `<a href="javascript:openVideo('${item.full_rel}')" class="pre-video">播放</a>`;
                if(ext === 'pdf') opsHtml += `<a href="javascript:openPdf('${item.full_rel}')" class="pre-pdf">PDF预览</a>`;
            }
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    ${nameHtml}
                    <div class="search-path-tip">完整路径：${item.full_rel}</div>
                </td>
                <td>${sizeText}</td>
                <td>${parentPath}</td>
                <td class="op">${opsHtml}</td>
            `;
            resultBody.appendChild(tr);
        });
    }catch(err){
        console.error("搜索请求异常",err);
        alert("搜索出错："+err.message);
    }
}
function formatSizeJs(b){
    if(b<1024)return b+" B";
    if(b<1048576)return (b/1024).toFixed(2)+" KB";
    return (b/1048576).toFixed(2)+" MB";
}
document.getElementById('search-btn').onclick = doGlobalSearch;
document.getElementById('search-input').onkeydown = e=>{if(e.key==='Enter')doGlobalSearch();}

// 全选复选框逻辑
const allCheck = document.getElementById('checkAll');
const batchCheckItems = document.querySelectorAll('.batch-check');
allCheck.onchange = function(){
    batchCheckItems.forEach(cb=>cb.checked = allCheck.checked);
}
batchCheckItems.forEach(cb=>{
    cb.addEventListener('change',()=>{
        let allChecked = true;
        batchCheckItems.forEach(item=>{
            if(!item.checked) allChecked = false;
        })
        allCheck.checked = allChecked;
    })
})

// 拖拽上传逻辑
const dropBox = document.getElementById('drop-box');
const fileInput = document.getElementById('file-input');
const folderInput = document.getElementById('folder-input');
const multiFolderInput = document.getElementById('multi-folder-input');
const progressBox = document.getElementById('progress-box');
const progressInner = document.getElementById('progress-inner');
const progressText = document.getElementById('progress-text');
function resetProgress(){
    progressBox.style.display = 'none';
    progressInner.style.width = '0%';
    progressText.innerText = '';
}
function readEntry(entry) {
    return new Promise(async (resolve) => {
        let fileList = [];
        let dirSet = new Set();
        if (entry.isFile) {
            entry.file(file=>{
                let rel = entry.fullPath.replace(/^\//, "");
                Object.defineProperty(file, "webkitRelativePath", { value: rel });
                fileList.push(file);
                resolve({ files: fileList, dirs: dirSet });
            });
        } else if (entry.isDirectory) {
            let dirRel = entry.fullPath.replace(/^\//, "");
            if(dirRel) dirSet.add(dirRel);
            let reader = entry.createReader();
            function readBatch(){
                reader.readEntries(async (subEntries)=>{
                    if(subEntries.length === 0){
                        resolve({ files: fileList, dirs: dirSet });
                        return;
                    }
                    for(const sub of subEntries){
                        const subRes = await readEntry(sub);
                        subRes.files.forEach(f=>fileList.push(f));
                        subRes.dirs.forEach(d=>dirSet.add(d));
                    }
                    readBatch();
                });
            }
            readBatch();
        }
    });
}
async function uploadFolderByEntries(items){
    resetProgress();
    let allFiles = [];
    let allDirs = new Set();
    progressBox.style.display = "block";
    progressText.textContent = "读取全部文件夹...";
    const taskList = [];
    for(let i=0; i<items.length; i++){
        const item = items[i];
        taskList.push(new Promise(async (res)=>{
            const entry = item.webkitGetAsEntry();
            if(!entry) return res();
            const data = await readEntry(entry);
            data.files.forEach(f=>allFiles.push(f));
            data.dirs.forEach(d=>allDirs.add(d));
            res();
        }));
    }
    await Promise.all(taskList);
    if(allFiles.length === 0){
        alert("未读取到文件夹内文件");
        return;
    }
    const dirArr = Array.from(allDirs).sort((a,b)=>a.split("/").length - b.split("/").length);
    for(const dir of dirArr){
        progressText.textContent = `创建目录：${dir}`;
        const fd = new FormData();
        fd.append("ajax_mkdir", "1");
        fd.append("target_dir", dir);
        await new Promise((res,rej)=>{
            const xhr = new XMLHttpRequest();
            xhr.open("POST", location.href);
            xhr.onload = ()=>{
                const ret = JSON.parse(xhr.responseText);
                ret.code ===1 ? res() : rej(ret.msg);
            };
            xhr.onerror = ()=>rej("网络错误");
            xhr.send(fd);
        }).catch(err=>{
            alert(`目录${dir}创建失败：${err}`);
        });
    }
    const total = allFiles.length;
    for(let idx=0; idx<total; idx++){
        const file = allFiles[idx];
        const relPath = file.webkitRelativePath;
        progressText.textContent = `上传 ${idx+1}/${total}：${relPath}`;
        const fd = new FormData();
        fd.append("ajax_upload", "1");
        fd.append("file", file);
        fd.append("relative_path", relPath);
        await new Promise((res,rej)=>{
            const xhr = new XMLHttpRequest();
            xhr.open("POST", location.href);
            xhr.upload.onprogress = e=>{
                const p = Math.round(e.loaded / e.total *100);
                progressInner.style.width = p + "%";
            };
            xhr.onload = ()=>{
                const ret = JSON.parse(xhr.responseText);
                ret.code ===1 ? res() : rej(ret.msg);
            };
            xhr.onerror = ()=>rej("上传失败");
            xhr.send(fd);
        }).catch(err=>{
            alert(`文件${relPath}上传失败：${err}`);
        });
    }
    progressText.textContent = "全部上传完成，800ms后刷新";

	    setTimeout(()=>location.reload(), 800);
}
async function uploadNormalFiles(files){
    resetProgress();
    if(!files.length) return alert("未选择文件");
    progressBox.style.display = 'block';
    for(let i=0; i<files.length; i++){
        let f = files[i];
        let fd = new FormData();
        fd.append("ajax_upload", "1");
        fd.append("file", f);
        if(f.webkitRelativePath){
            fd.append("relative_path", f.webkitRelativePath);
        }
        await new Promise((res, rej)=>{
            let xhr = new XMLHttpRequest();
            xhr.open("POST", siteRoot + "index.php?dir=" + encodeURIComponent(currentDir));
            xhr.upload.onprogress = e=>{
                let p = Math.round((e.loaded/e.total)*100);
                progressInner.style.width = p + "%";
                progressText.textContent = `文件${i+1}/${files.length} ${p}% ${f.name}`;
            };
            xhr.onload = ()=>{
                let ret = JSON.parse(xhr.responseText);
                if(ret.code !== 1) rej(ret.msg);
                else res();
            };
            xhr.onerror = ()=>rej("网络请求失败");
            xhr.send(fd);
        }).catch(err=>{
            alert("上传失败："+err);
        });
    }
    progressText.textContent = "上传完成，800ms后刷新页面";
    setTimeout(()=>location.reload(), 800);
}
folderInput.onchange = async function(){
    const files = this.files;
    if(!files.length) return;
    await uploadNormalFiles([...files]);
};
multiFolderInput.onchange = async function(){
    const files = this.files;
    if(!files.length) return;
    await uploadNormalFiles([...files]);
};
fileInput.onchange = function(){
    uploadNormalFiles(this.files);
};
dropBox.ondragover = e=>{
    e.preventDefault();
    dropBox.style.background = "#eef6ff";
};
dropBox.ondragleave = ()=>dropBox.style.background = "";
dropBox.ondrop = async e=>{
    e.preventDefault();
    dropBox.style.background = "";
    const dt = e.dataTransfer;
    if(dt.items && dt.items.length > 0){
        await uploadFolderByEntries(dt.items);
    }else{
        uploadNormalFiles(dt.files);
    }
};
// 点击弹窗空白关闭弹窗
document.querySelectorAll('.modal').forEach(m=>{
    m.onclick = e=>{if(e.target===m)closeAll();}
})
</script>
</body>
</html>


















