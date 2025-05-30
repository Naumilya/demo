<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Грузоперевозки</title>
	<link rel="stylesheet" href="assets/css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
	<header>
		<h1>Сервис грузоперевозок</h1>
		<nav>
			<a href="index.php">Главная</a>
			<?php if (!empty($_SESSION['user_id'])): ?>
			<a href="profile.php">Профиль</a>
			<?php if (!empty($_SESSION['is_admin'])): ?>
			<a href="admin.php">Админка</a>
			<?php endif; ?>
			<a href="logout.php">Выход</a>
			<?php else: ?>
			<a href="login.php">Вход</a>
			<a href="register.php">Регистрация</a>
			<?php endif; ?>
		</nav>
	</header>
	<main>