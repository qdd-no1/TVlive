<?php
header('Content-Type: application/json; charset=utf-8');

// 1. 处理 CORS 预检请求 (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    http_response_code(200);
    exit;
}

// 2. 获取搜索关键词
$wd = $_GET['wd'] ?? '';
if (empty($wd)) {
    echo json_encode(['list' => []]);
    exit;
}

// 3. 定义资源站配置
$sources = [
     ['name' => '超清资源一', 'url' => 'https://cj.ffzyapi.com/api.php/provide/vod/at/json/'],
     ['name' => '电影天堂', 'url' => 'http://caiji.dyttzyapi.com/api.php/provide/vod/from/dyttm3u8'],
    ['name' => '最大资源', 'url' => 'https://api.zuidapi.com/api.php/provide/vod/from/zuidam3u8/'],
   
     ['name' => '百度资源', 'url' => 'https://api.apibdzy.com/api.php/provide/vod/'],
];

// 4. 定义搜索函数
function fetchSource($source, $keyword) {
    $results = [];
    $apiUrl = $source['url'] . '?ac=detail&wd=' . urlencode($keyword);

    // 使用 cURL 发起请求 (替代 JS 的 fetch)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5秒超时
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略 SSL 错误 (测试用)

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        // 处理 Unicode 转义 (针对第二个接口返回的 \u 编码)
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = unicodeDecode($response);
            $data = json_decode($response, true);
        }

        if (isset($data['list']) && is_array($data['list'])) {
            foreach ($data['list'] as $item) {
                // 数据清洗逻辑
                $vodPic = $item['vod_pic'] ?? '';
                // 强制将 http 转为 https
                $vodPic = preg_replace('/^http:/', 'https:', $vodPic);
                
                // 处理剧情简介：去除 HTML 标签
                $content = $item['vod_content'] ?? '暂无剧情简介';
                $content = strip_tags($content);

                $results[] = [
                    'vod_id' => $item['vod_id'] ?? '',
                    'vod_name' => $item['vod_name'] ?? '',
                    'vod_pic' => $vodPic,
                    'vod_remarks' => $item['vod_remarks'] ?? '高清',
                    'vod_content' => $content,
                    'vod_play_url' => $item['vod_play_url'] ?? '',
                    'from' => $source['name'] // 标记来源
                ];
            }
        }
    }
    return $results;
}

// 辅助函数：处理 \u 编码的 Unicode 字符 (针对 api.yzzy-api.com)
function unicodeDecode($str) {
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-0', 'UCS-2BE');
    }, $str);
}

// 5. 执行搜索与合并
$allResults = [];
foreach ($sources as $source) {
    $allResults = array_merge($allResults, fetchSource($source, $wd));
}

// 6. 去重 (基于影片名称)
$uniqueList = [];
$seenNames = [];
foreach ($allResults as $item) {
    $name = $item['vod_name'];
    if (!isset($seenNames[$name])) {
        $seenNames[$name] = true;
        $uniqueList[] = $item;
    }
}

// 7. 输出最终 JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
echo json_encode(['list' => $uniqueList], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;