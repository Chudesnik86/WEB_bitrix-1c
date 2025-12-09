<?php

// Настройка cookies для работы с CORS (для frontend на localhost:3000)
// ВАЖНО: Эта настройка должна быть ДО вызова prolog_before.php
// Но так как init.php вызывается после, настройка cookies делается в API index.php

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php');
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/functions.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/functions.php');
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/events.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/events.php');
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/agents.php')) {
    require($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/agents.php');
}
