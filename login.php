<?php
require_once __DIR__ . '/includes/auth.php';

if (pengguna()) {
    redirect('/laporan/index.php');
}

$err   = null;
$email = '';
$next  = (string) ($_GET['next'] ?? '/laporan/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $next  = (string) ($_POST['next'] ?? '/laporan/index.php');

    $st = $pdo->prepare('SELECT id, nama, nim_nip, email, password_hash, role, aktif FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        $err = 'Email atau password salah.';
    } elseif (!(int) $user['aktif']) {
        $err = 'Akun ini sedang dinonaktifkan. Hubungi unit sarpras.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'      => (int) $user['id'],
            'nama'    => $user['nama'],
            'nim_nip' => $user['nim_nip'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ];
        flash('success', 'Selamat datang, ' . $user['nama'] . '.');
        $tujuan = str_starts_with($next, BASE . '/') ? substr($next, strlen(BASE)) : '/laporan/index.php';
        redirect($tujuan);
    }
}

$judul = 'Masuk';
include __DIR__ . '/includes/header.php';
?>

<div class="mx-auto grid max-w-4xl gap-8 px-4 py-14 md:grid-cols-[0.85fr_1.15fr]">
  <div class="hidden md:block">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Masuk</p>
    <h1 class="mt-2 text-2xl font-semibold text-navy">Lanjutkan laporan Anda</h1>
    <div class="aksen-garis mt-4"></div>
    <p class="mt-5 text-xs leading-relaxed text-muted">
      Pelapor dapat membuat laporan dan menanggapi tiket miliknya. Admin sarpras dapat
      memverifikasi dan memperbarui status penanganan.
    </p>
  </div>

  <div class="card p-6">
    <h2 class="text-lg font-semibold text-navy">Masuk ke akun</h2>

    <?php if ($err): ?>
      <div class="flash flash-error mt-4"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" class="mt-5" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-full">Masuk</button>
      <p class="mt-3 text-center text-xs text-muted">
        Belum punya akun? <a href="<?= BASE ?>/register.php" class="font-semibold text-navy underline decoration-gold underline-offset-2">Daftar</a>
      </p>
    </form>

    <div class="mt-5 rounded-lg border border-line bg-navy-light px-4 py-3 text-[11px] leading-relaxed text-muted">
      <p class="font-semibold text-navy">Akun contoh</p>
      <p>Admin: admin@kampus.test / admin123</p>
      <p>Pelapor: adridinan@kampus.test / pelapor123</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
