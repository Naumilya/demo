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