<?php

namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Iblock\ElementPropertyTable;
use Legacy\General\Constants;

// ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
Loader::includeModule('iblock'); // ОБЯЗАТЕЛЬНО! Без этого — фатал
// ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←

class CoursesTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query)
    {
        $query
            ->where('IBLOCK_ID', Constants::IB_COURSES) // ← подставь сюда ID инфоблока, когда будет
            ->where('ACTIVE', 'Y');
    }

    public static function withSelect($query)
    {
        // Описание курса
        $query->registerRuntimeField('DESCRIPTION', new ReferenceField(
            'DESCRIPTION',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSES_DESCRIPTION) // 12
            ]
        ));

        // Статус видимости (открытый/закрытый)
        $query->registerRuntimeField('IS_PUBLIC', new ReferenceField(
            'IS_PUBLIC',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSES_IS_PUBLIC) // 13
            ]
        ));

        // Дата начала доступа
        $query->registerRuntimeField('DATE_START', new ReferenceField(
            'DATE_START',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSES_DATE_START) // 14
            ]
        ));

        // Дата окончания доступа
        $query->registerRuntimeField('DATE_END', new ReferenceField(
            'DATE_END',
            ElementPropertyTable::class,
            [
                '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSES_DATE_END) // 15
            ]
        ));

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'DESCRIPTION_VALUE' => 'DESCRIPTION.VALUE',
            'IS_PUBLIC_VALUE'   => 'IS_PUBLIC.VALUE',
            'DATE_START_VALUE'  => 'DATE_START.VALUE',
            'DATE_END_VALUE'    => 'DATE_END.VALUE',
        ]);
    }

    // Только курсы, доступные сейчас
    public static function withActivePeriod($query)
    {
        $now = new \Bitrix\Main\Type\DateTime();

        $query->addFilter(null, [
            'LOGIC' => 'OR',
            ['>=DATE_END_VALUE' => $now],
            ['DATE_END_VALUE' => false],
        ]);

        $query->addFilter(null, [
            'LOGIC' => 'OR',
            ['<=DATE_START_VALUE' => $now],
            ['DATE_START_VALUE' => false],
        ]);
    }

    // Только открытые курсы
    public static function withPublicOnly($query)
    {
        $query->where('IS_PUBLIC_VALUE', 'Y');
    }
}