<?php
require_once '../includes/auth_check.php';
require_login();

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Обработка добавления новой категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $color = $_POST['color'] ?? '#3498db';
    
    if (!empty($name) && in_array($type, ['expense', 'income'])) {
        // Проверяем, нет ли уже такой категории у пользователя
        $stmt = $pdo->prepare("
            SELECT id FROM categories 
            WHERE (user_id = ? OR user_id IS NULL) 
            AND name = ? AND type = ?
        ");
        $stmt->execute([$user_id, $name, $type]);
        
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO categories (user_id, name, type, color) 
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $name, $type, $color])) {
                $message = '<div class="success-message">Категория успешно добавлена!</div>';
            } else {
                $message = '<div class="error-message">Ошибка при добавлении категории</div>';
            }
        } else {
            $message = '<div class="error-message">Такая категория уже существует</div>';
        }
    }
}

// Обработка удаления категории
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    
    // Проверяем, что категория принадлежит пользователю (не общая)
    $stmt = $pdo->prepare("
        DELETE FROM categories 
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$category_id, $user_id])) {
        // Обновляем транзакции, которые ссылались на эту категорию
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET category_id = (
                SELECT id FROM categories 
                WHERE type = (
                    SELECT type FROM categories WHERE id = ?
                ) 
                AND user_id IS NULL 
                LIMIT 1
            )
            WHERE category_id = ? AND user_id = ?
        ");
        $stmt->execute([$category_id, $category_id, $user_id]);
        
        $message = '<div class="success-message">Категория удалена!</div>';
    }
}

// Получаем все категории пользователя
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(t.id) as transaction_count,
           SUM(t.amount) as total_amount
    FROM categories c
    LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ?
    WHERE c.user_id = ? OR c.user_id IS NULL
    GROUP BY c.id
    ORDER BY 
        CASE WHEN c.user_id IS NULL THEN 0 ELSE 1 END,
        c.type,
        c.name
");
$stmt->execute([$user_id, $user_id]);
$categories = $stmt->fetchAll();

// Группируем категории по типу
$expense_categories = array_filter($categories, fn($c) => $c['type'] === 'expense');
$income_categories = array_filter($categories, fn($c) => $c['type'] === 'income');

