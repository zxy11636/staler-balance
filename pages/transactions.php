<?php
require_once '../includes/auth_check.php';
require_login();

require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_GET['delete'])) {
    $transaction_id = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$transaction_id, $user_id])) {
        $message = '<div class="success-message">Транзакция удалена!</div>';
    } else {
        $message = '<div class="error-message">Ошибка при удалении</div>';
    }
}

$type_filter = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "t.user_id = ?";
$params = [$user_id];

if ($type_filter && in_array($type_filter, ['income', 'expense'])) {
    $where .= " AND t.type = ?";
    $params[] = $type_filter;
}

if ($category_filter && is_numeric($category_filter)) {
    $where .= " AND t.category_id = ?";
    $params[] = $category_filter;
}

if ($date_from) {
    $where .= " AND t.date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where .= " AND t.date <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where .= " AND (t.comment LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql = "
    SELECT t.*, c.name as category_name, c.color as category_color, c.type as category_type
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE $where
    ORDER BY t.date DESC, t.id DESC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$transactions = $stmt->fetchAll();

$count_sql = "
    SELECT COUNT(*) as total 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE $where
";

$stmt = $pdo->prepare($count_sql);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$total_count = $stmt->fetch()['total'];
$total_pages = ceil($total_count / $limit);

$stats_sql = "
    SELECT 
        COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount END), 0) as total_income,
        COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount END), 0) as total_expense
    FROM transactions t
    WHERE $where
";

$stmt = $pdo->prepare($stats_sql);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$filter_stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT id, name, type 
    FROM categories 
    WHERE user_id IS NULL OR user_id = ?
    ORDER BY type, name
");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

