# Демо-экзамен

_Прошу пожалуйста, когда возьмете этот код, поменяйте стили, название папок, сделайте так чтобы у нас не повторялось. Я специально сделал так чтобы каждый писал свои стили сам, чуток добавив общие чтобы легче было списать_

Инструкция по установке:

1. Зайти в папку куда клонировать проект через `cmd` или `powershell` - либо openserver либо в XAMPP
2. Написать:

```bash
 curl -L -o project.zip https://github.com/Naumilya/demo/archive/HEAD.zip
```

3. Запустить сервер (OpenServer: Просто нажать запустить в панели задач <Фалжок зеленый>) или (XAMPP - включить mysql, apache)
4. Создать свою БД - gruzovik_db (код запросов таблиц ниже).
5. Написать свои стили. + Адаптив.
6. Заскринить что у вас получилось в формате png (рекомендую), либо создать отдельно файлы в формате html

---

## Весь код приложение:

### Подключение к БД (db/connect.php)

```php
<?php
$host = 'localhost';
$db   = 'gruzovik_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к базе данных']);
    exit;
}
?>
```

### SQL запросы

```sql
-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Таблица заявок
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cargo_type ENUM('хрупкое', 'скоропортящееся', 'требуется рефрижератор', 'животные', 'жидкость', 'мебель', 'мусор') NOT NULL,
    cargo_weight DECIMAL(10, 2) NOT NULL,
    dimensions VARCHAR(100) NOT NULL,
    from_address TEXT NOT NULL,
    to_address TEXT NOT NULL,
    datetime DATETIME NOT NULL,
    status ENUM('Новая', 'В работе', 'Отменена') DEFAULT 'Новая',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица отзывов (опционально)
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

```

---

## Папка `api` (нет, не мамка, хаха)

### login.php

```php
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
```

### register.php

```php
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
```

### submit_request.php

```php
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
```

---

## Папка `assets`

- css
- img
- js
- fonts

_опционально (и другие)_

### css/style.css

```css
* {
	padding: 0;
	margin: 0;
	box-sizing: border-box;
}

body {
	font-family: Arial, sans-serif;
	padding: 20px;
	background: #f4f4f4;
	color: #333;
	height: 100vh;
	display: flex;
	flex-direction: column;
}

nav {
	padding: 10px 0;
	display: flex;
	gap: 10px;
}

main {
	flex: 1;
}

section {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

h2 {
	margin-top: 0;
	color: #444;
}

form {
	margin-top: 10px;
}

form input,
form select,
form button {
	display: block;
	margin: 10px 0;
	padding: 8px;
	width: 100%;
	max-width: 400px;
}

table {
	width: 100%;
	background: #fff;
	border-collapse: collapse;
	margin-top: 20px;
}

th,
td {
	padding: 10px;
	text-align: left;
}

th {
	background: #eee;
}

a {
	color: #0066cc;
	text-decoration: none;
}

footer {
	text-align: center;
}
```

### img

В эту папку вы можете скачивать картинки, оставляю ссылку на тему "Грузовики", чтобы не искать. Качайте себе локально картинки, ибо вам никто не поверит что вы запомнили ссылку. Иммейте это в виду!

```bash
https://unsplash.com/s/photos/%D0%B3%D1%80%D1%83%D0%B7%D0%BE%D0%B2%D0%B8%D0%BA
```

---

## Папка `inc` (include)

### auth_check.php

```php
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

### header.php

```php
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
```

### footer.php

```php
</main>
<footer>
	<p>&copy; <?= date("Y") ?> Грузоперевозки</p>
</footer>
</body>

</html>
```

---

## Страницы (корень проекта)

### index.php

```php
<?php include 'inc/header.php'; ?>
<section>
	<img src="https://plus.unsplash.com/premium_photo-1678281888592-8ad623bb39e9" alt="Грузовик" width="300px">

	<h2>Добро пожаловать на портал «Грузовозофф»</h2>
	<p>Мы предоставляем сервис онлайн-заказа грузоперевозок по России. Быстро, удобно, надёжно.</p>


	<?php if (!isset($_SESSION['user_id'])): ?>
	<p><a href="register.php">Зарегистрируйтесь</a> или <a href="login.php">войдите</a>, чтобы оставить заявку.</p>
	<?php else: ?>
	<p><a href="request_form.php">Оформить новую заявку</a> или <a href="profile.php">посмотреть мои заявки</a>.</p>
	<?php endif; ?>
