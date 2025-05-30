<?php 
session_start();
include 'inc/header.php';
require_once 'db/connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "<p>Доступ запрещен. Только для администратора.</p>";
    include 'inc/footer.php';
    exit;
}

// Обновление статуса заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['request_id']]);
}

// Получение всех заявок
$stmt = $pdo->query("SELECT r.*, u.fio FROM requests r JOIN users u ON r.user_id = u.id ORDER BY r.datetime DESC");
$requests = $stmt->fetchAll();
?>

<h2>Панель администратора</h2>

<?php if ($requests): ?>
<table border="1" cellpadding="8">
	<tr>
		<th>ID</th>
		<th>ФИО пользователя</th>
		<th>Дата и время</th>
		<th>Тип груза</th>
		<th>Откуда → Куда</th>
		<th>Вес, габариты</th>
		<th>Статус</th>
		<th>Действия</th>
	</tr>
	<?php foreach ($requests as $req): ?>
	<tr>
		<td><?= $req['id'] ?></td>
		<td><?= htmlspecialchars($req['fio']) ?></td>
		<td><?= $req['datetime'] ?></td>
		<td><?= $req['cargo_type'] ?></td>
		<td><?= $req['from_address'] ?> → <?= $req['to_address'] ?></td>
		<td><?= $req['cargo_weight'] ?> кг, <?= $req['dimensions'] ?></td>
		<td><strong><?= $req['status'] ?></strong></td>
		<td>
			<form method="POST">
				<input type="hidden" name="request_id" value="<?= $req['id'] ?>">
				<select name="status">
					<option value="Новая" <?= $req['status'] == 'Новая' ? 'selected' : '' ?>>Новая</option>
					<option value="В работе" <?= $req['status'] == 'В работе' ? 'selected' : '' ?>>В работе</option>
					<option value="Отменена" <?= $req['status'] == 'Отменена' ? 'selected' : '' ?>>Отменена</option>
				</select>
				<button type="submit">Изменить</button>
			</form>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
<?php else: ?>
<p>Заявок пока нет.</p>
<?php endif; ?>

<?php include 'inc/footer.php'; ?>