<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\PromotionsTable;

class Promotions
{
    private static function processData($query)
    {
        $result = [];

        while ($arr = $query->fetch()) {
            $result[] = [
                'id' => $arr['ID'],
                'code' => $arr['CODE'],
                'image' => getFilePath($arr['IMAGE_VALUE']),
                'title' => $arr['TITLE_VALUE'],
                'badge' => $arr['BADGE_VALUE'],
                'description' =>\Bitrix\Main\Web\Json::decode($arr['PREVIEW_DESCRIPTION_VALUE'] ?? '{}')['blocks'][0]['value'],
            ];
        }

        return $result;
    }

    private static function processDetailData($query)
    {
        $result = [];

        if ($arr = $query->fetch()) {
            $result = [
                'id' => $arr['ID'],
                'code' => $arr['CODE'],
                'date' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
                'image' => getFilePath($arr['IMAGE_VALUE']),
                'title' => $arr['TITLE_VALUE'],
                'badge' => $arr['BADGE_VALUE'],
                'content' =>\Bitrix\Main\Web\Json::decode($arr['DETAIL_CONTENT_VALUE'] ?? '{}')['blocks'],
            ];

            foreach ($result['content'] as &$item) {
                if($item['name'] == 'iblock_elements' && $item['iblock_id'] == Constants::IB_PROMOCODES) {
                    $item['name'] = 'promocodes';
                    unset($item['iblock_id'], $item['element_ids']);
                }
            }

        }

        return array_change_key_case_recursive($result);
    }

    public static function get($arRequest)
    {
        $result = [];
        if (Loader::includeModule('iblock')) {
            $page = (int)$arRequest['page'];
            $limit = (int)$arRequest['limit'];

            $q = PromotionsTable::query()
                ->countTotal(true)
                ->withSelect()
                ->setLimit($limit)
                ->withPage($page)
                ->withOrderByDate('DESC')
                ->withDateActive()
                ->exec()
            ;

            $result['count'] = $q->getCount();
            $result['items'] = self::processData($q);
        }
        return $result;
    }

    public static function getByIds($arRequest)
    {
        $ids = $arRequest['ids'] ?? [];

        $result = [];
        if (Loader::includeModule('iblock')) {
            $q = PromotionsTable::query()
                ->withSelect()
                ->withFilterByIDs($ids)
                ->exec()
            ;
            $result = self::processData($q);
        }

        return DataProcessor::sortResultByIDs($result, $ids);
    }

    public static function getByCode($arRequest)
    {
        $code = $arRequest['code'];

        if(!$code) {
            throw new \Exception('Не передан код новости');
        }

        $result = [];
        if (Loader::includeModule('iblock')) {
            $q = PromotionsTable::query()
                ->withDetailSelect()
                ->withFilterByСode($code);
            $result = self::processDetailData($q);
        }

        return $result;
    }
}
