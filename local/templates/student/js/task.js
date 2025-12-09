// Страница задания
let taskData = null;
let myAnswer = null;
let lectureId = null;

document.addEventListener('DOMContentLoaded', async () => {
    const loadingEl = document.getElementById('task-loading');
    const errorEl = document.getElementById('task-error');
    const contentEl = document.getElementById('task-content');
    const titleEl = document.getElementById('task-title');
    const backLinkEl = document.getElementById('back-link');

    const answerTypeSelect = document.getElementById('answer-type');
    const answerTextGroup = document.getElementById('answer-text-group');
    const answerFileGroup = document.getElementById('answer-file-group');
    const answerLinkGroup = document.getElementById('answer-link-group');
    const form = document.getElementById('task-answer-form');
    const cancelEditBtn = document.getElementById('cancel-edit');

    // Обработчик изменения типа ответа
    answerTypeSelect.addEventListener('change', () => {
        const type = answerTypeSelect.value;
        answerTextGroup.style.display = type === 'text' ? 'block' : 'none';
        answerFileGroup.style.display = type === 'file' ? 'block' : 'none';
        answerLinkGroup.style.display = type === 'link' ? 'block' : 'none';
    });

    // Обработчик отправки формы
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitAnswer();
    });

    // Обработчик отмены редактирования
    cancelEditBtn.addEventListener('click', () => {
        loadMyAnswer();
    });

    try {
        // Загружаем задание
        const taskDataResult = await api.getTask(taskId);
        
        if (taskDataResult.error) {
            throw new Error(taskDataResult.error);
        }

        taskData = taskDataResult;
        lectureId = taskData.lectureId;

        // Загружаем лекцию для получения courseId
        let courseId = null;
        try {
            const lectureData = await api.getLecture(lectureId);
            if (lectureData && lectureData.courseId) {
                courseId = lectureData.courseId;
                backLinkEl.href = `/student/course/?id=${courseId}`;
            }
        } catch (error) {
            console.error('Error loading lecture:', error);
            backLinkEl.href = '/student/courses/';
        }

        loadingEl.style.display = 'none';
        titleEl.textContent = taskData.name;
        contentEl.style.display = 'block';

        // Отображаем информацию о задании
        displayTaskInfo();

        // Загружаем ответ студента
        await loadMyAnswer();

    } catch (error) {
        loadingEl.style.display = 'none';
        errorEl.textContent = error.message || 'Ошибка загрузки задания';
        errorEl.style.display = 'block';
        console.error(error);
    }
});

function displayTaskInfo() {
    const descriptionEl = document.getElementById('task-description');
    const fileEl = document.getElementById('task-file');
    const deadlineEl = document.getElementById('task-deadline');

    if (taskData.text) {
        descriptionEl.innerHTML = `<div class="task-text">${taskData.text}</div>`;
    } else {
        descriptionEl.innerHTML = '<p>Описание задания отсутствует.</p>';
    }

    if (taskData.file) {
        const fileName = taskData.file.split('/').pop();
        fileEl.innerHTML = `
            <a href="${taskData.file}" download class="file-link">
                📎 Скачать материал задания: ${fileName}
            </a>
        `;
    } else {
        fileEl.innerHTML = '';
    }

    if (taskData.deadline) {
        const deadline = new Date(taskData.deadline);
        const now = new Date();
        const isOverdue = deadline < now;
        
        deadlineEl.innerHTML = `
            <div class="deadline ${isOverdue ? 'overdue' : ''}">
                <strong>Срок сдачи:</strong> ${utils.formatDate(taskData.deadline)}
                ${isOverdue ? '<span class="badge badge-danger">Просрочено</span>' : ''}
            </div>
        `;
    } else {
        deadlineEl.innerHTML = '';
    }

    if (taskData.maxScore > 0) {
        deadlineEl.innerHTML += `<div class="max-score">Максимальный балл: ${taskData.maxScore}</div>`;
    }
}

