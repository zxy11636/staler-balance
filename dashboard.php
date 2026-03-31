<?php
require_once 'includes/auth_check.php';
require_login();

require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$username = $user['username'];
$email = $user['email'];

$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as balance
    FROM transactions 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.color as category_color 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.date DESC, t.id DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM categories WHERE type = 'expense' ORDER BY name");
$stmt->execute();
$expense_categories = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM categories WHERE type = 'income' ORDER BY name");
$stmt->execute();
$income_categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - StalerBalance</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-money-bill-wave"></i>
                <h2>StalerBalance</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                </div>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="active">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
            </li>
            <li>
                <a href="pages/transactions.php">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Транзакции</span>
                </a>
            </li>
            <li>
                <a href="pages/statistics.php">
                    <i class="fas fa-chart-pie"></i>
                    <span>Статистика</span>
                </a>
            </li>
            <li>
                <a href="pages/categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Категории</span>
                </a>
            </li>
            <li>
                <a href="pages/goals.php">
                    <i class="fas fa-bullseye"></i>
                    <span>Цели</span>
                </a>
            </li>
            <li class="logout">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выйти</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Основной контент -->
    <main class="main-content">
        <!-- Хедер -->
        <header class="top-header">
            <h1>Панель управления</h1>
            <div class="header-actions">
                <button class="btn-add-transaction" id="openTransactionModal">
                    <i class="fas fa-plus"></i>
                    Добавить операцию
                </button>
            </div>
        </header>

        <!-- Статистика -->
        <section class="stats-cards">
            <div class="stat-card balance">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Баланс</h3>
                    <p class="stat-amount"><?php echo number_format($stats['balance'] ?? 0, 2, '.', ' '); ?> ₽</p>
                </div>
            </div>
            
            <div class="stat-card income">
                <div class="stat-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-info">
                    <h3>Доходы</h3>
                    <p class="stat-amount">+<?php echo number_format($stats['total_income'] ?? 0, 2, '.', ' '); ?> ₽</p>
                </div>
            </div>
            
            <div class="stat-card expense">
                <div class="stat-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-info">
                    <h3>Расходы</h3>
                    <p class="stat-amount">-<?php echo number_format($stats['total_expense'] ?? 0, 2, '.', ' '); ?> ₽</p>
                </div>
            </div>
        </section>

        <!-- Основные действия -->
        <section class="quick-actions">
            <h2>Быстрые действия</h2>
            <div class="actions-grid">
                <a href="#" class="action-card" data-type="expense">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Добавить расход</span>
                </a>
                <a href="#" class="action-card" data-type="income">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Добавить доход</span>
                </a>
                <a href="pages/statistics.php" class="action-card">
                    <i class="fas fa-chart-line"></i>
                    <span>Посмотреть статистику</span>
                </a>
                <a href="pages/goals.php" class="action-card">
                    <i class="fas fa-bullseye"></i>
                    <span>Поставить цель</span>
                </a>
            </div>
        </section>

        <!-- Последние транзакции -->
        <section class="recent-transactions">
            <div class="section-header">
                <h2>Последние операции</h2>
                <a href="pages/transactions.php" class="view-all">Все операции →</a>
            </div>
            
            <?php if (empty($recent_transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>Пока нет операций</p>
                    <button class="btn-add-transaction">Добавить первую операцию</button>
                </div>
            <?php else: ?>
                <div class="transactions-list">
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <div class="transaction-item <?php echo $transaction['type']; ?>">
                            <div class="transaction-icon" style="background-color: <?php echo $transaction['category_color']; ?>20;">
                                <i class="fas fa-tag" style="color: <?php echo $transaction['category_color']; ?>;"></i>
                            </div>
                            <div class="transaction-details">
                                <h4><?php echo htmlspecialchars($transaction['category_name']); ?></h4>
                                <p><?php echo htmlspecialchars($transaction['comment'] ?: 'Без комментария'); ?></p>
                                <span class="transaction-date"><?php echo date('d.m.Y', strtotime($transaction['date'])); ?></span>
                            </div>
                            <div class="transaction-amount">
                                <span class="amount <?php echo $transaction['type']; ?>">
                                    <?php echo ($transaction['type'] == 'income' ? '+' : '-'); ?>
                                    <?php echo number_format($transaction['amount'], 2, '.', ' '); ?> ₽
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Модальное окно для добавления транзакции -->
    <div class="modal" id="transactionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Добавить операцию</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="transactionForm" action="api/add_transaction.php" method="POST">
                <div class="form-group">
                    <label for="transactionType">Тип операции</label>
                    <div class="type-selector">
                        <button type="button" class="type-btn active" data-type="expense">Расход</button>
                        <button type="button" class="type-btn" data-type="income">Доход</button>
                        <input type="hidden" name="type" id="transactionType" value="expense" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Сумма (₽)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Дата</label>
                        <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category">Категория</label>
                    <select id="category" name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <!-- Категории будут заполняться через JS -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comment">Комментарий (необязательно)</label>
                    <textarea id="comment" name="comment" rows="3" placeholder="Например: Покупка продуктов"></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus"></i>
                    Добавить операцию
                </button>
            </form>
        </div>
    </div>

    <!-- Подключаем скрипты -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>