const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = 3000;

// Настройка CORS
app.use(cors({
    origin: 'http://localhost:3000',
    credentials: true
}));

// Прокси для API запросов (чтобы cookies работали на одном домене)
app.use('/api', createProxyMiddleware({
    target: 'http://192.168.56.102',
    changeOrigin: true,
    cookieDomainRewrite: 'localhost',
    onProxyReq: (proxyReq, req, res) => {
        // Передаем cookies от клиента к backend
        if (req.headers.cookie) {
            console.log('Forwarding cookies to backend:', req.headers.cookie);
            proxyReq.setHeader('Cookie', req.headers.cookie);
        } else {
            console.log('No cookies in request to forward');
        }
    },
    onProxyRes: (proxyRes, req, res) => {
        // Сохраняем cookies от backend, изменяя домен на localhost
        const cookies = proxyRes.headers['set-cookie'];
        if (cookies) {
            console.log('Original cookies from backend:', cookies);
            const modifiedCookies = cookies.map(cookie => {
                // Заменяем домен на localhost
                // Для same-origin запросов через прокси используем SameSite=Lax (не требует Secure)
                let modifiedCookie = cookie
                    .replace(/Domain=[^;]+/gi, 'Domain=localhost')
                    .replace(/SameSite=None/gi, 'SameSite=Lax')
                    .replace(/SameSite=Strict/gi, 'SameSite=Lax');
                
                // Убираем Secure для localhost (не требуется для SameSite=Lax)
                modifiedCookie = modifiedCookie.replace(/;\s*Secure/gi, '');
                
                // Убеждаемся, что Path установлен
                if (!modifiedCookie.includes('Path=')) {
                    modifiedCookie += '; Path=/';
                }
                
                console.log('Modified cookie:', modifiedCookie);
                return modifiedCookie;
            });
            res.setHeader('Set-Cookie', modifiedCookies);
            console.log('Set-Cookie header set:', modifiedCookies);
        }
    },
    logLevel: 'silent' // Изменено на silent, так как мы используем console.log
}));

// Прокси для скачивания файлов
app.use('/download', createProxyMiddleware({
    target: 'http://192.168.56.102',
    changeOrigin: true,
    pathRewrite: {
        '^/download': '' // Убираем /download из пути, так как на backend путь начинается с /
    },
    cookieDomainRewrite: 'localhost',
    onProxyReq: (proxyReq, req, res) => {
        // Передаем cookies от клиента к backend
        if (req.headers.cookie) {
            proxyReq.setHeader('Cookie', req.headers.cookie);
        }
        console.log('Proxying download request to:', proxyReq.path);
    },
    onProxyRes: (proxyRes, req, res) => {
        // Для скачивания файлов передаем заголовки как есть
        // Не изменяем Content-Type и Content-Disposition
        // Убеждаемся, что CORS заголовки установлены
        if (!proxyRes.headers['access-control-allow-origin']) {
            res.setHeader('Access-Control-Allow-Origin', req.headers.origin || 'http://localhost:3000');
            res.setHeader('Access-Control-Allow-Credentials', 'true');
        }
    },
    logLevel: 'debug' // Временно включаем для отладки
}));

// Статические файлы
app.use(express.static(path.join(__dirname, 'public')));

// Роутинг для SPA
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/login', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.get('/courses', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'courses.html'));
});

app.get('/course', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'course.html'));
});

app.get('/task', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'task.html'));
});

app.listen(PORT, () => {
    console.log(`Frontend сервер запущен на http://localhost:${PORT}`);
    console.log(`Backend API: http://192.168.56.102/local/api/index.php`);
});

