<?php
require_once '../includes/auth_check.php';
require_login();

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Обработка добавления новой цели
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $name = trim($_POST['name']);
    $target_amount = floatval($_POST['target_amount']);
    $current_amount = floatval($_POST['current_amount'] ?? 0);
    $deadline = $_POST['deadline'] ?: null;
    
    if (!empty($name) && $target_amount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO goals (user_id, name, target_amount, current_amount, deadline) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$user_id, $name, $target_amount, $current_amount, $deadline])) {
            $message = '<div class="success-message">Цель успешно добавлена!</div>';
        } else {
            $message = '<div class="error-message">Ошибка при добавлении цели</div>';
        }
    }
}

// Обработка обновления прогресса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $goal_id = intval($_POST['goal_id']);
    $current_amount = floatval($_POST['current_amount']);
    
    $stmt = $pdo->prepare("
        UPDATE goals 
        SET current_amount = ? 
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$current_amount, $goal_id, $user_id])) {
        $message = '<div class="success-message">Прогресс обновлен!</div>';
    } else {
        $message = '<div class="error-message">Ошибка при обновлении</div>';
    }
}

// Обработка удаления цели
if (isset($_GET['delete'])) {
    $goal_id = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$goal_id, $user_id])) {
        $message = '<div class="success-message">Цель удалена!</div>';
    }
}

