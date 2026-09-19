/*
 Navicat Premium Data Transfer

 Source Server         : MYSQL LOCAL
 Source Server Type    : MariaDB
 Source Server Version : 110808 (11.8.8-MariaDB)
 Source Host           : 127.0.0.1:3306
 Source Schema         : pekanbaru_ittihad

 Target Server Type    : MariaDB
 Target Server Version : 110808 (11.8.8-MariaDB)
 File Encoding         : 65001

 Date: 07/09/2026 17:08:01
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for akt_jurnal
-- ----------------------------
DROP TABLE IF EXISTS `akt_jurnal`;
CREATE TABLE `akt_jurnal`  (
  `urut` int(11) NOT NULL,
  `no_tran` int(11) NULL DEFAULT NULL,
  `no_ref` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tanggal` datetime NULL DEFAULT NULL,
  `no_bukti` char(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `keterangan` char(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kode_perkiraan` char(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `debet` bigint(20) NULL DEFAULT NULL,
  `kredit` bigint(20) NULL DEFAULT NULL,
  `tahun` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `periode` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE,
  INDEX `IDX_JURNAL`(`no_ref` ASC, `tanggal` ASC, `no_bukti` ASC, `kode_perkiraan` ASC, `debet` ASC, `kredit` ASC, `tahun` ASC, `periode` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for akt_log
-- ----------------------------
DROP TABLE IF EXISTS `akt_log`;
CREATE TABLE `akt_log`  (
  `fungsi` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `transaksi` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `waktu` datetime NULL DEFAULT NULL,
  `users` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `akademik` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `angkatan` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7946 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for audittrail
-- ----------------------------
DROP TABLE IF EXISTS `audittrail`;
CREATE TABLE `audittrail`  (
  `Id` int(11) NOT NULL,
  `DateTime` datetime NOT NULL,
  `Script` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `User` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Action` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Table` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Field` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KeyValue` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `OldValue` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NewValue` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`Id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for cyber_key
-- ----------------------------
DROP TABLE IF EXISTS `cyber_key`;
CREATE TABLE `cyber_key`  (
  `users` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kunci` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `fid` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ket` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kel` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 36 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_det_ijin
-- ----------------------------
DROP TABLE IF EXISTS `hr_det_ijin`;
CREATE TABLE `hr_det_ijin`  (
  `id` int(11) NOT NULL,
  `nik` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tanggal` date NULL DEFAULT NULL,
  `keterangan` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_det_rekap_anekdot
-- ----------------------------
DROP TABLE IF EXISTS `hr_det_rekap_anekdot`;
CREATE TABLE `hr_det_rekap_anekdot`  (
  `id` int(11) NOT NULL,
  `periode` char(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nik` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `jml_anekdot` smallint(6) NULL DEFAULT NULL,
  `persen` smallint(6) NULL DEFAULT NULL,
  `hari_aktif` smallint(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_det_rekap_presensi
-- ----------------------------
DROP TABLE IF EXISTS `hr_det_rekap_presensi`;
CREATE TABLE `hr_det_rekap_presensi`  (
  `id` int(11) NOT NULL,
  `nik` char(12) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `qty_jml` smallint(6) NULL DEFAULT NULL,
  `qty_porsen` decimal(18, 0) NULL DEFAULT NULL,
  `qlt_jml` smallint(6) NULL DEFAULT NULL,
  `qlt_porsen` decimal(18, 0) NULL DEFAULT NULL,
  `periode` char(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kurang` smallint(6) NULL DEFAULT NULL,
  `porsen30` smallint(6) NULL DEFAULT NULL,
  `porsen20` smallint(6) NULL DEFAULT NULL,
  `porsen50` smallint(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_master_karyawan
-- ----------------------------
DROP TABLE IF EXISTS `hr_master_karyawan`;
CREATE TABLE `hr_master_karyawan`  (
  `id` int(11) NOT NULL,
  `NIK` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nama` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `title_depan` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `title_belakang` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `status_aktif` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tahun_masuk` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `alamat` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `golongan` char(7) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `jabatan` char(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kelompok` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `unit` char(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_mst_jam
-- ----------------------------
DROP TABLE IF EXISTS `hr_mst_jam`;
CREATE TABLE `hr_mst_jam`  (
  `jam_masuk` time NULL DEFAULT NULL,
  `jam_keluar` time NULL DEFAULT NULL,
  `unit` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_mst_tunjangan_kinerja
-- ----------------------------
DROP TABLE IF EXISTS `hr_mst_tunjangan_kinerja`;
CREATE TABLE `hr_mst_tunjangan_kinerja`  (
  `ID` int(11) NOT NULL,
  `PID` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NIK` char(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Nama` varchar(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Kelompok` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Tarif` int(11) NULL DEFAULT NULL,
  `unit` char(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`ID`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for hr_ref_periode
-- ----------------------------
DROP TABLE IF EXISTS `hr_ref_periode`;
CREATE TABLE `hr_ref_periode`  (
  `tahun` char(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `periode` char(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for log_billchange
-- ----------------------------
DROP TABLE IF EXISTS `log_billchange`;
CREATE TABLE `log_billchange`  (
  `timestamp` timestamp NULL DEFAULT NULL,
  `users` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `BILLCD` char(18) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NamaAkun` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `old_BILLAM` bigint(20) NULL DEFAULT NULL,
  `BILLAM` bigint(20) NULL DEFAULT NULL,
  `Type` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `hostname` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ip` varchar(75) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for log_paymentcancelled
-- ----------------------------
DROP TABLE IF EXISTS `log_paymentcancelled`;
CREATE TABLE `log_paymentcancelled`  (
  `timestamp` timestamp NULL DEFAULT NULL,
  `users` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `BILLCD` char(18) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `BILLAM` bigint(20) NULL DEFAULT NULL,
  `Type` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `hostname` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ip` varchar(75) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_kelas
-- ----------------------------
DROP TABLE IF EXISTS `mst_kelas`;
CREATE TABLE `mst_kelas`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kelas` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `jenjang` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `unit` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kelompok` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 136 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_kelas_copy
-- ----------------------------
DROP TABLE IF EXISTS `mst_kelas_copy`;
CREATE TABLE `mst_kelas_copy`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kelas` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `jenjang` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `unit` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kelompok` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `fakultas_id` int(11) NULL DEFAULT NULL,
  `prodi_id` int(11) NULL DEFAULT NULL,
  `jenispendaftaran_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 79 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_sekolah
-- ----------------------------
DROP TABLE IF EXISTS `mst_sekolah`;
CREATE TABLE `mst_sekolah`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `CODE01` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC01` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NMYAYASAN` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CODE02` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC02` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_tagihan
-- ----------------------------
DROP TABLE IF EXISTS `mst_tagihan`;
CREATE TABLE `mst_tagihan`  (
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `tagihan` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_thn_aka
-- ----------------------------
DROP TABLE IF EXISTS `mst_thn_aka`;
CREATE TABLE `mst_thn_aka`  (
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `thn_aka` varchar(18) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for mst_tree
-- ----------------------------
DROP TABLE IF EXISTS `mst_tree`;
CREATE TABLE `mst_tree`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `kelompok` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `menuatas` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `submenu` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `halaman` varchar(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `urut` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 63 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for ref_tran_status
-- ----------------------------
DROP TABLE IF EXISTS `ref_tran_status`;
CREATE TABLE `ref_tran_status`  (
  `STAT` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KET` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `JENIS` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `urut` int(11) NOT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for ref_user_status
-- ----------------------------
DROP TABLE IF EXISTS `ref_user_status`;
CREATE TABLE `ref_user_status`  (
  `urut` int(11) NOT NULL,
  `kode_status` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `uraian` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for scctbill
-- ----------------------------
DROP TABLE IF EXISTS `scctbill`;
CREATE TABLE `scctbill`  (
  `CUSTID` int(11) NOT NULL,
  `BILLCD` char(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `BILLAC` char(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `BILLNM` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `BILLAM` bigint(20) NULL DEFAULT NULL,
  `FLPART` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `PAIDST` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `PAIDDT` datetime NULL DEFAULT NULL,
  `NOREFF` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `FSTSBolehBayar` tinyint(4) NULL DEFAULT 1,
  `FUrutan` int(11) NULL DEFAULT NULL,
  `FTGLTagihan` datetime NULL DEFAULT NULL,
  `FIDBANK` char(7) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `FRecID` char(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `AA` int(11) NOT NULL AUTO_INCREMENT,
  `BTA` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`AA`, `CUSTID`, `BILLCD`) USING BTREE,
  INDEX `IDX_BILL`(`CUSTID` ASC, `BILLCD` ASC, `BILLAC` ASC, `BILLNM` ASC, `BILLAM` ASC, `PAIDST` ASC, `PAIDDT` ASC, `FSTSBolehBayar` ASC, `FUrutan` ASC, `BTA` ASC, `NOREFF` ASC) USING BTREE,
  CONSTRAINT `scctbill_ibfk_1` FOREIGN KEY (`CUSTID`) REFERENCES `scctcust` (`CUSTID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 18878 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for scctbill_detail
-- ----------------------------
DROP TABLE IF EXISTS `scctbill_detail`;
CREATE TABLE `scctbill_detail`  (
  `AA` int(11) NULL DEFAULT NULL,
  `KodePost` varchar(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `BILLAM` bigint(20) NULL DEFAULT NULL,
  `CUSTID` int(11) NOT NULL,
  `FID` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tahun` varchar(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `periode` varchar(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `BILLCD` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`KodePost`, `CUSTID`, `BILLCD`) USING BTREE,
  INDEX `IDX_DET`(`KodePost` ASC, `BILLAM` ASC, `CUSTID` ASC, `tahun` ASC, `periode` ASC, `BILLCD` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for scctcust
-- ----------------------------
DROP TABLE IF EXISTS `scctcust`;
CREATE TABLE `scctcust`  (
  `CUSTID` int(11) NOT NULL AUTO_INCREMENT,
  `NOCUST` char(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NMCUST` char(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NUM2ND` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `STCUST` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT '1',
  `CODE01` char(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT '75',
  `DESC01` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'ITTIHAD',
  `CODE02` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC02` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CODE03` char(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC03` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CODE04` char(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC04` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `CODE05` char(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DESC05` char(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TOTPAY` char(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `GENUS` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`CUSTID`) USING BTREE,
  INDEX `IDX_CUST`(`CUSTID` ASC, `NOCUST` ASC, `NMCUST` ASC, `NUM2ND` ASC, `STCUST` ASC, `CODE02` ASC, `DESC02` ASC, `CODE03` ASC, `DESC03` ASC, `DESC04` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3162 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sccttran
-- ----------------------------
DROP TABLE IF EXISTS `sccttran`;
CREATE TABLE `sccttran`  (
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `METODE` char(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TRXDATE` datetime NOT NULL,
  `NOREFF` char(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `FIDBANK` char(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KDCHANNEL` char(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `DEBET` bigint(20) NOT NULL,
  `KREDIT` bigint(20) NOT NULL,
  `REFFBANK` char(14) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TRANSNO` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19595 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_kantin
-- ----------------------------
DROP TABLE IF EXISTS `sm_kantin`;
CREATE TABLE `sm_kantin`  (
  `urut` int(11) NOT NULL,
  `KDKANTIN` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `NamaKantin` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KDMERCAN` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`, `KDKANTIN`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_mercan
-- ----------------------------
DROP TABLE IF EXISTS `sm_mercan`;
CREATE TABLE `sm_mercan`  (
  `urut` int(11) NOT NULL,
  `KDMERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NamaMercan` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `Saldo` bigint(20) NULL DEFAULT NULL,
  `TglUpdateSal` datetime NULL DEFAULT NULL,
  `MetodeUp` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_mercan_cair
-- ----------------------------
DROP TABLE IF EXISTS `sm_mercan_cair`;
CREATE TABLE `sm_mercan_cair`  (
  `urut` int(11) NOT NULL,
  `KDMERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NamaPenerima` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TglTerima` datetime NULL DEFAULT NULL,
  `Nominal` bigint(20) NULL DEFAULT NULL,
  `NoTerima` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_mercan_mesin
-- ----------------------------
DROP TABLE IF EXISTS `sm_mercan_mesin`;
CREATE TABLE `sm_mercan_mesin`  (
  `urut` int(11) NOT NULL,
  `KDMERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `MESIN` int(11) NULL DEFAULT NULL,
  `KDKANTIN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_mesin
-- ----------------------------
DROP TABLE IF EXISTS `sm_mesin`;
CREATE TABLE `sm_mesin`  (
  `urut` int(11) NOT NULL,
  `NOMESIN` varchar(23) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kode_mesin` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_pin
-- ----------------------------
DROP TABLE IF EXISTS `sm_pin`;
CREATE TABLE `sm_pin`  (
  `CUSTID` int(11) NULL DEFAULT NULL,
  `PID` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `PIN` char(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `BLOKIR` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`PID`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_rec_absen
-- ----------------------------
DROP TABLE IF EXISTS `sm_rec_absen`;
CREATE TABLE `sm_rec_absen`  (
  `urut` int(11) NOT NULL,
  `PID` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NOMESIN` char(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TGLTAPIN` datetime NULL DEFAULT NULL,
  `TGLTAPOUT` datetime NULL DEFAULT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_rec_info
-- ----------------------------
DROP TABLE IF EXISTS `sm_rec_info`;
CREATE TABLE `sm_rec_info`  (
  `urut` int(11) NOT NULL,
  `PID` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NOMESIN` char(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TGLTAP` datetime NULL DEFAULT NULL,
  `STAT` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_rec_pin
-- ----------------------------
DROP TABLE IF EXISTS `sm_rec_pin`;
CREATE TABLE `sm_rec_pin`  (
  `urut` int(11) NOT NULL,
  `PID` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NOMESIN` char(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TGLTAP` datetime NULL DEFAULT NULL,
  `STAT` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_rec_rfid
-- ----------------------------
DROP TABLE IF EXISTS `sm_rec_rfid`;
CREATE TABLE `sm_rec_rfid`  (
  `urut` int(11) NOT NULL,
  `PID` char(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NOMESIN` char(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TGLTAPIN` datetime NULL DEFAULT NULL,
  `TGLTAPOUT` datetime NULL DEFAULT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_rec_tap
-- ----------------------------
DROP TABLE IF EXISTS `sm_rec_tap`;
CREATE TABLE `sm_rec_tap`  (
  `urut` int(11) NOT NULL,
  `PID` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NOMESIN` char(17) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TGLTAP` datetime NULL DEFAULT NULL,
  `STAT` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_stat
-- ----------------------------
DROP TABLE IF EXISTS `sm_stat`;
CREATE TABLE `sm_stat`  (
  `STAT` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KET` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `JENIS` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_topup
-- ----------------------------
DROP TABLE IF EXISTS `sm_topup`;
CREATE TABLE `sm_topup`  (
  `urut` int(11) NOT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `NOMINAL` bigint(20) NULL DEFAULT NULL,
  `TOPUPNO` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TRXDATE` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_tran
-- ----------------------------
DROP TABLE IF EXISTS `sm_tran`;
CREATE TABLE `sm_tran`  (
  `urut` int(11) NOT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `NOMINAL` bigint(20) NULL DEFAULT NULL,
  `MESIN` int(11) NULL DEFAULT NULL,
  `TRANSNO` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TRXDATE` datetime NULL DEFAULT NULL,
  `MERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `STATUS` int(11) NULL DEFAULT NULL,
  `DETILBRG` int(11) NULL DEFAULT NULL,
  `Print` tinyint(4) NULL DEFAULT NULL,
  `KANTIN` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_tran_complete
-- ----------------------------
DROP TABLE IF EXISTS `sm_tran_complete`;
CREATE TABLE `sm_tran_complete`  (
  `urut` int(11) NOT NULL,
  `CUSTID` int(11) NULL DEFAULT NULL,
  `NOMINAL` bigint(20) NULL DEFAULT NULL,
  `MESIN` int(11) NULL DEFAULT NULL,
  `TRANSNO` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `TRXDATE` datetime NULL DEFAULT NULL,
  `MERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `STATUS` int(11) NULL DEFAULT NULL,
  `DETILBRG` int(11) NULL DEFAULT NULL,
  `Print` tinyint(4) NULL DEFAULT NULL,
  `KANTIN` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_tran_detail
-- ----------------------------
DROP TABLE IF EXISTS `sm_tran_detail`;
CREATE TABLE `sm_tran_detail`  (
  `urut` int(11) NOT NULL,
  `nama_barang` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `TRANSNO` char(16) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `HARGA` bigint(20) NULL DEFAULT NULL,
  `SUBTOTAL` bigint(20) NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_user
-- ----------------------------
DROP TABLE IF EXISTS `sm_user`;
CREATE TABLE `sm_user`  (
  `userlogin` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `kunci` varchar(70) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `ket` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `jabatan` int(11) NULL DEFAULT NULL,
  `MERCAN` varchar(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`, `userlogin`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1231 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for sm_user_levels
-- ----------------------------
DROP TABLE IF EXISTS `sm_user_levels`;
CREATE TABLE `sm_user_levels`  (
  `UserLevelID` int(11) NOT NULL,
  `UserLevelName` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`UserLevelID`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for u_akun
-- ----------------------------
DROP TABLE IF EXISTS `u_akun`;
CREATE TABLE `u_akun`  (
  `KodeAkun` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `NamaAkun` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NoRek` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`KodeAkun`) USING BTREE,
  INDEX `IDX_akun`(`KodeAkun` ASC, `NamaAkun` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for u_daftar_harga
-- ----------------------------
DROP TABLE IF EXISTS `u_daftar_harga`;
CREATE TABLE `u_daftar_harga`  (
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `kode_fak` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kode_prod` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KodeAkun` varchar(12) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `thn_masuk` varchar(18) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nominal` char(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NamaAkun` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NoRek` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kode_prod_psb` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 841 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- Table structure for u_daftar_harga_copy
-- ----------------------------
DROP TABLE IF EXISTS `u_daftar_harga_copy`;
CREATE TABLE `u_daftar_harga_copy`  (
  `urut` int(11) NOT NULL AUTO_INCREMENT,
  `kode_fak` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `kode_prod` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `KodeAkun` varchar(12) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `thn_masuk` varchar(18) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `nominal` char(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NamaAkun` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `NoRek` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`urut`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 352 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci;

-- ----------------------------
-- View structure for v_absensi
-- ----------------------------
DROP VIEW IF EXISTS `v_absensi`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_absensi` AS select `sm_rec_absen`.`NOMESIN` AS `NOMESIN`,`sm_rec_absen`.`TGLTAPIN` AS `TGLIN`,`sm_rec_absen`.`TGLTAPIN` AS `TGLTAPIN`,`sm_rec_absen`.`TGLTAPOUT` AS `TGLTAPOUT`,`scctcust`.`NOCUST` AS `NOCUST`,`scctcust`.`NMCUST` AS `NMCUST`,`scctcust`.`DESC02` AS `DESC02`,`scctcust`.`DESC03` AS `DESC03`,`scctcust`.`DESC04` AS `DESC04`,`sm_rec_absen`.`PID` AS `PID` from (`sm_rec_absen` join `scctcust` on(`sm_rec_absen`.`CUSTID` = `scctcust`.`CUSTID`));

-- ----------------------------
-- View structure for v_akuganteng
-- ----------------------------
DROP VIEW IF EXISTS `v_akuganteng`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_akuganteng` AS select `mst_kelas`.`kelas` AS `kelas`,`mst_kelas`.`jenjang` AS `jenjang`,`mst_kelas`.`id` AS `id`,`mst_kelas`.`unit` AS `unit`,`u_daftar_harga`.`thn_masuk` AS `thn_masuk`,`u_daftar_harga`.`nominal` AS `nominal`,`u_akun`.`NamaAkun` AS `NamaAkun`,`u_akun`.`NoRek` AS `NoRek`,`u_daftar_harga`.`kode_prod_psb` AS `kode_prod_psb`,`u_akun`.`KodeAkun` AS `KodeAkun` from ((`mst_kelas` join `u_daftar_harga` on(`mst_kelas`.`id` = `u_daftar_harga`.`kode_prod`)) join `u_akun` on(`u_daftar_harga`.`KodeAkun` = `u_akun`.`KodeAkun`));

-- ----------------------------
-- View structure for v_biaya_akun
-- ----------------------------
DROP VIEW IF EXISTS `v_biaya_akun`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_biaya_akun` AS select `mst_kelas`.`kelas` AS `kelas`,`mst_kelas`.`jenjang` AS `jenjang`,`mst_kelas`.`unit` AS `unit`,`u_daftar_harga`.`KodeAkun` AS `KodeAkun`,`u_daftar_harga`.`thn_masuk` AS `thn_masuk`,`u_daftar_harga`.`nominal` AS `nominal`,`u_akun`.`NamaAkun` AS `NamaAkun`,`u_daftar_harga`.`kode_prod` AS `CODE03`,`u_daftar_harga`.`kode_prod_psb` AS `kode_prod_psb` from ((`mst_kelas` join `u_daftar_harga` on(`mst_kelas`.`id` = `u_daftar_harga`.`kode_prod`)) join `u_akun` on(`u_daftar_harga`.`KodeAkun` = `u_akun`.`KodeAkun`));

-- ----------------------------
-- View structure for v_dt_daftar_harga
-- ----------------------------
DROP VIEW IF EXISTS `v_dt_daftar_harga`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_dt_daftar_harga` AS select `u_daftar_harga`.`kode_fak` AS `kode_fak`,`u_daftar_harga`.`kode_prod` AS `kode_prod`,`u_daftar_harga`.`KodeAkun` AS `KodeAkun`,`u_akun`.`NamaAkun` AS `NamaAkun`,`u_daftar_harga`.`thn_masuk` AS `thn_masuk`,`u_daftar_harga`.`nominal` AS `nominal` from (`u_daftar_harga` join `u_akun` on(`u_akun`.`KodeAkun` = `u_daftar_harga`.`KodeAkun`));

-- ----------------------------
-- View structure for v_saldo_va
-- ----------------------------
DROP VIEW IF EXISTS `v_saldo_va`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_saldo_va` AS select `sccttran`.`CUSTID` AS `CUSTID`,`scctcust`.`CODE04` AS `CODE04`,`scctcust`.`CODE01` AS `CODE01`,`scctcust`.`NOCUST` AS `NOCUST`,`scctcust`.`NMCUST` AS `NMCUST`,`scctcust`.`NUM2ND` AS `NUM2ND`,`scctcust`.`STCUST` AS `STCUST`,`scctcust`.`CODE02` AS `CODE02`,`scctcust`.`DESC02` AS `DESC02`,`scctcust`.`CODE03` AS `CODE03`,`scctcust`.`DESC03` AS `DESC03`,`scctcust`.`GENUS` AS `GENUS`,`scctcust`.`DESC04` AS `DESC04`,sum(`sccttran`.`KREDIT` - `sccttran`.`DEBET`) AS `SALDO` from (`sccttran` join `scctcust` on(`scctcust`.`CUSTID` = `sccttran`.`CUSTID` and `scctcust`.`STCUST` = 1)) group by `sccttran`.`CUSTID`,`scctcust`.`NOCUST`,`scctcust`.`NMCUST`,`scctcust`.`NUM2ND`,`scctcust`.`STCUST`,`scctcust`.`DESC02`,`scctcust`.`DESC03`,`scctcust`.`GENUS`,`scctcust`.`DESC04`,`scctcust`.`CODE02`,`scctcust`.`CODE03`;

-- ----------------------------
-- View structure for v_saldo_va_non
-- ----------------------------
DROP VIEW IF EXISTS `v_saldo_va_non`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_saldo_va_non` AS select `sccttran`.`CUSTID` AS `CUSTID`,`scctcust`.`CODE04` AS `CODE04`,`scctcust`.`CODE01` AS `CODE01`,`scctcust`.`NOCUST` AS `NOCUST`,`scctcust`.`NMCUST` AS `NMCUST`,`scctcust`.`NUM2ND` AS `NUM2ND`,`scctcust`.`STCUST` AS `STCUST`,`scctcust`.`CODE02` AS `CODE02`,`scctcust`.`DESC02` AS `DESC02`,`scctcust`.`CODE03` AS `CODE03`,`scctcust`.`DESC03` AS `DESC03`,`scctcust`.`GENUS` AS `GENUS`,`scctcust`.`DESC04` AS `DESC04`,sum(`sccttran`.`KREDIT` - `sccttran`.`DEBET`) AS `SALDO` from (`sccttran` join `scctcust` on(`scctcust`.`CUSTID` = `sccttran`.`CUSTID` and `scctcust`.`STCUST` = 0)) group by `sccttran`.`CUSTID`,`scctcust`.`NOCUST`,`scctcust`.`NMCUST`,`scctcust`.`NUM2ND`,`scctcust`.`STCUST`,`scctcust`.`DESC02`,`scctcust`.`DESC03`,`scctcust`.`GENUS`,`scctcust`.`DESC04`,`scctcust`.`CODE02`,`scctcust`.`CODE03`;

-- ----------------------------
-- View structure for v_sia_pembayaran
-- ----------------------------
DROP VIEW IF EXISTS `v_sia_pembayaran`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_sia_pembayaran` AS select `a`.`BTA` AS `BTA`,`a`.`BILLNM` AS `BILLNM`,`a`.`BILLAM` AS `BILLAM`,`b`.`NOCUST` AS `NOCUST`,`b`.`NMCUST` AS `NMCUST`,`a`.`PAIDDT` AS `PAIDDT`,`a`.`PAIDST` AS `PAIDST`,`a`.`AA` AS `AA`,`a`.`CUSTID` AS `CUSTID`,`a`.`BILLCD` AS `BILLCD` from (`scctbill` `a` join `scctcust` `b` on(`a`.`CUSTID` = `b`.`CUSTID`)) where `a`.`FSTSBolehBayar` = 1;

-- ----------------------------
-- View structure for v_sia_pembayaran_detail
-- ----------------------------
DROP VIEW IF EXISTS `v_sia_pembayaran_detail`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_sia_pembayaran_detail` AS select `scctbill_detail`.`AA` AS `AA`,`scctbill_detail`.`KodePost` AS `KodePost`,`scctbill_detail`.`BILLAM` AS `BILLAM`,`scctbill_detail`.`CUSTID` AS `CUSTID`,`scctbill_detail`.`FID` AS `FID`,`scctbill_detail`.`tahun` AS `tahun`,`scctbill_detail`.`periode` AS `periode`,`scctbill_detail`.`BILLCD` AS `BILLCD`,`u_akun`.`NamaAkun` AS `NamaAkun`,`u_akun`.`KodeAkun` AS `KodeAkun` from (`scctbill_detail` join `u_akun` on(`scctbill_detail`.`KodePost` = `u_akun`.`KodeAkun`)) where `scctbill_detail`.`BILLAM` <> 0;

-- ----------------------------
-- View structure for v_status_tapping
-- ----------------------------
DROP VIEW IF EXISTS `v_status_tapping`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_status_tapping` AS select `ref_tran_status`.`STAT` AS `STAT`,`ref_tran_status`.`KET` AS `KET` from `ref_tran_status` where `ref_tran_status`.`JENIS` = 'TAPPING';

-- ----------------------------
-- View structure for v_test
-- ----------------------------
DROP VIEW IF EXISTS `v_test`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_test` AS select `mst_kelas`.`kelas` AS `kelas`,`mst_kelas`.`jenjang` AS `jenjang`,`mst_kelas`.`unit` AS `unit`,`mst_kelas`.`kelompok` AS `kelompok`,`u_akun`.`KodeAkun` AS `KodeAkun`,`u_akun`.`NamaAkun` AS `NamaAkun`,`mst_kelas`.`id` AS `id`,`u_daftar_harga`.`thn_masuk` AS `thn_masuk`,`u_daftar_harga`.`nominal` AS `nominal`,`u_daftar_harga`.`NoRek` AS `NoRek`,`u_daftar_harga`.`kode_prod_psb` AS `kode_prod_psb`,`u_daftar_harga`.`urut` AS `urut` from ((`u_daftar_harga` join `u_akun` on(`u_daftar_harga`.`KodeAkun` = `u_akun`.`KodeAkun`)) join `mst_kelas` on(`u_daftar_harga`.`kode_prod` = `mst_kelas`.`id`));

-- ----------------------------
-- View structure for v_total_biaya
-- ----------------------------
DROP VIEW IF EXISTS `v_total_biaya`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_total_biaya` AS select sum(`v_biaya_akun`.`nominal`) AS `nominal`,`v_biaya_akun`.`kelas` AS `kelas`,`v_biaya_akun`.`jenjang` AS `jenjang`,`v_biaya_akun`.`unit` AS `unit`,`v_biaya_akun`.`CODE03` AS `CODE03`,`v_biaya_akun`.`thn_masuk` AS `thn_masuk`,`v_biaya_akun`.`kode_prod_psb` AS `kode_prod_psb` from `v_biaya_akun` group by `v_biaya_akun`.`CODE03`,`v_biaya_akun`.`kelas`,`v_biaya_akun`.`jenjang`,`v_biaya_akun`.`unit`,`v_biaya_akun`.`thn_masuk`;

-- ----------------------------
-- View structure for v_trans_done
-- ----------------------------
DROP VIEW IF EXISTS `v_trans_done`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_trans_done` AS select `sm_tran`.`urut` AS `urut`,`sm_tran`.`CUSTID` AS `CUSTID`,`sm_tran`.`NOMINAL` AS `NOMINAL`,`sm_tran`.`MESIN` AS `MESIN`,`sm_tran`.`TRANSNO` AS `TRANSNO`,`sm_tran`.`TRXDATE` AS `TRXDATE`,`sm_tran`.`MERCAN` AS `MERCAN`,`sm_tran`.`STATUS` AS `STATUS`,`sm_tran`.`DETILBRG` AS `DETILBRG`,`sm_tran`.`Print` AS `Print`,`sm_tran`.`KANTIN` AS `KANTIN` from `sm_tran` where `sm_tran`.`STATUS` <> 1 and `sm_tran`.`STATUS` <> 3;

-- ----------------------------
-- View structure for v_trans_done_detil
-- ----------------------------
DROP VIEW IF EXISTS `v_trans_done_detil`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_trans_done_detil` AS select `sm_tran_detail`.`urut` AS `urut`,`sm_tran_detail`.`nama_barang` AS `nama_barang`,`sm_tran_detail`.`qty` AS `qty`,`sm_tran_detail`.`TRANSNO` AS `TRANSNO`,`sm_tran_detail`.`HARGA` AS `HARGA`,`sm_tran_detail`.`SUBTOTAL` AS `SUBTOTAL` from `sm_tran_detail`;

-- ----------------------------
-- View structure for v_trans_saldo
-- ----------------------------
DROP VIEW IF EXISTS `v_trans_saldo`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_trans_saldo` AS select `sccttran`.`TRXDATE` AS `TRXDATE`,`sccttran`.`METODE` AS `KETERANGAN`,`sccttran`.`DEBET` AS `DEBET`,`sccttran`.`KREDIT` AS `KREDIT`,`scctcust`.`NOCUST` AS `NOCUST`,`scctcust`.`NUM2ND` AS `NUM2ND`,`sccttran`.`FIDBANK` AS `FIDBANK` from (`sccttran` left join `scctcust` on(`scctcust`.`CUSTID` = `sccttran`.`CUSTID`));

-- ----------------------------
-- Procedure structure for BankBayar
-- ----------------------------
DROP PROCEDURE IF EXISTS `BankBayar`;
delimiter ;;
CREATE PROCEDURE `BankBayar`(p_NoVa  varchar(20) , p_REFNO varchar(20),p_TRXDATE datetime, p_CHANNELID VARCHAR(3) , p_PAYMENT bigint, p_BILL BIGINT)
BEGIN
	-- input transaksi untuk nambah saldo
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(20);
	DECLARE v_CUSTID INT;	
	DECLARE v_SALDO BIGINT;
	DECLARE v_eLPe INT;
	DECLARE v_BILL BIGINT;
	
	-- set v_NIM = TRIM(LEADING '0' FROM SUBSTRING(p_NoVa, 7, 10));
	-- set v_CUSTID = GetCustID(v_NIM);
	set v_CUSTID = GetCustID(p_NoVa);
	
	SELECT COUNT(NOREFF) INTO v_eLPe FROM SCCTTRAN WHERE  NOREFF =  p_REFNO AND CUSTID = v_CUSTID;
	
	-- cek no reff smaa di transaksi jangan di isi
	IF v_eLPe = 0 THEN
		SELECT CONCAT('BMI' , DATE_FORMAT(NOW(),12) + RIGHT('0000000' + MAX(RIGHT(TRANSNO,6) + 1), 6)) INTO v_TRANSNO
		FROM SCCTTRAN	WHERE	SUBSTRING(TRANSNO,4,6) = DATE_FORMAT(NOW(),'%y%m%d');
		
		INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, KREDIT, TRANSNO, METODE)
		VALUES(v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, p_PAYMENT, v_TRANSNO, 'TOP UP');
		
		-- cari tagihan yang diantrikan di invoice		
		-- SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
		-- BEGIN TRANSACTION;
		SELECT FUrutan,BILLAM INTO v_eLPe,v_BILL FROM SCCTBILL WHERE CUSTID = v_CUSTID AND FSTSBolehBayar = 1 AND PAIDST = 0  ORDER BY FUrutan ASC LIMIT 0,1;
		
		IF  GetSaldoCus(v_CUSTID) >= v_BILL THEN -- jika transfer melebihi total nominal antrian inv, auto bayar invoice
		-- seluruh tagihan yang dari invoice dibayarkan	
			#SELECT MIN(FUrutan) INTO v_eLPe FROM SCCTBILL WHERE CUSTID = v_CUSTID  AND FSTSBolehBayar = 1 AND PAIDST = 0;
			
			
			UPDATE SCCTBILL SET PAIDST = 1, PAIDDT =  p_TRXDATE, FIDBANK = p_CHANNELID, NOREFF = p_REFNO
			WHERE	CUSTID = v_CUSTID   
			AND	FSTSBolehBayar = 1 
			AND	PAIDST = 0
			AND FUrutan = v_eLPe;
			
			-- input transaksi pembayaran untuk mengurangi saldo
			INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, DEBET, TRANSNO, METODE)
			VALUES (v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, v_BILL, v_TRANSNO, 'FROM INVOICE');
		END IF;
	END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for BankBayarOld
-- ----------------------------
DROP PROCEDURE IF EXISTS `BankBayarOld`;
delimiter ;;
CREATE PROCEDURE `BankBayarOld`(p_NoVa  varchar(20) , p_REFNO varchar(20),p_TRXDATE datetime, p_CHANNELID VARCHAR(3) , p_PAYMENT bigint, p_BILL BIGINT)
BEGIN
	-- input transaksi untuk nambah saldo
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(20);
	DECLARE v_CUSTID INT;	
	DECLARE v_SALDO BIGINT;
	DECLARE v_eLPe INT;
	
	-- set v_NIM = TRIM(LEADING '0' FROM SUBSTRING(p_NoVa, 7, 10));
	-- set v_CUSTID = GetCustID(v_NIM);
	set v_CUSTID = GetCustID(p_NoVa);
	
	SELECT COUNT(NOREFF) INTO v_eLPe FROM SCCTTRAN WHERE  NOREFF =  p_REFNO AND CUSTID = v_CUSTID;
	
	-- cek no reff smaa di transaksi jangan di isi
	IF v_eLPe = 0 THEN
		SELECT CONCAT('BMI' , DATE_FORMAT(NOW(),12) + RIGHT('0000000' + MAX(RIGHT(TRANSNO,6) + 1), 6)) INTO v_TRANSNO
		FROM SCCTTRAN	WHERE	SUBSTRING(TRANSNO,4,6) = DATE_FORMAT(NOW(),'%y%m%d');
		
		INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, KREDIT, TRANSNO, METODE)
		VALUES(v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, p_PAYMENT, v_TRANSNO, 'TOP UP');
		
		-- cari tagihan yang diantrikan di invoice		
		-- SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
		-- BEGIN TRANSACTION;
		IF  GetSaldoCus(v_CUSTID) >= p_BILL THEN -- jika transfer melebihi total nominal antrian inv, auto bayar invoice
		-- seluruh tagihan yang dari invoice dibayarkan	
			SELECT MIN(FUrutan) INTO v_eLPe FROM SCCTBILL WHERE CUSTID = v_CUSTID  AND FSTSBolehBayar = 1 AND PAIDST = 0;
			
			UPDATE SCCTBILL SET PAIDST = 1, PAIDDT =  p_TRXDATE, FIDBANK = p_CHANNELID, NOREFF = p_REFNO
			WHERE	CUSTID = v_CUSTID   
			AND	FSTSBolehBayar = 1 
			AND	PAIDST = 0
			AND FUrutan = v_eLPe;
			
			-- input transaksi pembayaran untuk mengurangi saldo
			INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, DEBET, TRANSNO, METODE)
			VALUES (v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, p_BILL, v_TRANSNO, 'FROM INVOICE');
		END IF;
	END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for BankRevesal
-- ----------------------------
DROP PROCEDURE IF EXISTS `BankRevesal`;
delimiter ;;
CREATE PROCEDURE `BankRevesal`(p_NoVa varchar(20) , p_REFNO varchar(20)  , p_TRXDATE DATETIME, p_CHANNELID VARCHAR(3) , p_PAYMENTDATE DATETIME, p_PAYMENT BIGINT)
BEGIN
	DECLARE v_BILL BIGINT; 
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(10);
	DECLARE v_CUSTID INT;	
	
	-- set @NIM = SUBSTRING(@NoVa, 7, 10)
	set v_NIM = TRIM(LEADING '0' FROM SUBSTRING(p_NoVa, 11, 6));
	set v_CUSTID = GetCustID(v_NIM);
	IF NOT EXISTS (SELECT NOREFF FROM sccttran WHERE NOREFF = p_REFNO AND METODE='REVERSAL') THEN
		update scctbill set PAIDST = 0
				, PAIDDT =  NULL
				, FIDBANK = NULL
				where
				CUSTID = v_CUSTID 
				AND PAIDDT = p_PAYMENTDATE
				AND NOREFF = p_REFNO
				AND	FSTSBolehBayar = 1 
				AND	PAIDST = 1;
		SELECT  TRANSNO INTO v_TRANSNO FROM
		SCCTTRAN
		WHERE
		CUSTID = v_CUSTID
		AND NOREFF = p_REFNO
		AND TRXDATE = p_PAYMENTDATE
		AND DEBET = 0;
		SELECT DEBET INTO v_BILL FROM
		SCCTTRAN
		WHERE
		CUSTID = v_CUSTID
		AND NOREFF = p_REFNO
		AND TRXDATE = p_PAYMENTDATE
		AND KREDIT = 0; 
	
		IF ISNULL(v_BILL) or v_BILL = 0 THEN
			INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, DEBET,  TRANSNO, METODE)
			VALUES (v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, p_PAYMENT, v_TRANSNO, 'REVERSAL');
		ELSE
			INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, DEBET, KREDIT, TRANSNO, METODE)
			VALUES (v_CUSTID, p_REFNO, p_TRXDATE, p_CHANNELID, p_PAYMENT, v_BILL, v_TRANSNO, 'REVERSAL');
		-- masih masalah di BILL atau PAYMENT
		END IF;
	END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for BLOKIR
-- ----------------------------
DROP PROCEDURE IF EXISTS `BLOKIR`;
delimiter ;;
CREATE PROCEDURE `BLOKIR`(p_PID VARCHAR(16))
BEGIN
	-- input transaksi untuk nambah saldo
	
		UPDATE SM_PIN SET BLOKIR = 1 WHERE RTRIM(PID) = p_PID;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for CekBlokir
-- ----------------------------
DROP PROCEDURE IF EXISTS `CekBlokir`;
delimiter ;;
CREATE PROCEDURE `CekBlokir`(p_PID VARCHAR(16))
BEGIN
	-- input transaksi untuk nambah saldo
	
		SELECT BLOKIR FROM SM_PIN WHERE RTRIM(PID) = p_PID;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for CekLogin
-- ----------------------------
DROP PROCEDURE IF EXISTS `CekLogin`;
delimiter ;;
CREATE PROCEDURE `CekLogin`(p_NoInduk varchar(10), p_Pass varchar(50))
BEGIN
	
	SELECT COUNT(urut) as UserAda, NMCUST as Nama, CODE02 as Jenjang, DESC02 as Unit, DESC03 as Kelas  FROM SM_USER
	INNER JOIN SCCTCUST ON NOCUST=userlogin and STCUST=1
	WHERE userlogin=p_NoInduk AND kunci=p_Pass
	GROUP BY NMCUST, DESC02, DESC03, CODE02;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetBelanja
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetBelanja`;
delimiter ;;
CREATE PROCEDURE `GetBelanja`(p_PID VARCHAR(16), p_NOMESIN CHAR(17))
BEGIN
	-- input transaksi untuk nambah saldo
		-- DECLARE @CUSTID INT
		-- SELECT @CUSTID = CUSTID FROM SM_PIN WHERE PID=@PID
		DECLARE v_MESIN INT;
		SELECT urut INTO v_MESIN FROM SM_MESIN WHERE NOMESIN=p_NOMESIN;
		SELECT
		SM_TRAN.CUSTID,
		SM_TRAN.NOMINAL,
		SM_TRAN.MESIN,
		SM_TRAN.TRANSNO,
		SM_TRAN.MERCAN,
		SM_TRAN.STATUS
		FROM
		SM_TRAN
		WHERE  STATUS = 1 and MESIN=v_MESIN;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetCustID
-- ----------------------------
DROP FUNCTION IF EXISTS `GetCustID`;
delimiter ;;
CREATE FUNCTION `GetCustID`(p_NIM CHAR(20))
 RETURNS int(11)
BEGIN
    DECLARE v_VHASIL INT;
		DECLARE d_NODAFTAR CHAR(10);
		DECLARE d_NIM CHAR(10);
	set d_NIM = TRIM(LEADING '0' FROM SUBSTRING(p_NIM, 7, 10));
	set d_NODAFTAR =  SUBSTRING(p_NIM, 7, 10);
	SELECT CUSTID INTO v_VHASIL FROM SCCTCUST WHERE (NUM2ND = d_NODAFTAR or NOCUST = d_NIM) AND STCUST=1;
    RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetDetailTagihan
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetDetailTagihan`;
delimiter ;;
CREATE PROCEDURE `GetDetailTagihan`(p_KodeCust INT, p_KodeTagihan varchar(20))
BEGIN
	SELECT
	SCCTBILL_DETAIL.KodePost as post,
	SCCTBILL_DETAIL.BILLAM as nominal_d,
	GetNamaPost(KodePost) as NamaPost
	FROM
	SCCTBILL_DETAIL 
	where SCCTBILL_DETAIL.CUSTID = p_KodeCust and SCCTBILL_DETAIL.BILLCD = p_KodeTagihan
	order by SCCTBILL_DETAIL.KodePost;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetJmlTunggak
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetJmlTunggak`;
delimiter ;;
CREATE PROCEDURE `GetJmlTunggak`(p_prodi varchar(2))
BEGIN
	SELECT
Count(SCCTBILL.BILLNM) AS jmlmhs,
SCCTCUST.CUSTID,
SCCTCUST.DESC03,
SCCTCUST.DESC02,
SCCTCUST.DESC04
-- dbo.SCCTCUST.NOCUST
FROM
SCCTBILL
INNER JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID
WHERE
SCCTBILL.PAIDST = 0
and rtrim(SCCTCUST.CODE03) = p_prodi -- and rtrim(dbo.SCCTCUST.DESC02) = @kelas  and rtrim(dbo.SCCTCUST.DESC04)=@thnmasuk
GROUP BY
SCCTCUST.CUSTID,
SCCTCUST.DESC02,
SCCTCUST.DESC03,
SCCTCUST.DESC04
-- dbo.SCCTCUST.NOCUST
ORDER BY SCCTCUST.DESC04;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetKDMercan
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetKDMercan`;
delimiter ;;
CREATE PROCEDURE `GetKDMercan`(p_MAC VARCHAR(20))
BEGIN
	SELECT
SM_MERCAN_MESIN.KDMERCAN,
SM_MERCAN_MESIN.MESIN
FROM
SM_MERCAN_MESIN
INNER JOIN SM_MESIN ON SM_MESIN.urut = SM_MERCAN_MESIN.MESIN
WHERE
SM_MESIN.NOMESIN=p_MAC;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetNamaMercan
-- ----------------------------
DROP FUNCTION IF EXISTS `GetNamaMercan`;
delimiter ;;
CREATE FUNCTION `GetNamaMercan`(p_KD CHAR(3))
 RETURNS varchar(50) CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
	DECLARE v_VHASIL VARCHAR(50);
	SELECT NamaMercan INTO v_VHASIL FROM SM_MERCAN WHERE KDMERCAN = p_KD;
	RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetNamaPost
-- ----------------------------
DROP FUNCTION IF EXISTS `GetNamaPost`;
delimiter ;;
CREATE FUNCTION `GetNamaPost`(p_Kode CHAR(5))
 RETURNS char(50) CHARSET latin1 COLLATE latin1_swedish_ci
BEGIN
	DECLARE v_VHASIL CHAR(50);
	SELECT NamaAkun INTO v_VHASIL FROM u_akun WHERE KodeAkun = p_Kode;
	RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetNoBelanja
-- ----------------------------
DROP FUNCTION IF EXISTS `GetNoBelanja`;
delimiter ;;
CREATE FUNCTION `GetNoBelanja`()
 RETURNS char(16) CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
  DECLARE v_VHASIL CHAR(16);
	SELECT CONCAT('SMC',DATE_FORMAT(NOW(),12),LPAD(COUNT(DISTINCT(TRANSNO))+1,7,0)) INTO v_VHASIL
	FROM SM_TRAN WHERE SUBSTRING(TRANSNO,4,6)=DATE_FORMAT(NOW(),12);
  RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetNoTerima
-- ----------------------------
DROP FUNCTION IF EXISTS `GetNoTerima`;
delimiter ;;
CREATE FUNCTION `GetNoTerima`()
 RETURNS char(16) CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
  DECLARE v_VHASIL CHAR(16);
	SELECT CONCAT('MCN',DATE_FORMAT(NOW(),12),LPAD(COUNT(DISTINCT(NoTerima))+1,7,0)) INTO v_VHASIL
	FROM SM_MERCAN_CAIR WHERE SUBSTRING(NoTerima,4,6)=DATE_FORMAT(NOW(),12);
  RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetPenerimaan
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetPenerimaan`;
delimiter ;;
CREATE PROCEDURE `GetPenerimaan`(p_Metode VARCHAR(10), p_Nim varchar(15) /* = NULL */, p_Nama_Tagihan varchar(30), p_Tahun_Akademik VARCHAR(50))
BEGIN
  
