<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$taskId = (int)$_GET['id'] ?? 0;
if (!$taskId) {
    LocalRedirect('/student/courses/');
}

$APPLICATION->SetTitle("Задание");
?>

<div id="student-task-app">
    <div class="container">
        <div class="task-header">
            <a href="#" id="back-link" class="back-link">← Назад к курсу</a>
            <h1 id="task-title">Загрузка...</h1>
        </div>

        <div id="task-loading" class="loading">Загрузка задания...</div>
        <div id="task-error" class="error" style="display: none;"></div>

        <div id="task-content" style="display: none;">
            <div class="task-info">
                <div id="task-description" class="task-description"></div>
                <div id="task-file" class="task-file"></div>
                <div id="task-deadline" class="task-deadline"></div>
            </div>

            <div class="task-answer-section">
                <h2>Ваше решение</h2>
                <div id="answer-status" class="answer-status"></div>
                
                <form id="task-answer-form">
                    <input type="hidden" id="task-id" value="<?= $taskId ?>">
                    <input type="hidden" id="answer-id" value="">

                    <div class="form-group">
                        <label>Тип ответа:</label>
                        <select id="answer-type" class="form-control">
                            <option value="text">Текстовый ответ</option>
                            <option value="file">Файл</option>
                            <option value="link">Ссылка на внешний ресурс</option>
                        </select>
                    </div>

                    <div id="answer-text-group" class="form-group">
                        <label for="answer-text">Текст ответа:</label>
                        <textarea id="answer-text" class="form-control" rows="10"></textarea>
                    </div>

                    <div id="answer-file-group" class="form-group" style="display: none;">
                        <label for="answer-file">Файл (максимум 50 МБ):</label>
                        <input type="file" id="answer-file" class="form-control">
                        <div id="current-file" class="current-file"></div>
                    </div>

                    <div id="answer-link-group" class="form-group" style="display: none;">
                        <label for="answer-link">Ссылка:</label>
                        <input type="url" id="answer-link" class="form-control" placeholder="https://...">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Отправить решение</button>
                        <button type="button" id="cancel-edit" class="btn btn-secondary" style="display: none;">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/local/templates/student/css/student.css">
<script src="/local/templates/student/js/api.js"></script>
<script>
    const taskId = <?= $taskId ?>;
</script>
<script src="/local/templates/student/js/task.js"></script>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>


