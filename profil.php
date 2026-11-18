<?php
require_once __DIR__ . '/includes/auth.php';
wajib_login();

$u   = pengguna();
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $lama  = (string) ($_POST['lama'] ?? '');
    $baru  = (string) ($_POST['baru'] ?? '');
    $ulang = (string) ($_POST['ulang'] ?? '');

    $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    $hash = (string) $st->fetchColumn();

    if (!password_verify($lama, $hash)) {
        $err[] = 'Password lama tidak sesuai.';
    }
    if (strlen($baru) < 8) {
        $err[] = 'Password baru minimal 8 karakter.';
    }
    if ($baru !== $ulang) {
        $err[] = 'Konfirmasi password baru tidak sama.';
    }

    if (!$err) {
        $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $up->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
        flash('success', 'Password berhasil diubah.');
        redirect('/profil.php');
    }
}

$st = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('selesai','ditolak')) AS selesai,
            SUM(status NOT IN ('selesai','ditolak')) AS berjalan
     FROM laporan WHERE user_id = ?"
);
$st->execute([$u['id']]);
$rekap = $st->fetch();

$judul = 'Profil';
include __DIR__ . '/includes/header.php';
?>

<div class="mx-auto grid max-w-5xl gap-6 px-4 py-12 md:grid-cols-[0.9fr_1.1fr]">
  <div class="card overflow-hidden">
    <div class="border-b border-line bg-navy px-5 py-6 text-white">
      <span class="grid h-12 w-12 place-items-center rounded-lg bg-white/10 text-base font-bold text-gold-soft">
        <?= e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) ?>
      </span>
      <h1 class="mt-3 text-lg font-semibold"><?= e($u['nama']) ?></h1>
      <p class="text-xs text-white/70"><?= e($u['email']) ?></p>
    </div>
    <dl class="divide-y divide-line text-xs">
      <div class="flex justify-between px-5 py-3"><dt class="text-muted">NIM / NIP</dt><dd class="font-medium"><?= e($u['nim_nip']) ?></dd></div>
      <div class="flex justify-between px-5 py-3"><dt class="text-muted">Peran</dt><dd class="font-medium capitalize"><?= e($u['role']) ?></dd></div>
      <div class="flex justify-between px-5 py-3"><dt class="text-muted">Total laporan</dt><dd class="font-medium"><?= (int) ($rekap['total'] ?? 0) ?></dd></div>
      <div class="flex justify-between px-5 py-3"><dt class="text-muted">Berjalan</dt><dd class="font-medium"><?= (int) ($rekap['berjalan'] ?? 0) ?></dd></div>
      <div class="flex justify-between px-5 py-3"><dt class="text-muted">Tuntas / ditolak</dt><dd class="font-medium"><?= (int) ($rekap['selesai'] ?? 0) ?></dd></div>
    </dl>
  </div>

  <div class="card p-6">
    <h2 class="text-lg font-semibold text-navy">Ubah password</h2>
    <p class="mt-1 text-xs text-muted">Gunakan password baru minimal 8 karakter.</p>

    <?php if ($err): ?>
      <div class="flash flash-error mt-4">
        <?php foreach ($err as $x): ?><div>&bull; <?= e($x) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-5" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="lama">Password lama</label>
        <input type="password" id="lama" name="lama" required>
      </div>
      <div class="field">
        <label for="baru">Password baru</label>
        <input type="password" id="baru" name="baru" required minlength="8">
      </div>
      <div class="field">
        <label for="ulang">Ulangi password baru</label>
        <input type="password" id="ulang" name="ulang" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary">Simpan password</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
