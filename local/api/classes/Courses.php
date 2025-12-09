<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\Iblock\CoursesTable;

class Courses
{
    private static function ensureIblockModule()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Iblock module is not available');
        }
    }
    private static function process($item)
    {
        return [
            'id'           => (int)$item['ID'],
            'name'         => $item['NAME'],
            'code'         => $item['CODE'],
            'preview'      => $item['PREVIEW_TEXT'] ?? '',
            'description'  => $item['DESCRIPTION_VALUE'] ?? '',
        ];
    }

    public static function getList($arRequest)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'User not authenticated'];
        }

        try {
            self::ensureIblockModule();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        // Проверяем, что константы определены
        if (!\Legacy\General\Constants::IB_COURSES) {
            return ['error' => 'IB_COURSES constant is not defined. Please set the courses infoblock ID in Constants.php'];
        }

        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, min(50, (int)($arRequest['limit'] ?? 10)));

        try {
            $query = CoursesTable::query()
                ->countTotal(true)
                ->setLimit($limit)
                ->setOffset(($page - 1) * $limit);
            
            // Устанавливаем дефолтный scope (IBLOCK_ID и ACTIVE)
            CoursesTable::setDefaultScope($query);
            
            // Добавляем runtime поля и select
            CoursesTable::withSelect($query);

            // Только публичные (для пользователей)
            $isAdmin = $USER->IsAdmin();
            if (!$isAdmin) {
                // Временно отключаем фильтр по публичности для отладки
                // CoursesTable::withPublicOnly($query);
                // Фильтрация по датам временно отключена из-за проблем с addFilter и runtime полями
                // CoursesTable::withActivePeriod($query);
            }

            $db = $query->exec();

            $items = [];
            $totalBeforeFilter = 0;
            $debugInfo = [];
            
            while ($item = $db->fetch()) {
                $totalBeforeFilter++;
                $processed = self::process($item);
                
                // Отладка: собираем информацию о курсе
                $courseDebug = [
                    'id' => $processed['id'],
                    'name' => $processed['name'],
                    'filteredOut' => false,
                    'filterReason' => null
                ];
                
                $items[] = $processed;
                $debugInfo[] = $courseDebug;
            }

            return [
                'items'       => $items,
                'total'       => count($items), // Используем количество после фильтрации
                'page'        => $page,
                'limit'       => $limit,
                'pages'       => ceil(count($items) / $limit),
                'debug'       => [
                    'totalBeforeFilter' => $totalBeforeFilter,
                    'totalAfterFilter' => count($items),
                    'isAdmin' => $isAdmin,
                    'courses' => $debugInfo
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    // Получить курс по ID
    public static function getById($arRequest)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'User not authenticated'];
        }

        try {
            self::ensureIblockModule();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        // Проверяем, что константы определены
        if (!\Legacy\General\Constants::IB_COURSES) {
            return ['error' => 'IB_COURSES constant is not defined. Please set the courses infoblock ID in Constants.php'];
        }

        $courseId = (int)($arRequest['courseId'] ?? $arRequest['id'] ?? 0);
        if ($courseId <= 0) {
            return ['error' => 'courseId required'];
        }

        try {
            $query = CoursesTable::query()
                ->where('ID', $courseId);
            
            // Устанавливаем дефолтный scope (IBLOCK_ID и ACTIVE)
            CoursesTable::setDefaultScope($query);
            
            // Добавляем runtime поля и select
            CoursesTable::withSelect($query);

            // Только публичные + в периоде действия (для пользователей)
            $isAdmin = $USER->IsAdmin();
            if (!$isAdmin) {
                CoursesTable::withPublicOnly($query);
                CoursesTable::withActivePeriod($query);
            }

            $item = $query->exec()->fetch();

            if (!$item) {
                return ['error' => 'Course not found'];
            }

            return self::process($item);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    // Получить прогресс студента по курсу
    public static function getProgress($arRequest)
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            return ['error' => 'User not authenticated'];
        }

        $courseId = (int)($arRequest['courseId'] ?? $arRequest['id'] ?? 0);
        if ($courseId <= 0) {
            return ['error' => 'courseId required'];
        }

        $userId = (int)$USER->GetID();

        // Получаем все лекции курса
        $lectures = \Legacy\Iblock\LecturesTable::query()
            ->withSelect()
            ->where('COURSE_ID', $courseId)
            ->addOrder('SORT_ORDER', 'ASC')
            ->exec();

        $allTasks = [];
        $completedTasks = 0;
        $tasksWithAnswers = [];

        while ($lecture = $lectures->fetch()) {
            // Получаем все задания лекции
            $tasks = \Legacy\Iblock\TasksTable::query()
                ->withSelect()
                ->where('LECTURE_ID', $lecture['ID'])
                ->exec();

            while ($task = $tasks->fetch()) {
                $allTasks[] = $task;

                // Проверяем, есть ли ответ студента
                $answer = \Legacy\Iblock\TaskAnswersTable::query()
                    ->withSelect()
                    ->where('TASK_ID', $task['ID'])
                    ->where('USER_ID', $userId)
                    ->exec()
                    ->fetch();

                if ($answer) {
                    $completedTasks++;
                    
                    // Определяем статус: если есть оценка (не пусто) - проверено, иначе - на проверке
                    $hasScore = isset($answer['SCORE']) && $answer['SCORE'] !== '' && $answer['SCORE'] !== null;
                    $status = $hasScore ? 'Проверено' : 'На проверке';
                    
                    $tasksWithAnswers[] = [
                        'taskId' => (int)$task['ID'],
                        'taskName' => $task['NAME'],
                        'lectureId' => (int)$lecture['ID'],
                        'lectureName' => $lecture['NAME'],
                        'answerId' => (int)$answer['ID'],
                        'score' => (int)($answer['SCORE'] ?? 0),
                        'maxScore' => (int)($task['MAX_SCORE'] ?? 0),
                        'comment' => $answer['COMMENT'] ?? '',
                        'dateSubmit' => $answer['DATE_SUBMIT_STR'] ?? null,
                        'status' => $status,
                    ];
                }
            }
        }

        $totalTasks = count($allTasks);
        $progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

        return [
            'courseId' => $courseId,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'progressPercent' => $progressPercent,
            'answers' => $tasksWithAnswers,
        ];
    }
}