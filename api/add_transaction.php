<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth_check.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit();
}

$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount'] ?? 0);
$type = $_POST['type'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$comment = trim($_POST['comment'] ?? '');

$errors = [];

if ($amount <= 0) {
    $errors[] = 'Сумма должна быть больше 0';
}

if (!in_array($type, ['expense', 'income'])) {
    $errors[] = 'Неверный тип операции';
}

if ($category_id <= 0) {
    $errors[] = 'Выберите категорию';
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
$stmt->execute([$category_id, $user_id]);
$category = $stmt->fetch();

if (!$category) {
    $errors[] = 'Категория не найдена';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, amount, type, category_id, date, comment) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$user_id, $amount, $type, $category_id, $date, $comment]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Операция успешно добавлена',
        'transaction_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>