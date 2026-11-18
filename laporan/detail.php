<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_login();

$u   = pengguna();
$id  = (int) ($_GET['id'] ?? 0);
$err = [];

$st = $pdo->prepare(
    'SELECT l.*, k.nama AS kategori, k.sla_hari, p.nama AS pelapor
     FROM laporan l
     JOIN kategori k ON k.id = l.kategori_id
     JOIN users p    ON p.id = l.user_id
     WHERE l.id = ?'
);
$st->execute([$id]);
$lap = $st->fetch();

if (!$lap) {
    http_response_code(404);
    $judul = 'Laporan tidak ditemukan';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-16 text-center"><p class="text-sm text-muted">Laporan tidak ditemukan.</p>'
       . '<a href="' . BASE . '/laporan/index.php" class="btn btn-primary mt-5">Kembali</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$pemilik = (int) $lap['user_id'] === (int) $u['id'];
$admin   = $u['role'] === 'admin';

if (!$pemilik && !$admin) {
    http_response_code(403);
    $judul = 'Akses ditolak';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-16 text-center"><p class="text-sm font-semibold text-[#991b1b]">Anda tidak berhak membuka laporan ini.</p>'
       . '<a href="' . BASE . '/laporan/index.php" class="btn btn-primary mt-5">Kembali ke laporan saya</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanggapan'])) {
    csrf_check();
    $isi = trim((string) $_POST['tanggapan']);
    if (mb_strlen($isi) < 3) {
        $err[] = 'Tanggapan minimal 3 karakter.';
    }
    if (mb_strlen($isi) > 1000) {
        $err[] = 'Tanggapan maksimal 1000 karakter.';
    }
    if (!$err) {
        $pdo->prepare('INSERT INTO tanggapan (laporan_id, user_id, isi) VALUES (?, ?, ?)')
            ->execute([$id, $u['id'], $isi]);
        flash('success', 'Tanggapan terkirim.');
        redirect('/laporan/detail.php?id=' . $id);
    }
}

$sr = $pdo->prepare('SELECT status, catatan, waktu FROM riwayat WHERE laporan_id = ? ORDER BY waktu ASC, id ASC');
$sr->execute([$id]);
$riwayat = $sr->fetchAll();

$st2 = $pdo->prepare(
    'SELECT t.isi, t.waktu, u.nama, u.role
     FROM tanggapan t JOIN users u ON u.id = t.user_id
     WHERE t.laporan_id = ? ORDER BY t.waktu ASC, t.id ASC'
);
$st2->execute([$id]);
$tanggapan = $st2->fetchAll();

$umur  = umur_hari($lap['dibuat']);
$final = in_array($lap['status'], ['selesai', 'ditolak'], true);
$lewat = !$final && $umur > (int) $lap['sla_hari'];