if p_Metode = 'List'
THEN
SELECT SCCTCUST.NMCUST as Mahasiswa,
		SCCTCUST.DESC02 as Fakultas,
		SCCTCUST.DESC03 as Jurusan,
		SCCTCUST.DESC05 as Alamat,
		SCCTBILL.BILLNM as NamaTagihan,
		SCCTBILL.BILLCD as KodeTagihan,
		SCCTBILL.BILLAM as Nominal,
		SCCTBILL.BTA as TahunAkademik,
		SCCTCUST.CUSTID as KodeCust,
		SCCTBILL.FIDBANK as KodeBank,
		SCCTBILL.PAIDDT as TanggalBayar
FROM
SCCTBILL
LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
where 
SCCTBILL.FSTSBolehBayar = 1 and SCCTCUST.STCUST = 1 AND SCCTBILL.PAIDST = 1 
AND (SCCTCUST.NOCUST = p_Nim OR SCCTCUST.NUM2ND = p_Nim);
ELSEIF p_Metode = 'Check'
THEN
		SELECT SCCTCUST.NMCUST as Mahasiswa,
		SCCTCUST.DESC02 as Fakultas,
		SCCTCUST.DESC03 as Jurusan,
		SCCTCUST.DESC05 as Alamat,
		SCCTBILL.BILLNM as NamaTagihan,
		SCCTBILL.BILLCD as KodeTagihan,
		SCCTBILL.BILLAM as Nominal,
		SCCTBILL.BTA as TahunAkademik,
		SCCTCUST.CUSTID as KodeCust,
		SCCTBILL.FIDBANK as KodeBank,
		SCCTBILL.PAIDDT as TanggalBayar
					-- dbo.SCCTBILL.CUSTID as custid
		FROM
		SCCTBILL
		LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
		where 
		SCCTBILL.FSTSBolehBayar = 1 and SCCTCUST.STCUST = 1 and SCCTBILL.PAIDST = 1
		AND SCCTBILL.BILLNM = p_Nama_Tagihan AND SCCTBILL.BTA = p_Tahun_Akademik
		AND (SCCTCUST.NOCUST = p_Nim OR SCCTCUST.NUM2ND = p_Nim);
	
