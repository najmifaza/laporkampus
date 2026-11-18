<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_login();

$u   = pengguna();
$fStatus = (string) ($_GET['status'] ?? '');
$statusSah = ['diterima', 'diverifikasi', 'dikerjakan', 'selesai', 'ditolak'];
if ($fStatus !== '' && !in_array($fStatus, $statusSah, true)) {
    $fStatus = '';
}
$cari = trim((string) ($_GET['q'] ?? ''));

$perHal = 10;
$hal    = max(1, (int) ($_GET['hal'] ?? 1));
$offset = ($hal - 1) * $perHal;

$where  = ['l.user_id = :uid'];
$params = ['uid' => $u['id']];
if ($fStatus !== '') {
    $where[] = 'l.status = :status';
    $params['status'] = $fStatus;
}
if ($cari !== '') {
    $where[] = '(l.judul LIKE :q OR l.lokasi LIKE :q OR l.kode LIKE :q)';
    $params['q'] = '%' . $cari . '%';
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);

$stTotal = $pdo->prepare("SELECT COUNT(*) FROM laporan l $sqlWhere");
$stTotal->execute($params);
$total = (int) $stTotal->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal = min($hal, $totalHal);
$offset = ($hal - 1) * $perHal;

$st = $pdo->prepare(
    "SELECT l.id, l.kode, l.judul, l.status, l.lokasi, l.dibuat, k.nama AS kategori, k.sla_hari
     FROM laporan l JOIN kategori k ON k.id = l.kategori_id
     $sqlWhere
     ORDER BY l.dibuat DESC
     LIMIT $perHal OFFSET $offset"
);
$st->execute($params);
$daftar = $st->fetchAll();

$judul = 'Laporan Saya';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-6xl px-4 py-10">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Ruang pelapor</p>
      <h1 class="mt-2 text-2xl font-semibold text-navy">Laporan saya</h1>
      <p class="mt-1 text-xs text-muted"><?= $total ?> laporan tercatat atas nama <?= e($u['nama']) ?>.</p>
    </div>
    <a href="<?= BASE ?>/laporan/buat.php" class="btn btn-primary">Buat laporan baru</a>
  </div>

  <form method="get" class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-end">
    <div class="field mb-0 flex-1">
      <label for="q">Cari</label>
      <input type="text" id="q" name="q" value="<?= e($cari) ?>" placeholder="Judul, lokasi, atau kode tiket">
    </div>
    <div class="field mb-0 sm:w-52">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Semua status</option>
        <?php foreach ($statusSah as $s): ?>
          <option value="<?= e($s) ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-ghost">Terapkan</button>
  </form>

  <?php if (!$daftar): ?>
    <div class="card mt-6 p-8 text-center">
      <p class="text-sm font-medium text-navy">Belum ada laporan pada filter ini.</p>
      <p class="mt-1 text-xs text-muted">Temukan kerusakan? Buat laporan agar masuk antrean sarpras.</p>
      <a href="<?= BASE ?>/laporan/buat.php" class="btn btn-gold mt-5">Buat laporan</a>
    </div>
  <?php else: ?>
    <div class="mt-6 space-y-3">
      <?php foreach ($daftar as $l):
          $umur  = umur_hari($l['dibuat']);
          $final = in_array($l['status'], ['selesai', 'ditolak'], true);
          $lewat = !$final && $umur > (int) $l['sla_hari'];
      ?>
        <a href="<?= BASE ?>/laporan/detail.php?id=<?= (int) $l['id'] ?>"
           class="card block p-5 transition hover:border-navy-mid hover:shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-navy"><?= e($l['judul']) ?></p>
              <p class="mt-1 text-[11px] text-muted">
                <?= e($l['kategori']) ?> &middot; <?= e($l['lokasi']) ?>
              </p>
              <p class="mt-1 font-mono text-[11px] text-muted"><?= e($l['kode']) ?></p>
            </div>
            <div class="flex flex-col items-end gap-1">
              <?= badge_status($l['status']) ?>
              <span class="text-[10px] text-muted"><?= $umur ?> hari sejak dilaporkan</span>
              <?php if ($lewat): ?><span class="badge badge-lewat">Lewat batas</span><?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($totalHal > 1): ?>
      <nav class="mt-6 flex items-center justify-between text-xs">
        <span class="text-muted">Halaman <?= $hal ?> dari <?= $totalHal ?></span>
        <div class="flex gap-2">
          <?php
          $qs = function (int $p) use ($fStatus, $cari) {
              $q = ['hal' => $p];
              if ($fStatus !== '') { $q['status'] = $fStatus; }
              if ($cari !== '') { $q['q'] = $cari; }
              return BASE . '/laporan/index.php?' . http_build_query($q);
          };
          ?>
          <?php if ($hal > 1): ?><a href="<?= e($qs($hal - 1)) ?>" class="btn btn-ghost btn-sm">Sebelumnya</a><?php endif; ?>
          <?php if ($hal < $totalHal): ?><a href="<?= e($qs($hal + 1)) ?>" class="btn btn-ghost btn-sm">Berikutnya</a><?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
