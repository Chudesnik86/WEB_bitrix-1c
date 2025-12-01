<?php
if (!defined('IBLOCK_NEWS_ID'))
{
	define('IBLOCK_NEWS_ID', 1);
}
if (!defined('IBLOCK_CATALOG_PROPERTY_PRICE_ID'))
{
	define('IBLOCK_CATALOG_PROPERTY_ARTNUMBER_ID', 6);
}

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('iblock', 'OnAfterIblockElementAdd', [
	'IblockHandler',
	'onNewsAdd'
]);

class IblockHandler
{
	static function onNewsAdd($arFields): void
	{
		if ($arFields['IBLOCK_ID'] !== IBLOCK_NEWS_ID)
		{
			return;
		}

		if (!$arFields['RESULT'])
		{
			return;
		}

		$userId = (int)$arFields['CREATED_BY'];
		$user = \Bitrix\Main\UserTable::getById($userId)->fetch();
		if (empty($user))
		{
			return;
		}

		$newsId = (int)$arFields['ID'];
		$newsName = $arFields['NAME'];
		$author = "{$user['LAST_NAME']} {$user['NAME']} [$userId]";
		CEventLog::Add([
			'SEVERITY' => 'INFO',
			'AUDIT_TYPE_ID' => 'ON_NEWS_ADD',
			'MODULE_ID' => '',
			'ITEM_ID' => $newsId,
			'DESCRIPTION' => "Добавлена новость [$newsId]: $newsName.\nАвтор: $author",
		]);
	}
}

$eventManager->addEventHandler('main', 'OnProlog', 'redirectFromTestPage');
function redirectFromTestPage(): void
{
	global $USER, $APPLICATION;
	$curPage = $APPLICATION->GetCurPage();
	if (str_ends_with($curPage, 'test.php') && !$USER->IsAdmin())
	{
		LocalRedirect('/');
	}
}

function checkNewsCountAgent(int $lastId = 0): string
{
	\Bitrix\Main\Loader::includeModule('iblock');
	$today = new \Bitrix\Main\Type\Date();
	$yesterday = (new \Bitrix\Main\Type\Date())->add('-1 day');

	$result = CIblockElement::GetList(
		['ID' => 'ASC'],
		[
			'IBLOCK_ID' => IBLOCK_NEWS_ID,
			'>ID' => $lastId,
		],
		false,
		false,
		['ID']
	);

	$count = 0;
	while ($item = $result->Fetch())
	{
		$lastId = $item['ID'];
		$count++;
	}

	if ($count > 0)
	{
		CEventLog::Add([
			'SEVERITY' => 'INFO',
			'AUDIT_TYPE_ID' => 'NEWS_COUNT_AGENT',
			'MODULE_ID' => '',
			'DESCRIPTION' => "Добавлено новостей: $count",
		]);
	}

	return "checkNewsCountAgent($lastId);";
}

$isDevServ = \Bitrix\Main\Config\Option::get('main', 'update_devsrv');
if ($isDevServ === 'Y')
{
	if (!defined('IS_DEV_SERVER'))
	{
		define('IS_DEV_SERVER', true);
	}
}

function is404Page(): bool
{
	global $APPLICATION;
	$curPage = $APPLICATION->GetCurPage();

	return $curPage === '/404.php';
}