end if;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetPIN
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetPIN`;
delimiter ;;
CREATE PROCEDURE `GetPIN`(p_PID VARCHAR(16))
BEGIN
	-- input transaksi untuk nambah saldo
	
		SELECT CUSTID, PIN FROM SM_PIN WHERE RTRIM(PID) = p_PID;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetRekapTagihan
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetRekapTagihan`;
delimiter ;;
CREATE PROCEDURE `GetRekapTagihan`(p_Tahun_Akademik VARCHAR(50) /* = '' */,
p_Kode_Post VARCHAR(50) /* = '' */,
p_Kelas VARCHAR(20))
BEGIN
	select a.KodePost as KodeTagihan, sum(a.BILLAM) as Nominal, d.DESC02 as Unit, d.DESC03 as Kelas,
 d.NOCUST as IDSantri, d.NMCUST as NamaSantri,
 c.NamaAkun as NamaTagihan, DATE_FORMAT(b.PAIDDT,120) as TanggalBayar, -- d.NOCUST as nim, d.NMCUST as NamaLengkap,
 b.BTA as TahunAkademik,
 b.FIDBANK as KodeBank
	from SCCTBILL_DETAIL a, SCCTBILL b, SCCTCUST d, u_akun c
	where
	a.BILLCD = b.BILLCD and a.CUSTID=b.CUSTID
	and b.CUSTID = d.CUSTID
	and b.PAIDST = 0
	and b.FSTSBolehBayar = 1
	and a.KodePost = c.KodeAkun
	-- and a.KodePost = @Kode_Post
	AND b.BTA = p_Tahun_Akademik 
	AND d.DESC03 = p_Kelas
	group by a.KodePost, d.DESC02, d.DESC03, 
	 c.NamaAkun,b.PAIDDT, d.NOCUST, d.NMCUST, b.BTA, b.FIDBANK;
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  >= @Dari_Tgl
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  <= @Sampai_Tgl
	END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetRekapTerima
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetRekapTerima`;
delimiter ;;
CREATE PROCEDURE `GetRekapTerima`(p_Tahun_Akademik VARCHAR(50) /* = '' */,
p_Kode_Post VARCHAR(50) /* = '' */,
p_Kelas VARCHAR(20))
BEGIN
	select a.KodePost as KodeTagihan, sum(a.BILLAM) as Nominal, d.DESC02 as Unit, d.DESC03 as Kelas,
 d.NOCUST as IDSantri, d.NMCUST as NamaSantri,
 c.NamaAkun as NamaTagihan, DATE_FORMAT(b.PAIDDT,120) as TanggalBayar, -- d.NOCUST as nim, d.NMCUST as NamaLengkap,
 b.BTA as TahunAkademik,
 b.FIDBANK as KodeBank
	from SCCTBILL_DETAIL a, SCCTBILL b, SCCTCUST d, u_akun c
	where
	a.BILLCD = b.BILLCD and a.CUSTID=b.CUSTID
	and b.CUSTID = d.CUSTID
	and b.PAIDST = 1
	and b.FSTSBolehBayar = 1
	and a.KodePost = c.KodeAkun
	-- and a.KodePost = @Kode_Post
	AND b.BTA = p_Tahun_Akademik 
	AND d.DESC03 = p_Kelas
	group by a.KodePost, d.DESC02, d.DESC03, 
	 c.NamaAkun,b.PAIDDT, d.NOCUST, d.NMCUST, b.BTA, b.FIDBANK;
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  >= @Dari_Tgl
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  <= @Sampai_Tgl
	END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetSaldo
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetSaldo`;
delimiter ;;
CREATE PROCEDURE `GetSaldo`(p_PID VARCHAR(16))
BEGIN
	-- input transaksi untuk nambah saldo
		DECLARE v_CUSTID INT;
		SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
		SELECT
		SCCTTRAN.CUSTID,
		SCCTCUST.NOCUST,
		SCCTCUST.NMCUST,
		SCCTCUST.NUM2ND,
		SCCTCUST.STCUST,
		SCCTCUST.DESC02,
		SCCTCUST.DESC03,
		SCCTCUST.GENUS,
		SCCTCUST.DESC04,
		sum(SCCTTRAN.KREDIT-SCCTTRAN.DEBET) as SALDO
		FROM
		SCCTTRAN
		LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTTRAN.CUSTID 
		WHERE
		 SCCTTRAN.CUSTID = v_CUSTID
		GROUP BY 
	  SCCTTRAN.CUSTID,
		SCCTCUST.NOCUST,
		SCCTCUST.NMCUST,
		SCCTCUST.NUM2ND,
		SCCTCUST.STCUST,
		SCCTCUST.DESC02,
		SCCTCUST.DESC03,
		SCCTCUST.GENUS,
		SCCTCUST.DESC04;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetSaldoCus
