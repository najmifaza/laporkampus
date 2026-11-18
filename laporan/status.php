<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}
csrf_check();

$id      = (int) ($_POST['id'] ?? 0);
$ke      = (string) ($_POST['status'] ?? '');
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$u       = pengguna();

$st = $pdo->prepare('SELECT id, status, kode FROM laporan WHERE id = ?');
$st->execute([$id]);
$lap = $st->fetch();

if (!$lap) {
    flash('error', 'Laporan tidak ditemukan.');
    redirect('/admin/index.php');
}
if (mb_strlen($catatan) < 5 || mb_strlen($catatan) > 255) {
    flash('error', 'Catatan wajib diisi 5-255 karakter.');
    redirect('/laporan/detail.php?id=' . $id);
}
if (!transisi_valid($lap['status'], $ke)) {
    flash('error', 'Transisi status tidak sah: ' . status_label($lap['status']) . ' ke ' . status_label($ke) . '.');
    redirect('/laporan/detail.php?id=' . $id);
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE laporan SET status = ? WHERE id = ? AND status = ?')
        ->execute([$ke, $id, $lap['status']]);
    $pdo->prepare('INSERT INTO riwayat (laporan_id, status, catatan, oleh_id) VALUES (?, ?, ?, ?)')
        ->execute([$id, $ke, $catatan, $u['id']]);
    $pdo->commit();
    flash('success', 'Status ' . $lap['kode'] . ' diperbarui menjadi ' . status_label($ke) . '.');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Status gagal diperbarui. Coba lagi.');
}

redirect('/laporan/detail.php?id=' . $id);
