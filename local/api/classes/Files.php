<?php

namespace Legacy\API;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

class Files
{
    /**
     * Скачивание файла лекции через временную папку
     * @param array $data
     * @return void
     */
    public static function downloadLectureFile($data = null)
    {
        global $USER;
        
        if (!$USER->IsAuthorized()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем ID файла из запроса
        $fileId = 0;
        if (is_array($data)) {
            $fileId = (int)($data['fileId'] ?? $data['file_id'] ?? $data['id'] ?? 0);
        }
        
        if ($fileId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'File ID required'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем информацию о файле из Bitrix
        $file = \CFile::GetFileArray($fileId);
        
        if (!$file || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file['SRC'])) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Создаем папку для временных файлов, если её нет
        $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/local/temp_downloads/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Генерируем уникальное имя для временного файла
        $originalFileName = $file['ORIGINAL_NAME'] ?: basename($file['SRC']);
        $tempFileName = uniqid('lecture_', true) . '_' . $originalFileName;
        $tempFilePath = $tempDir . $tempFileName;

        // Копируем файл во временную папку
        $sourcePath = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        if (!copy($sourcePath, $tempFilePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to copy file'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Определяем MIME-тип
        $mimeType = $file['CONTENT_TYPE'] ?: mime_content_type($tempFilePath);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }

        // Отправляем файл пользователю
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($originalFileName) . '"');
        header('Content-Length: ' . filesize($tempFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Читаем и отправляем файл
        $handle = fopen($tempFilePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }

        // Удаляем временный файл после отправки
        if (file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }

        exit;
    }

    /**
     * Скачивание файла задания через временную папку
     * @param array $data
     * @return void
     */
    public static function downloadTaskFile($data = null)
    {
        global $USER;
        
        if (!$USER->IsAuthorized()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем ID файла из запроса
        $fileId = 0;
        if (is_array($data)) {
            $fileId = (int)($data['fileId'] ?? $data['file_id'] ?? $data['id'] ?? 0);
        }
        
        if ($fileId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'File ID required'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем информацию о файле из Bitrix
        $file = \CFile::GetFileArray($fileId);
        
        if (!$file || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file['SRC'])) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Создаем папку для временных файлов, если её нет
        $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/local/temp_downloads/';
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create temp directory'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Генерируем уникальное имя для временного файла
        $originalFileName = $file['ORIGINAL_NAME'] ?: basename($file['SRC']);
        $tempFileName = uniqid('task_', true) . '_' . $originalFileName;
        $tempFilePath = $tempDir . $tempFileName;

        // Копируем файл во временную папку
        $sourcePath = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        if (!copy($sourcePath, $tempFilePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to copy file'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Определяем MIME-тип
        $mimeType = $file['CONTENT_TYPE'] ?: mime_content_type($tempFilePath);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }

        // Отправляем файл пользователю
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($originalFileName) . '"');
        header('Content-Length: ' . filesize($tempFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Читаем и отправляем файл
        $handle = fopen($tempFilePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }

        // Удаляем временный файл после отправки
        if (file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }

        exit;
    }

    /**
     * Скачивание файла ответа студента через временную папку
     * @param array $data
     * @return void
     */
    public static function downloadAnswerFile($data = null)
    {
        global $USER;
        
        if (!$USER->IsAuthorized()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем ID файла из запроса
        $fileId = 0;
        if (is_array($data)) {
            $fileId = (int)($data['fileId'] ?? $data['file_id'] ?? $data['id'] ?? 0);
        }
        
        if ($fileId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'File ID required'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем информацию о файле из Bitrix
        $file = \CFile::GetFileArray($fileId);
        
        if (!$file || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file['SRC'])) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Создаем папку для временных файлов, если её нет
        $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/local/temp_downloads/';
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create temp directory'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Генерируем уникальное имя для временного файла
        $originalFileName = $file['ORIGINAL_NAME'] ?: basename($file['SRC']);
        $tempFileName = uniqid('answer_', true) . '_' . $originalFileName;
        $tempFilePath = $tempDir . $tempFileName;

        // Копируем файл во временную папку
        $sourcePath = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        if (!copy($sourcePath, $tempFilePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to copy file'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Определяем MIME-тип
        $mimeType = $file['CONTENT_TYPE'] ?: mime_content_type($tempFilePath);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }

        // Отправляем файл пользователю
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($originalFileName) . '"');
        header('Content-Length: ' . filesize($tempFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Читаем и отправляем файл
        $handle = fopen($tempFilePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }

        // Удаляем временный файл после отправки
        if (file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }

        exit;
    }

    /**
     * Скачивание файла по прямому пути (для файлов из /upload/...)
     * @param string $filePath Путь к файлу относительно DOCUMENT_ROOT (например, /upload/task_answers/...)
     * @return void
     */
    public static function downloadFileByPath($filePath)
    {
        global $USER;
        
        if (!$USER->IsAuthorized()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($filePath) || strpos($filePath, '/upload/') !== 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file path'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Определяем MIME-тип
        $mimeType = mime_content_type($fullPath);
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }

        // Отправляем файл пользователю напрямую, без копирования
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes(basename($filePath)) . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Читаем и отправляем файл напрямую
        $handle = fopen($fullPath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }

        exit;
    }
}

