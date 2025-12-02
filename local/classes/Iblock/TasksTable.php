<?php

namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Iblock\ElementPropertyTable;
use Legacy\General\Constants;

Loader::includeModule('iblock');

class TasksTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query)
    {
        $query
            ->where('IBLOCK_ID', Constants::IB_TASKS)
            ->where('ACTIVE', 'Y');
    }

    public static function withSelect($query)
    {
        $query->registerRuntimeField('PROP_LECTURE', new ReferenceField(
            'PROP_LECTURE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASKS_LECTURE)]
        ));

        $query->registerRuntimeField('PROP_TEXT', new ReferenceField(
            'PROP_TEXT',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASKS_TEXT)]
        ));

        $query->registerRuntimeField('PROP_FILE', new ReferenceField(
            'PROP_FILE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASKS_FILE)]
        ));

        $query->registerRuntimeField('PROP_MAX_SCORE', new ReferenceField(
            'PROP_MAX_SCORE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASKS_MAX_SCORE)]
        ));

        $query->registerRuntimeField('PROP_DEADLINE', new ReferenceField(
            'PROP_DEADLINE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASKS_DEADLINE)]
        ));

        $query->registerRuntimeField('DEADLINE_STR', new ExpressionField(
            'DEADLINE_STR',
            'IF(%s IS NULL OR %s = "", "2099-12-31 23:59:59", %s)',
            ['PROP_DEADLINE.VALUE', 'PROP_DEADLINE.VALUE', 'PROP_DEADLINE.VALUE']
        ));

        $query->setSelect([
            'ID',
            'NAME',
            'LECTURE_ID'    => 'PROP_LECTURE.VALUE',
            'TEXT'          => 'PROP_TEXT.VALUE',
            'FILE_ID'       => 'PROP_FILE.VALUE',
            'MAX_SCORE'     => 'PROP_MAX_SCORE.VALUE',
            'DEADLINE_STR',
        ]);
    }
}