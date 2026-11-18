<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/laporan/index.php');
}
csrf_check();

$u  = pengguna();
$id = (int) ($_POST['id'] ?? 0);

$st = $pdo->prepare('SELECT id, user_id, status, foto FROM laporan WHERE id = ?');
$st->execute([$id]);
$lap = $st->fetch();

if (!$lap) {
    flash('error', 'Laporan tidak ditemukan.');
    redirect('/laporan/index.php');
}

$pemilik = (int) $lap['user_id'] === (int) $u['id'];
$admin   = $u['role'] === 'admin';

if (!$admin && !($pemilik && $lap['status'] === 'diterima')) {
    flash('error', 'Laporan ini tidak dapat Anda hapus.');
    redirect('/laporan/detail.php?id=' . $id);
}

$pdo->prepare('DELETE FROM laporan WHERE id = ?')->execute([$id]);

if (!empty($lap['foto'])) {
    $path = dirname(__DIR__) . '/' . $lap['foto'];
    if (is_file($path) && str_starts_with(realpath($path) ?: '', realpath(dirname(__DIR__) . '/uploads') ?: '')) {
        @unlink($path);
    }
}

flash('success', 'Laporan dihapus.');
redirect($admin ? '/admin/index.php' : '/laporan/index.php');