-- ----------------------------
DROP FUNCTION IF EXISTS `GetSaldoCus`;
delimiter ;;
CREATE FUNCTION `GetSaldoCus`(v_CUSTID INT)
 RETURNS bigint(20)
BEGIN
    DECLARE v_VHASIL BIGINT;    
	SELECT  SUM(KREDIT - DEBET) INTO v_VHASIL FROM sccttran WHERE CUSTID = v_CUSTID;
    RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetStatusTrans
-- ----------------------------
DROP FUNCTION IF EXISTS `GetStatusTrans`;
delimiter ;;
CREATE FUNCTION `GetStatusTrans`(p_TRANNO CHAR(17))
 RETURNS int(11)
BEGIN
  DECLARE v_VHASIL INT;
	SELECT STATUS INTO v_VHASIL FROM SM_TRAN WHERE TRANSNO = p_TRANNO;
  RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for GetTagihan
-- ----------------------------
DROP PROCEDURE IF EXISTS `GetTagihan`;
delimiter ;;
CREATE PROCEDURE `GetTagihan`(p_Metode VARCHAR(10), p_Nim varchar(20) /* = NULL */, p_Nama_Tagihan varchar(30), p_Tahun_Akademik VARCHAR(50))
BEGIN
  
if p_Metode = 'List'
	THEN
		SELECT SCCTCUST.NMCUST as Mahasiswa,
		SCCTCUST.DESC02 as Fakultas,
		SCCTCUST.DESC03 as Jurusan,
		SCCTCUST.DESC05 as Alamat,
		SCCTBILL.BILLNM as NamaTagihan,
		SCCTBILL.BILLCD as KodeTagihan,
		SCCTBILL.BILLAM as Nominal,
		SCCTBILL.BTA as TahunAkademik,
		SCCTCUST.CUSTID as KodeCust
					-- dbo.SCCTBILL.CUSTID as custid
		FROM
		SCCTBILL
		LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
		where 
		SCCTBILL.FSTSBolehBayar = 1 and SCCTCUST.STCUST = 1 and SCCTBILL.PAIDST = 0
		AND (SCCTCUST.NOCUST = p_Nim OR SCCTCUST.NUM2ND = p_Nim);
