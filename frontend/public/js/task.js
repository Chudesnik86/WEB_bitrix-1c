// Страница задания
let taskData = null;
let myAnswer = null;
let lectureId = null;

// Функция для десериализации текста из Bitrix (запасной вариант на фронтенде)
function unserializeText(value) {
    if (!value || typeof value !== 'string') {
        return value || '';
    }
    
    // Проверяем, является ли строка сериализованными данными Bitrix
    // Формат: a:2:{s:4:"TEXT";s:2:"С";s:4:"TYPE";s:4:"TEXT";}
    if (value.startsWith('a:')) {
        try {
            // Пытаемся распарсить сериализованную строку
            // Это упрощенный парсер для формата a:2:{s:4:"TEXT";s:2:"С";s:4:"TYPE";s:4:"TEXT";}
            const textMatch = value.match(/s:\d+:"TEXT";s:\d+:"([^"]*)"/);
            if (textMatch && textMatch[1]) {
                return textMatch[1];
            }
        } catch (e) {
            console.warn('Failed to unserialize text:', e);
        }
    }
    
    return value;
}

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
        // Проверяем авторизацию
        const user = await api.getUser();
        if (user.error) {
            window.location.href = '/login';
            return;
        }

        // Обновляем имя пользователя в хедере
        updateUserName(user);

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
                backLinkEl.href = `/course?id=${courseId}`;
            }
        } catch (error) {
            console.error('Error loading lecture:', error);
            backLinkEl.href = '/courses';
        }

        // Проверяем доступность лекции на фронтенде
        if (courseId) {
            try {
                // Получаем все лекции курса
                const lecturesData = await api.getLectures(courseId);
                if (lecturesData.items) {
                    let previousLecturesCompleted = true;
                    let currentLectureIndex = -1;
                    
                    // Находим индекс текущей лекции
                    for (let i = 0; i < lecturesData.items.length; i++) {
                        if (lecturesData.items[i].id === lectureId) {
                            currentLectureIndex = i;
                            break;
                        }
                    }
                    
                    // Первая лекция (индекс 0) всегда доступна - пропускаем проверку
                    if (currentLectureIndex === 0) {
                        // Первая лекция доступна, продолжаем загрузку
                    } else if (currentLectureIndex > 0) {
                        // Проверяем все предыдущие лекции (начиная с первой)
                        let previousLecturesCompleted = true;
                        
                        for (let i = 0; i < currentLectureIndex; i++) {
                            const prevLecture = lecturesData.items[i];
                            
                            // Получаем задания предыдущей лекции
                            const prevTasksData = await api.getTasks(prevLecture.id);
                            if (prevTasksData.items && prevTasksData.items.length > 0) {
                                // Проверяем, все ли задания выполнены
                                let allTasksCompleted = true;
                                
                                for (const task of prevTasksData.items) {
                                    try {
                                        const answerData = await api.getMyAnswer(task.id);
                                        if (!answerData || answerData.error) {
                                            allTasksCompleted = false;
                                            break;
                                        }
                                    } catch (error) {
                                        allTasksCompleted = false;
                                        break;
                                    }
                                }
                                
                                if (!allTasksCompleted) {
                                    previousLecturesCompleted = false;
                                    break;
                                }
                            }
                        }
                        
                        // Если предыдущие лекции не завершены, блокируем доступ
                        if (!previousLecturesCompleted) {
                            loadingEl.style.display = 'none';
                            errorEl.innerHTML = `
                                <div style="padding: 20px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                    <h3 style="margin-top: 0; color: #856404;">⚠️ Лекция недоступна</h3>
                                    <p style="color: #856404; margin-bottom: 10px;">
                                        Для доступа к этому заданию необходимо выполнить все задания предыдущих лекций.
                                    </p>
                                    <a href="/course?id=${courseId}" class="btn btn-primary" style="margin-top: 15px; display: inline-block; text-decoration: none; padding: 10px 20px; background-color: #007bff; color: white; border-radius: 4px;">
                                        Вернуться к курсу
                                    </a>
                                </div>
                            `;
                            errorEl.style.display = 'block';
                            contentEl.style.display = 'none';
                            return;
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking lecture availability:', error);
                // Продолжаем загрузку, если проверка не удалась
            }
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
        const text = unserializeText(taskData.text);
        descriptionEl.innerHTML = `<div class="task-text">${text}</div>`;
    } else {
        descriptionEl.innerHTML = '<p>Описание задания отсутствует.</p>';
    }

    if (taskData.file) {
        const fileName = taskData.file.split('/').pop().split('?')[0];
        fileEl.innerHTML = `
            <a href="${getDownloadUrl(taskData.file)}" class="file-link" download>
                📎 Скачать материал задания: ${fileName}
            </a>
        `;
    } else {
        fileEl.innerHTML = '';
    }

    if (taskData.deadline) {
        // Парсим дату правильно, учитывая формат DD.MM.YYYY
        let deadline;
        const deadlineStr = taskData.deadline;
        
        // Проверяем формат DD.MM.YYYY или DD.MM.YYYY HH:MM:SS
        const ddmmyyyyMatch = deadlineStr.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{1,2}):(\d{1,2}))?/);
        if (ddmmyyyyMatch) {
            const day = parseInt(ddmmyyyyMatch[1], 10);
            const month = parseInt(ddmmyyyyMatch[2], 10) - 1; // месяцы в JS начинаются с 0
            const year = parseInt(ddmmyyyyMatch[3], 10);
            const hours = ddmmyyyyMatch[4] ? parseInt(ddmmyyyyMatch[4], 10) : 23; // По умолчанию конец дня
            const minutes = ddmmyyyyMatch[5] ? parseInt(ddmmyyyyMatch[5], 10) : 59;
            const seconds = ddmmyyyyMatch[6] ? parseInt(ddmmyyyyMatch[6], 10) : 59;
            deadline = new Date(year, month, day, hours, minutes, seconds);
        } else {
            // Пробуем стандартный парсинг для других форматов
            deadline = new Date(deadlineStr);
        }
        
        const now = new Date();
        // Сравниваем только даты (без времени) для дедлайна, если время не указано
        // Задание просрочено, если текущая дата больше дедлайна
        const deadlineDateOnly = new Date(deadline.getFullYear(), deadline.getMonth(), deadline.getDate());
        const nowDateOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const isOverdue = nowDateOnly > deadlineDateOnly;
        
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
    const answerTypeSelect = document.getElementById('answer-type');
    const currentFileEl = document.getElementById('current-file');
    const cancelEditBtn = document.getElementById('cancel-edit');

    try {
        const answerData = await api.getMyAnswer(taskId);
        
        if (answerData.error && answerData.error !== 'Answer not found') {
            throw new Error(answerData.error);
        }

        if (answerData.error) {
            if (answerData.error === 'Answer not found') {
                // Нет ответа - показываем форму для отправки
                myAnswer = null;
                statusEl.innerHTML = '<div class="status-info">Вы еще не отправили решение.</div>';
                answerIdInput.value = '';
                answerTextInput.value = '';
                answerLinkInput.value = '';
                answerFileInput.value = '';
                currentFileEl.innerHTML = '';
                cancelEditBtn.style.display = 'none';
                
                // Показываем форму для отправки нового ответа
                const form = document.getElementById('task-answer-form');
                form.style.display = 'block';
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
            const fileName = answerData.file.split('/').pop().split('?')[0];
            currentFileEl.innerHTML = `
                <div class="current-file-info">
                    <a href="${getDownloadUrl(answerData.file)}" download>📎 ${fileName}</a>
                    <button type="button" class="btn-remove-file" onclick="removeCurrentFile()">Удалить</button>
                </div>
            `;
        } else if (answerData.text && (answerData.text.startsWith('http://') || answerData.text.startsWith('https://'))) {
            answerTypeSelect.value = 'link';
            answerTypeSelect.dispatchEvent(new Event('change'));
            answerLinkInput.value = unserializeText(answerData.text);
        } else {
            answerTypeSelect.value = 'text';
            answerTypeSelect.dispatchEvent(new Event('change'));
            answerTextInput.value = unserializeText(answerData.text || '');
        }

        // Отображаем статус
        let statusHtml = '<div class="status-info">';
        
        // Проверяем, есть ли оценка (проверено только если score > 0 или есть комментарий преподавателя)
        const scoreValue = parseInt(answerData.score) || 0;
        const hasComment = answerData.comment && answerData.comment.trim() !== '';
        const isChecked = scoreValue > 0 || hasComment; // Проверено, если оценка > 0 или есть комментарий
        const maxScore = taskData.maxScore || 0;
        
        if (isChecked) {
            // Есть оценка или комментарий - показываем результат (статус: Проверено)
            const percentage = maxScore > 0 ? Math.round((scoreValue / maxScore) * 100) : 0;
            let scoreClass = 'status-success';
            if (percentage < 50) {
                scoreClass = 'status-danger';
            } else if (percentage < 70) {
                scoreClass = 'status-warning';
            }
            
            statusHtml += `<div class="${scoreClass}" style="font-size: 1.2em; font-weight: bold; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: ${percentage >= 70 ? '#d4edda' : percentage >= 50 ? '#fff3cd' : '#f8d7da'};">
                ✅ Проверено. Ваша оценка: <span style="font-size: 1.3em;">${scoreValue}</span> / ${maxScore} баллов (${percentage}%)
            </div>`;
        } else {
            // Нет оценки и комментария - статус: На проверке (голубой фон)
            statusHtml += `<div class="status-info" style="font-size: 1.1em; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #d1ecf1; border-left: 4px solid #0c5460; color: #0c5460;">
                ⏳ На проверке. Ваш ответ отправлен и ожидает проверки преподавателем.
            </div>`;
        }
        
        if (answerData.dateSubmit) {
            statusHtml += `<div class="status-date">Отправлено: ${utils.formatDate(answerData.dateSubmit)}</div>`;
        }
        
        if (answerData.comment) {
            statusHtml += `<div class="teacher-comment" style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
                <strong>Комментарий преподавателя:</strong>
                <p style="margin-top: 5px; margin-bottom: 0;">${unserializeText(answerData.comment)}</p>
            </div>`;
        }
        
        // Добавляем кнопку редактирования (только если задание не проверено)
        if (!isChecked) {
            statusHtml += '<button type="button" id="edit-answer-btn" class="btn btn-secondary" style="margin-top: 10px;">Редактировать ответ</button>';
        }
        
        statusHtml += '</div>';
        statusEl.innerHTML = statusHtml;

        // Скрываем форму, так как ответ уже отправлен
        const form = document.getElementById('task-answer-form');
        form.style.display = 'none';
        cancelEditBtn.style.display = 'none';
        
        // Возвращаем текст кнопки отправки к исходному
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Отправить решение';
        }

        // Обработчик кнопки редактирования
        const editBtn = document.getElementById('edit-answer-btn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                enableEditMode();
            });
        }

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

