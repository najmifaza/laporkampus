<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$err   = [];
$edit  = null;
$nilai = ['nama' => '', 'sla_hari' => '7'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'simpan') {
        $id       = (int) ($_POST['id'] ?? 0);
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $sla      = (int) ($_POST['sla_hari'] ?? 0);
        $nilai    = ['nama' => $nama, 'sla_hari' => (string) $sla];

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 80) {
            $err[] = 'Nama kategori 3-80 karakter.';
        }
        if ($sla < 1 || $sla > 365) {
            $err[] = 'SLA harus 1-365 hari.';
        }
        if (!$err) {
            $cek = $pdo->prepare('SELECT 1 FROM kategori WHERE nama = ? AND id <> ?');
            $cek->execute([$nama, $id]);
            if ($cek->fetchColumn()) {
                $err[] = 'Nama kategori sudah dipakai.';
            }
        }
        if (!$err) {
            if ($id > 0) {
                $pdo->prepare('UPDATE kategori SET nama = ?, sla_hari = ? WHERE id = ?')->execute([$nama, $sla, $id]);
                flash('success', 'Kategori diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO kategori (nama, sla_hari) VALUES (?, ?)')->execute([$nama, $sla]);
                flash('success', 'Kategori ditambahkan.');
            }
            redirect('/admin/kategori.php');
        }
    }

    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE kategori_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            flash('error', 'Kategori masih dipakai laporan, tidak bisa dihapus.');
        } else {
            $pdo->prepare('DELETE FROM kategori WHERE id = ?')->execute([$id]);
            flash('success', 'Kategori dihapus.');
        }
        redirect('/admin/kategori.php');
    }
}

if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT id, nama, sla_hari FROM kategori WHERE id = ?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
    if ($edit) {
        $nilai = ['nama' => $edit['nama'], 'sla_hari' => (string) $edit['sla_hari']];
    }
}

$daftar = $pdo->query(
    'SELECT k.id, k.nama, k.sla_hari, COUNT(l.id) AS dipakai
     FROM kategori k LEFT JOIN laporan l ON l.kategori_id = k.id
     GROUP BY k.id ORDER BY k.nama'
)->fetchAll();

$judul = 'Kelola Kategori';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-5xl px-4 py-10">
  <a href="<?= BASE ?>/admin/index.php" class="text-xs font-semibold text-navy underline decoration-gold underline-offset-4">&larr; Dashboard</a>
  <h1 class="mt-3 text-2xl font-semibold text-navy">Kategori kerusakan</h1>
  <p class="mt-1 text-xs text-muted">SLA menentukan batas hari penanganan. Kategori yang sudah dipakai laporan tidak dapat dihapus.</p>

  <?php if ($err): ?>
    <div class="flash flash-error mt-5">
      <?php foreach ($err as $x): ?><div>&bull; <?= e($x) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="mt-6 grid gap-6 md:grid-cols-[0.75fr_1.25fr]">
    <div class="card p-5">
      <h2 class="text-sm font-semibold text-navy"><?= $edit ? 'Ubah kategori' : 'Tambah kategori' ?></h2>
      <form method="post" class="mt-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        <div class="field">
          <label for="nama">Nama kategori</label>
          <input type="text" id="nama" name="nama" value="<?= e($nilai['nama']) ?>" maxlength="80" required>
        </div>
        <div class="field">
          <label for="sla_hari">SLA (hari)</label>
          <input type="number" id="sla_hari" name="sla_hari" value="<?= e($nilai['sla_hari']) ?>" min="1" max="365" required>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan perubahan' : 'Tambah' ?></button>
          <?php if ($edit): ?>
            <a href="<?= BASE ?>/admin/kategori.php" class="btn btn-ghost">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card overflow-hidden">
      <div class="card-head"><h2 class="text-sm font-semibold text-navy">Daftar kategori</h2></div>
      <div class="overflow-x-auto">
        <table class="tabel">
          <thead><tr><th>Nama</th><th>SLA</th><th>Dipakai</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($daftar as $k): ?>
            <tr>
              <td class="text-xs font-medium text-navy"><?= e($k['nama']) ?></td>
              <td class="whitespace-nowrap text-[11px]"><?= (int) $k['sla_hari'] ?> hari</td>
              <td class="text-[11px]"><?= (int) $k['dipakai'] ?> laporan</td>
              <td class="whitespace-nowrap text-right">
                <a href="<?= BASE ?>/admin/kategori.php?edit=<?= (int) $k['id'] ?>" class="btn btn-ghost btn-sm">Ubah</a>
                <form method="post" class="inline" onsubmit="return confirm('Hapus kategori ini?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm" <?= (int) $k['dipakai'] > 0 ? 'disabled title="Masih dipakai laporan"' : '' ?>>Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
