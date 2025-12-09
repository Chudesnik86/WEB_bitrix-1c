<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$courseId = (int)$_GET['id'] ?? 0;
if (!$courseId) {
    LocalRedirect('/student/courses/');
}

$APPLICATION->SetTitle("Курс");
?>

<div id="student-course-app">
    <div class="container">
        <div class="course-header">
            <a href="/student/courses/" class="back-link">← Назад к курсам</a>
            <h1 id="course-title">Загрузка...</h1>
        </div>
        
        <div class="course-tabs">
            <button class="tab-btn active" data-tab="content">Содержание</button>
            <button class="tab-btn" data-tab="progress">Прогресс и оценки</button>
        </div>

        <div id="course-loading" class="loading">Загрузка курса...</div>
        <div id="course-error" class="error" style="display: none;"></div>

        <div id="tab-content" class="tab-content active">
            <div id="course-content"></div>
        </div>

        <div id="tab-progress" class="tab-content" style="display: none;">
            <div id="course-progress"></div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/local/templates/student/css/student.css">
<script src="/local/templates/student/js/api.js"></script>
<script>
    const courseId = <?= $courseId ?>;
</script>
<script src="/local/templates/student/js/course.js"></script>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>


