# Game Analytics System

Система аналитики для отслеживания игровых сессий и визуализации ключевых метрик.

## Установка и запуск

1. Клонируйте репозиторий:
```bash
git clone git@github.com:Aresenka/gameGears.git
cd gameGears
```

2. Создайте файл `.env` из примера:
```bash
cp .env.example .env
```

3. Запустите контейнеры:
```bash
docker-compose up -d
```

База данных будет автоматически инициализирована при первом запуске (скрипт `init.sql`).

4. Сгенерируйте тестовые данные:
```bash
docker-compose exec web php seeder.php
```

5. Откройте в браузере:
```
http://localhost:8080
```

## Структура проекта

- `api.php` - API для получения данных о сессиях
- `index.html` - Фронтенд с визуализацией данных
- `init.sql` - Инициализация базы данных
- `seeder.php` - Генератор тестовых данных
- `docker-compose.yml` - Конфигурация Docker
- `.env` - Переменные окружения
