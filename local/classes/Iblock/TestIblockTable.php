<?php

namespace Legacy\Iblock;

use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\Entity\ExpressionField;

class TestIblockTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_TEST_API_IBLOCK)
            ->where("ACTIVE", true);
    }


    public static function withSelect(Query $query)
    {
        // Price
        $query->registerRuntimeField(
            'PRICE',
            new ReferenceField(
                'PRICE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_API_IBLOCK_PRICE),
                ]
            )
        );

        // Название продукта
        $query->registerRuntimeField(
            'NAME_PRODUCT',
            new ReferenceField(
                'NAME_PRODUCT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_API_IBLOCK_NAME_PRODUCT),
                ]
            )
        );

        // Да/Нет
        $query->registerRuntimeField(
            'IS_ACTIVE',
            new ReferenceField(
                'IS_ACTIVE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_API_IBLOCK_IS_ACTIVE),
                ]
            )
        );

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'PRICE_VALUE' => 'PRICE.VALUE',
            'NAME_PRODUCT_VALUE' => 'NAME_PRODUCT.VALUE',
            'IS_ACTIVE_VALUE' => 'IS_ACTIVE.VALUE',
        ]);
    }

    public static function withFilterByIDs(Query $query, $ids)
    {
        $query->whereIn('ID', $ids);
    }

    public static function withOrderBySort(Query $query, $sort = 'ASC')
    {
        $query->addOrder('SORT', $sort);
    }

    public static function withPage(Query $query, int $page)
    {
        if ($page > 0) {
            $query->setOffset(($page - 1) * $query->getLimit());
        }
    }

    public static function withFilterByCity(Query $query, $city)
    {
        if ($city) {
            $query->addFilter('=CITY.VALUE', $city);
        }
    }

}