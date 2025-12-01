<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;

IncludeModuleLangFile(__FILE__);

class legacy_settings extends CModule
{
    var $MODULE_ID = 'legacy.settings';

    function __construct()
    {
        $arModuleVersion = array();

        include(__DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('LEGACY_SETTINGS_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('LEGACY_SETTINGS_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('LEGACY_SETTINGS_PARTNER_NAME');
        $this->PARTNER_URI = 'https://legacystudio.ru/';
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
        $this->InstallEvents();

    }

    public function InstallEvents()
    {
        $eventManager = Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler(
            'iblock',
            \Bitrix\Iblock\Model\PropertyFeature::class.'::OnPropertyFeatureBuildList',
            $this->MODULE_ID,
            'Legacy\Settings\Events\PropertyFeature',
            'OnPropertyFeatureBuildList',
            100,
            '',
            []
        );
    }

    public function DoUninstall()
    {
        Loader::includeModule($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
        Option::delete($this->MODULE_ID);
    }
}
