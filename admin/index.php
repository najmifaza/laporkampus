<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$statusSah = ['diterima', 'diverifikasi', 'dikerjakan', 'selesai', 'ditolak'];
$fStatus = (string) ($_GET['status'] ?? '');
if ($fStatus !== '' && !in_array($fStatus, $statusSah, true)) {
    $fStatus = '';
}
$fKategori = (int) ($_GET['kategori'] ?? 0);
$cari      = trim((string) ($_GET['q'] ?? ''));

$kategoriList = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();

$perHal = 10;
$hal    = max(1, (int) ($_GET['hal'] ?? 1));

$where  = ['1 = 1'];
$params = [];
if ($fStatus !== '') {
    $where[] = 'l.status = :status';
    $params['status'] = $fStatus;
}
if ($fKategori > 0) {
    $where[] = 'l.kategori_id = :kat';
    $params['kat'] = $fKategori;
}
if ($cari !== '') {
    $where[] = '(l.judul LIKE :q OR l.lokasi LIKE :q OR l.kode LIKE :q OR p.nama LIKE :q)';
    $params['q'] = '%' . $cari . '%';
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);

$stTotal = $pdo->prepare("SELECT COUNT(*) FROM laporan l JOIN users p ON p.id = l.user_id $sqlWhere");
$stTotal->execute($params);
$total    = (int) $stTotal->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);
$offset   = ($hal - 1) * $perHal;

$st = $pdo->prepare(
    "SELECT l.id, l.kode, l.judul, l.status, l.lokasi, l.dibuat, k.nama AS kategori, k.sla_hari, p.nama AS pelapor
     FROM laporan l
     JOIN kategori k ON k.id = l.kategori_id
     JOIN users p    ON p.id = l.user_id
     $sqlWhere
     ORDER BY FIELD(l.status, 'diterima','diverifikasi','dikerjakan','selesai','ditolak'), l.dibuat DESC
     LIMIT $perHal OFFSET $offset"
);
$st->execute($params);
$daftar = $st->fetchAll();

// Rekap
$stat = $pdo->query(
    "SELECT
       COUNT(*) AS total,
       SUM(status = 'diterima')     AS diterima,
       SUM(status = 'diverifikasi') AS diverifikasi,
       SUM(status = 'dikerjakan')   AS dikerjakan,
       SUM(status = 'selesai')      AS selesai,
       SUM(status = 'ditolak')      AS ditolak
     FROM laporan"
)->fetch();

$perKategori = $pdo->query(
    "SELECT k.nama, k.sla_hari, COUNT(l.id) AS jumlah,
            SUM(l.status NOT IN ('selesai','ditolak')) AS berjalan
     FROM kategori k LEFT JOIN laporan l ON l.kategori_id = k.id
     GROUP BY k.id ORDER BY jumlah DESC"
)->fetchAll();

$perLokasi = $pdo->query(
    "SELECT lokasi, COUNT(*) AS jumlah FROM laporan GROUP BY lokasi ORDER BY jumlah DESC LIMIT 5"
)->fetchAll();

