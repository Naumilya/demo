<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Не авторизован']);
    exit;
}

require_once '../db/connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO requests (user_id, cargo_type, cargo_weight, dimensions, from_address, to_address, datetime) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $_SESSION['user_id'],
    $data['cargo_type'],
    $data['weight'],
    $data['dimensions'],
    $data['from_address'],
    $data['to_address'],
    $data['datetime']
]);

echo json_encode(['status' => 'success', 'message' => 'Заявка отправлена на рассмотрение']);