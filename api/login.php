<?php
header('Content-Type: application/json');
require_once '../db/connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$login = $data['login'] ?? '';
$password = $data['password'] ?? '';

if (!$login || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Введите логин и пароль']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный логин или пароль']);
    exit;
}

session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['is_admin'] = ($user['login'] === 'admin');

echo json_encode(['status' => 'success', 'message' => 'Авторизация успешна']);