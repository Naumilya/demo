<?php 
include 'inc/auth_check.php'; 
include 'inc/header.php'; 
require_once 'db/connect.php';

$stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<h2>Мои заявки</h2>

<?php if ($requests): ?>
<ul>
	<?php foreach ($requests as $req): ?>
	<li>
		<?= htmlspecialchars($req['datetime']) ?> —
		<?= htmlspecialchars($req['cargo_type']) ?> —
		<?= htmlspecialchars($req['from_address']) ?> → <?= htmlspecialchars($req['to_address']) ?>
		<strong>[<?= $req['status'] ?>]</strong>
	</li>
	<?php endforeach; ?>
</ul>
<?php else: ?>
<p>У вас нет заявок.</p>
<?php endif; ?>

<p><a href="request_form.php">Оформить новую заявку</a></p>

<?php include 'inc/footer.php'; ?>