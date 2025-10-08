CREATE TABLE IF NOT EXISTS tbl_jam_kerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jam_kerja VARCHAR(100) NOT NULL,
    jam_masuk TIME NOT NULL,
    jam_pulang TIME NOT NULL,
    toleransi_telat_menit INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Masukkan data default agar tidak kosong
INSERT INTO tbl_jam_kerja (nama_jam_kerja, jam_masuk, jam_pulang, toleransi_telat_menit) 
VALUES ('Jam Kerja Standar', '06:30:00', '15:00:00', 15); 