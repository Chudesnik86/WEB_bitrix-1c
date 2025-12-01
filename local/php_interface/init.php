<?php

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
