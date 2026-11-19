<?php
require_once __DIR__ . '/auth.php';
$u = pengguna();
$judul = $judul ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> &middot; <?= APP_NAME ?></title>
<meta name="description" content="Pelaporan dan penelusuran kerusakan fasilitas kampus Universitas Jenderal Soedirman.">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        navy:  { DEFAULT: '#1d536c', dark: '#12384a', mid: '#3f7896', light: '#eff5f8', edge: '#cbdde6' },
        gold:  { DEFAULT: '#b98515', bright: '#eeb700', soft: '#f5c66b', dim: '#fdf6e3' },
        line:  '#e0e6ea',
        muted: '#657985'
      },
      fontFamily: { sans: ['Poppins', 'Segoe UI', 'system-ui', 'sans-serif'] }
    }
  }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/style.css">
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-navy-dark">

<!-- strip utilitas -->
<div class="bg-navy-dark text-white">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-1.5 text-[11px] tracking-wide">
    <span class="flex items-center gap-3">
      <img src="<?= BASE ?>/assets/img/logo-unsoed-putih.webp" alt="Universitas Jenderal Soedirman"
           class="h-6 w-auto opacity-95" width="600" height="103">
      <span class="hidden h-3 w-px bg-white/25 sm:block"></span>
      <span class="font-medium">Sistem Pelaporan Fasilitas</span>
    </span>
    <span class="text-white/80">Helpdesk Sarpras</span>
  </div>
</div>

<!-- navbar -->
<header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
    <a href="<?= BASE ?>/index.php" class="flex items-center gap-3">
      <img src="<?= BASE ?>/assets/img/logo-unsoed.webp" alt="Lambang Universitas Jenderal Soedirman"
           class="h-11 w-11 object-contain" width="180" height="180">
      <span class="leading-tight">
        <span class="block text-base font-semibold text-navy"><?= APP_NAME ?></span>
        <span class="block text-[10px] uppercase tracking-[0.14em] text-muted"><?= APP_SUB ?></span>
      </span>
    </a>

    <nav class="hidden items-center gap-1 text-sm font-medium md:flex">
      <a href="<?= BASE ?>/index.php" class="nav-link">Beranda</a>
      <?php if ($u && $u['role'] === 'admin'): ?>
        <a href="<?= BASE ?>/admin/index.php" class="nav-link">Dashboard</a>
        <a href="<?= BASE ?>/admin/kategori.php" class="nav-link">Kategori</a>
        <a href="<?= BASE ?>/admin/pengguna.php" class="nav-link">Pengguna</a>
      <?php endif; ?>
    </nav>

    <div class="flex items-center gap-2">
      <?php if ($u): ?>
        <a href="<?= BASE ?>/profil.php" class="hidden text-right leading-tight sm:block">
          <span class="block text-xs font-semibold text-navy"><?= e($u['nama']) ?></span>
          <span class="block text-[10px] uppercase tracking-wider text-muted"><?= e($u['role']) ?></span>
        </a>
        <a href="<?= BASE ?>/logout.php" class="btn btn-ghost btn-sm">Keluar</a>
      <?php else: ?>
        <a href="<?= BASE ?>/login.php" class="btn btn-ghost btn-sm">Masuk</a>
        <a href="<?= BASE ?>/register.php" class="btn btn-primary btn-sm">Daftar</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- menu mobile -->
  <div class="flex gap-1 overflow-x-auto border-t border-line px-4 py-2 text-xs font-medium md:hidden">
    <a href="<?= BASE ?>/index.php" class="nav-link">Beranda</a>
    <?php if ($u && $u['role'] === 'admin'): ?>
      <a href="<?= BASE ?>/admin/index.php" class="nav-link">Dashboard</a>
      <a href="<?= BASE ?>/admin/kategori.php" class="nav-link">Kategori</a>
      <a href="<?= BASE ?>/admin/pengguna.php" class="nav-link">Pengguna</a>
    <?php endif; ?>
  </div>
</header>

<main class="flex-1">
<?php foreach (ambil_flash() as $f): ?>
  <div class="mx-auto mt-4 max-w-6xl px-4">
    <div class="flash flash-<?= e($f['jenis']) ?>"><?= e($f['pesan']) ?></div>
  </div>
<?php endforeach; ?>
