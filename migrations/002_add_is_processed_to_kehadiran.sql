ALTER TABLE `tbl_kehadiran`
ADD COLUMN `is_processed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Belum diproses, 1: Sudah diproses'
AFTER `status`; 