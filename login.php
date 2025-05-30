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