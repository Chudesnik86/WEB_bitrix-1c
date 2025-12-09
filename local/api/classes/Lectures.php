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
        // Возвращаем URL для скачивания через временную папку через прокси
        // Frontend работает на localhost:3000, поэтому используем относительный путь через прокси
        return '/download/local/api/download.php?type=lecture&fileId=' . $fileId;
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

    private static function process($item, $isAvailableByPrevious = true)
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
        $isAvailableByDate = !$dateAvailable || $now >= $dateAvailable;
        
        // Лекция доступна только если доступна по дате И по предыдущим лекциям
        $isAvailable = $isAvailableByDate && $isAvailableByPrevious;

        // Обрабатываем CONTENT - может быть сериализованным
        $content = $item['CONTENT'] ?? '';
        $content = self::unserializeText($content);
        
        return [
            'id'              => (int)$item['ID'],
            'name'            => trim($item['NAME']),
            'code'            => $item['CODE'] ?? '',
            'courseId'        => (int)$item['COURSE_ID'],
            'sort'            => (int)($item['SORT_ORDER'] ?: 500),
            'content'         => $content,
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

        $userId = (int)$USER->GetID();

        $db = \Legacy\Iblock\LecturesTable::query()
            ->withSelect()
            ->where('COURSE_ID', $courseId)
            ->addOrder('SORT_ORDER', 'ASC')
            ->addOrder('ID', 'ASC')
            ->exec();

        $items = [];
        $previousLecturesCompleted = true; // Первая лекция всегда доступна
        
        while ($item = $db->fetch()) {
            $lectureId = (int)$item['ID'];
            $isAvailableByPrevious = $previousLecturesCompleted;
            
            // Если предыдущие лекции не завершены, текущая недоступна
            if (!$previousLecturesCompleted) {
                $isAvailableByPrevious = false;
            } else {
                // Проверяем, все ли задания текущей лекции выполнены
                $tasks = \Legacy\Iblock\TasksTable::query()
                    ->withSelect()
                    ->where('LECTURE_ID', $lectureId)
                    ->exec();
                
                $allTasksCompleted = true;
                $hasTasks = false;
                
                while ($task = $tasks->fetch()) {
                    $hasTasks = true;
                    $taskId = (int)$task['ID'];
                    
                    // Проверяем, есть ли ответ студента на это задание
                    $answer = \Legacy\Iblock\TaskAnswersTable::query()
                        ->withSelect()
                        ->where('TASK_ID', $taskId)
                        ->where('USER_ID', $userId)
                        ->exec()
                        ->fetch();
                    
                    if (!$answer) {
                        // Нет ответа на задание - лекция не завершена
                        $allTasksCompleted = false;
                        break;
                    }
                }
                
                // Если в лекции нет заданий, считаем её завершенной
                if (!$hasTasks) {
                    $allTasksCompleted = true;
                }
                
                // Если не все задания выполнены, текущая и следующие лекции недоступны
                if (!$allTasksCompleted) {
                    $isAvailableByPrevious = false;
                    $previousLecturesCompleted = false;
                } else {
                    // Все задания выполнены - следующая лекция будет доступна
                    $previousLecturesCompleted = true;
                }
            }
            
            $processed = self::process($item, $isAvailableByPrevious);
            $items[] = $processed;
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