$judul = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-6xl px-4 py-10">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Unit sarpras</p>
      <h1 class="mt-2 text-2xl font-semibold text-navy">Dashboard penanganan</h1>
      <p class="mt-1 text-xs text-muted"><?= (int) $stat['total'] ?> laporan tercatat, <?= (int) $stat['diterima'] ?> belum diverifikasi.</p>
    </div>
    <div class="flex gap-2">
      <a href="<?= BASE ?>/admin/kategori.php" class="btn btn-ghost btn-sm">Kelola kategori</a>
      <a href="<?= BASE ?>/admin/pengguna.php" class="btn btn-ghost btn-sm">Kelola pengguna</a>
    </div>
  </div>

  <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
    <?php
    $kartu = [
        ['Diterima', $stat['diterima'], 'badge-diterima'],
        ['Diverifikasi', $stat['diverifikasi'], 'badge-diverifikasi'],
        ['Dikerjakan', $stat['dikerjakan'], 'badge-dikerjakan'],
        ['Selesai', $stat['selesai'], 'badge-selesai'],
        ['Ditolak', $stat['ditolak'], 'badge-ditolak'],
    ];
    foreach ($kartu as [$label, $nilai, $kelas]): ?>
      <div class="card px-4 py-3">
        <p class="text-xl font-semibold text-navy"><?= (int) $nilai ?></p>
        <p class="mt-1 text-[10px] uppercase tracking-wider text-muted"><?= e($label) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-[1.55fr_0.45fr]">
    <div class="card overflow-hidden">
      <div class="card-head flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-navy">Daftar laporan</h2>
        <span class="text-[11px] text-muted"><?= $total ?> hasil</span>
      </div>

      <form method="get" class="grid gap-2 border-b border-line bg-white px-4 py-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
        <div class="field mb-0">
          <label for="q">Cari</label>
          <input type="text" id="q" name="q" value="<?= e($cari) ?>" placeholder="Judul, lokasi, kode, pelapor">
        </div>
        <div class="field mb-0 sm:w-40">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">Semua</option>
            <?php foreach ($statusSah as $s): ?>
              <option value="<?= e($s) ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field mb-0 sm:w-48">
          <label for="kategori">Kategori</label>
          <select id="kategori" name="kategori">
            <option value="0">Semua</option>
            <?php foreach ($kategoriList as $k): ?>
              <option value="<?= (int) $k['id'] ?>" <?= $fKategori === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-ghost">Terapkan</button>
      </form>

      <div class="overflow-x-auto">
        <table class="tabel">
          <thead>
            <tr>
              <th>Tiket</th>
              <th>Laporan</th>
              <th>Pelapor</th>
              <th>Status</th>
              <th>Umur</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$daftar): ?>
            <tr><td colspan="6" class="py-8 text-center text-xs text-muted">Tidak ada laporan pada filter ini.</td></tr>
          <?php endif; ?>
          <?php foreach ($daftar as $l):
              $umur  = umur_hari($l['dibuat']);
              $final = in_array($l['status'], ['selesai', 'ditolak'], true);
              $lewat = !$final && $umur > (int) $l['sla_hari'];
          ?>
            <tr data-cari="<?= e($l['judul'] . ' ' . $l['lokasi'] . ' ' . $l['kode'] . ' ' . $l['pelapor']) ?>">
              <td class="whitespace-nowrap font-mono text-[11px] text-muted"><?= e($l['kode']) ?></td>
              <td>
                <p class="font-medium text-navy"><?= e($l['judul']) ?></p>
                <p class="mt-0.5 text-[11px] text-muted"><?= e($l['kategori']) ?> &middot; <?= e($l['lokasi']) ?></p>
              </td>
              <td class="whitespace-nowrap text-[11px]"><?= e($l['pelapor']) ?></td>
              <td><?= badge_status($l['status']) ?></td>
              <td class="whitespace-nowrap text-[11px] text-muted">
                <?= $umur ?> hari
                <?php if ($lewat): ?><br><span class="text-[10px] font-semibold text-[#b91c1c]">lewat batas</span><?php endif; ?>
              </td>
              <td class="whitespace-nowrap text-right">
                <a href="<?= BASE ?>/laporan/detail.php?id=<?= (int) $l['id'] ?>" class="btn btn-ghost btn-sm">Buka</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalHal > 1): ?>
        <nav class="flex items-center justify-between border-t border-line px-4 py-3 text-xs">
          <span class="text-muted">Halaman <?= $hal ?> dari <?= $totalHal ?></span>
          <div class="flex gap-2">
            <?php
            $qs = function (int $p) use ($fStatus, $fKategori, $cari) {
                $q = ['hal' => $p];
                if ($fStatus !== '') { $q['status'] = $fStatus; }
                if ($fKategori > 0)  { $q['kategori'] = $fKategori; }
                if ($cari !== '')    { $q['q'] = $cari; }
                return BASE . '/admin/index.php?' . http_build_query($q);
            };
            ?>
            <?php if ($hal > 1): ?><a href="<?= e($qs($hal - 1)) ?>" class="btn btn-ghost btn-sm">Sebelumnya</a><?php endif; ?>
            <?php if ($hal < $totalHal): ?><a href="<?= e($qs($hal + 1)) ?>" class="btn btn-ghost btn-sm">Berikutnya</a><?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>
    </div>

    <div class="space-y-6">
      <div class="card p-5">
        <h2 class="text-sm font-semibold text-navy">Rekap kategori</h2>
        <table class="tabel mt-3">
          <thead><tr><th>Kategori</th><th>Total</th><th>Proses</th></tr></thead>
          <tbody>
          <?php foreach ($perKategori as $k): ?>
            <tr>
              <td class="text-[11px]"><?= e($k['nama']) ?><br><span class="text-[10px] text-muted">target <?= (int) $k['sla_hari'] ?> hari</span></td>
              <td class="text-[11px] font-semibold"><?= (int) $k['jumlah'] ?></td>
              <td class="text-[11px]"><?= (int) $k['berjalan'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card p-5">
        <h2 class="text-sm font-semibold text-navy">Lokasi tersering</h2>
        <?php if (!$perLokasi): ?>
          <p class="mt-3 text-xs text-muted">Belum ada data.</p>
        <?php else: ?>
          <ul class="mt-3 space-y-2 text-xs">
            <?php foreach ($perLokasi as $lk): ?>
              <li class="flex items-start justify-between gap-2">
                <span class="text-navy-dark"><?= e($lk['lokasi']) ?></span>
                <span class="shrink-0 font-semibold text-navy"><?= (int) $lk['jumlah'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
