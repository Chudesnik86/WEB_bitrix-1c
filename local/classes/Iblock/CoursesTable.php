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

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'DESCRIPTION_VALUE' => 'DESCRIPTION.VALUE',
        ]);
    }

    // Только курсы, доступные сейчас
    public static function withActivePeriod($query)
    {
        // Временно отключаем фильтрацию по датам, так как addFilter не работает корректно с runtime полями
        // Фильтрация будет выполняться на уровне PHP после получения данных
        // TODO: Реализовать правильную фильтрацию через where или подзапросы
    }

    // Только открытые курсы
    public static function withPublicOnly($query)
    {
        // Теперь используется "Да" вместо "Y"
        // Фильтрация по публичности выполняется на уровне PHP, так как нужно проверять "Да"
        // $query->where('IS_PUBLIC_VALUE', 'Да');
    }
}