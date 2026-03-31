<?php
require_once '../includes/auth_check.php';
require_login();

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];

// Получаем параметры периода
$period = $_GET['period'] ?? 'month'; // week, month, year, custom
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // начало текущего месяца
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // конец текущего месяца

// Если выбран стандартный период
if ($period === 'week') {
    $date_from = date('Y-m-d', strtotime('monday this week'));
    $date_to = date('Y-m-d', strtotime('sunday this week'));
} elseif ($period === 'month') {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-t');
} elseif ($period === 'year') {
    $date_from = date('Y-01-01');
    $date_to = date('Y-12-31');
}

// Общая статистика за период
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as total_expense,
        COUNT(*) as transaction_count
    FROM transactions 
    WHERE user_id = ? AND date BETWEEN ? AND ?
");
$stmt->execute([$user_id, $date_from, $date_to]);
$period_stats = $stmt->fetch();

// Статистика по категориям расходов
$stmt = $pdo->prepare("
    SELECT 
        c.name as category_name,
        c.color as category_color,
        SUM(t.amount) as total_amount,
        COUNT(t.id) as transaction_count,
        ROUND(SUM(t.amount) * 100 / (SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ?), 1) as percentage
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? 
        AND t.type = 'expense' 
        AND t.date BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.color
    ORDER BY total_amount DESC
");
$stmt->execute([$user_id, $date_from, $date_to, $user_id, $date_from, $date_to]);
$expense_by_category = $stmt->fetchAll();

// Статистика по категориям доходов
$stmt = $pdo->prepare("
    SELECT 
        c.name as category_name,
        c.color as category_color,
        SUM(t.amount) as total_amount,
        COUNT(t.id) as transaction_count,
        ROUND(SUM(t.amount) * 100 / (SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income' AND date BETWEEN ? AND ?), 1) as percentage
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? 
        AND t.type = 'income' 
        AND t.date BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.color
    ORDER BY total_amount DESC
");
$stmt->execute([$user_id, $date_from, $date_to, $user_id, $date_from, $date_to]);
$income_by_category = $stmt->fetchAll();

// Ежемесячная статистика за последние 6 месяцев
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance
    FROM transactions 
    WHERE user_id = ? 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$user_id]);
$monthly_stats = $stmt->fetchAll();

// Подготавливаем данные для графиков (JSON)
$chart_expense_data = [];
$chart_income_data = [];
$chart_monthly_labels = [];
$chart_monthly_income = [];
$chart_monthly_expense = [];

foreach ($expense_by_category as $item) {
    $chart_expense_data[] = [
        'name' => $item['category_name'],
        'value' => (float)$item['total_amount'],
        'color' => $item['category_color'],
        'percentage' => (float)$item['percentage']
    ];
}

foreach ($income_by_category as $item) {
    $chart_income_data[] = [
        'name' => $item['category_name'],
        'value' => (float)$item['total_amount'],
        'color' => $item['category_color'],
        'percentage' => (float)$item['percentage']
    ];
}

foreach ($monthly_stats as $item) {
    $chart_monthly_labels[] = date('M Y', strtotime($item['month'] . '-01'));
    $chart_monthly_income[] = (float)$item['income'];
    $chart_monthly_expense[] = (float)$item['expense'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - MoneyFlow</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/statistics.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <header class="top-header">
            <h1>Статистика</h1>
            <div class="header-actions">
                <div class="period-selector">
                    <form method="GET" class="period-form">
                        <select name="period" onchange="this.form.submit()">
                            <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Эта неделя</option>
                            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Этот месяц</option>
                            <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Этот год</option>
                            <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Произвольный период</option>
                        </select>
                    </form>
                </div>
            </div>
        </header>

        <?php if ($period === 'custom'): ?>
        <section class="custom-period">
            <form method="GET" class="date-range-form">
                <input type="hidden" name="period" value="custom">
                <div class="date-inputs">
                    <div class="form-group">
                        <label for="date_from"><i class="fas fa-calendar"></i> С</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_to"><i class="fas fa-calendar"></i> По</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-chart-line"></i> Показать статистику
                    </button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section class="period-stats">
            <div class="stats-grid">
                <div class="stat-card income">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Доходы</h3>
                        <p class="stat-amount">+<?php echo number_format($period_stats['total_income'], 2, '.', ' '); ?> ₽</p>
                    </div>
                </div>
                
                <div class="stat-card expense">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Расходы</h3>
                        <p class="stat-amount">-<?php echo number_format($period_stats['total_expense'], 2, '.', ' '); ?> ₽</p>
                    </div>
                </div>
                
                <div class="stat-card balance">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Баланс</h3>
                        <?php 
                        $period_balance = $period_stats['total_income'] - $period_stats['total_expense'];
                        ?>
                        <p class="stat-amount <?php echo $period_balance >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo number_format($period_balance, 2, '.', ' '); ?> ₽
                        </p>
                    </div>
                </div>
                
                <div class="stat-card count">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Операций</h3>
                        <p class="stat-amount"><?php echo $period_stats['transaction_count']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="period-info">
                <i class="fas fa-calendar-alt"></i>
                <span>
                    Период: 
                    <?php if ($period === 'custom'): ?>
                        <?php echo date('d.m.Y', strtotime($date_from)); ?> - <?php echo date('d.m.Y', strtotime($date_to)); ?>
                    <?php else: ?>
                        <?php 
                        echo match($period) {
                            'week' => 'Неделя ' . date('d.m.Y', strtotime($date_from)) . ' - ' . date('d.m.Y', strtotime($date_to)),
                            'month' => date('F Y', strtotime($date_from)),
                            'year' => date('Y', strtotime($date_from)) . ' год',
                            default => date('d.m.Y', strtotime($date_from)) . ' - ' . date('d.m.Y', strtotime($date_to))
                        };
                        ?>
                    <?php endif; ?>
                </span>
            </div>
        </section>

        <section class="charts-section">
            <div class="charts-row">

                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Расходы по категориям</h3>
                        <?php if (empty($expense_by_category)): ?>
                            <p class="no-data">Нет данных о расходах за выбранный период</p>
                        <?php endif; ?>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="expenseChart"></canvas>
                    </div>
                    <?php if (!empty($expense_by_category)): ?>
                    <div class="chart-legend">
                        <?php foreach ($expense_by_category as $item): ?>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: <?php echo $item['category_color']; ?>"></span>
                                <span class="legend-name"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                <span class="legend-value"><?php echo number_format($item['total_amount'], 2, '.', ' '); ?> ₽</span>
                                <span class="legend-percentage"><?php echo $item['percentage']; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Доходы по категориям</h3>
                        <?php if (empty($income_by_category)): ?>
                            <p class="no-data">Нет данных о доходах за выбранный период</p>
                        <?php endif; ?>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="incomeChart"></canvas>
                    </div>
                    <?php if (!empty($income_by_category)): ?>
                    <div class="chart-legend">
                        <?php foreach ($income_by_category as $item): ?>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: <?php echo $item['category_color']; ?>"></span>
                                <span class="legend-name"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                <span class="legend-value">+<?php echo number_format($item['total_amount'], 2, '.', ' '); ?> ₽</span>
                                <span class="legend-percentage"><?php echo $item['percentage']; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chart-container full-width">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Динамика за 6 месяцев</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </section>

        <section class="detailed-stats">
            <div class="stats-tabs">
                <button class="tab-btn active" data-tab="expenses">Расходы</button>
                <button class="tab-btn" data-tab="incomes">Доходы</button>
            </div>
            
            <div class="stats-content">
                <!-- Таблица расходов -->
                <div id="expenses-tab" class="stats-table active">
                    <table>
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>Сумма</th>
                                <th>Количество</th>
                                <th>Доля</th>
                                <th>Средний чек</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expense_by_category)): ?>
                                <tr>
                                    <td colspan="5" class="no-data">Нет данных о расходах</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expense_by_category as $item): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge" style="background-color: <?php echo $item['category_color']; ?>20; color: <?php echo $item['category_color']; ?>;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="amount negative">-<?php echo number_format($item['total_amount'], 2, '.', ' '); ?> ₽</td>
                                    <td><?php echo $item['transaction_count']; ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="bar-fill" style="width: <?php echo $item['percentage']; ?>%; background-color: <?php echo $item['category_color']; ?>;"></div>
                                            <span class="percentage-text"><?php echo $item['percentage']; ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['total_amount'] / $item['transaction_count'], 2, '.', ' '); ?> ₽</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Таблица доходов -->
                <div id="incomes-tab" class="stats-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>Сумма</th>
                                <th>Количество</th>
                                <th>Доля</th>
                                <th>Средний чек</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($income_by_category)): ?>
                                <tr>
                                    <td colspan="5" class="no-data">Нет данных о доходах</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($income_by_category as $item): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge" style="background-color: <?php echo $item['category_color']; ?>20; color: <?php echo $item['category_color']; ?>;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="amount positive">+<?php echo number_format($item['total_amount'], 2, '.', ' '); ?> ₽</td>
                                    <td><?php echo $item['transaction_count']; ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="bar-fill" style="width: <?php echo $item['percentage']; ?>%; background-color: <?php echo $item['category_color']; ?>;"></div>
                                            <span class="percentage-text"><?php echo $item['percentage']; ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['total_amount'] / $item['transaction_count'], 2, '.', ' '); ?> ₽</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        const chartData = {
            expense: <?php echo json_encode($chart_expense_data); ?>,
            income: <?php echo json_encode($chart_income_data); ?>,
            monthly: {
                labels: <?php echo json_encode($chart_monthly_labels); ?>,
                income: <?php echo json_encode($chart_monthly_income); ?>,
                expense: <?php echo json_encode($chart_monthly_expense); ?>
            }
        };
    </script>
    <script src="../assets/js/statistics.js"></script>
</body>
</html>