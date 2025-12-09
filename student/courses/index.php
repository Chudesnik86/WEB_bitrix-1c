<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Мои курсы");
?>

<div id="student-courses-app">
    <div class="container">
        <h1>Мои курсы</h1>
        <div id="courses-loading" class="loading">Загрузка курсов...</div>
        <div id="courses-error" class="error" style="display: none;"></div>
        <div id="courses-list" class="courses-list"></div>
    </div>
</div>

<link rel="stylesheet" href="/local/templates/student/css/student.css">
<script src="/local/templates/student/js/api.js"></script>
<script src="/local/templates/student/js/courses.js"></script>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>


