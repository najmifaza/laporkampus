<?php
require_once __DIR__ . '/includes/auth.php';
$u = pengguna();

$stat = [
    'total'    => (int) $pdo->query('SELECT COUNT(*) FROM laporan')->fetchColumn(),
    'selesai'  => (int) $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'selesai'")->fetchColumn(),
    'proses'   => (int) $pdo->query("SELECT COUNT(*) FROM laporan WHERE status IN ('diterima','diverifikasi','dikerjakan')")->fetchColumn(),
    'kategori' => (int) $pdo->query('SELECT COUNT(*) FROM kategori')->fetchColumn(),
];

$terbaru = $pdo->query(
    "SELECT l.kode, l.judul, l.status, l.dibuat, k.nama AS kategori
     FROM laporan l JOIN kategori k ON k.id = l.kategori_id
     ORDER BY l.dibuat DESC LIMIT 5"
)->fetchAll();

$judul = 'Beranda';
include __DIR__ . '/includes/header.php';
?>

<section class="hero relative overflow-hidden text-white">
  <img src="<?= BASE ?>/assets/img/gedung-fisip.webp" alt=""
       class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-[0.16]" aria-hidden="true">
  <div class="absolute inset-0 bg-gradient-to-r from-navy-dark/92 via-navy-dark/80 to-navy/55"></div>
  <div class="hero-grid absolute inset-0 opacity-40"></div>
  <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-14 md:grid-cols-[1.15fr_0.85fr] md:py-20">
    <div>
      <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] uppercase tracking-[0.14em] text-gold-soft">
        Unit Sarana &amp; Prasarana
      </span>
      <h1 class="mt-5 text-3xl font-semibold leading-tight sm:text-4xl">
        Laporkan kerusakan fasilitas kampus.<br>
        <span class="text-gold-soft">Pantau sampai selesai.</span>
      </h1>
      <div class="aksen-garis mt-6"></div>
      <p class="mt-5 max-w-xl text-[13px] leading-relaxed text-white/85 sm:text-sm">
        Satu saluran untuk lampu mati, AC rusak, kursi patah, proyektor bermasalah, hingga kran bocor.
        Setiap laporan mendapat kode tiket dan jejak penanganan yang bisa Anda lacak kapan saja,
        tanpa perlu bertanya lewat grup percakapan.
      </p>

      <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <?php
        $kartu = [
            ['Total laporan', $stat['total']],
            ['Dalam proses', $stat['proses']],
            ['Selesai', $stat['selesai']],
            ['Kategori', $stat['kategori']],
        ];
        foreach ($kartu as [$label, $nilai]): ?>
          <div class="rounded-lg border border-white/15 bg-white/5 px-4 py-3">
            <p class="text-2xl font-semibold text-white"><?= (int) $nilai ?></p>
            <p class="mt-0.5 text-[11px] uppercase tracking-wider text-white/60"><?= e($label) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card self-start overflow-hidden text-navy-dark">
      <img src="<?= BASE ?>/assets/img/gerbang-unsoed.webp" alt="Gerbang utama Kampus Universitas Jenderal Soedirman"
           class="h-36 w-full object-cover" width="739" height="415">
      <div class="p-6">
      <h2 class="text-base font-semibold text-navy">Lacak tiket laporan</h2>
      <p class="mt-1 text-xs text-muted">Masukkan kode tiket yang Anda terima saat membuat laporan.</p>
      <form action="<?= BASE ?>/track.php" method="get" class="mt-4">
        <div class="field">
          <label for="kode">Kode tiket</label>
          <input type="text" id="kode" name="kode" placeholder="LP-20261118-0001" maxlength="20" required>
        </div>
        <button type="submit" class="btn btn-primary w-full">Lacak laporan</button>
      </form>
      <div class="mt-5 border-t border-line pt-4 text-xs text-muted">
        Halaman pelacakan bersifat publik dan tidak menampilkan identitas pelapor.
      </div>
      <?php if (!$u): ?>
        <a href="<?= BASE ?>/register.php" class="btn btn-ghost mt-4 w-full">Daftar akun untuk melapor</a>
      <?php elseif ($u['role'] === 'pelapor'): ?>
        <a href="<?= BASE ?>/laporan/buat.php" class="btn btn-gold mt-4 w-full">Buat laporan baru</a>
        <a href="<?= BASE ?>/laporan/index.php" class="btn btn-ghost mt-2 w-full">Lihat laporan saya</a>
      <?php else: ?>
        <a href="<?= BASE ?>/admin/index.php" class="btn btn-gold mt-4 w-full">Buka dashboard sarpras</a>
      <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="border-b border-line bg-white">
  <div class="mx-auto max-w-6xl px-4 py-14">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Fasilitas kampus</p>
        <h2 class="mt-2 text-2xl font-semibold text-navy">Yang kami jaga bersama</h2>
        <p class="mt-2 text-xs leading-relaxed text-muted">
          Ruang kuliah, laboratorium, perpustakaan, hingga gedung fakultas. Kerusakan sekecil apa pun
          layak dilaporkan agar tidak mengganggu kegiatan belajar.
        </p>
      </div>
    </div>

    <?php
    $gedung = [
        ['gedung-fisip.webp',   'Gedung FISIP',                 'Fakultas Ilmu Sosial dan Ilmu Politik'],
        ['gedung-faperta.webp', 'Gedung Fakultas Pertanian',     'Auditorium dan ruang kuliah'],
        ['gedung-fapet.webp',   'Gedung Fakultas Peternakan',    'Laboratorium dan kandang percobaan'],
        ['gedung-fib.webp',     'Gerbang Fakultas Ilmu Budaya',  'Akses masuk dan area parkir'],
    ];
    ?>
    <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <?php foreach ($gedung as [$berkas, $nama, $ket]): ?>
        <figure class="group overflow-hidden rounded-lg border border-line bg-white">
          <img src="<?= BASE ?>/assets/img/<?= e($berkas) ?>" alt="<?= e($nama) ?> Universitas Jenderal Soedirman"
               class="h-40 w-full object-cover transition duration-300 group-hover:scale-[1.03]" loading="lazy">
          <figcaption class="border-t border-line px-4 py-3">
            <p class="text-xs font-semibold text-navy"><?= e($nama) ?></p>
            <p class="mt-0.5 text-[11px] text-muted"><?= e($ket) ?></p>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="cara" class="mx-auto max-w-6xl px-4 py-14">
  <div class="max-w-2xl">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Cara kerja</p>
    <h2 class="mt-2 text-2xl font-semibold text-navy">Tiga langkah, jejak tercatat</h2>
    <div class="aksen-garis mt-3"></div>
  </div>

  <div class="mt-8 grid gap-4 md:grid-cols-3">
    <div class="card p-5">
      <span class="text-[11px] font-semibold tracking-[0.16em] text-gold">LANGKAH 01</span>
      <h3 class="mt-2 text-base font-semibold text-navy">Laporkan</h3>
      <p class="mt-2 text-xs leading-relaxed text-muted">
        Isi kategori, judul, lokasi, dan deskripsi kerusakan. Foto bukti bersifat opsional,
        maksimal 2 MB berformat JPG atau PNG.
      </p>
    </div>
    <div class="card p-5">
      <span class="text-[11px] font-semibold tracking-[0.16em] text-gold">LANGKAH 02</span>
      <h3 class="mt-2 text-base font-semibold text-navy">Diverifikasi</h3>
      <p class="mt-2 text-xs leading-relaxed text-muted">
        Unit sarpras memeriksa laporan. Setiap keputusan wajib disertai catatan, sehingga
        laporan yang ditolak pun punya alasan yang jelas.
      </p>
    </div>
    <div class="card p-5">
      <span class="text-[11px] font-semibold tracking-[0.16em] text-gold">LANGKAH 03</span>
      <h3 class="mt-2 text-base font-semibold text-navy">Dipantau sampai selesai</h3>
      <p class="mt-2 text-xs leading-relaxed text-muted">
        Status bergerak dari diterima, dikerjakan, sampai selesai. Pelapor melihat seluruh
        riwayat beserta waktu dan catatan petugas.
      </p>
    </div>
  </div>
