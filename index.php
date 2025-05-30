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