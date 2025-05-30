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