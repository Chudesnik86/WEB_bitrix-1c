// Страница списка курсов
document.addEventListener('DOMContentLoaded', async () => {
    const loadingEl = document.getElementById('courses-loading');
    const errorEl = document.getElementById('courses-error');
    const listEl = document.getElementById('courses-list');

    try {
        // Проверяем роль пользователя
        console.log('Checking user authentication...');
        console.log('Cookies:', document.cookie);
        
        let user = await api.getUser();
        console.log('User data from API (first attempt):', user);
        
        // Если пользователь не авторизован, пробуем еще раз через небольшую задержку
        // (на случай, если cookies еще не установились после редиректа)
        if (!user || user.error) {
            console.log('User not authenticated on first attempt, retrying after 500ms...');
            await new Promise(resolve => setTimeout(resolve, 500));
            user = await api.getUser();
            console.log('User data from API (retry):', user);
        }
        
        if (!user || user.error) {
            console.error('User not authenticated after retry:', user);
            // Пользователь не авторизован, редирект на страницу входа
            window.location.href = '/login';
            return;
        }

        if (user.role !== 'student') {
            console.warn('User role is not student:', user.role);
            errorEl.textContent = 'Доступ запрещен. Эта страница только для студентов.';
            errorEl.style.display = 'block';
            loadingEl.style.display = 'none';
            return;
        }
        
        // Обновляем имя пользователя в хедере
        updateUserName(user);
        
        console.log('User authenticated as student, loading courses...');

        // Загружаем курсы
        const data = await api.getCourses(1, 50);
        
        console.log('Courses data from API:', data);
        
        if (data.error) {
            throw new Error(data.error);
        }

        loadingEl.style.display = 'none';

        if (!data.items || data.items.length === 0) {
            console.warn('No courses found. Debug info:', data.debug);
            let debugHtml = '<p class="empty-message">У вас пока нет доступных курсов.</p>';
            if (data.debug) {
                debugHtml += `<p style="color: #999; font-size: 12px; margin-top: 10px;">
                    Отладка: найдено ${data.debug.totalBeforeFilter} курсов до фильтрации, 
                    ${data.debug.totalAfterFilter} после фильтрации
                </p>`;
                
                if (data.debug.courses && data.debug.courses.length > 0) {
                    debugHtml += '<div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px; font-size: 12px;">';
                    debugHtml += '<strong>Детали фильтрации:</strong><br>';
                    data.debug.courses.forEach(course => {
                        debugHtml += `<br><strong>Курс "${course.name}" (ID: ${course.id}):</strong><br>`;
                        debugHtml += `- IS_PUBLIC_VALUE (ID): "${course.IS_PUBLIC_VALUE}"<br>`;
                        if (course.IS_PUBLIC_ENUM_VALUE) {
                            debugHtml += `- IS_PUBLIC_ENUM_VALUE: "${course.IS_PUBLIC_ENUM_VALUE}"<br>`;
                        }
                        debugHtml += `- isPublic: ${course.isPublic}<br>`;
                        debugHtml += `- isActiveNow: ${course.isActiveNow}<br>`;
                        debugHtml += `- dateStart: ${course.dateStart}<br>`;
                        debugHtml += `- dateEnd: ${course.dateEnd}<br>`;
                        if (course.filteredOut) {
                            debugHtml += `<span style="color: red;">- Отфильтрован: ${course.filterReason}</span><br>`;
                        } else {
                            debugHtml += `<span style="color: green;">- Прошел фильтрацию</span><br>`;
                        }
                    });
                    debugHtml += '</div>';
                }
            }
            listEl.innerHTML = debugHtml;
            return;
        }

        // Отображаем курсы
        listEl.innerHTML = data.items.map(course => `
            <div class="course-card">
                <h2><a href="/course?id=${course.id}">${course.name}</a></h2>
                ${course.preview ? `<p class="course-preview">${course.preview}</p>` : ''}
                <div class="course-meta">
                    ${course.dateStart ? `<span>Начало: ${utils.formatDate(course.dateStart)}</span>` : ''}
                    ${course.dateEnd ? `<span>Окончание: ${utils.formatDate(course.dateEnd)}</span>` : ''}
                </div>
                <a href="/course?id=${course.id}" class="btn btn-primary">Открыть курс</a>
            </div>
        `).join('');

    } catch (error) {
        loadingEl.style.display = 'none';
        errorEl.textContent = error.message || 'Ошибка загрузки курсов';
        errorEl.style.display = 'block';
        console.error(error);
    }
});

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

