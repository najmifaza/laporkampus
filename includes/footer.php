</main>

<footer class="mt-16 border-t border-line bg-navy-dark text-white/75">
  <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
    <div>
      <div class="flex items-center gap-3">
        <img src="<?= BASE ?>/assets/img/logo-unsoed-putih.webp" alt="Universitas Jenderal Soedirman"
             class="h-6 w-auto" width="600" height="103">
      </div>
      <p class="mt-3 text-xs leading-relaxed">
        Saluran tunggal pelaporan kerusakan fasilitas kampus. Setiap laporan mendapat
        kode tiket dan jejak penanganan yang dapat dilacak.
      </p>
    </div>
    <div class="text-xs">
      <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gold-soft">Navigasi</p>
      <ul class="space-y-2">
        <li><a href="<?= BASE ?>/index.php" class="hover:text-white">Beranda</a></li>
        <li><a href="<?= BASE ?>/track.php" class="hover:text-white">Lacak Tiket</a></li>
        <li><a href="<?= BASE ?>/laporan/buat.php" class="hover:text-white">Buat Laporan</a></li>
        <li><a href="<?= BASE ?>/register.php" class="hover:text-white">Daftar Akun</a></li>
      </ul>
    </div>
    <div class="text-xs">
      <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gold-soft">Kontak</p>
      <p>Unit Sarana dan Prasarana</p>
      <p class="mt-1 text-white/50">Gedung Rektorat, Kampus Karangwangkal</p>
      <p class="mt-1 text-white/50">Purwokerto, Jawa Tengah</p>
    </div>
  </div>
  <div class="border-t border-white/10 py-3 text-center text-[11px] text-white/40">
    &copy; <?= date('Y') ?> <?= APP_NAME ?>. Tugas mata kuliah Pemrograman Web I &mdash; Adridinan Najmi Faza (H1D025059).<br>
    <span class="text-white/30">Logo dan foto gedung: unsoed.ac.id</span>
  </div>
</footer>

<script src="<?= BASE ?>/assets/app.js"></script>
</body>
</html>
