<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_pelapor();   // admin hanya memakai dashboard

$u   = pengguna();
$err = [];
$nilai = ['kategori_id' => '', 'judul' => '', 'deskripsi' => '', 'lokasi' => ''];

$kategori = $pdo->query('SELECT id, nama, sla_hari FROM kategori ORDER BY nama')->fetchAll();
$dirUnggah = dirname(__DIR__) . '/uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nilai['kategori_id'] = (string) ($_POST['kategori_id'] ?? '');
    $nilai['judul']       = trim((string) ($_POST['judul'] ?? ''));
    $nilai['deskripsi']   = trim((string) ($_POST['deskripsi'] ?? ''));
    $nilai['lokasi']      = trim((string) ($_POST['lokasi'] ?? ''));

    $idKategoriSah = array_map(static fn($k) => (string) $k['id'], $kategori);
    if (!in_array($nilai['kategori_id'], $idKategoriSah, true)) {
        $err[] = 'Pilih kategori kerusakan yang tersedia.';
    }
    if (mb_strlen($nilai['judul']) < 10 || mb_strlen($nilai['judul']) > 120) {
        $err[] = 'Judul harus 10-120 karakter.';
    }
    if (mb_strlen($nilai['deskripsi']) < 20) {
        $err[] = 'Deskripsi minimal 20 karakter agar petugas paham masalahnya.';
    }
    if (mb_strlen($nilai['lokasi']) < 3) {
        $err[] = 'Lokasi wajib diisi (gedung, ruang, lantai).';
    }

    // Verifikasi foto di server, bukan percaya atribut accept di browser
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        if (($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $err[] = 'Unggahan foto gagal. Coba lagi.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $err[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
            $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($izin[$mime])) {
                $err[] = 'Foto harus berformat JPG atau PNG.';
            } else {
                $foto = 'uploads/' . bin2hex(random_bytes(16)) . '.' . $izin[$mime];
            }
        }
    }

    if (!$err) {
        if ($foto && !move_uploaded_file($_FILES['foto']['tmp_name'], dirname(__DIR__) . '/' . $foto)) {
            $err[] = 'Foto gagal disimpan di server.';
        } else {
            try {
                $pdo->beginTransaction();
                $kode = kode_tiket($pdo);
                $ins  = $pdo->prepare(
                    'INSERT INTO laporan (kode, user_id, kategori_id, judul, deskripsi, lokasi, foto, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "diterima")'
                );
                $ins->execute([
                    $kode,
                    $u['id'],
                    (int) $nilai['kategori_id'],
                    $nilai['judul'],
                    $nilai['deskripsi'],
                    $nilai['lokasi'],
                    $foto,
                ]);
                $idLaporan = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO riwayat (laporan_id, status, catatan, oleh_id) VALUES (?, ?, ?, NULL)')
                    ->execute([$idLaporan, 'diterima', 'Laporan diterima sistem']);
                $pdo->commit();

                flash('success', 'Laporan terkirim. Kode tiket Anda: ' . $kode);
                redirect('/laporan/detail.php?id=' . $idLaporan);
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($foto && is_file(dirname(__DIR__) . '/' . $foto)) {
                    @unlink(dirname(__DIR__) . '/' . $foto);
                }
                $err[] = 'Laporan gagal disimpan. Coba lagi beberapa saat.';
            }
        }
    }
}

$judul = 'Buat Laporan';
include __DIR__ . '/../includes/header.php';
?>

<div class="mx-auto max-w-3xl px-4 py-10">
  <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Formulir laporan</p>
  <h1 class="mt-2 text-2xl font-semibold text-navy">Laporkan kerusakan fasilitas</h1>
  <p class="mt-1 text-xs text-muted">Satu laporan untuk satu kerusakan. Kode tiket dibuat otomatis setelah laporan terkirim.</p>

  <?php if ($err): ?>
    <div class="flash flash-error mt-5">
      <?php foreach ($err as $x): ?><div>&bull; <?= e($x) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card mt-5 p-6" data-validasi novalidate>
    <?= csrf_field() ?>

    <div class="field">
      <label for="kategori_id">Kategori kerusakan</label>
      <select id="kategori_id" name="kategori_id" required>
        <option value="">— pilih kategori —</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int) $k['id'] ?>" <?= (string) $k['id'] === $nilai['kategori_id'] ? 'selected' : '' ?>>
            <?= e($k['nama']) ?> (target <?= (int) $k['sla_hari'] ?> hari)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="judul">Judul laporan</label>
      <input type="text" id="judul" name="judul" value="<?= e($nilai['judul']) ?>"
             maxlength="120" placeholder="Contoh: Lampu ruang GKB 2.03 mati" required>
      <p class="hint">10-120 karakter, sebutkan objek dan lokasi singkat.</p>
    </div>

    <div class="field">
      <label for="lokasi">Lokasi</label>
      <input type="text" id="lokasi" name="lokasi" value="<?= e($nilai['lokasi']) ?>"
             maxlength="160" placeholder="Gedung, ruang, lantai" required>
    </div>

    <div class="field">
      <label for="deskripsi">Deskripsi kerusakan</label>
      <textarea id="deskripsi" name="deskripsi" required
                placeholder="Jelaskan kondisi, sejak kapan, dan dampaknya terhadap kegiatan."><?= e($nilai['deskripsi']) ?></textarea>
      <p class="hint"><span id="hitung-deskripsi">0 karakter</span> &middot; minimal 20 karakter</p>
    </div>

    <div class="field">
      <label for="foto">Foto bukti (opsional)</label>
      <input type="file" id="foto" name="foto" accept="image/jpeg,image/png">
      <p class="hint">JPG atau PNG, maksimal 2 MB.</p>
      <div id="pratinjau-foto"></div>
    </div>

    <div class="flex flex-wrap gap-2">
      <button type="submit" class="btn btn-primary">Kirim laporan</button>
      <a href="<?= BASE ?>/laporan/index.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
