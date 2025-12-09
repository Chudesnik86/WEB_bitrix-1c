// API клиент для работы с backend
class StudentAPI {
    constructor() {
        // URL backend через прокси (чтобы cookies работали на одном домене)
        this.baseUrl = '/api/local/api/index.php';
    }

    async request(class_, method, params = {}) {
        const formData = new FormData();
        formData.append('CLASS', class_);
        formData.append('METHOD', method);

        // Добавляем параметры
        for (const key in params) {
            if (params[key] !== null && params[key] !== undefined) {
                if (params[key] instanceof File) {
                    formData.append(key, params[key]);
                } else {
                    formData.append(key, params[key]);
                }
            }
        }

        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include' // Важно для передачи cookies
            });

            const data = await response.json();
            
            if (data.status === 'error') {
                throw new Error(data.errorMessage || 'Ошибка API');
            }

            return data.result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Auth API
    async login(login, password) {
        return this.request('Auth', 'login', { login, password });
    }

    async logout() {
        return this.request('Auth', 'logout');
    }

    // User API
    async getUser() {
        return this.request('User', 'get');
    }

    // Courses API
    async getCourses(page = 1, limit = 10) {
        return this.request('Courses', 'getList', { page, limit });
    }

    async getCourse(courseId) {
        return this.request('Courses', 'getById', { id: courseId });
    }

    async getCourseProgress(courseId) {
        return this.request('Courses', 'getProgress', { courseId });
    }

    // Lectures API
    async getLectures(courseId) {
        return this.request('Lectures', 'getList', { courseId });
    }

    async getLecture(lectureId) {
        return this.request('Lectures', 'getById', { id: lectureId });
    }

    // Tasks API
    async getTasks(lectureId) {
        return this.request('Tasks', 'getList', { lectureId });
    }

    async getTask(taskId) {
        return this.request('Tasks', 'getById', { id: taskId });
    }

    // TaskAnswers API
    async getTaskAnswers(taskId) {
        return this.request('TaskAnswers', 'getList', { taskId });
    }

    async getMyAnswer(taskId) {
        return this.request('TaskAnswers', 'getMyAnswer', { taskId });
    }

    async submitAnswer(taskId, answerId, text, link, file) {
        const params = { taskId };
        
        if (answerId) {
            params.answerId = answerId;
        }
        
        if (text) {
            params.text = text;
        }
        
        if (link) {
            params.link = link;
        }

        const formData = new FormData();
        formData.append('CLASS', 'TaskAnswers');
        formData.append('METHOD', 'submit');
        
        for (const key in params) {
            formData.append(key, params[key]);
        }
        
        if (file) {
            formData.append('file', file);
        }

        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include' // Важно для передачи cookies
            });

            const data = await response.json();
            
            if (data.status === 'error') {
                throw new Error(data.errorMessage || 'Ошибка API');
            }

            return data.result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
}

// Глобальный экземпляр API
const api = new StudentAPI();

// Утилиты для форматирования
const utils = {
    formatDate(dateString) {
        if (!dateString) return '';
        
        let date;
        
        // Проверяем формат даты - если это DD.MM.YYYY или DD.MM.YYYY HH:MM:SS
        // Bitrix может отправлять дату в формате "10.12.2025" или "2025-12-10 00:00:00"
        if (typeof dateString === 'string') {
            // Если дата в формате DD.MM.YYYY или DD.MM.YYYY HH:MM:SS
            const ddmmyyyyMatch = dateString.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):(\d{1,2}):(\d{1,2}))?/);
            if (ddmmyyyyMatch) {
                const day = parseInt(ddmmyyyyMatch[1], 10);
                const month = parseInt(ddmmyyyyMatch[2], 10) - 1; // месяцы в JS начинаются с 0
                const year = parseInt(ddmmyyyyMatch[3], 10);
                const hours = ddmmyyyyMatch[4] ? parseInt(ddmmyyyyMatch[4], 10) : 0;
                const minutes = ddmmyyyyMatch[5] ? parseInt(ddmmyyyyMatch[5], 10) : 0;
                const seconds = ddmmyyyyMatch[6] ? parseInt(ddmmyyyyMatch[6], 10) : 0;
                date = new Date(year, month, day, hours, minutes, seconds);
            } else {
                // Пробуем стандартный парсинг для других форматов
                date = new Date(dateString);
            }
        } else {
            date = new Date(dateString);
        }
        
        // Проверяем, что дата валидна
        if (isNaN(date.getTime())) {
            return dateString; // Возвращаем исходную строку, если не удалось распарсить
        }
        
        // Форматируем вручную для контроля порядка: день месяц год
        const day = date.getDate();
        const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 
                       'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        
        // Если есть время, добавляем его
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        
        // Проверяем, есть ли время (не 00:00)
        if (hours !== '00' || minutes !== '00') {
            return `${day} ${month} ${year} г. в ${hours}:${minutes}`;
        } else {
            return `${day} ${month} ${year} г.`;
        }
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }
};