ELSEIF p_Metode = 'Check'
	THEN
		SELECT SCCTCUST.NMCUST as Mahasiswa,
		SCCTCUST.DESC02 as Fakultas,
		SCCTCUST.DESC03 as Jurusan,
		SCCTCUST.DESC05 as Alamat,
		SCCTBILL.BILLNM as NamaTagihan,
		SCCTBILL.BILLCD as KodeTagihan,
		SCCTBILL.BILLAM as Nominal,
		SCCTBILL.BTA as TahunAkademik,
		SCCTCUST.CUSTID as KodeCust
					-- dbo.SCCTBILL.CUSTID as custid
		FROM
		SCCTBILL
		LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
		where 
		SCCTBILL.FSTSBolehBayar = 1 and SCCTCUST.STCUST = 1 and SCCTBILL.PAIDST = 0
		AND SCCTBILL.BILLNM = p_Nama_Tagihan AND SCCTBILL.BTA = p_Tahun_Akademik
		AND (SCCTCUST.NOCUST = p_Nim OR SCCTCUST.NUM2ND = p_Nim);
	
end if;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetTopupNo
-- ----------------------------
DROP FUNCTION IF EXISTS `GetTopupNo`;
delimiter ;;
CREATE FUNCTION `GetTopupNo`()
 RETURNS char(16) CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
  DECLARE v_VHASIL CHAR(16);
	SELECT CONCAT('SCT',DATE_FORMAT(NOW(),12),LPAD(COUNT(DISTINCT(TOPUPNO))+1,7,0)) into v_VHASIL
	FROM SM_TOPUP WHERE SUBSTRING(TOPUPNO,4,6)=DATE_FORMAT(NOW(),12);
  RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for GetTransNo
