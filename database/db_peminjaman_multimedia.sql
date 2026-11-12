-- ============================================
-- DATABASE PEMINJAMAN MULTIMEDIA
-- ============================================

CREATE DATABASE IF NOT EXISTS db_peminjaman_multimedia3
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE db_peminjaman_multimedia3;


-- ============================================
-- HAPUS TABEL LAMA
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================
-- TABEL USERS
-- ============================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'peminjam') NOT NULL DEFAULT 'peminjam',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================
-- TABEL BARANG
-- ============================================

CREATE TABLE barang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 0,
    kondisi VARCHAR(100) NOT NULL DEFAULT 'Baik',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================
-- TABEL PEMINJAMAN
-- ============================================

CREATE TABLE peminjaman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    barang_id INT UNSIGNED NOT NULL,
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

) ENGINE=InnoDB;


-- ============================================
-- DATA AWAL USERS
-- ============================================

-- Login admin:
-- Username : admin
-- Password : password

INSERT INTO users (
    nama,
    username,
    password,
    role,
    status
) VALUES
(
    'Administrator KOMINFO',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'aktif'
),
(
    'Pengguna Contoh',
    'peminjam1',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'peminjam',
    'aktif'
);


-- ============================================
-- DATA AWAL BARANG
-- ============================================

INSERT INTO barang (
    nama_barang,
    kategori,
    jumlah,
    kondisi
) VALUES
('Kamera DSLR', 'Kamera', 4, 'Baik'),
('Tripod Kamera', 'Aksesori Kamera', 8, 'Baik'),
('Microphone Wireless', 'Audio', 6, 'Baik'),
('Lighting LED', 'Pencahayaan', 5, 'Baik'),
('Video Capture Card', 'Perangkat Streaming', 3, 'Baik');


-- ============================================
-- DATA CONTOH PEMINJAMAN
-- ============================================

INSERT INTO peminjaman (
    user_id,
    barang_id,
    tanggal_pinjam,
    tanggal_kembali,
    status
) VALUES
(
    2,
    1,
    CURDATE() - INTERVAL 2 DAY,
    NULL,
    'dipinjam'
),
(
    2,
    2,
    CURDATE() - INTERVAL 7 DAY,
    CURDATE() - INTERVAL 5 DAY,
    'dikembalikan'
);