<?php

namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Iblock\ElementPropertyTable;
use Legacy\General\Constants;

Loader::includeModule('iblock');

class TaskAnswersTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query)
    {
        $query
            ->where('IBLOCK_ID', Constants::IB_TASK_ANSWERS)
            ->where('ACTIVE', 'Y');
    }

    public static function withSelect($query)
    {
        $query->registerRuntimeField('PROP_TASK', new ReferenceField(
            'PROP_TASK',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_TASK)]
        ));

        $query->registerRuntimeField('PROP_USER', new ReferenceField(
            'PROP_USER',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_USER_ID)]
        ));

        $query->registerRuntimeField('PROP_FILE', new ReferenceField(
            'PROP_FILE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_FILE)]
        ));

        $query->registerRuntimeField('PROP_TEXT', new ReferenceField(
            'PROP_TEXT',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_TEXT)]
        ));

        $query->registerRuntimeField('PROP_SCORE', new ReferenceField(
            'PROP_SCORE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_SCORE)]
        ));

        $query->registerRuntimeField('PROP_DATE', new ReferenceField(
            'PROP_DATE',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_DATE_SUBMIT)]
        ));

        $query->registerRuntimeField('PROP_COMMENT', new ReferenceField(
            'PROP_COMMENT',
            ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_ANSWERS_COMMENT)]
        ));

        $query->registerRuntimeField('DATE_SUBMIT_STR', new ExpressionField(
            'DATE_SUBMIT_STR',
            'IF(%s IS NULL OR %s = "", NOW(), %s)',
            ['PROP_DATE.VALUE', 'PROP_DATE.VALUE', 'PROP_DATE.VALUE']
        ));

        $query->setSelect([
            'ID',
            'NAME',
            'TASK_ID'        => 'PROP_TASK.VALUE',
            'USER_ID'        => 'PROP_USER.VALUE',
            'FILE_ID'        => 'PROP_FILE.VALUE',
            'TEXT'           => 'PROP_TEXT.VALUE',
            'SCORE'          => 'PROP_SCORE.VALUE',
            'DATE_SUBMIT_STR',
            'COMMENT'        => 'PROP_COMMENT.VALUE',
        ]);
    }
}