-- ----------------------------
DROP FUNCTION IF EXISTS `GetTransNo`;
delimiter ;;
CREATE FUNCTION `GetTransNo`()
 RETURNS char(16) CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
  DECLARE v_VHASIL CHAR(16);
	SELECT  CONCAT('SMC',DATE_FORMAT(NOW(),12),LPAD(COUNT(DISTINCT(TRANSNO))+1,7,0)) into v_VHASIL
	FROM SM_TRAN WHERE SUBSTRING(TRANSNO,4,6)=DATE_FORMAT(NOW(),12);
  RETURN v_VHASIL;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for Get_Jml_Mhs
-- ----------------------------
DROP PROCEDURE IF EXISTS `Get_Jml_Mhs`;
delimiter ;;
CREATE PROCEDURE `Get_Jml_Mhs`(p_PRODI varchar(3), p_ANGKATAN varchar(4), p_KELAS VARCHAR(15))
BEGIN
	Select COUNT(NOCUST) as JMLMHS  from SCCTCUST WHERE CODE03=p_PRODI AND DESC04=p_ANGKATAN
AND STCUST=1 and DESC02=p_KELAS;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for Get_Max_urutan
-- ----------------------------
DROP PROCEDURE IF EXISTS `Get_Max_urutan`;
delimiter ;;
CREATE PROCEDURE `Get_Max_urutan`(p_NIM varchar(18))
BEGIN
DECLARE v_Urutan INT;
BEGIN
	Select CUSTID, IFNULL(COUNT(FUrutan), 0) + 2 as Urutan  from SCCTBILL WHERE 
