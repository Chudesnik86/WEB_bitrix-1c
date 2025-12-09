// API клиент для работы с backend
class StudentAPI {
    constructor() {
        // URL backend сервера
        this.baseUrl = 'http://192.168.56.102/local/api/index.php';
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
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
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

