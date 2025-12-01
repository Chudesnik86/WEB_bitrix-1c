<?php

namespace Legacy\API;

use Legacy\General\Constants;
use Bitrix\Main\Loader;
use Legacy\Iblock\IblockElementTable;

class IblockTemplate
{
    public static function getElement($arRequest) // обращаться [ваш домен]/api/IblockTemplate/getElement/?параметр1=значение&параметр2=значение
    {
        if (Loader::includeModule('iblock')) { // чтобы работать с элементами модуля, необходимо его подключить, в примере работаем с инфоблоком
            $query = IblockElementTable::query()
                ->withSelect()
                ->addFilter('IBLOCK_ID', Constants::IB_TEMPLATE) // для получения элементов конкретного инфоблока, фильтруем записи по его id
                ->withFilter()
                ->withOrder()
                ->withPage($arRequest['limit'], $arRequest['page'])
            ;
            $count = $query->queryCountTotal(); // количество записей ответа
            $db = $query->exec();

            $result = [];
            while ($res = $db->fetch()) {
                $result[] = $res;
            }
            return $result;
        }
        throw new \Exception('Не удалось подключить необходимые модули');
    }
}