// Включить режим редактирования
function enableEditMode() {
    const form = document.getElementById('task-answer-form');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const statusEl = document.getElementById('answer-status');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Показываем форму
    form.style.display = 'block';
    
    // Показываем кнопку отмены
    cancelEditBtn.style.display = 'inline-block';
    
    // Меняем текст кнопки отправки
    if (submitBtn) {
        submitBtn.textContent = 'Сохранить изменения';
    }
    
    // Обновляем статус - убираем кнопку редактирования
    if (myAnswer) {
        let statusHtml = '<div class="status-info">';
        
        // Проверяем, есть ли оценка (проверено только если score > 0 или есть комментарий преподавателя)
        const scoreValue = parseInt(myAnswer.score) || 0;
        const hasComment = myAnswer.comment && myAnswer.comment.trim() !== '';
        const isChecked = scoreValue > 0 || hasComment; // Проверено, если оценка > 0 или есть комментарий
        const maxScore = taskData.maxScore || 0;
        
        if (isChecked) {
            const percentage = maxScore > 0 ? Math.round((scoreValue / maxScore) * 100) : 0;
            let scoreClass = 'status-success';
            if (percentage < 50) {
                scoreClass = 'status-danger';
            } else if (percentage < 70) {
                scoreClass = 'status-warning';
            }
            
            statusHtml += `<div class="${scoreClass}" style="font-size: 1.2em; font-weight: bold; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: ${percentage >= 70 ? '#d4edda' : percentage >= 50 ? '#fff3cd' : '#f8d7da'};">
                ✅ Проверено. Ваша оценка: <span style="font-size: 1.3em;">${scoreValue}</span> / ${maxScore} баллов (${percentage}%)
            </div>`;
        } else {
            // Нет оценки и комментария - статус: На проверке (голубой фон)
            statusHtml += `<div class="status-info" style="font-size: 1.1em; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #d1ecf1; border-left: 4px solid #0c5460; color: #0c5460;">
                ⏳ На проверке. Ваш ответ отправлен и ожидает проверки преподавателем.
            </div>`;
        }
        
        if (myAnswer.dateSubmit) {
            statusHtml += `<div class="status-date">Отправлено: ${utils.formatDate(myAnswer.dateSubmit)}</div>`;
        }
        
        if (myAnswer.comment) {
            statusHtml += `<div class="teacher-comment" style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
                <strong>Комментарий преподавателя:</strong>
                <p style="margin-top: 5px; margin-bottom: 0;">${unserializeText(myAnswer.comment)}</p>
            </div>`;
        }
        
        statusHtml += '<div class="edit-mode-notice" style="margin-top: 10px; color: #666; font-style: italic;">Режим редактирования. Измените ответ и нажмите "Сохранить изменения" для сохранения.</div>';
        statusHtml += '</div>';
        statusEl.innerHTML = statusHtml;
    }
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
            
            // Перезагружаем ответ (форма будет скрыта в loadMyAnswer)
            setTimeout(() => {
                loadMyAnswer();
            }, 500);
        }
    } catch (error) {
        statusEl.innerHTML = `<div class="error">Ошибка отправки: ${error.message}</div>`;
        console.error(error);
    }
}