$judul = 'Detail Laporan';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-4xl px-4 py-10">
  <a href="<?= $admin ? BASE . '/admin/index.php' : BASE . '/laporan/index.php' ?>"
     class="text-xs font-semibold text-navy underline decoration-gold underline-offset-4">
    &larr; <?= $admin ? 'Kembali ke dashboard' : 'Kembali ke laporan saya' ?>
  </a>

  <div class="card mt-4 overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line bg-navy-light px-5 py-4">
      <div class="min-w-0">
        <p class="font-mono text-xs text-muted"><?= e($lap['kode']) ?></p>
        <h1 class="mt-1 text-xl font-semibold text-navy"><?= e($lap['judul']) ?></h1>
        <p class="mt-1 text-xs text-muted">
          <?= e($lap['kategori']) ?> &middot; <?= e($lap['lokasi']) ?> &middot;
          <?= date('d M Y H:i', strtotime($lap['dibuat'])) ?> &middot; <?= $umur ?> hari
        </p>
        <?php if ($admin): ?>
          <p class="mt-1 text-[11px] text-muted">Pelapor: <?= e($lap['pelapor']) ?> &middot; target <?= (int) $lap['sla_hari'] ?> hari</p>
        <?php endif; ?>
      </div>
      <div class="flex flex-col items-end gap-1">
        <?= badge_status($lap['status']) ?>
        <?php if ($lewat): ?><span class="badge badge-lewat">Lewat batas</span><?php endif; ?>
      </div>
    </div>

    <div class="grid gap-6 px-5 py-5 md:grid-cols-[1.2fr_0.8fr]">
      <div>
        <p class="text-sm leading-relaxed text-navy-dark"><?= nl2br(e($lap['deskripsi'])) ?></p>
        <?php if (!empty($lap['foto'])): ?>
          <a href="<?= BASE . '/' . e($lap['foto']) ?>" target="_blank" rel="noopener" class="mt-4 inline-block">
            <img src="<?= BASE . '/' . e($lap['foto']) ?>" alt="Foto bukti laporan <?= e($lap['kode']) ?>"
                 class="h-48 w-auto rounded-lg border border-line object-cover">
          </a>
        <?php endif; ?>
      </div>

      <div class="rounded-lg border border-line bg-navy-light/60 p-4">
        <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold">Jejak penanganan</h2>
        <div class="timeline mt-4">
          <?php foreach ($riwayat as $r): ?>
            <div class="timeline-item <?= in_array($r['status'], ['selesai', 'ditolak'], true) ? 'final' : '' ?>">
              <div class="flex flex-wrap items-center gap-2">
                <?= badge_status($r['status']) ?>
                <span class="timeline-waktu"><?= date('d M H:i', strtotime($r['waktu'])) ?></span>
              </div>
              <?php if (!empty($r['catatan'])): ?>
                <p class="mt-1 text-xs text-navy-dark"><?= e($r['catatan']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($admin && !$final): ?>
      <div class="border-t border-line bg-white px-5 py-5">
        <h2 class="text-sm font-semibold text-navy">Perbarui status</h2>
        <p class="mt-1 text-[11px] text-muted">
          Transisi sah dari status <strong><?= e(status_label($lap['status'])) ?></strong>:
          <?php
          $pilihan = ['diterima' => ['diverifikasi', 'ditolak'], 'diverifikasi' => ['dikerjakan'], 'dikerjakan' => ['selesai']][$lap['status']] ?? [];
          echo $pilihan ? e(implode(' atau ', array_map('status_label', $pilihan))) : 'tidak ada (status final)';
          ?>.
        </p>
        <?php if ($pilihan): ?>
          <form method="post" action="<?= BASE ?>/laporan/status.php" class="mt-3 grid gap-3 sm:grid-cols-[0.35fr_1fr_auto] sm:items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $lap['id'] ?>">
            <div class="field mb-0">
              <label for="status">Status baru</label>
              <select id="status" name="status" required>
                <?php foreach ($pilihan as $p): ?>
                  <option value="<?= e($p) ?>"><?= e(status_label($p)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field mb-0">
              <label for="catatan">Catatan (wajib)</label>
              <input type="text" id="catatan" name="catatan" minlength="5" maxlength="255" required
                     placeholder="Contoh: teknisi dijadwalkan Selasa pagi">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mt-6 grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
    <div class="card p-5">
      <h2 class="text-sm font-semibold text-navy">Tanggapan</h2>

      <?php if ($err): ?>
        <div class="flash flash-error mt-3">
          <?php foreach ($err as $x): ?><div>&bull; <?= e($x) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="mt-4 space-y-3">
        <?php if (!$tanggapan): ?>
          <p class="text-xs text-muted">Belum ada tanggapan.</p>
        <?php endif; ?>
        <?php foreach ($tanggapan as $t): ?>
          <div class="rounded-lg border border-line bg-navy-light/50 px-4 py-3">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-semibold text-navy"><?= e($t['nama']) ?></span>
              <span class="rounded-full bg-white px-2 py-0.5 text-[10px] uppercase tracking-wider text-muted"><?= e($t['role']) ?></span>
              <span class="text-[10px] text-muted"><?= date('d M Y H:i', strtotime($t['waktu'])) ?></span>
            </div>
            <p class="mt-1 text-xs leading-relaxed text-navy-dark"><?= nl2br(e($t['isi'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($pemilik || $admin): ?>
        <form method="post" class="mt-4" novalidate>
          <?= csrf_field() ?>
          <div class="field">
            <label for="tanggapan">Tulis tanggapan</label>
            <textarea id="tanggapan" name="tanggapan" maxlength="1000" required
                      class="!min-h-[5rem]" placeholder="Tambahkan informasi atau pertanyaan."></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Kirim tanggapan</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="card p-5">
      <h2 class="text-sm font-semibold text-navy">Tindakan</h2>
      <ul class="mt-3 space-y-2 text-xs text-muted">
        <li>Kode tiket dapat dibagikan untuk pelacakan publik.</li>
        <li>Status hanya berubah lewat admin, dengan catatan wajib.</li>
      </ul>
      <a href="<?= BASE ?>/track.php?kode=<?= e($lap['kode']) ?>" class="btn btn-ghost btn-sm mt-4 w-full">Lihat halaman lacak</a>

      <?php if ($admin || ($pemilik && $lap['status'] === 'diterima')): ?>
        <form method="post" action="<?= BASE ?>/laporan/hapus.php" class="mt-3"
              onsubmit="return confirm('Hapus laporan ini? Tindakan tidak dapat dibatalkan.');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $lap['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm w-full">Hapus laporan</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
