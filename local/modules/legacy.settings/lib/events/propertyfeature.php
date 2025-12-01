<?php

namespace Legacy\Settings\Events;

class PropertyFeature
{
    public static function OnPropertyFeatureBuildList(\Bitrix\Main\Event $event)
    {
        $features = [];

        $features[] = [
            'MODULE_ID' => 'legacy.settings',
            'FEATURE_ID' => 'LEGACY_USE_IN_BASKET',
            'FEATURE_NAME' => 'LEGACY Показывать свойство в корзине',
        ];

        $features[] = [
            'MODULE_ID' => 'legacy.settings',
            'FEATURE_ID' => 'LEGACY_USE_IN_DETAIL_TABLE',
            'FEATURE_NAME' => 'LEGACY Показывать на детальной странице в таблице',
        ];

        $features[] = [
            'MODULE_ID' => 'legacy.settings',
            'FEATURE_ID' => 'LEGACY_USE_IN_LISTING',
            'FEATURE_NAME' => 'LEGACY Показывать в листинге каталога',
        ];

//        $features[] = [
//            'MODULE_ID' => 'legacy.settings',
//            'FEATURE_ID' => 'LEGACY_USE_TO_BUILD_OFFERS',
//            'FEATURE_NAME' => 'Использовать в построении торговых предложений',
//        ];

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $features);
    }
}
