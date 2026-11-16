<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';

if (logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare('SELECT id_user,username,password,nama,level FROM users WHERE username=? LIMIT 1');
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id_user'  => $user['id_user'],
      'username' => $user['username'],
      'nama'     => $user['nama'],
      'level'    => $user['level'],
    ];
    header('Location: dashboard.php'); exit;
  }
  $error = 'Username atau password belum sesuai.';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk · EnergiLestari</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <a class="brand login-brand" href="index.php">
    <span class="brand-mark">🌿</span>
    <span><b>EnergiLestari</b><small>Hemat Energi, Selamatkan Bumi</small></span>
  </a>
  <div class="login-layout">
    <div class="login-art">
      <span class="eyebrow eyebrow-light">SELAMAT DATANG KEMBALI</span>
      <h1>Setiap aksi kecil<br>punya <em>arti besar.</em></h1>
      <p>Masuk dan lanjutkan langkahmu untuk lingkungan yang lebih hemat energi.</p>
      <div class="login-art-bubble">🌱 <span><b>Hemat Energi</b><small>Selamatkan Bumi</small></span></div>
    </div>
    <form class="login-card" method="post">
      <span class="eyebrow">AKUN ENERGILESTARI</span>
      <h2>Masuk ke akunmu</h2>
      <p class="muted">Masukkan username dan password yang terdaftar.</p>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="username">Username</label>
      <input class="form-control" id="username" name="username" autocomplete="username" placeholder="Masukkan username" required autofocus>
      <label for="password">Password</label>
      <div class="password-wrap">
        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>
        <button type="button" class="password-toggle" id="togglePassword">Lihat</button>
      </div>
      <button class="btn btn-primary btn-full" type="submit">Masuk ke Dashboard <span>→</span></button>
      <a class="back-link" href="index.php">← Kembali ke beranda</a>
    </form>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>