</section>

<section class="border-y border-line bg-navy-light/70">
  <div class="mx-auto max-w-6xl px-4 py-12">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Terbaru</p>
        <h2 class="mt-2 text-2xl font-semibold text-navy">Laporan masuk</h2>
      </div>
      <a href="<?= BASE ?>/track.php" class="text-xs font-semibold text-navy underline decoration-gold decoration-2 underline-offset-4">
        Lacak dengan kode tiket
      </a>
    </div>

    <?php if (!$terbaru): ?>
      <p class="mt-8 text-sm text-muted">Belum ada laporan yang masuk.</p>
    <?php else: ?>
      <ul class="mt-6 divide-y divide-line overflow-hidden rounded-lg border border-line bg-white">
        <?php foreach ($terbaru as $l): ?>
          <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-navy-dark"><?= e($l['judul']) ?></p>
              <p class="mt-1 text-[11px] text-muted">
                <?= e($l['kategori']) ?> &middot; <?= e($l['kode']) ?> &middot;
                <?= date('d M Y H:i', strtotime($l['dibuat'])) ?>
              </p>
            </div>
            <?= badge_status($l['status']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="mt-3 text-[11px] text-muted">
        Ditampilkan tanpa identitas pelapor. Detail lengkap hanya dapat diakses pemilik tiket dan petugas.
      </p>
    <?php endif; ?>
  </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-14">
  <div class="grid items-center gap-8 rounded-xl border border-line bg-white p-6 md:grid-cols-[1fr_auto] md:p-8">
    <div>
      <h2 class="text-xl font-semibold text-navy"><?= ($u && $u['role'] === 'admin') ? 'Pantau penanganan laporan' : 'Menemukan kerusakan lain?' ?></h2>
      <p class="mt-2 max-w-xl text-xs leading-relaxed text-muted">
        Satu laporan satu kerusakan. Hindari menggabungkan beberapa masalah dalam satu tiket
        agar penanganannya tidak tertunda.
      </p>
    </div>
    <?php if ($u && $u['role'] === 'admin'): ?>
      <a href="<?= BASE ?>/admin/index.php" class="btn btn-primary">Buka dashboard</a>
    <?php else: ?>
      <a href="<?= BASE ?>/laporan/buat.php" class="btn btn-primary">Buat laporan</a>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
