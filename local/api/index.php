<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use \Bitrix\Main\Context;
use Legacy\General\Api;

header("HTTP/1.1 200 OK");

$request = Context::getCurrent()->getRequest();
$namespace = '\Legacy\API';
$class = $namespace.'\\'.ucwords($request->get('CLASS'));
$method = $request->get('METHOD');
$arRequest = $request->toArray();
unset($arRequest['CLASS']);
unset($arRequest['METHOD']);
$request->set($arRequest);

$api = Api::getInstance();

header('Content-Type: application/json; charset=utf-8');
echo $api->execute($class, $method);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');