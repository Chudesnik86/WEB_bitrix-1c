<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\Iblock\LecturesTable;
use Legacy\General\Constants;

Loader::includeModule('iblock');

class Lectures
{
    private static function getFileUrl($fileId)
    {
        if (!$fileId) return null;
        $file = \CFile::GetFileArray($fileId);
        return $file ? 'https://' . $_SERVER['HTTP_HOST'] . $file['SRC'] : null;
    }

    private static function process($item)
    {
        $dateAvailable = null;

        // ←←← ГЛАВНОЕ ИСПРАВЛЕНИЕ ЗДЕСЬ ←←←
        if (!empty($item['DATE_AVAILABLE_STR']) && $item['DATE_AVAILABLE_STR'] !== '2099-12-31 23:59:59') {
            try {
                // Правильно создаём DateTime из строки
                $dateAvailable = \Bitrix\Main\Type\DateTime::createFromTimestamp(
                    strtotime($item['DATE_AVAILABLE_STR'])
                );
            } catch (\Exception $e) {
                // Если вдруг битый формат — просто игнорируем
                $dateAvailable = null;
            }
        }

        $now = new \Bitrix\Main\Type\DateTime();
        $isAvailable = !$dateAvailable || $now >= $dateAvailable;

        return [
            'id'              => (int)$item['ID'],
            'name'            => trim($item['NAME']),
            'code'            => $item['CODE'] ?? '',
            'courseId'        => (int)$item['COURSE_ID'],
            'sort'            => (int)($item['SORT_ORDER'] ?: 500),
            'content'         => $item['CONTENT'] ?? '',
            'file'            => self::getFileUrl($item['FILE_ID']),
            'dateAvailable'   => $dateAvailable?->toString(),
            'requirePrevious' => $item['REQUIRE_PREV'] === 'Y',
            'isAvailable'     => $isAvailable,
        ];
    }

    // Основной метод — как у Courses::getList
    // Вставь этот метод в класс Legacy\API\Lectures

    public static function getList($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        // Извлекаем courseId из любого места, куда его может засунуть роутер
        $courseId = 0;
        if (is_array($data)) {
            $courseId = (int)($data['courseId'] ?? $data['course_id'] ?? $data['COURSE_ID'] ?? $data['Id'] ?? $data['id'] ?? 0);
        }
        // На случай, если кто-то передаёт напрямую как параметр (не в массиве)
        if ($courseId <= 0 && func_num_args() > 0) {
            $courseId = (int)$data;
        }

        if ($courseId <= 0) {
            return ['error' => 'courseId required'];
        }

        $db = \Legacy\Iblock\LecturesTable::query()
            ->withSelect()
            ->where('COURSE_ID', $courseId)
            ->addOrder('SORT_ORDER', 'ASC')
            ->addOrder('ID', 'ASC')
            ->exec();

        $items = [];
        while ($item = $db->fetch()) {
            $items[] = self::process($item);
        }

        return [
            'items' => $items,
            'total' => count($items),
            'courseId' => $courseId
        ];
    }

    // Дополнительно — по ID
    public static function getById($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        // Берём ID из любого места: Id, id, lectureId, ID — всё равно найдём
        $id = 0;
        if (is_array($data)) {
            $id = (int)($data['id'] ?? $data['Id'] ?? $data['lectureId'] ?? $data['ID'] ?? 0);
        }
        if ($id <= 0 && isset($data['result'])) {
            // если пришёл уже результат от другого метода
            return $data['result'];
        }

        if ($id <= 0) {
            return ['error' => 'Lecture ID required'];
        }

        $item = \Legacy\Iblock\LecturesTable::query()
            ->withSelect()
            ->where('ID', $id)
            ->exec()
            ->fetch();

        if (!$item) {
            return ['error' => 'Lecture not found'];
        }

        return self::process($item);
    }
}