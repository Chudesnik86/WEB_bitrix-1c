// Страница курса
let currentTab = 'content';
let courseData = null;

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
    
    for (const lecture of courseData.lectures) {
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

        const isAvailable = lecture.isAvailable !== false;
        const availabilityClass = isAvailable ? '' : 'disabled';
        
        html += `
            <div class="lecture-item ${availabilityClass}">
                <div class="lecture-header">
                    <h3>${lecture.name}</h3>
                    ${!isAvailable ? '<span class="badge">Недоступно</span>' : ''}
                </div>
                ${lecture.content ? `<div class="lecture-content">${lecture.content}</div>` : ''}
                ${lecture.file ? `
                    <div class="lecture-file">
                        <a href="${lecture.file}" download class="file-link">
                            📎 Скачать материал
                        </a>
                    </div>
                ` : ''}
                ${tasks.length > 0 ? `
                    <div class="tasks-list">
                        <h4>Задания:</h4>
                        ${tasks.map(task => `
                            <div class="task-item">
                                <a href="/student/task/?id=${task.id}">${task.name}</a>
                                ${task.deadline ? `<span class="task-deadline">До: ${utils.formatDate(task.deadline)}</span>` : ''}
                                ${!task.isActive ? '<span class="badge badge-warning">Просрочено</span>' : ''}
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
                            <h4><a href="/student/task/?id=${answer.taskId}">${answer.taskName}</a></h4>
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
                                <p>${answer.comment}</p>
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

