<?php

namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Iblock\ElementPropertyTable;
use Legacy\General\Constants;

Loader::includeModule('iblock');

class LecturesTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query)
    {
        $query
            ->where('IBLOCK_ID', Constants::IB_LECTURES)
            ->where('ACTIVE', 'Y');
    }

    public static function withSelect($query)
    {
        $query->registerRuntimeField('PROP_COURSE', new ReferenceField(
            'PROP_COURSE',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_COURSE)
            ]
        ));

        $query->registerRuntimeField('PROP_SORT', new ReferenceField(
            'PROP_SORT',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_LECTURE_SORT_ORDER)
            ]
        ));

        $query->registerRuntimeField('PROP_CONTENT', new ReferenceField(
            'PROP_CONTENT',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_CONTENT)
            ]
        ));

        $query->registerRuntimeField('PROP_FILE', new ReferenceField(
            'PROP_FILE',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_FILE)
            ]
        ));

        $query->registerRuntimeField('PROP_DATE', new ReferenceField(
            'PROP_DATE',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_DATE_AVAILABLE)
            ]
        ));

        $query->registerRuntimeField('PROP_PREV', new ReferenceField(
            'PROP_PREV',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURES_REQUIRE_PREV)
            ]
        ));

        $query->registerRuntimeField('DATE_AVAILABLE_STR', new ExpressionField(
            'DATE_AVAILABLE_STR',
            'IF(%s IS NULL OR %s = "", "2099-12-31 23:59:59", %s)',
            ['PROP_DATE.VALUE', 'PROP_DATE.VALUE', 'PROP_DATE.VALUE']
        ));

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'COURSE_ID'     => 'PROP_COURSE.VALUE',
            'SORT_ORDER'    => 'PROP_SORT.VALUE',
            'CONTENT'       => 'PROP_CONTENT.VALUE',
            'FILE_ID'       => 'PROP_FILE.VALUE',
            'DATE_AVAILABLE_STR',
            'REQUIRE_PREV'  => 'PROP_PREV.VALUE',
        ]);
    }
}