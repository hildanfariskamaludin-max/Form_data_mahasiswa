-- ============================================
-- DATABASE SETUP: CRUD Data Mahasiswa
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS db_mahasiswa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_mahasiswa;

CREATE TABLE IF NOT EXISTS mahasiswa (
  id        INT          NOT NULL AUTO_INCREMENT,
  nim       VARCHAR(20)  NOT NULL UNIQUE,
  nama      VARCHAR(100) NOT NULL,
  jurusan   VARCHAR(100) NOT NULL,
  foto      VARCHAR(255) NOT NULL DEFAULT 'default.png',
  created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO mahasiswa (nim, nama, jurusan, foto) VALUES
  ('2021001001', 'dikrul ganteng',      'Teknik Informatika', 'default.png'),
  ('2021001002', 'hildan faris', 'Sistem Informasi',   'default.png'),
  ('2021001003', 'kemal',      'Teknik Elektro',     'default.png');