// Статистика
$user_categories_count = count(array_filter($categories, fn($c) => $c['user_id'] !== null));
$default_categories_count = count(array_filter($categories, fn($c) => $c['user_id'] === null));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категории - MoneyFlow</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Боковое меню -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Основной контент -->
    <main class="main-content">
        <!-- Хедер -->
        <header class="top-header">
            <h1>Категории</h1>
            <div class="header-actions">
                <button class="btn-add-category" id="openCategoryModal">
                    <i class="fas fa-plus"></i>
                    Добавить категорию
                </button>
            </div>
        </header>

        <!-- Сообщения -->
        <?php echo $message; ?>

        <!-- Общая статистика -->
        <section class="categories-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Всего категорий</h3>
                        <p class="stat-amount"><?php echo count($categories); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon expense">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Расходы</h3>
                        <p class="stat-amount"><?php echo count($expense_categories); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon income">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Доходы</h3>
                        <p class="stat-amount"><?php echo count($income_categories); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon custom">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Ваши категории</h3>
                        <p class="stat-amount"><?php echo $user_categories_count; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="info-note">
                <i class="fas fa-info-circle"></i>
                <p>Стандартные категории нельзя удалить или изменить. Вы можете создавать свои категории.</p>
            </div>
        </section>

        <!-- Категории расходов -->
        <section class="categories-section">
            <div class="section-header">
                <h2><i class="fas fa-arrow-up"></i> Категории расходов</h2>
                <span class="category-count"><?php echo count($expense_categories); ?></span>
            </div>
            
            <?php if (empty($expense_categories)): ?>
                <div class="empty-categories">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Нет категорий расходов</p>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($expense_categories as $category): 
                        $is_default = $category['user_id'] === null;
                    ?>
                        <div class="category-card <?php echo $is_default ? 'default' : 'custom'; ?>">
                            <div class="category-color" style="background-color: <?php echo $category['color']; ?>;"></div>
                            <div class="category-info">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="category-meta">
                                    <span class="transactions-count">
                                        <i class="fas fa-receipt"></i>
                                        <?php echo $category['transaction_count']; ?> операций
                                    </span>
                                    <?php if ($category['total_amount'] > 0): ?>
                                        <span class="total-amount">
                                            <i class="fas fa-ruble-sign"></i>
                                            <?php echo number_format($category['total_amount'], 0, '.', ' '); ?> ₽
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_default): ?>
                                    <span class="category-badge default-badge">
                                        <i class="fas fa-shield-alt"></i> Стандартная
                                    </span>
                                <?php else: ?>
                                    <span class="category-badge custom-badge">
                                        <i class="fas fa-user"></i> Ваша
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_default): ?>
                                <div class="category-actions">
                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                       class="btn-delete-category" 
                                       onclick="return confirm('Удалить категорию? Все транзакции будут перенесены в стандартную категорию.')"
                                       title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Категории доходов -->
        <section class="categories-section">
            <div class="section-header">
                <h2><i class="fas fa-arrow-down"></i> Категории доходов</h2>
                <span class="category-count"><?php echo count($income_categories); ?></span>
            </div>
            
            <?php if (empty($income_categories)): ?>
                <div class="empty-categories">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Нет категорий доходов</p>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($income_categories as $category): 
                        $is_default = $category['user_id'] === null;
                    ?>
                        <div class="category-card <?php echo $is_default ? 'default' : 'custom'; ?>">
                            <div class="category-color" style="background-color: <?php echo $category['color']; ?>;"></div>
                            <div class="category-info">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="category-meta">
                                    <span class="transactions-count">
                                        <i class="fas fa-receipt"></i>
                                        <?php echo $category['transaction_count']; ?> операций
                                    </span>
                                    <?php if ($category['total_amount'] > 0): ?>
                                        <span class="total-amount">
                                            <i class="fas fa-ruble-sign"></i>
                                            <?php echo number_format($category['total_amount'], 0, '.', ' '); ?> ₽
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_default): ?>
                                    <span class="category-badge default-badge">
                                        <i class="fas fa-shield-alt"></i> Стандартная
                                    </span>
                                <?php else: ?>
                                    <span class="category-badge custom-badge">
                                        <i class="fas fa-user"></i> Ваша
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_default): ?>
                                <div class="category-actions">
                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                       class="btn-delete-category" 
                                       onclick="return confirm('Удалить категорию? Все транзакции будут перенесены в стандартную категорию.')"
                                       title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Модальное окно для добавления категории -->
    <div class="modal" id="categoryModal">
        <div class="modal-content category-modal">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="title-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div>
                        <h3>Новая категория</h3>
                        <p class="modal-subtitle">Добавьте свою категорию для учета</p>
                    </div>
                </div>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" id="categoryForm" class="category-form">
                <input type="hidden" name="add_category" value="1">
                
                <div class="form-sections-container">
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fas fa-edit"></i>
                            <h4>Основная информация</h4>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-with-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text" 
                                       id="categoryName" 
                                       name="name" 
                                       placeholder="Название категории"
                                       required>
                            </div>
                            <small class="input-hint">Например: Кофе, Такси, Образование</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoryType">
                                <i class="fas fa-filter"></i>
                                Тип категории
                            </label>
                            <div class="type-selector">
                                <label class="type-option">
                                    <input type="radio" name="type" value="expense" checked>
                                    <div class="option-content">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Расход</span>
                                    </div>
                                </label>
                                <label class="type-option">
                                    <input type="radio" name="type" value="income">
                                    <div class="option-content">
                                        <i class="fas fa-arrow-down"></i>
                                        <span>Доход</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fas fa-palette"></i>
                            <h4>Внешний вид</h4>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoryColor">
                                <i class="fas fa-fill-drip"></i>
                                Цвет категории
                            </label>
                            <div class="color-picker">
                                <input type="color" 
                                       id="categoryColor" 
                                       name="color" 
                                       value="#3498db"
                                       class="color-input">
                                <div class="color-presets">
                                    <?php 
                                    $color_presets = [
                                        '#e74c3c', '#3498db', '#9b59b6', '#2ecc71', 
                                        '#f39c12', '#1abc9c', '#e67e22', '#95a5a6',
                                        '#d35400', '#c0392b', '#8e44ad', '#27ae60'
                                    ];
                                    foreach ($color_presets as $color):
                                    ?>
                                        <button type="button" 
                                                class="color-preset" 
                                                style="background-color: <?php echo $color; ?>;"
                                                data-color="<?php echo $color; ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <small class="input-hint">Выберите цвет для визуального выделения категории</small>
                        </div>
                        
                        <div class="color-preview">
                            <div class="preview-header">
                                <h5>Предварительный просмотр</h5>
                            </div>
                            <div class="preview-content">
                                <div class="preview-category" id="colorPreview">
                                    <div class="preview-color" id="previewColor"></div>
                                    <div class="preview-info">
                                        <h4 id="previewName">Новая категория</h4>
                                        <p id="previewType">Расход</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="cancelCategory">
                        Отмена
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        Создать категорию
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Подключаем скрипты -->
    <script src="../assets/js/categories.js"></script>
</body>
</html>