CUSTID = -- @CUSTID -- group by custid
(
SELECT CUSTID FROM SCCTCUST where STCUST = 1 and (rtrim(NUM2ND) = p_NIM or rtrim(NOCUST) = p_NIM)
) 
GROUP BY CUSTID;
-- RETURN @Urutan;
END;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for LihatKelas
-- ----------------------------
DROP PROCEDURE IF EXISTS `LihatKelas`;
delimiter ;;
CREATE PROCEDURE `LihatKelas`(p_Unit VARCHAR(5))
BEGIN
SELECT 
mst_kelas.kelas as NamaKelas
FROM
mst_kelas
where 
mst_kelas.jenjang=p_Unit;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for LihatPenerimaan
-- ----------------------------
DROP PROCEDURE IF EXISTS `LihatPenerimaan`;
delimiter ;;
CREATE PROCEDURE `LihatPenerimaan`(p_Tahun_Akademik VARCHAR(50) /* = '' */,
p_Nama_Tagihan VARCHAR(50) /* = '' */,
p_Kelas VARCHAR(20))
BEGIN
IF p_Nama_Tagihan = '' 
	THEN
	SELECT 
			SCCTCUST.NOCUST as IDSantri,
			SCCTCUST.NMCUST as NamaSantri,
			SCCTCUST.DESC02 as Unit,
			SCCTCUST.DESC03 as Kelas,
			SCCTCUST.DESC05 as Alamat,
			SCCTBILL.BILLNM as NamaTagihan,
			SCCTBILL.BILLCD as KodeTagihan,
			SCCTBILL.BILLAM as Nominal,
			SCCTBILL.BTA as TahunAkademik,
			SCCTCUST.CUSTID as KodeCust,
			SCCTBILL.FIDBANK as KodeBank,
			SCCTBILL.PAIDDT as TanggalBayar
	FROM
	SCCTBILL
	LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
	where SCCTBILL.FSTSBolehBayar = 1  AND SCCTBILL.PAIDST = 1
	AND SCCTBILL.BTA = p_Tahun_Akademik 
	AND DESC03 = p_Kelas;
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  >= @Dari_Tgl
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  <= @Sampai_Tgl
ELSE
		SELECT 
			SCCTCUST.NOCUST as IDSantri,
			SCCTCUST.NMCUST as NamaSantri,
			SCCTCUST.DESC02 as Unit,
			SCCTCUST.DESC03 as Kelas,
			SCCTCUST.DESC05 as Alamat,
			SCCTBILL.BILLNM as NamaTagihan,
			SCCTBILL.BILLCD as KodeTagihan,
			SCCTBILL.BILLAM as Nominal,
			SCCTBILL.BTA as TahunAkademik,
			SCCTCUST.CUSTID as KodeCust,
			SCCTBILL.FIDBANK as KodeBank,
			SCCTBILL.PAIDDT as TanggalBayar
	FROM
	SCCTBILL
	LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
	where SCCTBILL.FSTSBolehBayar = 1  AND SCCTBILL.PAIDST = 1
	AND SCCTBILL.BTA = p_Tahun_Akademik 
	AND SCCTBILL.BILLNM = p_Nama_Tagihan
	AND DESC03 = p_Kelas;
END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for LihatTagihan
-- ----------------------------
DROP PROCEDURE IF EXISTS `LihatTagihan`;
delimiter ;;
CREATE PROCEDURE `LihatTagihan`(p_Tahun_Akademik VARCHAR(50) /* = '' */,
p_Nama_Tagihan VARCHAR(50) /* = '' */,
p_Kelas VARCHAR(20))
BEGIN
IF p_Nama_Tagihan = '' 
	THEN
	SELECT 
			SCCTCUST.NOCUST as IDSantri,
			SCCTCUST.NMCUST as NamaSantri,
			SCCTCUST.DESC02 as Unit,
			SCCTCUST.DESC03 as Kelas,
			SCCTCUST.DESC05 as Alamat,
			SCCTBILL.BILLNM as NamaTagihan,
			SCCTBILL.BILLCD as KodeTagihan,
			SCCTBILL.BILLAM as Nominal,
			SCCTBILL.BTA as TahunAkademik,
			SCCTCUST.CUSTID as KodeCust
	FROM
	SCCTBILL
	LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
	where SCCTBILL.FSTSBolehBayar = 1  AND SCCTBILL.PAIDST = 0
	AND SCCTBILL.BTA = p_Tahun_Akademik 
	AND DESC03 = p_Kelas;
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  >= @Dari_Tgl
-- AND CONVERT(varchar,SCCTBILL.PAIDDT,112)  <= @Sampai_Tgl
ELSE
		SELECT 
			SCCTCUST.NOCUST as IDSantri,
			SCCTCUST.NMCUST as NamaSantri,
			SCCTCUST.DESC02 as Unit,
			SCCTCUST.DESC03 as Kelas,
			SCCTCUST.DESC05 as Alamat,
			SCCTBILL.BILLNM as NamaTagihan,
			SCCTBILL.BILLCD as KodeTagihan,
			SCCTBILL.BILLAM as Nominal,
			SCCTBILL.BTA as TahunAkademik,
			SCCTCUST.CUSTID as KodeCust
	FROM
	SCCTBILL
	LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID = SCCTBILL.CUSTID 
	where SCCTBILL.FSTSBolehBayar = 1  AND SCCTBILL.PAIDST = 0
	AND SCCTBILL.BTA = p_Tahun_Akademik 
	AND SCCTBILL.BILLNM = p_Nama_Tagihan
	AND DESC03 = p_Kelas;
END IF;
END
;;
delimiter ;

-- ----------------------------
-- Function structure for LPAD
-- ----------------------------
DROP FUNCTION IF EXISTS `LPAD`;
delimiter ;;
CREATE FUNCTION `LPAD`(p_string LONGTEXT, -- Initial string
    p_length INT,          -- Size of final string
    p_pad CHAR)
 RETURNS longtext CHARSET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci
BEGIN
    RETURN REPLICATE(p_pad, p_length - CHAR_LENGTH(RTRIM(p_string))) + p_string;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for RekapAll
