<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

// Настройка cookies для работы с CORS ПЕРЕД инициализацией Bitrix
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.cookie_secure', '0');
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use Legacy\API\Files;

// CORS заголовки
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000'];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept");
}

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $type = $_GET['type'] ?? $_POST['type'] ?? '';
    $fileId = (int)($_GET['fileId'] ?? $_POST['fileId'] ?? $_GET['id'] ?? $_POST['id'] ?? 0);
    $filePath = $_GET['path'] ?? $_POST['path'] ?? '';
    
    // Если передан прямой путь к файлу (например, /upload/...), обрабатываем его
    if (!empty($filePath) && strpos($filePath, '/upload/') === 0) {
        Files::downloadFileByPath($filePath);
    } elseif ($type === 'lecture' && $fileId > 0) {
        Files::downloadLectureFile(['fileId' => $fileId]);
    } elseif ($type === 'task' && $fileId > 0) {
        Files::downloadTaskFile(['fileId' => $fileId]);
    } elseif ($type === 'answer' && $fileId > 0) {
        Files::downloadAnswerFile(['fileId' => $fileId]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. Provide type and fileId, or path'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');

