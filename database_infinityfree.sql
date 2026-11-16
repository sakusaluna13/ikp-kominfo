-- ============================================================
-- SQL DUMP UNTUK INFINITYFREE (SIMPEL MEDIA KOMINFO)
-- Langsung Import di phpMyAdmin InfinityFree
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. TABEL USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'peminjam') NOT NULL DEFAULT 'peminjam',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    foto VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. TABEL BARANG
-- ------------------------------------------------------------
CREATE TABLE barang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 0,
    kondisi VARCHAR(100) NOT NULL DEFAULT 'Baik',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. TABEL PEMINJAMAN
-- ------------------------------------------------------------
CREATE TABLE peminjaman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    barang_id INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'menunggu persetujuan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_peminjaman_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_peminjaman_barang
        FOREIGN KEY (barang_id)
        REFERENCES barang(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_peminjaman_status (status),
    INDEX idx_peminjaman_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DATA AWAL USERS
-- Password default: password
-- ------------------------------------------------------------
INSERT INTO users (id, nama, username, password, role, status) VALUES
(1, 'Administrator KOMINFO', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'aktif'),
(2, 'Staf Peminjam', 'staf01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'peminjam', 'aktif');

-- ------------------------------------------------------------
-- DATA AWAL 15 BARANG
-- ------------------------------------------------------------
INSERT INTO barang (id, nama_barang, kategori, jumlah, kondisi) VALUES
(1, 'Kamera DSLR', 'Kamera', 4, 'Baik'),
(2, 'Tripod Kamera', 'Aksesori Kamera', 8, 'Baik'),
(3, 'Microphone Wireless', 'Audio', 6, 'Baik'),
(4, 'Lighting LED', 'Pencahayaan', 5, 'Baik'),
(5, 'Video Capture Card', 'Perangkat Streaming', 3, 'Baik'),
(6, 'Drone DJI Mavic 3 Pro', 'Drone', 2, 'Baik'),
(7, 'Sony Alpha A7 IV', 'Kamera', 3, 'Baik'),
(8, 'Lensa Sony 24-70mm f/2.8 GM', 'Lensa', 2, 'Baik'),
(9, 'Gimbal DJI RS 3 Pro', 'Stabilizer', 3, 'Baik'),
(10, 'Audio Mixer Yamaha MG10XU', 'Audio', 2, 'Baik'),
(11, 'Mic Rode Wireless GO II', 'Audio', 4, 'Baik'),
(12, 'Lampu Studio Godox SL-60W Kit', 'Pencahayaan', 4, 'Baik'),
(13, 'Blackmagic ATEM Mini Pro', 'Perangkat Streaming', 2, 'Baik'),
(14, 'Teleprompter Kit', 'Video', 2, 'Baik'),
(15, 'Green Screen Portable', 'Studio', 3, 'Baik');

-- ------------------------------------------------------------
-- DATA CONTOH TRANSAKSI
-- ------------------------------------------------------------
INSERT INTO peminjaman (user_id, barang_id, jumlah, tanggal_pinjam, tanggal_kembali, status) VALUES
(2, 2, 1, '2026-11-05', '2026-11-07', 'dikembalikan'),
(2, 1, 1, '2026-11-10', NULL, 'dipinjam');

