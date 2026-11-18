<?php
// Helper umum: escape, CSRF, flash, badge, kode tiket, aturan status

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function flash(string $jenis, string $pesan): void
{
    $_SESSION['flash'][] = ['jenis' => $jenis, 'pesan' => $pesan];
}

function ambil_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $path): void
{
    header('Location: ' . BASE . $path);
    exit;
}

function status_label(string $s): string
{
    $map = [
        'diterima'     => 'Diterima',
        'diverifikasi' => 'Diverifikasi',
        'dikerjakan'   => 'Dikerjakan',
        'selesai'      => 'Selesai',
        'ditolak'      => 'Ditolak',
    ];
    return $map[$s] ?? ucfirst($s);
}

function badge_status(string $s): string
{
    return '<span class="badge badge-' . e($s) . '">' . e(status_label($s)) . '</span>';
}

function transisi_valid(string $dari, string $ke): bool
{
    $map = [
        'diterima'     => ['diverifikasi', 'ditolak'],
        'diverifikasi' => ['dikerjakan'],
        'dikerjakan'   => ['selesai'],
    ];
    return in_array($ke, $map[$dari] ?? [], true);
}

function kode_tiket(PDO $pdo): string
{
    $tgl = date('Ymd');
    $hitung = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE kode LIKE ?');
    $cek    = $pdo->prepare('SELECT 1 FROM laporan WHERE kode = ?');

    for ($i = 1; $i <= 20; $i++) {
        $hitung->execute(['LP-' . $tgl . '-%']);
        $urut = (int) $hitung->fetchColumn() + $i;
        $kode = 'LP-' . $tgl . '-' . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
        $cek->execute([$kode]);
        if (!$cek->fetchColumn()) {
            return $kode;
        }
    }
    return 'LP-' . $tgl . '-' . str_pad((string) random_int(9000, 9999), 4, '0', STR_PAD_LEFT);
}

function umur_hari(string $dibuat): int
{
    $ts = strtotime($dibuat);
    return $ts ? max(0, (int) floor((time() - $ts) / 86400)) : 0;
}

function potong(string $teks, int $n = 110): string
{
    $teks = trim(preg_replace('/\s+/', ' ', $teks));
    return mb_strlen($teks) > $n ? mb_substr($teks, 0, $n) . '…' : $teks;
}
