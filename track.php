<?php
require_once __DIR__ . '/includes/auth.php';

$kode   = strtoupper(trim((string) ($_GET['kode'] ?? '')));
$kode   = preg_replace('/\s+/', '', $kode);
$laporan = null;
$riwayat = [];

if ($kode !== '') {
    // Whitelist kolom: identitas pelapor sengaja tidak diambil
    $st = $pdo->prepare(
        'SELECT l.kode, l.judul, l.deskripsi, l.lokasi, l.status, l.dibuat,
                k.nama AS kategori, k.sla_hari
         FROM laporan l JOIN kategori k ON k.id = l.kategori_id
         WHERE l.kode = ?'
    );
    $st->execute([$kode]);
    $laporan = $st->fetch();

    if ($laporan) {
        $sr = $pdo->prepare('SELECT status, catatan, waktu FROM riwayat WHERE laporan_id = (SELECT id FROM laporan WHERE kode = ?) ORDER BY waktu ASC, id ASC');
        $sr->execute([$kode]);
        $riwayat = $sr->fetchAll();
    }
}

$judul = 'Lacak Tiket';
include __DIR__ . '/includes/header.php';
?>

<section class="border-b border-line bg-navy-light">
  <div class="mx-auto max-w-3xl px-4 py-12">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Pelacakan publik</p>
    <h1 class="mt-2 text-2xl font-semibold text-navy sm:text-3xl">Lacak status laporan</h1>
    <p class="mt-2 max-w-xl text-sm text-muted">
      Masukkan kode tiket. Hasil pelacakan tidak memuat nama, NIM/NIP, email, maupun foto pelapor.
    </p>

    <form method="get" action="<?= BASE ?>/track.php" class="mt-6 flex flex-col gap-2 sm:flex-row">
      <input type="text" name="kode" value="<?= e($kode) ?>" placeholder="LP-20261118-0001"
             maxlength="20" required
             class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-navy focus:outline-none focus:ring-2 focus:ring-navy/15">
      <button type="submit" class="btn btn-primary shrink-0">Lacak</button>
    </form>
  </div>
</section>

<div class="mx-auto max-w-3xl px-4 py-10">
<?php if ($kode === ''): ?>
  <div class="card p-6 text-sm text-muted">
    Belum ada kode dimasukkan. Kode tiket berformat <span class="font-mono text-navy">LP-YYYYMMDD-NNNN</span>
    dan dikirim saat laporan berhasil dibuat.
  </div>
<?php elseif (!$laporan): ?>
  <div class="card border-[#fecaca] p-6">
    <p class="text-sm font-semibold text-[#991b1b]">Kode tiket tidak ditemukan</p>
    <p class="mt-1 text-xs text-muted">Periksa kembali kode <span class="font-mono"><?= e($kode) ?></span>. Pastikan tidak ada karakter tertukar.</p>
  </div>
<?php else: ?>
  <?php
  $umur = umur_hari($laporan['dibuat']);
  $final = in_array($laporan['status'], ['selesai', 'ditolak'], true);
  $lewat = !$final && $umur > (int) $laporan['sla_hari'];
  ?>
  <div class="card overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line bg-navy-light px-5 py-4">
      <div>
        <p class="font-mono text-xs text-muted"><?= e($laporan['kode']) ?></p>
        <h2 class="mt-1 text-lg font-semibold text-navy"><?= e($laporan['judul']) ?></h2>
        <p class="mt-1 text-xs text-muted">
          <?= e($laporan['kategori']) ?> &middot; <?= e($laporan['lokasi']) ?> &middot;
          dilaporkan <?= date('d M Y H:i', strtotime($laporan['dibuat'])) ?>
        </p>
      </div>
      <div class="flex flex-col items-end gap-1">
        <?= badge_status($laporan['status']) ?>
        <?php if ($lewat): ?><span class="badge badge-lewat">Lewat batas <?= (int) $laporan['sla_hari'] ?> hari</span><?php endif; ?>
      </div>
    </div>

    <div class="px-5 py-4">
      <p class="text-sm leading-relaxed text-navy-dark"><?= nl2br(e($laporan['deskripsi'])) ?></p>
    </div>

    <div class="border-t border-line px-5 py-5">
      <h3 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold">Jejak penanganan</h3>
      <div class="timeline mt-4">
        <?php foreach ($riwayat as $r): ?>
          <div class="timeline-item <?= in_array($r['status'], ['selesai', 'ditolak'], true) ? 'final' : '' ?>">
            <div class="flex flex-wrap items-center gap-2">
              <?= badge_status($r['status']) ?>
              <span class="timeline-waktu"><?= date('d M Y H:i', strtotime($r['waktu'])) ?></span>
            </div>
            <?php if (!empty($r['catatan'])): ?>
              <p class="mt-1 text-xs text-navy-dark"><?= e($r['catatan']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <p class="mt-4 text-[11px] text-muted">
    Butuh menanggapi laporan ini? <a href="<?= BASE ?>/login.php" class="font-semibold text-navy underline decoration-gold underline-offset-2">Masuk</a>
    dengan akun pelapor agar dapat menambah tanggapan.
  </p>
<?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
