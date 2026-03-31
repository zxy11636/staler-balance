<?php
// api/get_categories.php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем категории расходов и доходов
    $stmt = $pdo->prepare("
        SELECT id, name, type, color 
        FROM categories 
        WHERE user_id IS NULL OR user_id = ?
        ORDER BY type, name
    ");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll();
    
    // Если нет категорий для пользователя, используем общие (user_id IS NULL)
    if (empty($categories)) {
        $stmt = $pdo->prepare("
            SELECT id, name, type, color 
            FROM categories 
            WHERE user_id IS NULL
            ORDER BY type, name
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll();
    }
    
    // Группируем по типу
    $grouped_categories = [
        'expense' => [],
        'income' => []
    ];
    
    foreach ($categories as $category) {
        $grouped_categories[$category['type']][] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'color' => $category['color']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $grouped_categories
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка базы данных: ' . $e->getMessage(),
        'categories' => ['expense' => [], 'income' => []]
    ]);
}
?>