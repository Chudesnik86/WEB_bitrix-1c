#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Console\UpdateCommand;
use Legacy\General\DocumentRoot;

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

$_SERVER['DOCUMENT_ROOT'] = DocumentRoot::get();
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$app = new Application('Legacy Console App', 'v1.0.0');
$app -> add(new UpdateCommand());
$app -> run();