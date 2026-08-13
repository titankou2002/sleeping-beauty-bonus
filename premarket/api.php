<?php
// premarket/api.php — 自選股清單管理 API（資料存在伺服器本機，永遠不進 git）
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$dataDir = __DIR__ . '/data';
$listFile = $dataDir . '/watchlist.json';
$adminFile = $dataDir . '/admin.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$defaultList = [
    ['code' => '2330', 'name' => '台積電'],
    ['code' => '2308', 'name' => '台達電'],
    ['code' => '3665', 'name' => '貿聯-KY'],
    ['code' => '6196', 'name' => '帆宣'],
    ['code' => '6561', 'name' => '是方'],
    ['code' => '2344', 'name' => '華邦電'],
    ['code' => '6139', 'name' => '亞翔'],
    ['code' => '00878', 'name' => '國泰永續高股息'],
    ['code' => '00919', 'name' => '群益台灣精選高息'],
];

function load_json($file, $default) {
    if (!file_exists($file)) {
        return $default;
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function has_password() {
    global $adminFile;
    return file_exists($adminFile);
}

function check_password($password) {
    global $adminFile;
    if (!has_password()) return false;
    $admin = load_json($adminFile, null);
    if (!$admin || empty($admin['hash'])) return false;
    return password_verify((string)$password, $admin['hash']);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : $_POST;
}
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

switch ($action) {

    case 'list':
        echo json_encode([
            'ok' => true,
            'list' => load_json($listFile, $defaultList),
            'password_set' => has_password(),
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'set_password':
        // 只有「尚未設定過密碼」時才允許（第一次造訪自行設定，之後要改密碼須先驗證舊密碼）
        $newPassword = trim((string)($input['password'] ?? ''));
        if ($newPassword === '' || mb_strlen($newPassword) < 4) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '密碼至少需要 4 個字元']);
            break;
        }
        if (has_password()) {
            $old = trim((string)($input['old_password'] ?? ''));
            if (!check_password($old)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => '舊密碼不正確，無法變更']);
                break;
            }
        }
        save_json($adminFile, ['hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        echo json_encode(['ok' => true]);
        break;

    case 'add':
    case 'remove':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method not allowed']);
            break;
        }
        if (!has_password()) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '請先設定管理密碼']);
            break;
        }
        if (!check_password($input['password'] ?? '')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => '密碼錯誤']);
            break;
        }

        $list = load_json($listFile, $defaultList);

        if ($action === 'add') {
            $code = trim((string)($input['code'] ?? ''));
            $name = trim((string)($input['name'] ?? ''));
            if ($code === '' || $name === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => '請輸入代碼與名稱']);
                break;
            }
            foreach ($list as $item) {
                if ($item['code'] === $code) {
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'error' => '此代碼已存在清單中']);
                    break 2;
                }
            }
            $list[] = ['code' => $code, 'name' => $name];
            save_json($listFile, $list);
            echo json_encode(['ok' => true, 'list' => $list], JSON_UNESCAPED_UNICODE);
        } else {
            $code = trim((string)($input['code'] ?? ''));
            $list = array_values(array_filter($list, function ($item) use ($code) {
                return $item['code'] !== $code;
            }));
            save_json($listFile, $list);
            echo json_encode(['ok' => true, 'list' => $list], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown action']);
}
