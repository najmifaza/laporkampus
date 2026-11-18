-- LaporKampus — skema + data awal
-- Import lewat phpMyAdmin atau: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS laporkampus
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laporkampus;

DROP TABLE IF EXISTS tanggapan;
DROP TABLE IF EXISTS riwayat;
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  nim_nip       VARCHAR(20)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('pelapor','admin') NOT NULL DEFAULT 'pelapor',
  aktif         TINYINT(1)   NOT NULL DEFAULT 1,
  dibuat        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  nama     VARCHAR(80) NOT NULL UNIQUE,
  sla_hari SMALLINT    NOT NULL DEFAULT 7
) ENGINE=InnoDB;

CREATE TABLE laporan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kode        VARCHAR(20)  NOT NULL UNIQUE,
  user_id     INT          NOT NULL,
  kategori_id INT          NOT NULL,
  judul       VARCHAR(120) NOT NULL,
  deskripsi   TEXT         NOT NULL,
  lokasi      VARCHAR(160) NOT NULL,
  foto        VARCHAR(255) DEFAULT NULL,
  status      ENUM('diterima','diverifikasi','dikerjakan','selesai','ditolak') NOT NULL DEFAULT 'diterima',
  dibuat      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diperbarui  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lap_user FOREIGN KEY (user_id)     REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_lap_kat  FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT,
  INDEX idx_status (status),
  INDEX idx_kategori (kategori_id),
  INDEX idx_dibuat (dibuat)
) ENGINE=InnoDB;

CREATE TABLE riwayat (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  laporan_id INT NOT NULL,
  status     VARCHAR(20) NOT NULL,
  catatan    VARCHAR(255) DEFAULT NULL,
  oleh_id    INT DEFAULT NULL,
  waktu      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_riw_lap  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
  CONSTRAINT fk_riw_user FOREIGN KEY (oleh_id)    REFERENCES users(id)   ON DELETE SET NULL,
  INDEX idx_laporan (laporan_id)
) ENGINE=InnoDB;

CREATE TABLE tanggapan (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  laporan_id INT NOT NULL,
  user_id    INT NOT NULL,
  isi        VARCHAR(1000) NOT NULL,
  waktu      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tag_lap  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
  CONSTRAINT fk_tag_user FOREIGN KEY (user_id)    REFERENCES users(id)   ON DELETE CASCADE,
  INDEX idx_laporan (laporan_id)
) ENGINE=InnoDB;

-- Akun contoh (admin123 / pelapor123)
INSERT INTO users (nama, nim_nip, email, password_hash, role) VALUES
('Admin Sarpras', '198001012005011001', 'admin@kampus.test',
 '$2y$10$sbVJ46X6jtxwd4RYwfhO1.kkcNwfUUq6MGM5AuRTPG4gh8eFHOoBu', 'admin'),
('Adridinan Najmi Faza', 'H1D025059', 'adridinan@kampus.test',
 '$2y$10$ejv.HEgTbcWXiZ0NG604AO.mEK6DncQgIGuBy95BRl/abLb9/W2d.', 'pelapor');

INSERT INTO kategori (nama, sla_hari) VALUES
('Listrik & Penerangan', 3),
('AC & Ventilasi', 7),
('Furnitur (kursi/meja/papan)', 14),
('Perangkat Kelas (proyektor/komputer)', 7),
('Air & Sanitasi', 3),
('Bangunan (atap/plafon/dinding)', 21);

-- Contoh laporan + jejak status
INSERT INTO laporan (kode, user_id, kategori_id, judul, deskripsi, lokasi, status, dibuat) VALUES
('LP-20261118-0001', 2, 1, 'Lampu ruang kuliah GKB 2 lantai 3 mati',
 'Empat dari enam lampu di ruang GKB 2.03 mati sejak pekan lalu, kuliah sore jadi gelap dan sulit membaca papan tulis.',
 'GKB 2, Ruang 2.03, Lantai 3', 'dikerjakan', NOW() - INTERVAL 2 DAY),
('LP-20261118-0002', 2, 4, 'Proyektor ruang seminar tidak menyala',
 'Proyektor di ruang seminar lantai 2 hanya menampilkan warna biru. Kabel HDMI sudah dicoba diganti, tetap sama.',
 'Gedung Seminar, Ruang 2.1', 'diterima', NOW() - INTERVAL 6 HOUR);

INSERT INTO riwayat (laporan_id, status, catatan, oleh_id, waktu) VALUES
(1, 'diterima', 'Laporan diterima sistem', NULL, NOW() - INTERVAL 2 DAY),
(1, 'diverifikasi', 'Kerusakan terkonfirmasi, penggantian ballast dijadwalkan', 1, NOW() - INTERVAL 30 HOUR),
(1, 'dikerjakan', 'Teknisi sedang mengganti 4 unit lampu', 1, NOW() - INTERVAL 6 HOUR),
(2, 'diterima', 'Laporan diterima sistem', NULL, NOW() - INTERVAL 6 HOUR);
