// Страница курса
let currentTab = 'content';
let courseData = null;

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
    const loadingEl = document.getElementById('course-loading');
    const errorEl = document.getElementById('course-error');
    const contentEl = document.getElementById('course-content');
    const progressEl = document.getElementById('course-progress');
    const titleEl = document.getElementById('course-title');

    // Обработчики вкладок
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            switchTab(tab);
        });
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

        // Загружаем информацию о курсе
        const courseInfo = await api.getCourse(courseId);
        
        if (courseInfo.error) {
            throw new Error(courseInfo.error);
        }

        // Загружаем лекции курса
        const lecturesData = await api.getLectures(courseId);
        
        if (lecturesData.error) {
            throw new Error(lecturesData.error);
        }

        courseData = {
            id: courseId,
            name: courseInfo.name,
            lectures: lecturesData.items || []
        };

        loadingEl.style.display = 'none';
        titleEl.textContent = courseData.name || `Курс #${courseData.id}`;

        // Загружаем задания для каждой лекции
        await loadCourseContent();
        
    } catch (error) {
        loadingEl.style.display = 'none';
        errorEl.textContent = error.message || 'Ошибка загрузки курса';
        errorEl.style.display = 'block';
        console.error(error);
    }
});

async function loadCourseContent() {
    const contentEl = document.getElementById('course-content');
    
    if (!courseData.lectures || courseData.lectures.length === 0) {
        contentEl.innerHTML = '<p class="empty-message">В курсе пока нет лекций.</p>';
        return;
    }

    let html = '<div class="lectures-list">';
    
    // Флаг для отслеживания доступности лекций
    let previousLecturesCompleted = true;
    
    for (let i = 0; i < courseData.lectures.length; i++) {
        const lecture = courseData.lectures[i];
        
        // Загружаем задания для лекции
        let tasks = [];
        try {
            const tasksData = await api.getTasks(lecture.id);
            if (tasksData.items) {
                tasks = tasksData.items;
            }
        } catch (error) {
            console.error('Error loading tasks for lecture', lecture.id, error);
        }

        // Первая лекция (индекс 0) всегда доступна
        let isAvailable = false;
        if (i === 0) {
            // Первая лекция всегда доступна
            isAvailable = lecture.isAvailable !== false;
        } else {
            // Для остальных лекций проверяем доступность на основе предыдущих
            isAvailable = previousLecturesCompleted && (lecture.isAvailable !== false);
        }
        
        // Проверяем выполнение всех заданий текущей лекции
        let allTasksCompleted = true;
        let completedCount = 0;
        
        if (tasks.length > 0) {
            for (const task of tasks) {
                try {
                    const answerData = await api.getMyAnswer(task.id);
                    if (answerData && !answerData.error) {
                        completedCount++;
                    } else {
                        allTasksCompleted = false;
                    }
                } catch (error) {
                    allTasksCompleted = false;
                }
            }
        } else {
            // Лекция без заданий считается завершенной
            allTasksCompleted = true;
        }
        
        // Обновляем доступность на основе выполнения заданий
        if (i === 0) {
            // Первая лекция всегда доступна
            isAvailable = lecture.isAvailable !== false;
            // Если все задания выполнены, следующая лекция будет доступна
            previousLecturesCompleted = allTasksCompleted;
        } else {
            // Для остальных лекций: доступна только если предыдущие завершены
            if (previousLecturesCompleted) {
                isAvailable = lecture.isAvailable !== false;
                // Если все задания текущей лекции выполнены, следующая будет доступна
                previousLecturesCompleted = allTasksCompleted;
            } else {
                // Предыдущие лекции не завершены - текущая недоступна
                isAvailable = false;
                previousLecturesCompleted = false;
            }
        }
        
        const availabilityClass = isAvailable ? '' : 'disabled';
        
        html += `
            <div class="lecture-item ${availabilityClass}">
                <div class="lecture-header">
                    <h3>${lecture.name}</h3>
                    ${!isAvailable ? '<span class="badge badge-warning">🔒 Недоступно</span>' : ''}
                    ${isAvailable && tasks.length > 0 ? `<span class="badge badge-info">Заданий: ${tasks.length}</span>` : ''}
                </div>
                ${!isAvailable && i > 0 ? `
                    <div class="lecture-locked-message" style="padding: 15px; background-color: #f8f9fa; border-left: 4px solid #ffc107; margin: 10px 0; border-radius: 4px;">
                        <strong>⚠️ Лекция недоступна</strong>
                        <p style="margin: 5px 0 0 0; color: #666;">
                            Для доступа к этой лекции необходимо выполнить все задания предыдущих лекций.
                        </p>
                    </div>
                ` : ''}
                ${lecture.content && isAvailable ? `<div class="lecture-content">${unserializeText(lecture.content)}</div>` : ''}
                ${lecture.file && isAvailable ? `
                    <div class="lecture-file">
                        <a href="${getDownloadUrl(lecture.file)}" class="file-link" download>
                            📎 Скачать материал
                        </a>
                    </div>
                ` : ''}
                ${tasks.length > 0 && isAvailable ? `
                    <div class="tasks-list">
                        <h4>Задания:</h4>
                        ${tasks.map(task => `
                            <div class="task-item">
                                <a href="/task?id=${task.id}" class="task-link">${task.name}</a>
                                ${task.deadline ? `<span class="task-deadline">До: ${utils.formatDate(task.deadline)}</span>` : ''}
                                ${!task.isActive ? '<span class="badge badge-warning">Просрочено</span>' : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                ${tasks.length > 0 && !isAvailable ? `
                    <div class="tasks-list" style="opacity: 0.5;">
                        <h4>Задания (недоступны):</h4>
                        ${tasks.map(task => `
                            <div class="task-item" style="pointer-events: none;">
                                <span style="color: #999; text-decoration: line-through; cursor: not-allowed;">${task.name}</span>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    html += '</div>';
    contentEl.innerHTML = html;
}

async function loadCourseProgress() {
    const progressEl = document.getElementById('course-progress');
    
    try {
        const progressData = await api.getCourseProgress(courseData.id);
        
        if (progressData.error) {
            throw new Error(progressData.error);
        }

        let html = `
            <div class="progress-summary">
                <h3>Прогресс по курсу</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progressData.progressPercent}%"></div>
                    </div>
                    <div class="progress-text">
                        Выполнено: ${progressData.completedTasks} из ${progressData.totalTasks} заданий (${progressData.progressPercent}%)
                    </div>
                </div>
            </div>
        `;

        if (progressData.answers && progressData.answers.length > 0) {
            html += '<div class="answers-list"><h3>Ваши ответы и оценки</h3>';
            
            progressData.answers.forEach(answer => {
                const statusClass = answer.status === 'Проверено' ? 'success' : 
                                   answer.status === 'На проверке' ? 'warning' : 'danger';
                
                html += `
                    <div class="answer-item">
                        <div class="answer-header">
                            <h4><a href="/task?id=${answer.taskId}">${answer.taskName}</a></h4>
                            <span class="badge badge-${statusClass}">${answer.status}</span>
                        </div>
                        <div class="answer-meta">
                            <span>Лекция: ${answer.lectureName}</span>
                            ${answer.dateSubmit ? `<span>Отправлено: ${utils.formatDate(answer.dateSubmit)}</span>` : ''}
                        </div>
                        ${answer.score > 0 ? `
                            <div class="answer-score">
                                Оценка: ${answer.score} / ${answer.maxScore}
                            </div>
                        ` : ''}
                        ${answer.comment ? `
                            <div class="answer-comment">
                                <strong>Комментарий преподавателя:</strong>
                                <p>${unserializeText(answer.comment)}</p>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
        } else {
            html += '<p class="empty-message">У вас пока нет отправленных ответов.</p>';
        }

        progressEl.innerHTML = html;
        
    } catch (error) {
        progressEl.innerHTML = `<div class="error">Ошибка загрузки прогресса: ${error.message}</div>`;
        console.error(error);
    }
}

function switchTab(tab) {
    currentTab = tab;
    
    // Обновляем кнопки вкладок
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });
    
    // Показываем/скрываем контент
    document.getElementById('tab-content').style.display = tab === 'content' ? 'block' : 'none';
    document.getElementById('tab-progress').style.display = tab === 'progress' ? 'block' : 'none';
    
    // Загружаем прогресс при переключении на вкладку
    if (tab === 'progress') {
        loadCourseProgress();
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

