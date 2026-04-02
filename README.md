# Staler Balance — Учёт личных финансов

Веб-приложение для отслеживания доходов и расходов. Авторизация, дашборд с аналитикой, REST API для управления операциями. Написано на чистом PHP без фреймворков.

<!-- Раскомментируй и вставь ссылку на скриншот: -->
<!-- ![Дашборд](screenshots/dashboard.png) -->

## Возможности

- Авторизация и управление сессиями
- Дашборд с обзором баланса
- Добавление доходов и расходов
- REST API для работы с данными (папка `api/`)
- История операций

## Стек

- **Backend:** PHP (без фреймворков)
- **Frontend:** HTML, CSS, JavaScript
- **База данных:** MySQL

## Установка и запуск

```bash
# Клонировать репозиторий
git clone https://github.com/zxy11636/staler-balance.git
cd staler-balance

# Импортировать базу данных
mysql -u root -p < moneyflow.sql

# Настроить подключение к БД в includes/

# Запустить через встроенный PHP-сервер
php -S localhost:8080
```

Приложение будет доступно по адресу `http://localhost:8080`.

## Структура проекта

```
staler-balance/
├── api/            # REST API endpoints
├── assets/         # CSS, JavaScript, изображения
├── includes/       # Подключение к БД, общие функции
├── pages/          # Страницы приложения
├── dashboard.php   # Главная панель
├── index.php       # Точка входа / авторизация
├── logout.php      # Выход из системы
└── moneyflow.sql   # Дамп базы данных
```

## Автор

**Матвей Семахин** — [Telegram](https://t.me/tutaNETU) · [Email](mailto:prodzxy@mail.ru)