$expense_categories = array_filter($categories, fn($c) => $c['type'] === 'expense');
$income_categories = array_filter($categories, fn($c) => $c['type'] === 'income');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Транзакции - MoneyFlow</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/transactions.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .custom-message {
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="top-header">
            <h1>Транзакции</h1>
            <div class="header-actions">
                <button class="btn-add-transaction" id="openTransactionModal">
                    <i class="fas fa-plus"></i>
                    Добавить операцию
                </button>
            </div>
        </header>

        <?php echo $message; ?>

        <section class="filters-section">
            <form method="GET" class="filters-form" id="filtersForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="type_filter"><i class="fas fa-filter"></i> Тип</label>
                        <select id="type_filter" name="type" onchange="this.form.submit()">
                            <option value="">Все типы</option>
                            <option value="income" <?php echo $type_filter === 'income' ? 'selected' : ''; ?>>Доходы</option>
                            <option value="expense" <?php echo $type_filter === 'expense' ? 'selected' : ''; ?>>Расходы</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="category_filter"><i class="fas fa-tag"></i> Категория</label>
                        <select id="category_filter" name="category" onchange="this.form.submit()">
                            <option value="">Все категории</option>
                            <optgroup label="Расходы">
                                <?php foreach ($expense_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Доходы">
                                <?php foreach ($income_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from"><i class="fas fa-calendar"></i> С</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to"><i class="fas fa-calendar"></i> По</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search"><i class="fas fa-search"></i> Поиск</label>
                        <div class="search-input">
                            <input type="text" id="search" name="search" placeholder="По комментарию..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Применить фильтры
                    </button>
                    <a href="transactions.php" class="btn-filter-reset">
                        <i class="fas fa-times"></i> Сбросить
                    </a>
                </div>
            </form>
            
            <?php if ($total_count > 0): ?>
            <div class="filter-stats">
                <div class="stat-badge income">
                    <i class="fas fa-arrow-down"></i>
                    <span>Доходы: <strong>+<?php echo number_format($filter_stats['total_income'] ?? 0, 2, '.', ' '); ?> ₽</strong></span>
                </div>
                <div class="stat-badge expense">
                    <i class="fas fa-arrow-up"></i>
                    <span>Расходы: <strong>-<?php echo number_format($filter_stats['total_expense'] ?? 0, 2, '.', ' '); ?> ₽</strong></span>
                </div>
                <div class="stat-badge total">
                    <i class="fas fa-wallet"></i>
                    <span>Итого: <strong><?php echo number_format(($filter_stats['total_income'] ?? 0) - ($filter_stats['total_expense'] ?? 0), 2, '.', ' '); ?> ₽</strong></span>
                </div>
                <div class="stat-badge count">
                    <i class="fas fa-list"></i>
                    <span>Найдено: <strong><?php echo $total_count; ?></strong></span>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <section class="transactions-section">
            <div class="section-header">
                <h2>Список операций</h2>
                <div class="export-actions">
                    <button class="btn-export" onclick="exportTransactions()">
                        <i class="fas fa-file-export"></i> Экспорт
                    </button>
                </div>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Транзакции не найдены</h3>
                    <p>Попробуйте изменить параметры фильтрации или добавьте первую транзакцию</p>
                </div>
            <?php else: ?>
                <div class="transactions-table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Категория</th>
                                <th>Комментарий</th>
                                <th>Тип</th>
                                <th>Сумма</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="transaction-row <?php echo $transaction['type']; ?>">
                                    <td class="transaction-date">
                                        <?php echo date('d.m.Y', strtotime($transaction['date'])); ?>
                                    </td>
                                    <td class="transaction-category">
                                        <span class="category-badge" style="background-color: <?php echo $transaction['category_color']; ?>20; color: <?php echo $transaction['category_color']; ?>;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($transaction['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="transaction-comment">
                                        <?php echo htmlspecialchars($transaction['comment'] ?: '—'); ?>
                                    </td>
                                    <td class="transaction-type">
                                        <span class="type-badge <?php echo $transaction['type']; ?>">
                                            <?php echo $transaction['type'] === 'income' ? 'Доход' : 'Расход'; ?>
                                        </span>
                                    </td>
                                    <td class="transaction-amount">
                                        <span class="amount <?php echo $transaction['type']; ?>">
                                            <?php echo ($transaction['type'] === 'income' ? '+' : '-'); ?>
                                            <?php echo number_format($transaction['amount'], 2, '.', ' '); ?> ₽
                                        </span>
                                    </td>
                                    <td class="transaction-actions">
                                        <button class="btn-edit" onclick="editTransaction(<?php echo $transaction['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $transaction['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Удалить эту транзакцию?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-link first">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link prev">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="page-link last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="summary-info">
                    <p>
                        Показано <?php echo count($transactions); ?> из <?php echo $total_count; ?> транзакций
                        <?php if ($date_from || $date_to): ?>
                            за период 
                            <?php if ($date_from) echo 'с ' . date('d.m.Y', strtotime($date_from)); ?>
                            <?php if ($date_to) echo ' по ' . date('d.m.Y', strtotime($date_to)); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div class="modal" id="transactionModal">
        <div class="modal-content transaction-modal">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="title-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle">Новая транзакция</h3>
                        <p class="modal-subtitle">Добавление операции</p>
                    </div>
                </div>
                <button class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" id="transactionForm" class="transaction-form" action="../api/add_transaction.php">
                <input type="hidden" id="transactionId" name="transaction_id" value="">
                
                <div class="form-sections-container">
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fas fa-edit"></i>
                            <h4>Основная информация</h4>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="transactionType">
                                    <i class="fas fa-exchange-alt"></i>
                                    Тип операции
                                </label>
                                <div class="type-selector">
                                    <label class="type-option">
                                        <input type="radio" name="type" value="expense" id="typeExpense" checked>
                                        <div class="option-content">
                                            <i class="fas fa-arrow-up"></i>
                                            <span>Расход</span>
                                        </div>
                                    </label>
                                    <label class="type-option">
                                        <input type="radio" name="type" value="income" id="typeIncome">
                                        <div class="option-content">
                                            <i class="fas fa-arrow-down"></i>
                                            <span>Доход</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Сумма (₽)
                                </label>
                                <div class="input-with-icon">
                                    <i class="fas fa-ruble-sign"></i>
                                    <input type="number" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0.01" 
                                           placeholder="0.00"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">
                                <i class="fas fa-calendar"></i>
                                Дата
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" 
                                       id="date" 
                                       name="date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fas fa-tags"></i>
                            <h4>Детали</h4>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">
                                <i class="fas fa-tag"></i>
                                Категория
                            </label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Выберите категорию</option>
                                <optgroup label="Расходы" id="expenseCategories">
                                    <?php foreach ($expense_categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Доходы" id="incomeCategories">
                                    <?php foreach ($income_categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">
                                <i class="fas fa-comment"></i>
                                Комментарий
                            </label>
                            <textarea id="comment" 
                                      name="comment" 
                                      rows="3" 
                                      placeholder="Например: Покупка продуктов в супермаркете"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="cancelTransaction">
                        Отмена
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus-circle"></i>
                        <span id="submitText">Добавить операцию</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('transactionModal');
            const openButtons = document.querySelectorAll('[id^="openTransactionModal"]');
            const closeButton = document.querySelector('.modal-close');
            const cancelButton = document.getElementById('cancelTransaction');
            const transactionForm = document.getElementById('transactionForm');
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const modalTitle = document.getElementById('modalTitle');
            const modalSubtitle = document.querySelector('.modal-subtitle');
            const submitText = document.getElementById('submitText');
            const transactionIdInput = document.getElementById('transactionId');
            const expenseCategories = document.getElementById('expenseCategories');
            const incomeCategories = document.getElementById('incomeCategories');
            const categorySelect = document.getElementById('category_id');

            openButtons.forEach(button => {
                button.addEventListener('click', function() {
                    resetForm();
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });

            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            closeButton.addEventListener('click', closeModal);
            cancelButton.addEventListener('click', closeModal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            typeRadios.forEach(radio => {
                radio.addEventListener('change', updateCategories);
            });

            function updateCategories() {
                const selectedType = document.querySelector('input[name="type"]:checked').value;
                
                if (selectedType === 'expense') {
                    expenseCategories.style.display = 'block';
                    incomeCategories.style.display = 'none';
                } else {
                    expenseCategories.style.display = 'none';
                    incomeCategories.style.display = 'block';
                }
                
                categorySelect.value = '';
            }

            function resetForm() {
                transactionForm.reset();
                transactionIdInput.value = '';
                modalTitle.textContent = 'Новая транзакция';
                modalSubtitle.textContent = 'Добавление операции';
                submitText.textContent = 'Добавить операцию';
                document.querySelector('input[name="type"][value="expense"]').checked = true;
                document.getElementById('date').value = new Date().toISOString().split('T')[0];
                updateCategories();
            }

            transactionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const isEdit = transactionIdInput.value !== '';
                const submitBtn = this.querySelector('.btn-submit');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
                submitBtn.disabled = true;
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(isEdit ? 'Транзакция обновлена!' : 'Транзакция добавлена!', 'success');
                        closeModal();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage(data.message || 'Произошла ошибка', 'error');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Ошибка соединения с сервером', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });

            window.editTransaction = function(transactionId) {
                fetch(`../api/get_transaction.php?id=${transactionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            transactionIdInput.value = data.transaction.id;
                            modalTitle.textContent = 'Редактирование транзакции';
                            modalSubtitle.textContent = 'Изменение операции';
                            submitText.textContent = 'Сохранить изменения';
                            document.querySelector(`input[name="type"][value="${data.transaction.type}"]`).checked = true;
                            updateCategories();
                            setTimeout(() => {
                                document.getElementById('amount').value = data.transaction.amount;
                                document.getElementById('date').value = data.transaction.date;
                                document.getElementById('category_id').value = data.transaction.category_id;
                                document.getElementById('comment').value = data.transaction.comment || '';
                            }, 100);
                            modal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        } else {
                            showMessage('Не удалось загрузить данные транзакции', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessage('Ошибка загрузки данных', 'error');
                    });
            };

            window.exportTransactions = function() {
                const params = new URLSearchParams(window.location.search);
                window.open(`../api/export_transactions.php?${params.toString()}`, '_blank');
            };

            function showMessage(text, type) {
                const oldMessage = document.querySelector('.custom-message');
                if (oldMessage) oldMessage.remove();
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `custom-message ${type === 'success' ? 'success-message' : 'error-message'}`;
                messageDiv.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${text}</span>
                `;
                
                const mainContent = document.querySelector('.main-content');
                mainContent.insertBefore(messageDiv, mainContent.firstChild);
                
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.style.opacity = '0';
                        setTimeout(() => {
                            if (messageDiv.parentNode) messageDiv.remove();
                        }, 300);
                    }
                }, 5000);
            }

            updateCategories();
        });
    </script>
</body>
</html>