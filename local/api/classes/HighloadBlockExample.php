<?php

namespace Legacy\API;

use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\Service\GeoIp\SypexGeo;
use Legacy\General\Constants;
use Legacy\HighLoadBlock\Entity;
use Bitrix\Main\Config\Option;

class HighloadBlockExample
{
    public static function get()
    {
        return Entity::getInstance()->getRow(Constants::HLBLOCK_CITIES_LIST,
            ['filter' => ['UF_DEFAULT' => 1]]
        )['UF_CITY'];;
    }

    public static function getDefaultCities()
    {
        $result = [];

        $db = Entity::getInstance()->getList(Constants::HLBLOCK_CITIES_LIST, [
            'order' => ['UF_SORT' => 'ASC']
        ]);

        foreach ($db as $res) {
            $result[] = $res['UF_CITY'];
        }
        return $result;
    }
}
