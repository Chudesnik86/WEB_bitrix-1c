<?php

namespace Legacy\API;

use Legacy\Iblock\TasksTable;

class Tasks
{
    private static function getFileUrl($fileId)
    {
        if (!$fileId) return null;
        $file = \CFile::GetFileArray($fileId);
        return $file ? 'https://' . $_SERVER['HTTP_HOST'] . $file['SRC'] : null;
    }

    private static function process($item)
    {
        $deadline = null;
        if (!empty($item['DEADLINE_STR']) && $item['DEADLINE_STR'] !== '2099-12-31 23:59:59') {
            try {
                $deadline = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($item['DEADLINE_STR']));
            } catch (\Exception $e) {
                $deadline = null;
            }
        }

        $now = new \Bitrix\Main\Type\DateTime();
        $isActive = !$deadline || $now <= $deadline;

        return [
            'id'         => (int)$item['ID'],
            'name'       => trim($item['NAME']),
            'lectureId'  => (int)$item['LECTURE_ID'],
            'text'       => $item['TEXT'] ?? '',
            'file'       => self::getFileUrl($item['FILE_ID']),
            'maxScore'   => (int)($item['MAX_SCORE'] ?: 0),
            'deadline'   => $deadline?->toString(),
            'isActive'   => $isActive,
        ];
    }

    // Получить все задания по лекции
    public static function getList($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        $lectureId = 0;
        if (is_array($data)) {
            $lectureId = (int)($data['lectureId'] ?? $data['Id'] ?? $data['id'] ?? 0);
        }

        if ($lectureId <= 0) {
            return ['error' => 'lectureId required'];
        }

        $db = TasksTable::query()
            ->withSelect()
            ->where('LECTURE_ID', $lectureId)
            ->exec();

        $items = [];
        while ($item = $db->fetch()) {
            $items[] = self::process($item);
        }

        return ['items' => $items];
    }

    // НОВЫЙ МЕТОД — ПОЛУЧИТЬ ОДНО ЗАДАНИЕ ПО ID
    public static function getById($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        $taskId = 0;
        if (is_array($data)) {
            $taskId = (int)($data['taskId'] ?? $data['Id'] ?? $data['id'] ?? $data['TASK_ID'] ?? 0);
        }

        if ($taskId <= 0) {
            return ['error' => 'taskId required'];
        }

        $item = TasksTable::query()
            ->withSelect()
            ->where('ID', $taskId)
            ->exec()
            ->fetch();

        if (!$item) {
            return ['error' => 'Task not found'];
        }

        return self::process($item);
    }
}