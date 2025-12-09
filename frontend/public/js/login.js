// Страница авторизации
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('login-form');
    const loginBtn = document.getElementById('login-btn');
    const errorEl = document.getElementById('error-message');
    const successEl = document.getElementById('success-message');

    // Проверяем, не авторизован ли уже пользователь
    try {
        const user = await api.getUser();
        if (user && !user.error) {
            // Пользователь уже авторизован, редирект на страницу курсов
            window.location.href = '/courses';
            return;
        }
    } catch (error) {
        // Пользователь не авторизован, продолжаем
    }

    // Обработчик отправки формы
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('password').value;

        // Скрываем предыдущие сообщения
        errorEl.style.display = 'none';
        successEl.style.display = 'none';

        // Валидация
        if (!login || !password) {
            errorEl.textContent = 'Заполните все поля';
            errorEl.style.display = 'block';
            return;
        }

        // Блокируем кнопку
        loginBtn.disabled = true;
        loginBtn.textContent = 'Вход...';

        try {
            const result = await api.login(login, password);
            
            if (result.error) {
                throw new Error(result.error);
            }

            // API возвращает { success: true, message: "Successfully authenticated", user: {...} } или { success: false, error: "..." }
            console.log('Login result:', result); // Отладка всего результата
            
            if (result.success === true || result.message === 'Successfully authenticated') {
                // Успешная авторизация
                const user = result.user; // Пользователь возвращается сразу из login
                console.log('User data from login:', user); // Отладка
                console.log('Full result:', result); // Отладка
                console.log('Cookies:', document.cookie); // Отладка cookies
                
                if (user && user.role === 'student') {
                    successEl.textContent = 'Успешный вход! Перенаправление...';
                    successEl.style.display = 'block';
                    
                    // Даем время cookies установиться, затем проверяем и делаем редирект
                    setTimeout(async () => {
                        console.log('Checking cookies after login:', document.cookie);
                        
                        // Проверяем, что cookies установились, делая тестовый запрос
                        try {
                            const verifyUser = await api.getUser();
                            console.log('User verification after login:', verifyUser);
                            
                            if (verifyUser && !verifyUser.error && verifyUser.role === 'student') {
                                console.log('Cookies verified, redirecting to courses...');
                                window.location.href = '/courses';
                            } else {
                                console.warn('Cookies not verified, but redirecting anyway...');
                                // Все равно делаем редирект, возможно cookies установятся на следующей странице
                                window.location.href = '/courses';
                            }
                        } catch (error) {
                            console.error('Error verifying user after login:', error);
                            // Все равно делаем редирект
                            window.location.href = '/courses';
                        }
                    }, 1000); // Увеличиваем задержку до 1 секунды
                } else {
                    // Показываем ошибку
                    let errorMsg = 'Доступ разрешен только для студентов.\n\n';
                    
                    if (user) {
                        errorMsg += 'Информация для отладки:\n';
                        if (user.groups && user.groups.length > 0) {
                            errorMsg += `ID групп: ${user.groups.join(', ')}\n`;
                        }
                        if (user.role) {
                            errorMsg += `Определенная роль: "${user.role}"\n`;
                        } else {
                            errorMsg += 'Роль не определена.\n';
                            errorMsg += `\nПроверьте, что пользователь состоит в группе с ID 7 (константа GROUP_STUDENT).\n`;
                            errorMsg += `Текущие группы пользователя: ${user.groups ? user.groups.join(', ') : 'не определены'}`;
                        }
                    } else {
                        // Если user не вернулся, пробуем получить через отдельный запрос
                        errorMsg += 'Пользователь не вернулся из login. Пробуем получить через getUser...\n';
                        setTimeout(async () => {
                            try {
                                const userData = await api.getUser();
                                console.log('User data from getUser:', userData);
                                
                                if (userData && !userData.error && userData.role === 'student') {
                                    window.location.href = '/courses';
                                } else if (userData && userData.error) {
                                    errorEl.textContent = `Ошибка: ${userData.error}. Сессия не сохранилась между запросами. Возможно проблема с cookies между доменами.`;
                                    errorEl.style.display = 'block';
                                    successEl.style.display = 'none';
                                    loginBtn.disabled = false;
                                    loginBtn.textContent = 'Войти';
                                } else {
                                    errorEl.innerHTML = errorMsg.replace(/\n/g, '<br>');
                                    errorEl.style.display = 'block';
                                    successEl.style.display = 'none';
                                    loginBtn.disabled = false;
                                    loginBtn.textContent = 'Войти';
                                }
                            } catch (error) {
                                console.error('Error getting user:', error);
                                errorEl.textContent = `Ошибка получения данных пользователя: ${error.message}`;
                                errorEl.style.display = 'block';
                                successEl.style.display = 'none';
                                loginBtn.disabled = false;
                                loginBtn.textContent = 'Войти';
                            }
                        }, 300);
                        return; // Выходим, чтобы не показывать ошибку дважды
                    }
                    
                    errorEl.innerHTML = errorMsg.replace(/\n/g, '<br>');
                    errorEl.style.display = 'block';
                    successEl.style.display = 'none';
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Войти';
                }
            } else {
                // Если есть error, используем его, иначе message
                const errorMsg = result.error || result.message || 'Ошибка авторизации';
                throw new Error(errorMsg);
            }
        } catch (error) {
            errorEl.textContent = error.message || 'Ошибка входа. Проверьте логин и пароль.';
            errorEl.style.display = 'block';
            loginBtn.disabled = false;
            loginBtn.textContent = 'Войти';
        }
    });
});