// Получаем все цели пользователя
$stmt = $pdo->prepare("
    SELECT *,
           ROUND((current_amount / target_amount) * 100, 1) as progress_percentage,
           DATEDIFF(deadline, CURDATE()) as days_left
    FROM goals 
    WHERE user_id = ?
    ORDER BY 
        CASE 
            WHEN progress_percentage >= 100 THEN 3
            WHEN days_left < 0 THEN 2
            ELSE 1
        END,
        deadline ASC,
        created_at DESC
");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll();

// Статистика по целям
$total_goals = count($goals);
$completed_goals = array_filter($goals, fn($g) => $g['current_amount'] >= $g['target_amount']);
$active_goals = array_filter($goals, fn($g) => $g['current_amount'] < $g['target_amount']);
$total_target = array_sum(array_column($goals, 'target_amount'));
$total_current = array_sum(array_column($goals, 'current_amount'));
$overall_progress = $total_target > 0 ? round(($total_current / $total_target) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Цели накоплений - MoneyFlow</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/goals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Боковое меню -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Основной контент -->
    <main class="main-content">
        <!-- Хедер -->
        <header class="top-header">
            <h1>Цели накоплений</h1>
            <div class="header-actions">
                <button class="btn-add-goal" id="openGoalModal">
                    <i class="fas fa-plus"></i>
                    Новая цель
                </button>
            </div>
        </header>

        <!-- Сообщения -->
        <?php echo $message; ?>

        <!-- Общая статистика -->
        <section class="goals-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Всего целей</h3>
                        <p class="stat-amount"><?php echo $total_goals; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Активные</h3>
                        <p class="stat-amount"><?php echo count($active_goals); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Выполнено</h3>
                        <p class="stat-amount"><?php echo count($completed_goals); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon progress">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Общий прогресс</h3>
                        <p class="stat-amount"><?php echo $overall_progress; ?>%</p>
                    </div>
                </div>
            </div>
            
            <!-- Общая шкала прогресса -->
            <div class="overall-progress">
                <div class="progress-header">
                    <h4>Общий прогресс по всем целям</h4>
                    <span><?php echo number_format($total_current, 0, '.', ' '); ?> ₽ / <?php echo number_format($total_target, 0, '.', ' '); ?> ₽</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($overall_progress, 100); ?>%;"></div>
                </div>
            </div>
        </section>

        <!-- Список целей -->
        <section class="goals-list-section">
            <div class="section-header">
                <h2>Мои цели</h2>
                <div class="view-options">
                    <button class="view-btn active" data-filter="all">Все</button>
                    <button class="view-btn" data-filter="active">Активные</button>
                    <button class="view-btn" data-filter="completed">Выполненные</button>
                </div>
            </div>
            
            <?php if (empty($goals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>У вас еще нет целей</h3>
                    <p>Создайте свою первую финансовую цель и начните копить!</p>
                    <button class="btn-add-goal" id="openGoalModalEmpty">Создать цель</button>
                </div>
            <?php else: ?>
                <div class="goals-grid">
                    <?php foreach ($goals as $goal): 
                        $progress = min($goal['progress_percentage'], 100);
                        $is_completed = $goal['current_amount'] >= $goal['target_amount'];
                        $is_overdue = $goal['days_left'] < 0 && !$is_completed;
                        $days_text = $goal['days_left'] > 0 ? "Осталось {$goal['days_left']} дн." : ($goal['days_left'] == 0 ? "Последний день" : "Просрочено");
                        
                        // Определяем цвет в зависимости от прогресса
                        if ($is_completed) {
                            $progress_color = '#27ae60';
                            $status_class = 'completed';
                            $status_text = 'Выполнена';
                        } elseif ($is_overdue) {
                            $progress_color = '#e74c3c';
                            $status_class = 'overdue';
                            $status_text = 'Просрочена';
                        } elseif ($progress >= 75) {
                            $progress_color = '#f39c12';
                            $status_class = 'almost';
                            $status_text = 'Почти готово';
                        } else {
                            $progress_color = '#3498db';
                            $status_class = 'active';
                            $status_text = 'В процессе';
                        }
                    ?>
                        <div class="goal-card <?php echo $status_class; ?>" data-status="<?php echo $is_completed ? 'completed' : 'active'; ?>">
                            <div class="goal-header">
                                <h3><?php echo htmlspecialchars($goal['name']); ?></h3>
                                <div class="goal-actions">
                                    <button class="btn-edit-goal" data-goal-id="<?php echo $goal['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $goal['id']; ?>" class="btn-delete-goal" onclick="return confirm('Удалить цель?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="goal-progress">
                                <div class="progress-info">
                                    <span class="current-amount"><?php echo number_format($goal['current_amount'], 0, '.', ' '); ?> ₽</span>
                                    <span class="target-amount">/ <?php echo number_format($goal['target_amount'], 0, '.', ' '); ?> ₽</span>
                                    <span class="progress-percentage"><?php echo $progress; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%; background-color: <?php echo $progress_color; ?>;"></div>
                                </div>
                            </div>
                            
                            <div class="goal-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>
                                        <?php if ($goal['deadline']): ?>
                                            До <?php echo date('d.m.Y', strtotime($goal['deadline'])); ?>
                                        <?php else: ?>
                                            Без срока
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span class="<?php echo $is_overdue ? 'overdue-text' : ''; ?>">
                                        <?php echo $days_text; ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="status-badge" style="background-color: <?php echo $progress_color; ?>20; color: <?php echo $progress_color; ?>;">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Форма для обновления прогресса -->
                            <form method="POST" class="update-progress-form">
                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                <input type="hidden" name="update_progress" value="1">
                                <div class="update-controls">
                                    <input type="number" 
                                           name="current_amount" 
                                           value="<?php echo $goal['current_amount']; ?>" 
                                           min="0" 
                                           max="<?php echo $goal['target_amount']; ?>"
                                           step="100"
                                           class="progress-input">
                                    <button type="submit" class="btn-update">
                                        <i class="fas fa-sync-alt"></i> Обновить
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Модальное окно для добавления цели -->
    <div class="modal" id="goalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Новая цель</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" id="goalForm">
                <input type="hidden" name="add_goal" value="1">
                
                <div class="form-group">
                    <label for="goalName"><i class="fas fa-bullseye"></i> Название цели</label>
                    <input type="text" id="goalName" name="name" placeholder="Например: Новый ноутбук" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="targetAmount"><i class="fas fa-money-bill-wave"></i> Целевая сумма (₽)</label>
                        <input type="number" id="targetAmount" name="target_amount" min="1" step="100" placeholder="50000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="currentAmount"><i class="fas fa-piggy-bank"></i> Текущая сумма (₽)</label>
                        <input type="number" id="currentAmount" name="current_amount" min="0" step="100" placeholder="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="deadline"><i class="fas fa-calendar"></i> Дата цели (необязательно)</label>
                    <input type="date" id="deadline" name="deadline" min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <div class="preview-card">
                        <h4>Предварительный просмотр:</h4>
                        <div class="preview-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" id="previewProgress" style="width: 0%;"></div>
                            </div>
                            <div class="preview-text">
                                <span id="previewCurrent">0 ₽</span> / 
                                <span id="previewTarget">0 ₽</span>
                                (<span id="previewPercentage">0%</span>)
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus"></i>
                    Создать цель
                </button>
            </form>
        </div>
    </div>

    <!-- Подключаем скрипты -->
    <script src="../assets/js/goals.js"></script>
</body>
</html>