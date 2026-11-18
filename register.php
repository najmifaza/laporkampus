<?php
require_once __DIR__ . '/includes/auth.php';

if (pengguna()) {
    redirect('/laporan/index.php');
}

$err   = [];
$nilai = ['nama' => '', 'nim_nip' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nilai['nama']    = trim((string) ($_POST['nama'] ?? ''));
    $nilai['nim_nip'] = trim((string) ($_POST['nim_nip'] ?? ''));
    $nilai['email']   = trim((string) ($_POST['email'] ?? ''));
    $pass             = (string) ($_POST['password'] ?? '');
    $pass2            = (string) ($_POST['password2'] ?? '');

    if (mb_strlen($nilai['nama']) < 3) {
        $err[] = 'Nama minimal 3 karakter.';
    }
    if (!preg_match('/^[A-Za-z0-9.]{3,20}$/', $nilai['nim_nip'])) {
        $err[] = 'NIM/NIP 3-20 karakter, hanya huruf, angka, dan titik.';
    }
    if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
        $err[] = 'Format email tidak valid.';
    }
    if (strlen($pass) < 8) {
        $err[] = 'Password minimal 8 karakter.';
    }
    if ($pass !== $pass2) {
        $err[] = 'Konfirmasi password tidak sama.';
    }

    if (!$err) {
        $cek = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $cek->execute([$nilai['email']]);
        if ($cek->fetchColumn()) {
            $err[] = 'Email sudah terdaftar. Gunakan email lain atau masuk.';
        }
    }

    if (!$err) {
        $st = $pdo->prepare(
            'INSERT INTO users (nama, nim_nip, email, password_hash, role) VALUES (?, ?, ?, ?, "pelapor")'
        );
        $st->execute([
            $nilai['nama'],
            $nilai['nim_nip'],
            $nilai['email'],
            password_hash($pass, PASSWORD_DEFAULT),
        ]);
        flash('success', 'Akun berhasil dibuat. Silakan masuk.');
        redirect('/login.php');
    }
}

$judul = 'Daftar Akun';
include __DIR__ . '/includes/header.php';
?>

<div class="mx-auto grid max-w-5xl gap-8 px-4 py-12 md:grid-cols-[0.9fr_1.1fr]">
  <div class="hidden md:block">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Registrasi</p>
    <h1 class="mt-2 text-2xl font-semibold text-navy">Satu akun untuk semua laporan Anda</h1>
    <div class="aksen-garis mt-4"></div>
    <ul class="mt-6 space-y-3 text-xs leading-relaxed text-muted">
      <li class="flex gap-2"><span class="text-gold">&#9679;</span> Semua laporan yang Anda kirim tersimpan dalam satu daftar.</li>
      <li class="flex gap-2"><span class="text-gold">&#9679;</span> Setiap laporan punya kode tiket yang bisa dibagikan.</li>
      <li class="flex gap-2"><span class="text-gold">&#9679;</span> Status penanganan terlihat lengkap dengan catatan petugas.</li>
      <li class="flex gap-2"><span class="text-gold">&#9679;</span> Identitas Anda tidak pernah tampil di halaman publik.</li>
    </ul>
  </div>

  <div class="card p-6">
    <h2 class="text-lg font-semibold text-navy">Buat akun pelapor</h2>
    <p class="mt-1 text-xs text-muted">Gunakan email kampus agar mudah dihubungi petugas.</p>

    <?php if ($err): ?>
      <div class="flash flash-error mt-4">
        <?php foreach ($err as $x): ?><div>&bull; <?= e($x) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-5" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="<?= e($nilai['nama']) ?>" required maxlength="100">
      </div>
      <div class="field">
        <label for="nim_nip">NIM / NIP</label>
        <input type="text" id="nim_nip" name="nim_nip" value="<?= e($nilai['nim_nip']) ?>" required maxlength="20">
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($nilai['email']) ?>" required maxlength="120">
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="8">
          <p class="hint">Minimal 8 karakter.</p>
        </div>
        <div class="field">
          <label for="password2">Ulangi password</label>
          <input type="password" id="password2" name="password2" required minlength="8">
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-full">Daftar</button>
      <p class="mt-3 text-center text-xs text-muted">
        Sudah punya akun? <a href="<?= BASE ?>/login.php" class="font-semibold text-navy underline decoration-gold underline-offset-2">Masuk</a>
      </p>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
