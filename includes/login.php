<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = 'Заполните все поля';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            header('Location: ../dashboard.php');
            exit();
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['old_login_data'] = ['email' => $email];
        header('Location: ../index.php#auth');
        exit();
    }
}
?>