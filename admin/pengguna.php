<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$u   = pengguna();
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id   = (int) ($_POST['id'] ?? 0);
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($id === (int) $u['id'] && in_array($aksi, ['admin', 'pelapor', 'nonaktif'], true)) {
        flash('error', 'Anda tidak dapat mengubah peran atau menonaktifkan akun sendiri.');
        redirect('/admin/pengguna.php');
    }

    if ($aksi === 'admin' || $aksi === 'pelapor') {
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$aksi, $id]);
        flash('success', 'Peran pengguna diperbarui menjadi ' . $aksi . '.');
    } elseif ($aksi === 'aktif' || $aksi === 'nonaktif') {
        $pdo->prepare('UPDATE users SET aktif = ? WHERE id = ?')->execute([$aksi === 'aktif' ? 1 : 0, $id]);
        flash('success', 'Status akun diperbarui.');
    }
    redirect('/admin/pengguna.php');
}

$daftar = $pdo->query(
    'SELECT u.id, u.nama, u.nim_nip, u.email, u.role, u.aktif, u.dibuat, COUNT(l.id) AS laporan
     FROM users u LEFT JOIN laporan l ON l.user_id = u.id
     GROUP BY u.id ORDER BY u.role, u.nama'
)->fetchAll();

$judul = 'Kelola Pengguna';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-5xl px-4 py-10">
  <a href="<?= BASE ?>/admin/index.php" class="text-xs font-semibold text-navy underline decoration-gold underline-offset-4">&larr; Dashboard</a>
  <h1 class="mt-3 text-2xl font-semibold text-navy">Pengguna</h1>
  <p class="mt-1 text-xs text-muted">Akun nonaktif tidak dapat masuk. Peran admin memberi akses ke dashboard dan perubahan status.</p>

  <div class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="tabel">
        <thead>
          <tr><th>Nama</th><th>NIM / NIP</th><th>Email</th><th>Peran</th><th>Laporan</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $p): $diri = (int) $p['id'] === (int) $u['id']; ?>
          <tr>
            <td class="text-xs font-medium text-navy">
              <?= e($p['nama']) ?><?= $diri ? ' <span class="text-[10px] text-muted">(Anda)</span>' : '' ?>
            </td>
            <td class="whitespace-nowrap text-[11px]"><?= e($p['nim_nip']) ?></td>
            <td class="text-[11px]"><?= e($p['email']) ?></td>
            <td class="text-[11px] capitalize"><?= e($p['role']) ?></td>
            <td class="text-[11px]"><?= (int) $p['laporan'] ?></td>
            <td>
              <span class="badge <?= (int) $p['aktif'] ? 'badge-selesai' : 'badge-ditolak' ?>">
                <?= (int) $p['aktif'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td class="whitespace-nowrap text-right">
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="aksi" value="<?= $p['role'] === 'admin' ? 'pelapor' : 'admin' ?>">
                <button type="submit" class="btn btn-ghost btn-sm" <?= $diri ? 'disabled' : '' ?>>
                  <?= $p['role'] === 'admin' ? 'Jadikan pelapor' : 'Jadikan admin' ?>
                </button>
              </form>
              <form method="post" class="inline" onsubmit="return confirm('Ubah status akun ini?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="aksi" value="<?= (int) $p['aktif'] ? 'nonaktif' : 'aktif' ?>">
                <button type="submit" class="btn btn-ghost btn-sm" <?= $diri ? 'disabled' : '' ?>>
                  <?= (int) $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
