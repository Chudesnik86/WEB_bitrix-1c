<?php

namespace Legacy\Iblock;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Legacy\General\Constants;

class IblockElementTable extends ElementTable
{
    public static function withSelect(Query $query)
    {
        $query->setSelect([ // поля таблицы
            'ID',
            'NAME',
            'CODE',
            'псевдоним поля' => 'поле'
        ]);

        $query->addSelect('поле', 'псевдоним поля');

        $query->addSelect(new ExpressionField('COUNT_ID', 'COUNT(%s)', ['ID']));
    }

    public static function withRuntime(Query $query)
    {
        $query->registerRuntimeField(
            'поле',
            new ReferenceField(
                'поле',
                SmthTable::class, // таблица, к которой присоединяться
                ['ref.SMTH_TABLE_ID' => 'this.ID'] // ключи, по которым присоединяться
            )
        );
    }

    public static function withRuntimeSections(Query $query) // присоединение разделов к запросу
    {
        $query->registerRuntimeField(
            'SECTION',
            new ReferenceField(
                'SECTION',
                SectionTable::class,
                ['ref.IBLOCK_SECTION_ID' => 'this.ID']
            )
        );

        $query->addSelect('SECTION.*', 'SECTION_'); // выбор всех полей
    }

    public static function withRuntimeProperties(Query $query) // присоединение пользовательских свойств к запросу
    {
        $query->registerRuntimeField(
            'PROPERTY',
            new ReferenceField(
                'PROPERTY',
                ElementPropertyTable::class,
                [
                    'ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::PROPERTY)
                ]
            )
        );

        $query->addSelect('PROPERTY.VALUE', 'PROPERTY_VALUE'); // выбор значения поля
    }

    public static function withFilter(Query $query) // фильтрация
    {
        $query->setFilter([
            'поле' => 'фильтр',
            '!=поле1' => 'фильтр',
            '<=поле2' => 'фильтр'
        ]);

        $query->addFilter('поле', 'фильтр');

        $query->addFilter(null, [
            'LOGIC' => 'AND',
            'поле' => 'фильтр',
            'поле1' => 'фильтр'
        ]);

    }

    public static function withOrder(Query $query) //сортировка
    {
        $query->setOrder([
            'поле' => 'сортировка', // 'ASC' - по возрастанию, 'DESC' - по убыванию
        ]);
    }

    public static function withPage(Query $query, $limit, $page) //пагинация
    {
        $query->setLimit($limit); // количество записей
        $query->setOffset(($page - 1) * $limit); // смещение на количество записей
    }
}