-- ----------------------------
DROP PROCEDURE IF EXISTS `RekapAll`;
delimiter ;;
CREATE PROCEDURE `RekapAll`(p_TahunAka char(20))
BEGIN
	SELECT SCCTCUST.DESC02, SCCTCUST.DESC03, SCCTCUST.DESC04, 
		SUM(CASE WHEN SCCTBILL_DETAIL.KodePost = '401' AND SCCTBILL.PAIDST=1 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'REG.Awal',
		SUM(CASE WHEN SCCTBILL_DETAIL.KodePost in ('403','404','405','408') AND SCCTBILL.PAIDST=1 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'SPP',
		SUM(CASE WHEN SCCTBILL_DETAIL.KodePost in ('402','410','411','412') AND SCCTBILL.PAIDST=1 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'SPI',
		SUM(CASE WHEN SCCTBILL_DETAIL.KodePost in ('400','406','407','409') AND SCCTBILL.PAIDST=1 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'Lain2',
		SUM(CASE WHEN SCCTBILL.PAIDST=1 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'Jml Pemb',
		SUM(CASE WHEN SCCTBILL.PAIDST=0 THEN SCCTBILL_DETAIL.BILLAM ELSE 0 END) as 'Sisa Tag'
		FROM SCCTBILL_DETAIL 
		LEFT JOIN SCCTBILL ON SCCTBILL.CUSTID=SCCTBILL_DETAIL.CUSTID and SCCTBILL.BILLCD=SCCTBILL_DETAIL.BILLCD
		LEFT JOIN SCCTCUST ON SCCTCUST.CUSTID=SCCTBILL_DETAIL.CUSTID 
		WHERE 
		SCCTBILL.BTA = p_TahunAka 
		AND SCCTBILL.FSTSBolehBayar=1
		GROUP BY SCCTCUST.DESC02, SCCTCUST.DESC03, SCCTCUST.DESC04;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for SaldoBayar
-- ----------------------------
DROP PROCEDURE IF EXISTS `SaldoBayar`;
delimiter ;;
CREATE PROCEDURE `SaldoBayar`(p_NIM CHAR(20), p_BILLCD VARCHAR(10), p_REFNO VARCHAR(12) /* = NULL */, p_PAYMENT BIGINT, p_BTA NVARCHAR(30))
BEGIN
		DECLARE v_TRANSNO VARCHAR(16); DECLARE v_CUSTID INT; DECLARE v_BILLCDw VARCHAR(10); DECLARE v_BILLCDi VARCHAR(10); DECLARE v_BILLCDs VARCHAR(10);

		SET v_CUSTID = GetCustID(p_NIM);

		SELECT CONCAT('WAKAF-' , COUNT(BILLCD)+1) INTO v_BILLCDw FROM SCCTBILL WHERE CUSTID = v_CUSTID AND SUBSTRING(BILLCD, 1, 5) = 'WAKAF';
		SELECT CONCAT('INFAQ-' , COUNT(BILLCD)+1) INTO v_BILLCDi FROM SCCTBILL WHERE CUSTID = v_CUSTID AND SUBSTRING(BILLCD, 1, 5) = 'INFAQ';
		SELECT CONCAT('SAKU-' , COUNT(BILLCD)+1) INTO v_BILLCDs FROM SCCTBILL WHERE CUSTID = v_CUSTID AND SUBSTRING(BILLCD, 1, 4) = 'SAKU';

		IF p_BILLCD = 'WAKAF' THEN
				INSERT INTO SCCTBILL (CUSTID, BILLCD, BILLNM, BILLAM, PAIDST, PAIDDT, NOREFF, FSTSBolehBayar, 
				FUrutan, FTGLTagihan, FIDBANK, FRecID, BTA)
				VALUES (v_CUSTID,v_BILLCDw,'WAKAF',p_PAYMENT,1,NOW(),p_REFNO,1,1,NOW(),'SALDO','MOBILE',p_BTA);
				
				INSERT INTO SCCTBILL_DETAIL (KodePost, BILLAM, CUSTID, FID, tahun, periode, BILLCD) 
				values ('902', p_PAYMENT, v_CUSTID, 'MOB', DATE_FORMAT(NOW(),'%Y'), DATE_FORMAT(NOW(),'%m'), v_BILLCDw);
		ELSEIF p_BILLCD = 'INFAQ' THEN
				INSERT INTO SCCTBILL (CUSTID, BILLCD, BILLNM, BILLAM, PAIDST, PAIDDT, NOREFF, FSTSBolehBayar, 
				FUrutan, FTGLTagihan, FIDBANK, FRecID, BTA)
				VALUES (v_CUSTID,v_BILLCDi,'INFAQ',p_PAYMENT,1,NOW(),p_REFNO,1,1,NOW(),'SALDO','MOBILE',p_BTA);

				INSERT INTO SCCTBILL_DETAIL (KodePost, BILLAM, CUSTID, FID, tahun, periode, BILLCD) 
				values ('901', p_PAYMENT, v_CUSTID, 'MOB', DATE_FORMAT(NOW(),'%Y'), DATE_FORMAT(NOW(),'%m'), v_BILLCDi);
		ELSEIF p_BILLCD = 'SAKU' THEN
				INSERT INTO SCCTBILL (CUSTID, BILLCD, BILLNM, BILLAM, PAIDST, PAIDDT, NOREFF, FSTSBolehBayar, 
				FUrutan, FTGLTagihan, FIDBANK, FRecID, BTA)
				VALUES (v_CUSTID,v_BILLCDs,'SAKU',p_PAYMENT,1,NOW(),p_REFNO,1,1,NOW(),'SALDO','MOBILE',p_BTA);
				
				INSERT INTO SCCTBILL_DETAIL (KodePost, BILLAM, CUSTID, FID, tahun, periode, BILLCD) 
				values ('701', p_PAYMENT, v_CUSTID, 'MOB', DATE_FORMAT(NOW(),'%Y'), DATE_FORMAT(NOW(),'%m'), v_BILLCDs);
		ELSE
				UPDATE SCCTBILL SET PAIDST = 1, PAIDDT =  NOW(), FIDBANK = 'SALDO', NOREFF = p_BILLCD 
				WHERE CUSTID = v_CUSTID AND BILLCD = p_BILLCD AND FSTSBolehBayar = 1 AND PAIDST = 0;
		END IF;

		SELECT CONCAT('BMI' , DATE_FORMAT(NOW(),'%y%m%d') + RIGHT('0000000' + MAX(RIGHT(TRANSNO,6) + 1), 6)) INTO v_TRANSNO FROM SCCTTRAN WHERE SUBSTRING(TRANSNO,4,6) = DATE_FORMAT(NOW(),'%y%m%d');

		INSERT INTO SCCTTRAN (CUSTID, NOREFF, TRXDATE, KDCHANNEL, DEBET, TRANSNO, METODE) VALUES (v_CUSTID, p_REFNO, NOW(), 'SALDO', p_PAYMENT, v_TRANSNO, 'FROM SALDO');
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for SentBelanja
-- ----------------------------
DROP PROCEDURE IF EXISTS `SentBelanja`;
delimiter ;;
CREATE PROCEDURE `SentBelanja`(p_CUSTID INT, p_IDMESIN INT, p_NOMINAL BIGINT, p_KDMERCAN CHAR(3))
BEGIN
		DECLARE v_TRANSNO CHAR(16);
		SELECT GetNoBelanja() INTO v_TRANSNO;
		INSERT INTO SM_TRAN (
		SM_TRAN.CUSTID,
		SM_TRAN.NOMINAL,
		SM_TRAN.MESIN,
		SM_TRAN.TRANSNO,
    SM_TRAN.TRXDATE,
		SM_TRAN.MERCAN,
		SM_TRAN.STATUS
		)
		VALUES (
		p_CUSTID,
		p_NOMINAL,
		p_IDMESIN,
		v_TRANSNO,
		NOW(),
		p_KDMERCAN,
		1
    );
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for SMBatalBayarNom
-- ----------------------------
DROP PROCEDURE IF EXISTS `SMBatalBayarNom`;
delimiter ;;
CREATE PROCEDURE `SMBatalBayarNom`(p_PID varchar(20) /* = NULL */, p_NOMESIN VARCHAR(23))
BEGIN
	-- input transaksi untuk nambah saldo
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(10);
	DECLARE v_CUSTID INT;	
		DECLARE v_MESIN INT;
		SELECT urut INTO v_MESIN FROM SM_MESIN WHERE NOMESIN=p_NOMESIN;
		
			IF EXISTS (SELECT TRANSNO FROM SM_TRAN WHERE STATUS = 1 AND MESIN = v_MESIN )
			THEN
				-- update STATUS Complete = 5 jika Saldo
				SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
				UPDATE SM_TRAN set STATUS = 0 , CUSTID=v_CUSTID WHERE MESIN=v_MESIN AND STATUS = 1;
					
				
				-- 
			END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for SMBayar
-- ----------------------------
DROP PROCEDURE IF EXISTS `SMBayar`;
delimiter ;;
CREATE PROCEDURE `SMBayar`(p_PID varchar(20) /* = NULL */, p_NOMESIN VARCHAR(23) /* = NULL */, p_PAYMENT INT,
p_SALDO BIGINT)
BEGIN
	-- input transaksi untuk nambah saldo
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(10);
	DECLARE v_CUSTID INT;	
		DECLARE v_MESIN INT;
		SELECT urut INTO v_MESIN FROM SM_MESIN WHERE NOMESIN=p_NOMESIN;
		
			IF EXISTS (SELECT TRANSNO FROM SM_TRAN WHERE STATUS = 1 AND MESIN = v_MESIN )
			THEN
				-- update STATUS Complete = 5 jika Saldo
				IF p_SALDO >= p_PAYMENT
					THEN
					SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
					UPDATE SM_TRAN set STATUS = 5 , CUSTID=v_CUSTID WHERE MESIN=v_MESIN AND STATUS = 1;
					
					END IF;
				-- 
			END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for SMBayarNom
-- ----------------------------
DROP PROCEDURE IF EXISTS `SMBayarNom`;
delimiter ;;
CREATE PROCEDURE `SMBayarNom`(p_PID varchar(20) /* = NULL */, p_NOMESIN VARCHAR(23))
BEGIN
	-- input transaksi untuk nambah saldo
	DECLARE v_TRANSNO VARCHAR(16);
	DECLARE v_NIM VARCHAR(10);
	DECLARE v_CUSTID INT;	
		DECLARE v_MESIN INT;
		SELECT urut INTO v_MESIN FROM SM_MESIN WHERE NOMESIN=p_NOMESIN;
		
			IF EXISTS (SELECT TRANSNO FROM SM_TRAN WHERE STATUS = 1 AND MESIN = v_MESIN )
			THEN
				-- update STATUS Complete = 5 jika Saldo
				SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
				UPDATE SM_TRAN set STATUS = 5 , CUSTID=v_CUSTID WHERE MESIN=v_MESIN AND STATUS = 1;
					
				
				-- 
			END IF;
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for TAPPING
-- ----------------------------
DROP PROCEDURE IF EXISTS `TAPPING`;
delimiter ;;
CREATE PROCEDURE `TAPPING`(p_PID VARCHAR(16), p_NOMESIN CHAR(17), p_BERHASIL CHAR(1))
BEGIN
	DECLARE v_Coba INT;
	DECLARE v_tsql LONGTEXT;
	-- input transaksi untuk nambah saldo
	IF NOT EXISTS (SELECT STAT FROM SM_Rec_TAP WHERE STAT = 0 and PID= p_PID) THEN
		SELECT COUNT(PID) INTO v_Coba FROM SM_Rec_TAP WHERE STAT=3 AND PID= p_PID AND DATE_FORMAT(TGLTAP,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');
		if v_Coba <= 2 THEN
				INSERT INTO SM_Rec_TAP (PID, NOMESIN, STAT, TGLTAP) VALUES (p_PID, p_NOMESIN, p_BERHASIL, now());
		ELSE
				SELECT MAX(STAT) FROM SM_Rec_TAP WHERE STAT=3 AND PID= p_PID;
		END IF;
	END IF;	
	
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for TAPPINGAbsen
-- ----------------------------
DROP PROCEDURE IF EXISTS `TAPPINGAbsen`;
delimiter ;;
CREATE PROCEDURE `TAPPINGAbsen`(p_PID VARCHAR(16), p_NOMESIN CHAR(17))
BEGIN
	DECLARE v_CUSTID INT;
	-- input transaksi untuk nambah saldo
	IF EXISTS (SELECT PID FROM SM_PIN WHERE PID= p_PID)
	THEN
		IF NOT EXISTS (SELECT PID FROM SM_Rec_ABSEN WHERE PID= p_PID AND DATE_FORMAT(TGLTAPIN,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')) THEN
				
				SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
				INSERT INTO SM_Rec_ABSEN (PID, NOMESIN, TGLTAPIN, CUSTID) VALUES (p_PID, p_NOMESIN, now(),v_CUSTID);
		ELSE
				UPDATE SM_Rec_ABSEN SET TGLTAPOUT=now() WHERE PID= p_PID AND DATE_FORMAT(TGLTAPIN,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');
			END IF;
	
	END IF;	
END
;;
delimiter ;

-- ----------------------------
-- Procedure structure for TAPPINGAbsenGuru
-- ----------------------------
DROP PROCEDURE IF EXISTS `TAPPINGAbsenGuru`;
delimiter ;;
CREATE PROCEDURE `TAPPINGAbsenGuru`(p_PID VARCHAR(16), p_NOMESIN CHAR(17))
BEGIN
	DECLARE v_CUSTID INT;
	-- input transaksi untuk nambah saldo
	IF EXISTS (SELECT PID FROM SM_PIN WHERE PID= p_PID)
	THEN
		IF NOT EXISTS (SELECT PID FROM SM_Rec_ABSEN WHERE PID= p_PID AND DATE_FORMAT(TGLTAPIN,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d'))
			THEN
				
				SELECT CUSTID INTO v_CUSTID FROM SM_PIN WHERE PID=p_PID;
				INSERT INTO SM_Rec_ABSEN (PID, NOMESIN, TGLTAPIN, CUSTID) VALUES (p_PID, p_NOMESIN, now(),0);
		ELSE
				UPDATE SM_Rec_ABSEN SET TGLTAPOUT=now() WHERE PID= p_PID AND DATE_FORMAT(TGLTAPIN,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');
			END IF;
	
	END IF;	
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
