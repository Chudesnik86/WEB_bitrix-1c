<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

// Настройка cookies для работы с CORS ПЕРЕД инициализацией Bitrix
// Через прокси запросы идут на same-origin, поэтому используем SameSite=Lax
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    // Настраиваем параметры cookies для работы через прокси (same-origin)
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.cookie_secure', '0'); // Для localhost не требуется Secure
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use \Bitrix\Main\Context;
use Legacy\General\Api;

// CORS заголовки для разрешения запросов с localhost:3000
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

header("HTTP/1.1 200 OK");
header('Content-Type: application/json; charset=utf-8');

// Обработчик фатальных ошибок для возврата JSON вместо HTML
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'errorCode' => $error['type'],
            'errorMessage' => $error['message'],
            'result' => [
                'message' => $error['message'],
                'file' => basename($error['file']),
                'line' => $error['line']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

try {
    $request = Context::getCurrent()->getRequest();
    $namespace = '\Legacy\API';
    $class = $namespace.'\\'.ucwords($request->get('CLASS'));
    $method = $request->get('METHOD');
    $arRequest = $request->toArray();
    unset($arRequest['CLASS']);
    unset($arRequest['METHOD']);
    $request->set($arRequest);

    $api = Api::getInstance();
    echo $api->execute($class, $method);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'errorCode' => $e->getCode() ?: 1,
        'errorMessage' => $e->getMessage(),
        'result' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');