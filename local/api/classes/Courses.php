<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\Iblock\CoursesTable;

class Courses
{
    private static function process($item)
    {
        $now = new \Bitrix\Main\Type\DateTime();

        $dateStart = $item['DATE_START_VALUE'] instanceof \Bitrix\Main\Type\Date
            ? $item['DATE_START_VALUE']->toString()
            : ($item['DATE_START_VALUE'] ?: null);

        $dateEnd = $item['DATE_END_VALUE'] instanceof \Bitrix\Main\Type\Date
            ? $item['DATE_END_VALUE']->toString()
            : ($item['DATE_END_VALUE'] ?: null);

        $isActiveNow = true;
        if ($dateStart && $now < \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($dateStart))) {
            $isActiveNow = false;
        }
        if ($dateEnd && $now > \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($dateEnd))) {
            $isActiveNow = false;
        }

        return [
            'id'           => (int)$item['ID'],
            'name'         => $item['NAME'],
            'code'         => $item['CODE'],
            'preview'      => $item['PREVIEW_TEXT'] ?? '',
            'description'  => $item['DESCRIPTION_VALUE'] ?? '',
            'isPublic'     => $item['IS_PUBLIC_VALUE'] === 'Y',
            'dateStart'    => $dateStart,
            'dateEnd'      => $dateEnd,
            'isActiveNow'  => $isActiveNow,
        ];
    }

    public static function getList($arRequest)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'User not authenticated'];
        }

        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, min(50, (int)($arRequest['limit'] ?? 10)));

        $query = CoursesTable::query()
            ->countTotal(true)
            ->setLimit($limit)
            ->setOffset(($page - 1) * $limit)
            ->withSelect();

        // Только публичные + в периоде действия (для пользователей)
        if (!\Bitrix\Main\Engine\CurrentUser::get()->isAdmin()) {
            $query->withPublicOnly()->withActivePeriod();
        }

        $db = $query->exec();

        $items = [];
        while ($item = $db->fetch()) {
            $items[] = self::process($item);
        }

        return [
            'items'       => $items,
            'total'       => (int)$db->getCount(),
            'page'        => $page,
            'limit'       => $limit,
            'pages'       => ceil($db->getCount() / $limit),
        ];
    }
}