async function loadMyAnswer() {
    const statusEl = document.getElementById('answer-status');
    const answerIdInput = document.getElementById('answer-id');
    const answerTextInput = document.getElementById('answer-text');
    const answerLinkInput = document.getElementById('answer-link');
    const answerFileInput = document.getElementById('answer-file');
    const currentFileEl = document.getElementById('current-file');
    const cancelEditBtn = document.getElementById('cancel-edit');

    try {
        const answerData = await api.getMyAnswer(taskId);
        
        if (answerData.error && answerData.error !== 'Answer not found') {
            throw new Error(answerData.error);
        }

        if (answerData.error) {
            if (answerData.error === 'Answer not found') {
                // Нет ответа
                myAnswer = null;
                statusEl.innerHTML = '<div class="status-info">Вы еще не отправили решение.</div>';
                answerIdInput.value = '';
                answerTextInput.value = '';
                answerLinkInput.value = '';
                answerFileInput.value = '';
                currentFileEl.innerHTML = '';
                cancelEditBtn.style.display = 'none';
                return;
            } else {
                throw new Error(answerData.error);
            }
        }

        myAnswer = answerData;
        answerIdInput.value = answerData.id;

        // Определяем тип ответа и заполняем поля
        if (answerData.file) {
            answerTypeSelect.value = 'file';
            answerTypeSelect.dispatchEvent(new Event('change'));
            const fileName = answerData.file.split('/').pop();
            currentFileEl.innerHTML = `
                <div class="current-file-info">
                    <a href="${answerData.file}" download>📎 ${fileName}</a>
                    <button type="button" class="btn-remove-file" onclick="removeCurrentFile()">Удалить</button>
                </div>
            `;
        } else if (answerData.text && (answerData.text.startsWith('http://') || answerData.text.startsWith('https://'))) {
            answerTypeSelect.value = 'link';
            answerTypeSelect.dispatchEvent(new Event('change'));
            answerLinkInput.value = answerData.text;
        } else {
            answerTypeSelect.value = 'text';
            answerTypeSelect.dispatchEvent(new Event('change'));
            answerTextInput.value = answerData.text || '';
        }

        // Отображаем статус
        let statusHtml = '<div class="status-info">';
        if (answerData.score > 0) {
            statusHtml += `<div class="status-success">✅ Проверено. Оценка: ${answerData.score} / ${taskData.maxScore || 0}</div>`;
        } else if (answerData.comment) {
            statusHtml += `<div class="status-warning">⏳ На проверке</div>`;
        } else {
            statusHtml += `<div class="status-pending">📤 Отправлено</div>`;
        }
        
        if (answerData.dateSubmit) {
            statusHtml += `<div class="status-date">Отправлено: ${utils.formatDate(answerData.dateSubmit)}</div>`;
        }
        
        if (answerData.comment) {
            statusHtml += `<div class="teacher-comment"><strong>Комментарий преподавателя:</strong><p>${answerData.comment}</p></div>`;
        }
        
        statusHtml += '</div>';
        statusEl.innerHTML = statusHtml;

        cancelEditBtn.style.display = 'none';

    } catch (error) {
        statusEl.innerHTML = `<div class="error">Ошибка загрузки ответа: ${error.message}</div>`;
        console.error(error);
    }
}

function removeCurrentFile() {
    document.getElementById('current-file').innerHTML = '';
    document.getElementById('answer-file').value = '';
    document.getElementById('answer-type').value = 'file';
    document.getElementById('answer-type').dispatchEvent(new Event('change'));
}

async function submitAnswer() {
    const answerIdInput = document.getElementById('answer-id');
    const answerTypeSelect = document.getElementById('answer-type');
    const answerTextInput = document.getElementById('answer-text');
    const answerLinkInput = document.getElementById('answer-link');
    const answerFileInput = document.getElementById('answer-file');
    const statusEl = document.getElementById('answer-status');
    const cancelEditBtn = document.getElementById('cancel-edit');

    const answerId = answerIdInput.value ? parseInt(answerIdInput.value) : null;
    const type = answerTypeSelect.value;
    let text = '';
    let link = '';
    let file = null;

    // Валидация
    if (type === 'text') {
        text = answerTextInput.value.trim();
        if (!text) {
            alert('Введите текст ответа');
            return;
        }
    } else if (type === 'file') {
        file = answerFileInput.files[0];
        if (!file && !document.getElementById('current-file').innerHTML) {
            alert('Выберите файл');
            return;
        }
        if (file && file.size > 50 * 1024 * 1024) {
            alert('Размер файла не должен превышать 50 МБ');
            return;
        }
    } else if (type === 'link') {
        link = answerLinkInput.value.trim();
        if (!link) {
            alert('Введите ссылку');
            return;
        }
        if (!link.startsWith('http://') && !link.startsWith('https://')) {
            link = 'https://' + link;
        }
    }

    statusEl.innerHTML = '<div class="loading">Отправка решения...</div>';

    try {
        const result = await api.submitAnswer(taskId, answerId, text, link, file);
        
        if (result.error) {
            throw new Error(result.error);
        }

        if (result.success) {
            statusEl.innerHTML = '<div class="status-success">✅ Решение успешно отправлено!</div>';
            
            // Перезагружаем ответ
            setTimeout(() => {
                loadMyAnswer();
            }, 500);
        }
    } catch (error) {
        statusEl.innerHTML = `<div class="error">Ошибка отправки: ${error.message}</div>`;
        console.error(error);
    }
}

