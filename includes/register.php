<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    

    $errors = [];
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Имя пользователя должно содержать минимум 3 символа';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email адрес';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Пользователь с таким email или именем уже существует';
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$username, $email, $hashed_password])) {

            $user_id = $pdo->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            header('Location: ../dashboard.php');
            exit();
        } else {
            $errors[] = 'Ошибка при регистрации. Попробуйте позже.';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['old_register_data'] = [
            'username' => $username,
            'email' => $email
        ];
        header('Location: ../index.php#auth');
        exit();
    }
}
?>