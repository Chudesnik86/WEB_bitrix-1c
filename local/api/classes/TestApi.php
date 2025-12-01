<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use http\Exception;
use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\TestIblockTable;

class TestApi
{
    private static function processData($query)
    {
        $result = [];

        while ($arr = $query->fetch()) {
            $result[] = [
                'id' => $arr['ID'],
                'name' => $arr['NAME'],
                'code' => $arr['CODE'],
                'previewText' => $arr['PREVIEW_TEXT'],
                'product' => $arr['NAME_PRODUCT_VALUE'],
                'active' => $arr['IS_ACTIVE_VALUE'],
                'price' => (float)$arr['PRICE_VALUE'],
                'activeFrom' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
                'activeTo' => $arr['ACTIVE_TO'] ? $arr['ACTIVE_TO']->format('c') : null,
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
                'name' => $arr['NAME'],
                'code' => $arr['CODE'],
                'previewText' => $arr['PREVIEW_TEXT'],
                'detailText' => $arr['DETAIL_TEXT'],
                'product' => $arr['NAME_PRODUCT_VALUE'],
                'active' => $arr['IS_ACTIVE_VALUE'],
                'price' => (float)$arr['PRICE_VALUE'],
                'document' => getFilePath($arr['DOCUMENT_VALUE']),
                'activeFrom' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
                'activeTo' => $arr['ACTIVE_TO'] ? $arr['ACTIVE_TO']->format('c') : null,
            ];
        }

        return $result;
    }

    public static function getList($arRequest)
    {
        $result = [];

        if (Loader::includeModule('iblock')) {

            $page = max(1, (int)($arRequest['page'] ?? 1));
            $limit = max(1, min(50, (int)($arRequest['limit'] ?? 1)));
            $city = $arRequest['city'] ?? null;
            $minPrice = isset($arRequest['minPrice']) ? (float)$arRequest['minPrice'] : null;
            $maxPrice = isset($arRequest['maxPrice']) ? (float)$arRequest['maxPrice'] : null;

            try {
                $query = TestIblockTable::query()
                    ->countTotal(true)
                    ->setLimit($limit)
                    ->withPage($page)
                    ->withSelect();

                $q = $query->exec();

                $result['totalCount'] = $q->getCount();
                $result['currentPage'] = $page;
                $result['pageSize'] = $limit;
                $result['totalPages'] = ceil($result['totalCount'] / $limit);
                $result['items'] = self::processData($q);

            } catch (\Exception $e) {
                return $e->getMessage();
                throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
            }
        }

        return $result;
    }

    public static function getByIds($arRequest)
    {
        $ids = $arRequest['ids'] ?? [];
        $result = [];

        if (empty($ids)) {
            return $result;
        }

        if (Loader::includeModule('iblock')) {

            try {
                $q = TestIblockTable::query()
                    ->withSelect()
                    ->withFilterByIDs($ids)
                    ->exec();

                $result = self::processData($q);

            } catch (\Exception $e) {
                return $e->getMessage();
                throw new \Exception('Ошибка при получении данных по ID: ' . $e->getMessage());
            }
        }

        return DataProcessor::sortResultByIDs($result, $ids);
    }

}