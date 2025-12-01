<?php

namespace Legacy\Iblock;

use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\DateTime;

class PromotionsTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_PROMOTIONS)
            ->where("ACTIVE", true);
    }

    public static function withSelect(Query $query)
    {
        $query->registerRuntimeField(
            'TITLE',
            new ReferenceField(
                'TITLE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_TITLE),
                ]
            )
        );

        $query->registerRuntimeField(
            'IMAGE',
            new ReferenceField(
                'IMAGE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_IMAGE),
                ]
            )
        );

        $query->registerRuntimeField(
            'PREVIEW_DESCRIPTION',
            new ReferenceField(
                'PREVIEW_DESCRIPTION',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_PREVIEW_DESCRIPTION),
                ]
            )
        );

        $query->registerRuntimeField(
            'BADGE',
            new ReferenceField(
                'BADGE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_BADGE),
                ]
            )
        );

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'TITLE_VALUE' => 'TITLE.VALUE',
            'IMAGE_VALUE' => 'IMAGE.VALUE',
            'PREVIEW_DESCRIPTION_VALUE' => 'PREVIEW_DESCRIPTION.VALUE',
            'BADGE_VALUE' => 'BADGE.VALUE',
        ]);
    }

    public static function withDetailSelect(Query $query)
    {
        $query->registerRuntimeField(
            'TITLE',
            new ReferenceField(
                'TITLE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_TITLE),
                ]
            )
        );

        $query->registerRuntimeField(
            'IMAGE',
            new ReferenceField(
                'IMAGE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_IMAGE),
                ]
            )
        );

        $query->registerRuntimeField(
            'DETAIL_CONTENT',
            new ReferenceField(
                'DETAIL_CONTENT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_DETAIL_CONTENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'BADGE',
            new ReferenceField(
                'BADGE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?',Constants::IB_PROP_PROMOTIONS_BADGE),
                ]
            )
        );

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'TITLE_VALUE' => 'TITLE.VALUE',
            'IMAGE_VALUE' => 'IMAGE.VALUE',
            'DETAIL_CONTENT_VALUE' => 'DETAIL_CONTENT.VALUE',
            'BADGE_VALUE' => 'BADGE.VALUE',
        ]);
    }

    public static function withFilterByIDs(Query $query, $ids)
    {
        $query->whereIn('ID', $ids);
    }

    public static function withFilterByСode(Query $query, $code)
    {
        $query->where('CODE', $code);
    }
    public static function withOrderByDate(Query $query, $order){
        $query->addOrder('ACTIVE_FROM', $order);
    }
    public static function withSectionCode(Query $query, $code)
    {
        if (isset($code)) {
            $query->addFilter('=IBLOCK_SECTION.CODE', $code);
        }
    }
    public static function withPage(Query $query, int $page)
    {
        if ($page > 0) {
            $query->setOffset(($page - 1) * $query->getLimit());
        }
    }

    public static function withDateActive(Query $query)
    {
        $dt = new DateTime();
        $query->addFilter(null, [
            'LOGIC' => 'OR',
            '<=ACTIVE_FROM' => $dt,
            'ACTIVE_FROM' => null,
        ]);
        $query->addFilter(null, [
            'LOGIC' => 'OR',
            '>=ACTIVE_TO' => $dt,
            'ACTIVE_TO' => null,
        ]);
    }
    public static function withOrderBySort($query, $sort)
    {
        $query->addOrder('SORT', $sort);
    }

}
