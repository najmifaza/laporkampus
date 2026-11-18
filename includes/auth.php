<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/fungsi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function pengguna(): ?array
{
    return $_SESSION['user'] ?? null;
}

function wajib_login(): void
{
    if (!pengguna()) {
        flash('info', 'Silakan masuk terlebih dahulu.');
        redirect('/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? BASE . '/index.php'));
    }
}

function wajib_admin(): void
{
    wajib_login();
    if ((pengguna()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        $judul = 'Akses ditolak';
        include __DIR__ . '/header.php';
        echo '<div class="mx-auto max-w-3xl px-4 py-16 text-center">'
           . '<p class="text-5xl font-bold text-navy">403</p>'
           . '<h1 class="mt-3 text-xl font-semibold">Halaman ini khusus admin sarpras</h1>'
           . '<p class="mt-2 text-sm text-muted">Akun Anda masuk sebagai pelapor.</p>'
           . '<a href="' . BASE . '/laporan/index.php" class="btn btn-primary mt-6 inline-flex">Kembali ke laporan saya</a>'
           . '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}
