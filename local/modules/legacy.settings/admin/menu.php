<?php
use Bitrix\Main\Localization\Loc;

if (!defined('LEGACY_SETTINGS')) {
    define('LEGACY_SETTINGS', 'legacy.settings');
}

Loc::loadMessages(__FILE__);

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnBuildGlobalMenu',
    function (&$arGlobalMenu) {

        if (!isset($arGlobalMenu['global_menu_legacy'])) {
            $arGlobalMenu['global_menu_legacy'] = [
                'menu_id' => 'global_menu_legacy',
                'text' => Loc::getMessage('LEGACY_SETTINGS_MENU_MODULE_NAME'),
                'title' => Loc::getMessage('LEGACY_SETTINGS_MENU_MODULE_FULL_NAME'),
                'sort' => 2000,
                'items_id' => 'global_menu_legacy_settings_items',
            ];
        } else {
            $arGlobalMenu['global_menu_legacy'] = [
                'text' => Loc::getMessage('LEGACY_SETTINGS_MENU_LEGACY'),
                'title' => Loc::getMessage('LEGACY_SETTINGS_MENU_LEGACY'),
            ];
        }

        if (isset($arMenu)) {
            $arGlobalMenu['global_menu_legacy']['items'][LEGACY_SETTINGS] = $arMenu;
        }
    }
);

$aMenu = [
    [
        'parent_menu' => 'global_menu_legacy',
        'section' => 'LEGACY_SETTINGS',
        'text' => Loc::getMessage('LEGACY_SETTINGS_MENU_MODULE_NAME_LONG'),
        'title' => Loc::getMessage('LEGACY_SETTINGS_MENU_MODULE_FULL_NAME'),
        'sort' => 500,
        'icon' => 'util_menu_icon',
        'page_icon' => 'LEGACY_SETTINGS',
        'items_id' => 'menu_LEGACY_SETTINGS_items',
        'module_id' => 'legacy.settings',
        'url' => sprintf('/bitrix/admin/settings.php?mid=%s&lang=%s&mid_menu=1&tabControl_active_tab=%s', LEGACY_SETTINGS, urlencode(LANGUAGE_ID), 'settings'),
    ]
];

return $aMenu;