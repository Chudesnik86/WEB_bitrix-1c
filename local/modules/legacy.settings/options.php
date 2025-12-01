<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);

$module_id = 'legacy.settings';
\Bitrix\Main\Loader::includeModule($module_id);

$arTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('LEGACY_SETTINGS_OPTIONS_TAB_SETTINGS'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => 'Внутренние страницы',
    ],
    [
        'DIV' => 'edit3',
        'TAB' => 'Токены',
    ]
];

$arGroups = [
    'LOGOTYPES' => [
        'TAB' => 'edit1',
        'TITLE' => 'Логотипы'
    ],
    'HEADER' => [
        'TAB' => 'edit1',
        'TITLE' => 'Шапка сайта'
    ],
    'CONTACTS' => [
        'TAB' => 'edit1',
        'TITLE' => 'Контактная информация'
    ],
    'PAYSYSTEMS' => [
        'TAB' => 'edit1',
        'TITLE' => 'Платежные системы'
    ],
    'BONUSES' => [
        'TAB' => 'edit2',
        'TITLE' => 'Бонусная программа'
    ],
    'TOKENS' => [
        'TAB' => 'edit3',
        'TITLE' => 'Токены'
    ],
];

$arOptions = [
    [
        'GROUP' => 'LOGOTYPES',
        'SORT' => '1',
        'NAME' => 'logo',
        'TITLE' => 'Логотип',
        'TYPE' => 'IMAGE',
    ],
    [
        'GROUP' => 'CONTACTS',
        'SORT' => '1',
        'NAME' => 'feedback_link',
        'TITLE' => 'Ссылка для обратной связи',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'CONTACTS',
        'SORT' => '2',
        'NAME' => 'phone_number',
        'TITLE' => 'Номер телефона',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'PAYSYSTEMS',
        'SORT' => '1',
        'NAME' => 'paysystems',
        'TITLE' => 'Платежные системы',
        'TYPE' => 'MIMAGE',
    ],
    [
        'GROUP' => 'HEADER',
        'SORT' => '1',
        'NAME' => 'header_text',
        'TITLE' => 'Текст в шапке',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'HEADER',
        'SORT' => '2',
        'NAME' => 'header_link',
        'TITLE' => 'Ссылка',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'HEADER',
        'SORT' => '3',
        'NAME' => 'header_link_text',
        'TITLE' => 'Текст ссылки',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'BONUSES',
        'SORT' => '1',
        'NAME' => 'bonuses_button_text',
        'TITLE' => 'Текст на кнопке в баннере',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'BONUSES',
        'SORT' => '2',
        'NAME' => 'bonuses_button_link',
        'TITLE' => 'Ссылка кнопки в баннере',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'TOKENS',
        'SORT' => '1',
        'NAME' => 'dadata',
        'TITLE' => 'DaData API-ключ',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'TOKENS',
        'SORT' => '2',
        'NAME' => 'sms_ru',
        'TITLE' => 'SMS.RU api_id',
        'TYPE' => 'TEXT',
    ],
    [
        'GROUP' => 'TOKENS',
        'SORT' => '100',
        'NAME' => 'jwt_secret',
        'TYPE' => 'TEXT',
    ],
];

$moduleOptions = new CModuleOptions($module_id, $arTabs, $arGroups, $arOptions);
$moduleOptions->showOptions();