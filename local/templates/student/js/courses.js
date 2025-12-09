// Страница списка курсов
document.addEventListener('DOMContentLoaded', async () => {
    const loadingEl = document.getElementById('courses-loading');
    const errorEl = document.getElementById('courses-error');
    const listEl = document.getElementById('courses-list');

    try {
        // Проверяем роль пользователя
        const user = await api.getUser();
        if (user.error) {
            throw new Error('Необходима авторизация');
        }

        if (user.role !== 'student') {
            errorEl.textContent = 'Доступ запрещен. Эта страница только для студентов.';
            errorEl.style.display = 'block';
            loadingEl.style.display = 'none';
            return;
        }

        // Загружаем курсы
        const data = await api.getCourses(1, 50);
        
        if (data.error) {
            throw new Error(data.error);
        }

        loadingEl.style.display = 'none';

        if (!data.items || data.items.length === 0) {
            listEl.innerHTML = '<p class="empty-message">У вас пока нет доступных курсов.</p>';
            return;
        }

        // Отображаем курсы
        listEl.innerHTML = data.items.map(course => `
            <div class="course-card">
                <h2><a href="/student/course/?id=${course.id}">${course.name}</a></h2>
                ${course.preview ? `<p class="course-preview">${course.preview}</p>` : ''}
                <div class="course-meta">
                    ${course.dateStart ? `<span>Начало: ${utils.formatDate(course.dateStart)}</span>` : ''}
                    ${course.dateEnd ? `<span>Окончание: ${utils.formatDate(course.dateEnd)}</span>` : ''}
                </div>
                <a href="/student/course/?id=${course.id}" class="btn btn-primary">Открыть курс</a>
            </div>
        `).join('');

    } catch (error) {
        loadingEl.style.display = 'none';
        errorEl.textContent = error.message || 'Ошибка загрузки курсов';
        errorEl.style.display = 'block';
        console.error(error);
    }
});


