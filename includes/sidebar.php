
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
                <span class="username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Пользователь'); ?></span>
                <span class="user-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="../dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>Главная</span>
            </a>
        </li>
        <li>
            <a href="transactions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-exchange-alt"></i>
                <span>Транзакции</span>
            </a>
        </li>
        <li>
            <a href="statistics.php" <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-chart-pie"></i>
                <span>Статистика</span>
            </a>
        </li>
        <li>
            <a href="categories.php" <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-tags"></i>
                <span>Категории</span>
            </a>
        </li>
        <li>
            <a href="goals.php" <?php echo basename($_SERVER['PHP_SELF']) == 'goals.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-bullseye"></i>
                <span>Цели</span>
            </a>
        </li>
        <li class="logout">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выйти</span>
            </a>
        </li>
    </ul>
</nav>