// Функция для обновления имени пользователя в хедере
function updateUserName(user) {
    const userNameEl = document.getElementById('user-name');
    if (userNameEl && user) {
        let fullName = '';
        if (user.firstName && user.lastName) {
            fullName = `${user.firstName} ${user.lastName}`.trim();
        } else if (user.firstName) {
            fullName = user.firstName;
        } else if (user.lastName) {
            fullName = user.lastName;
        } else if (user.name) {
            fullName = user.name;
        }
        
        if (fullName) {
            userNameEl.textContent = fullName;
        }
    }
}

// Функция для преобразования URL в правильный формат для скачивания
function getDownloadUrl(url) {
    if (!url) return '#';
    
    // Нормализуем URL - убираем экранированные слеши и лишние символы
    if (typeof url === 'string') {
        url = url.replace(/\\\//g, '/').trim();
    }
    
    // Базовый URL backend (без порта, http вместо https)
    const backendUrl = 'http://192.168.56.102';
    
    // Если это абсолютный URL с хостом, нормализуем его
    if (url.startsWith('http://') || url.startsWith('https://')) {
        try {
            const urlObj = new URL(url);
            // Если это путь к файлу в upload, возвращаем прямой URL к файлу
            if (urlObj.pathname.startsWith('/upload/')) {
                // Убираем порт, меняем https на http, возвращаем прямой путь к файлу
                return backendUrl + urlObj.pathname;
            }
            // Для других путей тоже нормализуем
            return backendUrl + urlObj.pathname + urlObj.search;
        } catch (e) {
            // Если не удалось распарсить, пробуем извлечь путь вручную
            const pathMatch = url.match(/\/upload\/[^?\s]*/);
            if (pathMatch) {
                return backendUrl + pathMatch[0];
            }
        }
    }
    
    // Если это относительный путь к файлу в upload, возвращаем прямой URL
    if (url.startsWith('/upload/')) {
        return backendUrl + url;
    }
    
    // Если это неправильный формат /download/upload/..., извлекаем путь
    if (url.startsWith('/download/upload/')) {
        const pathAfterDownload = url.substring('/download'.length);
        return backendUrl + pathAfterDownload;
    }
    
    // Если это уже правильный формат через наш endpoint, преобразуем в прямой URL
    if (url.includes('/download/local/api/download.php') || url.includes('/local/api/download.php')) {
        // Извлекаем path из query параметров
        try {
            const urlObj = new URL(url.startsWith('http') ? url : 'http://dummy' + url);
            const pathParam = urlObj.searchParams.get('path');
            if (pathParam && pathParam.startsWith('/upload/')) {
                return backendUrl + pathParam;
            }
        } catch (e) {
            // Если не удалось распарсить, пробуем извлечь path вручную
            const pathMatch = url.match(/path=([^&]*)/);
            if (pathMatch && pathMatch[1]) {
                const decodedPath = decodeURIComponent(pathMatch[1]);
                if (decodedPath.startsWith('/upload/')) {
                    return backendUrl + decodedPath;
                }
            }
        }
    }
    
    // Для других относительных путей
    if (!url.startsWith('/download') && !url.startsWith('http')) {
        if (url.startsWith('/upload/')) {
            return backendUrl + url;
        }
        return backendUrl + (url.startsWith('/') ? url : '/' + url);
    }
    
    // Если уже начинается с /download, убираем /download и добавляем backend URL
    if (url.startsWith('/download')) {
        const pathWithoutDownload = url.substring('/download'.length);
        return backendUrl + pathWithoutDownload;
    }
    
    return url;
}

// Старая функция downloadFile оставлена для совместимости, но больше не используется
async function downloadFile(url) {
    try {
        console.log('Downloading file from URL (original):', url);
        
        // Нормализуем URL - убираем экранированные слеши и лишние символы
        if (typeof url === 'string') {
            // Заменяем экранированные слеши на обычные
            url = url.replace(/\\\//g, '/');
            // Убираем лишние пробелы
            url = url.trim();
        }
        
        console.log('Downloading file from URL (normalized):', url);
        
        // URL может быть в разных форматах:
        // 1. /download/local/api/download.php?type=task&fileId=... (правильный формат)
        // 2. http://192.168.56.102/upload/... (прямой путь к файлу Bitrix)
        // 3. https://192.168.56.102:80/upload/... (прямой путь с портом)
        // 4. /upload/... (относительный путь к файлу Bitrix)
        // 5. /download/upload/... (неправильный формат, который нужно исправить)
        
        let downloadUrl = url;
        
        // Если это уже правильный формат через наш endpoint, используем как есть
        if (url.includes('/download/local/api/download.php')) {
            // Уже правильный формат
            downloadUrl = url;
        } else if (url.startsWith('/download/upload/')) {
            // Неправильный формат: /download/upload/... -> нужно преобразовать в правильный
            // Извлекаем путь после /download/
            const pathAfterDownload = url.substring('/download'.length);
            downloadUrl = '/download/local/api/download.php?path=' + encodeURIComponent(pathAfterDownload);
        } else if (url.startsWith('http://') || url.startsWith('https://')) {
            // Абсолютный URL - извлекаем путь
            try {
                const urlObj = new URL(url);
                // Если это путь к файлу в upload, используем специальный endpoint
                if (urlObj.pathname.startsWith('/upload/')) {
                    // Извлекаем путь и преобразуем в формат для скачивания
                    downloadUrl = '/download/local/api/download.php?path=' + encodeURIComponent(urlObj.pathname);
                } else if (urlObj.pathname.startsWith('/local/api/download.php')) {
                    // Если это уже наш endpoint, но с абсолютным URL, извлекаем query параметры
                    downloadUrl = '/download' + urlObj.pathname + urlObj.search;
                } else {
                    downloadUrl = '/download' + urlObj.pathname + urlObj.search;
                }
            } catch (e) {
                console.error('Error parsing URL:', e, url);
                // Если не удалось распарсить, пробуем извлечь путь вручную
                if (url.includes('/upload/')) {
                    const pathMatch = url.match(/\/upload\/[^?\s]*/);
                    if (pathMatch) {
                        downloadUrl = '/download/local/api/download.php?path=' + encodeURIComponent(pathMatch[0]);
                    } else {
                        downloadUrl = url;
                    }
                } else {
                    downloadUrl = url;
                }
            }
        } else if (url.startsWith('/upload/')) {
            // Относительный путь к файлу в upload - это прямой путь к файлу Bitrix
            // Используем специальный endpoint для скачивания по пути
            downloadUrl = '/download/local/api/download.php?path=' + encodeURIComponent(url);
        } else if (!url.startsWith('/download')) {
            // Другие относительные пути
            downloadUrl = '/download' + (url.startsWith('/') ? url : '/' + url);
        }
        
        console.log('Final download URL:', downloadUrl);
        
        const response = await fetch(downloadUrl, {
            method: 'GET',
            credentials: 'include' // Важно для передачи cookies
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`Ошибка скачивания: ${response.status} ${response.statusText}`);
        }
        
        // Получаем имя файла из заголовка или URL
        const contentDisposition = response.headers.get('Content-Disposition');
        let fileName = 'file';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
            if (fileNameMatch && fileNameMatch[1]) {
                fileName = fileNameMatch[1].replace(/['"]/g, '');
                // Декодируем URL-encoded имя файла
                try {
                    fileName = decodeURIComponent(fileName);
                } catch (e) {
                    // Если не удалось декодировать, используем как есть
                }
            }
        } else {
            // Пытаемся извлечь имя файла из URL
            const urlParts = url.split('/');
            fileName = urlParts[urlParts.length - 1].split('?')[0] || 'file';
        }
        
        console.log('Downloading file as:', fileName);
        
        // Получаем blob
        const blob = await response.blob();
        
        // Создаем ссылку для скачивания
        const blobUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
        
        console.log('File download initiated');
    } catch (error) {
        console.error('Ошибка скачивания файла:', error);
        alert('Ошибка скачивания файла: ' + error.message);
    }
}

