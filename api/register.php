<?php
header('Content-Type: application/json');
require_once '../db/connect.php';

// Получение данных из POST
$data = json_decode(file_get_contents("php://input"), true);

// Валидация данных
$errors = [];

if (!preg_match('/^[а-яА-ЯёЁ\s]+$/u', $data['fio'] ?? '')) {
    $errors[] = 'Некорректное ФИО';
}
if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $data['phone'] ?? '')) {
    $errors[] = 'Некорректный номер телефона';
}
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email';
}
if (!preg_match('/^[а-яА-ЯёЁ0-9]{6,}$/u', $data['login'] ?? '')) {
    $errors[] = 'Логин должен быть на кириллице и не короче 6 символов';
}
if (strlen($data['password'] ?? '') < 6) {
    $errors[] = 'Пароль должен быть не менее 6 символов';
}

if ($errors) {
    echo json_encode(['status' => 'error', 'messages' => $errors]);
    exit;
}

// Проверка уникальности логина
$stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
$stmt->execute([$data['login']]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'messages' => ['Логин уже используется']]);
    exit;
}

// Хеширование пароля и вставка
$hash = password_hash($data['password'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (fio, phone, email, login, password) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $data['fio'],
    $data['phone'],
    $data['email'],
    $data['login'],
    $hash
]);

echo json_encode(['status' => 'success', 'message' => 'Регистрация прошла успешно']);