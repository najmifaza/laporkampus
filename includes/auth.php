<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/fungsi.php';

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_name('LK_SESSION');
    session_start();
}

// Header keamanan dasar
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
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

// Admin fokus di dashboard: tidak membuat laporan
function wajib_pelapor(): void
{
    wajib_login();
    if ((pengguna()['role'] ?? '') !== 'pelapor') {
        http_response_code(403);
        $judul = 'Akses ditolak';
        include __DIR__ . '/header.php';
        echo '<div class="mx-auto max-w-3xl px-4 py-16 text-center">'
           . '<p class="text-5xl font-bold text-navy">403</p>'
           . '<h1 class="mt-3 text-xl font-semibold">Akun admin tidak membuat laporan</h1>'
           . '<p class="mt-2 text-sm text-muted">Tugas admin sarpras adalah memverifikasi dan memperbarui status laporan di dashboard.</p>'
           . '<a href="' . BASE . '/admin/index.php" class="btn btn-primary mt-6 inline-flex">Buka dashboard</a>'
           . '</div>';
        include __DIR__ . '/footer.php';
        exit;
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