</section>

<?php include 'inc/footer.php'; ?>
```

### admin.php

```php
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
```

### login.php

```php
<?php include 'inc/header.php'; ?>
<h2>Вход</h2>
<form id="loginForm">
	<label>Логин: <input type="text" name="login" required></label><br>
	<label>Пароль: <input type="password" name="password" required></label><br>
	<button type="submit">Войти</button>
</form>
<div id="loginMessage"></div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
	e.preventDefault();
	const formData = new FormData(this);
	const jsonData = JSON.stringify(Object.fromEntries(formData));

	const response = await fetch('api/login.php', {
		method: 'POST',
		body: jsonData
	});

	const result = await response.json();
	document.getElementById('loginMessage').innerText = result.message;

	if (result.status === 'success') {
		setTimeout(() => window.location.href = 'profile.php', 1000);
	}
});
</script>
<?php include 'inc/footer.php'; ?>
```

### logout.php

```php
<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit;
```

### profile.php

```php
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
```

### register.php

```php
<?php include 'inc/header.php'; ?>
<h2>Регистрация</h2>
<form id="registerForm">
	<label>ФИО: <input type="text" name="fio" required></label><br>
	<label>Телефон: <input type="text" name="phone" placeholder="+7(123)-456-78-90" required></label><br>
	<label>Email: <input type="email" name="email" required></label><br>
	<label>Логин: <input type="text" name="login" required></label><br>
	<label>Пароль: <input type="password" name="password" required></label><br>
	<button type="submit">Зарегистрироваться</button>
</form>
<div id="registerMessage"></div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
	e.preventDefault();
	const formData = new FormData(this);
	const jsonData = JSON.stringify(Object.fromEntries(formData));

	const response = await fetch('api/register.php', {
		method: 'POST',
		body: jsonData
	});

	const result = await response.json();
	document.getElementById('registerMessage').innerText = Array.isArray(result.messages) ? result.messages.join(
		', ') : result.message;

	if (result.status === 'success') {
		setTimeout(() => window.location.href = 'login.php', 1000);
	}
});
</script>
<?php include 'inc/footer.php'; ?>
```

### request_form.php

```php
<?php
include 'inc/auth_check.php';
include 'inc/header.php';
?>

<h2>Оформить заявку</h2>
<form id="requestForm">
	<label>Дата и время перевозки: <input type="datetime-local" name="datetime" required></label><br>
	<label>Вес груза (кг): <input type="number" name="weight" required></label><br>
	<label>Габариты груза: <input type="text" name="dimensions" required></label><br>
	<label>Адрес отправления: <input type="text" name="from_address" required></label><br>
	<label>Адрес доставки: <input type="text" name="to_address" required></label><br>
	<label>Тип груза:
		<select name="cargo_type" required>
			<option value="">-- выберите --</option>
			<option>хрупкое</option>
			<option>скоропортящееся</option>
			<option>требуется рефрижератор</option>
			<option>животные</option>
			<option>жидкость</option>
			<option>мебель</option>
			<option>мусор</option>
		</select>
	</label><br>
	<button type="submit">Отправить заявку</button>
</form>

<div id="requestMessage"></div>

<script>
document.getElementById('requestForm').addEventListener('submit', async function(e) {
	e.preventDefault();
	const formData = new FormData(this);
	const jsonData = JSON.stringify(Object.fromEntries(formData));

	const response = await fetch('api/submit_request.php', {
		method: 'POST',
		body: jsonData
	});

	const result = await response.json();
	document.getElementById('requestMessage').innerText = result.message;

	if (result.status === 'success') {
		setTimeout(() => window.location.href = 'profile.php', 1000);
	}
});
</script>

<?php include 'inc/footer.php'; ?>
```

---

## Удачи ребят на экзамене, не забудьте удалить этот файл после того как все сделали!

                      _
                      \`*-.
                       )  _`-.
                      .  : `. .
                      : _   '  \
                      ; *` _.   `*-._
                      `-.-'          `-.
                        ;       `       `.
                        :.       .        \
                        . \  .   :   .-'   .
                        '  `+.;  ;  '      :
                        :  '  |    ;       ;-.
                        ; '   : :`-:     _.`* ;
               [bug] .*' /  .*' ; .*`- +'  `*'
                     `*-*   `*-*  `*-*'
