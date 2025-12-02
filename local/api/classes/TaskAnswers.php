<?php

namespace Legacy\API;

use Legacy\Iblock\TaskAnswersTable;

class TaskAnswers
{
    private static function getFileUrl($fileId)
    {
        if (!$fileId) return null;
        $file = \CFile::GetFileArray($fileId);
        return $file ? 'https://' . $_SERVER['HTTP_HOST'] . $file['SRC'] : null;
    }

    private static function process($item)
    {
        $dateSubmit = null;
        if (!empty($item['DATE_SUBMIT_STR'])) {
            try {
                $dateSubmit = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($item['DATE_SUBMIT_STR']));
            } catch (\Exception $e) {
                $dateSubmit = null;
            }
        }

        return [
            'id'         => (int)$item['ID'],
            'taskId'     => (int)$item['TASK_ID'],
            'userId'     => (int)$item['USER_ID'],
            'text'       => $item['TEXT'] ?? '',
            'file'       => self::getFileUrl($item['FILE_ID']),
            'score'      => (int)($item['SCORE'] ?: 0),
            'dateSubmit' => $dateSubmit?->toString(),
            'comment'    => $item['COMMENT'] ?? '',
        ];
    }

    // Все ответы по заданию
    public static function getList($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        $taskId = 0;
        if (is_array($data)) {
            $taskId = (int)($data['taskId'] ?? $data['Id'] ?? $data['id'] ?? 0);
        }

        if ($taskId <= 0) {
            return ['error' => 'taskId required'];
        }

        $db = TaskAnswersTable::query()
            ->withSelect()
            ->where('TASK_ID', $taskId)
            ->addOrder('DATE_SUBMIT_STR', 'DESC')
            ->exec();

        $items = [];
        while ($item = $db->fetch()) {
            $items[] = self::process($item);
        }

        return ['items' => $items];
    }

    // Один конкретный ответ
    public static function getById($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        $answerId = 0;
        if (is_array($data)) {
            $answerId = (int)($data['answerId'] ?? $data['Id'] ?? $data['id'] ?? 0);
        }

        if ($answerId <= 0) {
            return ['error' => 'answerId required'];
        }

        $item = TaskAnswersTable::query()
            ->withSelect()
            ->where('ID', $answerId)
            ->exec()
            ->fetch();

        if (!$item) {
            return ['error' => 'Answer not found'];
        }

        return self::process($item);
    }
}