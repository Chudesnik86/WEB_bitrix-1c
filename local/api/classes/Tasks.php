<?php

namespace Legacy\API;

use Legacy\Iblock\TasksTable;

class Tasks
{
    private static function getFileUrl($fileId)
    {
        if (!$fileId) return null;
        // Возвращаем URL для скачивания через временную папку через прокси
        // Frontend работает на localhost:3000, поэтому используем относительный путь через прокси
        return '/download/local/api/download.php?type=task&fileId=' . $fileId;
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
        $deadline = null;
        if (!empty($item['DEADLINE_STR']) && $item['DEADLINE_STR'] !== '2099-12-31 23:59:59') {
            try {
                $deadlineStr = $item['DEADLINE_STR'];
                
                // Проверяем формат DD.MM.YYYY или DD.MM.YYYY HH:MM:SS
                if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{1,2}):(\d{1,2}))?$/', $deadlineStr, $matches)) {
                    // Парсим дату в формате DD.MM.YYYY
                    $day = (int)$matches[1];
                    $month = (int)$matches[2];
                    $year = (int)$matches[3];
                    $hour = isset($matches[4]) ? (int)$matches[4] : 23;
                    $minute = isset($matches[5]) ? (int)$matches[5] : 59;
                    $second = isset($matches[6]) ? (int)$matches[6] : 59;
                    
                    // Создаем DateTime объект напрямую
                    $deadline = \Bitrix\Main\Type\DateTime::createFromUserTime(
                        sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second)
                    );
                } else {
                    // Пробуем стандартный парсинг для других форматов
                    $deadline = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($deadlineStr));
                }
            } catch (\Exception $e) {
                $deadline = null;
            }
        }

        $now = new \Bitrix\Main\Type\DateTime();
        // Задание активно, если дедлайна нет или текущее время меньше или равно дедлайну
        // Для сравнения используем только дату (без времени), если время не указано в дедлайне
        $isActive = true;
        if ($deadline) {
            // Сравниваем даты: если текущая дата больше дедлайна, задание просрочено
            $nowDate = new \Bitrix\Main\Type\Date($now->format('Y-m-d'));
            $deadlineDate = new \Bitrix\Main\Type\Date($deadline->format('Y-m-d'));
            $isActive = $nowDate <= $deadlineDate;
        }

        // Обрабатываем TEXT - может быть сериализованным
        $text = $item['TEXT'] ?? '';
        $text = self::unserializeText($text);
        
        return [
            'id'         => (int)$item['ID'],
            'name'       => trim($item['NAME']),
            'lectureId'  => (int)$item['LECTURE_ID'],
            'text'       => $text,
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