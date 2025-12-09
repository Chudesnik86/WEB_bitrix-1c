<?php

namespace Legacy\API;

use Legacy\Iblock\TaskAnswersTable;

class TaskAnswers
{
    private static function getFileUrl($fileId)
    {
        if (!$fileId) return null;
        // Возвращаем URL для скачивания через временную папку через прокси
        // Frontend работает на localhost:3000, поэтому используем относительный путь через прокси
        return '/download/local/api/download.php?type=answer&fileId=' . $fileId;
    }

    /**
     * Десериализует текстовое поле, если оно хранится в сериализованном виде
     * В Bitrix HTML-свойства хранятся как: a:2:{s:4:"TEXT";s:2:"С";s:4:"TYPE";s:4:"TEXT";}
     * @param mixed $value
     * @return string
     */
    private static function unserializeText($value)
    {
        if (empty($value) && $value !== '0') {
            return '';
        }

        // Если это уже массив с ключом TEXT, просто возвращаем текст
        if (is_array($value) && isset($value['TEXT'])) {
            return (string)$value['TEXT'];
        }

        // Если это не строка, преобразуем в строку
        if (!is_string($value)) {
            $value = (string)$value;
        }

        // Проверяем, является ли строка сериализованными данными
        // В Bitrix HTML-свойства начинаются с 'a:' (массив)
        if (is_string($value) && strlen($value) > 0 && strpos($value, 'a:') === 0) {
            $unserialized = @unserialize($value);
            if ($unserialized !== false && is_array($unserialized)) {
                // Стандартный формат Bitrix: массив с ключом TEXT
                if (isset($unserialized['TEXT'])) {
                    return (string)$unserialized['TEXT'];
                }
            }
        }

        return $value;
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

        // Обрабатываем TEXT и COMMENT - могут быть сериализованными
        $text = self::unserializeText($item['TEXT'] ?? '');
        $comment = self::unserializeText($item['COMMENT'] ?? '');
        
        return [
            'id'         => (int)$item['ID'],
            'taskId'     => (int)$item['TASK_ID'],
            'userId'     => (int)$item['USER_ID'],
            'text'       => $text,
            'file'       => self::getFileUrl($item['FILE_ID']),
            'score'      => (int)($item['SCORE'] ?: 0),
            'dateSubmit' => $dateSubmit?->toString(),
            'comment'    => $comment,
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

    // Получить ответ текущего пользователя по заданию
    public static function getMyAnswer($data = null)
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

        $userId = (int)$USER->GetID();

        $item = TaskAnswersTable::query()
            ->withSelect()
            ->where('TASK_ID', $taskId)
            ->where('USER_ID', $userId)
            ->addOrder('DATE_SUBMIT_STR', 'DESC')
            ->exec()
            ->fetch();

        if (!$item) {
            return ['error' => 'Answer not found'];
        }

        return self::process($item);
    }

    // Создать или обновить ответ на задание
    public static function submit($data = null)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'Unauthorized'];
        }

        \Bitrix\Main\Loader::includeModule('iblock');

        $taskId = (int)($data['taskId'] ?? 0);
        $text = trim($data['text'] ?? '');
        $link = trim($data['link'] ?? '');
        $answerId = (int)($data['answerId'] ?? 0);

        if ($taskId <= 0) {
            return ['error' => 'taskId required'];
        }

        // Проверяем, что задание существует
        $task = \Legacy\Iblock\TasksTable::query()
            ->withSelect()
            ->where('ID', $taskId)
            ->exec()
            ->fetch();

        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $userId = (int)$USER->GetID();
        $iblockId = \Legacy\General\Constants::IB_TASK_ANSWERS;

        // Обработка файла
        $fileId = null;
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES['file']['size'];
            $maxSize = 50 * 1024 * 1024; // 50 МБ

            if ($fileSize > $maxSize) {
                return ['error' => 'File size exceeds 50 MB limit'];
            }

            $arFile = $_FILES['file'];
            $fileId = \CFile::SaveFile($arFile, 'task_answers');
            if (!$fileId) {
                return ['error' => 'Failed to save file'];
            }
        }

        // Если передан link, сохраняем его в text
        if (!empty($link)) {
            $text = $link;
        }

        // Если есть существующий ответ, обновляем его
        if ($answerId > 0) {
            $existingAnswer = TaskAnswersTable::query()
                ->withSelect()
                ->where('ID', $answerId)
                ->where('USER_ID', $userId)
                ->exec()
                ->fetch();

            if (!$existingAnswer) {
                return ['error' => 'Answer not found or access denied'];
            }

            $element = new \CIBlockElement();
            $arFields = [
                'NAME' => 'Ответ на задание #' . $taskId,
            ];

            $arProps = [
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_TASK => $taskId,
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_USER_ID => $userId,
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_DATE_SUBMIT => new \Bitrix\Main\Type\DateTime(),
            ];

            if (!empty($text)) {
                $arProps[\Legacy\General\Constants::IB_PROP_TASK_ANSWERS_TEXT] = $text;
            }

            if ($fileId) {
                // Удаляем старый файл, если есть
                if ($existingAnswer['FILE_ID']) {
                    \CFile::Delete($existingAnswer['FILE_ID']);
                }
                $arProps[\Legacy\General\Constants::IB_PROP_TASK_ANSWERS_FILE] = $fileId;
            }
            // Если файл не передан, старый файл остается (если был)

            if ($element->Update($answerId, $arFields, false, false, true, true)) {
                \CIBlockElement::SetPropertyValues($answerId, $iblockId, $arProps);
                return ['success' => true, 'answerId' => $answerId];
            } else {
                return ['error' => $element->LAST_ERROR];
            }
        } else {
            // Создаем новый ответ
            $element = new \CIBlockElement();
            $arFields = [
                'IBLOCK_ID' => $iblockId,
                'NAME' => 'Ответ на задание #' . $taskId,
                'ACTIVE' => 'Y',
            ];

            $arProps = [
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_TASK => $taskId,
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_USER_ID => $userId,
                \Legacy\General\Constants::IB_PROP_TASK_ANSWERS_DATE_SUBMIT => new \Bitrix\Main\Type\DateTime(),
            ];

            if (!empty($text)) {
                $arProps[\Legacy\General\Constants::IB_PROP_TASK_ANSWERS_TEXT] = $text;
            }

            if ($fileId) {
                $arProps[\Legacy\General\Constants::IB_PROP_TASK_ANSWERS_FILE] = $fileId;
            }

            if ($newId = $element->Add($arFields, false, false, true, true)) {
                \CIBlockElement::SetPropertyValues($newId, $iblockId, $arProps);
                return ['success' => true, 'answerId' => $newId];
            } else {
                return ['error' => $element->LAST_ERROR];
            }
        }
    }
}