<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StalerBalance - Учет финансов</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>

    <div class="container">
        <!-- Хедер -->
        <header class="header">
            <div class="logo">
                <i class="fas fa-money-bill-wave"></i>
                <h1>StalerBalance</h1>
            </div>
            <nav class="nav">
                <a href="#features">Возможности</a>
                <a href="#about">О проекте</a>
                <a href="#auth" class="btn-outline">Войти</a>
            </nav>
        </header>

        <section class="hero">
            <div class="hero-content">
                <h2 class="hero-title">Контролируйте свои финансы с <span class="gradient-text">StalerBalance</span></h2>
                <p class="hero-subtitle">Простой и удобный сервис для учета финансов компании</p>
                <div class="hero-buttons">
                    <a href="#auth" class="btn-primary">Начать бесплатно</a>
                    <a href="#features" class="btn-secondary">Узнать больше</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="dashboard-preview">
                    <div class="preview-card income">
                        <i class="fas fa-arrow-down"></i>
                        <span>Доходы: +85,430 ₽</span>
                    </div>
                    <div class="preview-card expense">
                        <i class="fas fa-arrow-up"></i>
                        <span>Расходы: -42,150 ₽</span>
                    </div>
                    <div class="preview-chart"></div>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <h2 class="section-title">Что умеет StalerBalance</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Детальная статистика</h3>
                    <p>Наглядные графики и диаграммы по категориям расходов и доходов</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Цели</h3>
                    <p>Ставьте финансовые цели и отслеживайте прогресс их достижения</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Гибкие категории</h3>
                    <p>Создавайте собственные категории расходов и доходов</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Доступ с любого устройства</h3>
                    <p>Адаптивный дизайн для комфортной работы на ПК и смартфоне</p>
                </div>
            </div>
        </section>

        <!-- Форма авторизации/регистрации -->
        <section id="auth" class="auth-section">
            
            
            <div class="auth-container">
                <div class="auth-tabs">
                    <button class="tab-btn active" data-tab="login">Вход</button>
                    <button class="tab-btn" data-tab="register">Регистрация</button>
                </div>

                <div class="auth-messages">
    <?php
    if (isset($_SESSION['register_errors'])) {
        echo '<div class="error-notification register-error">';
        echo '<div class="error-icon">';
        echo '<i class="fas fa-user-slash"></i>';
        echo '</div>';
        echo '<div class="error-content">';
        echo '<h4>Ошибка регистрации</h4>';
        foreach ($_SESSION['register_errors'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
        echo '<button class="error-close"><i class="fas fa-times"></i></button>';
        echo '</div>';
        unset($_SESSION['register_errors']);
    }
    if (isset($_SESSION['login_errors'])) {
        echo '<div class="error-notification login-error">';
        echo '<div class="error-icon">';
        echo '<i class="fas fa-lock"></i>';
        echo '</div>';
        echo '<div class="error-content">';
        echo '<h4>Ошибка входа</h4>';
        foreach ($_SESSION['login_errors'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
        echo '<button class="error-close"><i class="fas fa-times"></i></button>';
        echo '</div>';
        unset($_SESSION['login_errors']);
    }
    $old_register = $_SESSION['old_register_data'] ?? [];
    $old_login = $_SESSION['old_login_data'] ?? [];
    unset($_SESSION['old_register_data'], $_SESSION['old_login_data']);
    ?>
                </div>

<form id="login-form" class="auth-form active" action="includes/login.php" method="POST">
                    <div class="form-group">
                        <label for="login-email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="login-email" name="email" 
                placeholder="ваш@email.com" 
                value="<?php echo htmlspecialchars($old_login['email'] ?? ''); ?>" 
                required>
            </div>
                    <div class="form-group">
                        <label for="login-password"><i class="fas fa-lock"></i> Пароль</label>
                        <input type="password" id="login-password" name="password" placeholder="Введите пароль" required>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i> Войти в аккаунт
                    </button>
                </form>
                
                <!-- Форма регистрации -->
                <form id="register-form" class="auth-form" action="includes/register.php" method="POST">
                    <div class="form-group">
                        <label for="reg-username"><i class="fas fa-user"></i> Имя пользователя</label>
                        <input type="text" id="reg-username" name="username" placeholder="Придумайте логин" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="reg-email" name="email" placeholder="ваш@email.com" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password"><i class="fas fa-lock"></i> Пароль</label>
                        <input type="password" id="reg-password" name="password" placeholder="Не менее 6 символов" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password-confirm"><i class="fas fa-lock"></i> Подтверждение пароля</label>
                        <input type="password" id="reg-password-confirm" name="password_confirm" placeholder="Повторите пароль" required>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Создать аккаунт
                    </button>
                </form>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>StalerBalance</span>
                    </div>
                    <p class="footer-tagline">Простой учет личных финансов</p>
                </div>
                
                <div class="footer-links">
                    <a href="#features">Возможности</a>
                    <a href="#auth">Начать</a>
                    <a href="mailto:support@moneyflow.ru" class="contact-link">
                        <i class="fas fa-envelope"></i>
                        Поддержка
                    </a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 StalerBalance</p>
                <div class="footer-legal">
                    <a href="#">Конфиденциальность</a>
                    <span class="separator">•</span>
                    <a href="#">Условия</a>
                </div>
            </div>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['login_errors']) || isset($_SESSION['register_errors'])): ?>

        setTimeout(function() {
            const authSection = document.getElementById('auth');
            if (authSection) {

                authSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                <?php if (isset($_SESSION['login_errors'])): ?>
                    document.querySelector('.tab-btn[data-tab="login"]').click();
                <?php elseif (isset($_SESSION['register_errors'])): ?>
                    document.querySelector('.tab-btn[data-tab="register"]').click();
                <?php endif; ?>
            }
        }, 100);
    <?php endif; ?>

    if (window.location.hash === '#auth') {
        setTimeout(function() {
            const authSection = document.getElementById('auth');
            if (authSection) {
                authSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }
});
</script>
</body>
</html>