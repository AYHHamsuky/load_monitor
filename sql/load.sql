-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table load_monitor.activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `activity_id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` int DEFAULT NULL,
  `activity_type` enum('LOGIN','LOGOUT','DATA_ENTRY','DATA_EDIT','DATA_DELETE','CORRECTION_REQUEST','CORRECTION_APPROVE','CORRECTION_REJECT','INTERRUPTION_LOG','INTERRUPTION_EDIT','INTERRUPTION_APPROVE','REPORT_GENERATE','EXPORT_DATA','VIEW_DASHBOARD','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_description` text COLLATE utf8mb4_unicode_ci,
  `related_table` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_time` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_activity_time` (`activity_time`),
  KEY `idx_date` (`activity_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.activity_logs: ~0 rows (approximately)

-- Dumping structure for table load_monitor.analytics_reports
CREATE TABLE IF NOT EXISTS `analytics_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `description` text,
  `parameters` json DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_public` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table load_monitor.analytics_reports: ~0 rows (approximately)

-- Dumping structure for table load_monitor.area_offices
CREATE TABLE IF NOT EXISTS `area_offices` (
  `ao_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ao_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ao_id`),
  UNIQUE KEY `uniq_ao_name` (`ao_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.area_offices: ~27 rows (approximately)
INSERT INTO `area_offices` (`ao_id`, `ao_name`, `created_at`) VALUES
	('1', 'Barnawa', '2026-01-15 14:48:38'),
	('10', 'Kebbi South', '2026-01-15 14:48:38'),
	('11', 'Makera', '2026-01-15 14:48:38'),
	('12', 'Mando', '2026-01-15 14:48:38'),
	('13', 'Millennium City', '2026-01-15 14:48:38'),
	('14', 'Rigasa', '2026-01-15 14:48:38'),
	('15', 'Sabon Gari', '2026-01-15 14:48:38'),
	('16', 'Samaru', '2026-01-15 14:48:38'),
	('17', 'Saminaka', '2026-01-15 14:48:38'),
	('18', 'Sokoto Central', '2026-01-15 14:48:38'),
	('19', 'Sokoto East', '2026-01-15 14:48:38'),
	('2', 'Doka', '2026-01-15 14:48:38'),
	('20', 'Sokoto North', '2026-01-15 14:48:38'),
	('21', 'Sokoto South', '2026-01-15 14:48:38'),
	('22', 'Tudun Wada', '2026-01-15 14:48:38'),
	('23', 'Zamfara Central', '2026-01-15 14:48:38'),
	('24', 'Zamfara North', '2026-01-15 14:48:38'),
	('25', 'Zamfara West', '2026-01-15 14:48:38'),
	('26', 'Zaria City', '2026-01-15 14:48:38'),
	('27', 'Unaligned', '2026-01-15 14:48:38'),
	('3', 'Gonin Gora', '2026-01-15 14:48:38'),
	('4', 'Jaji', '2026-01-15 14:48:38'),
	('5', 'Kafanchan', '2026-01-15 14:48:38'),
	('6', 'Kawo', '2026-01-15 14:48:38'),
	('7', 'Kebbi Central', '2026-01-15 14:48:38'),
	('8', 'Kebbi East', '2026-01-15 14:48:38'),
	('9', 'Kebbi North', '2026-01-15 14:48:38');

-- Dumping structure for table load_monitor.complaint_log
CREATE TABLE IF NOT EXISTS `complaint_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `complaint_ref` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Auto-generated reference',
  `feeder_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `complaint_type` enum('NO_SUPPLY','LOW_VOLTAGE','INTERMITTENT','TRANSFORMER_FAULT','LINE_FAULT','OTHERS') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `complaint_source` enum('CUSTOMER_CALL','FIELD_PATROL','INTERNAL_MONITORING','DSO_REPORT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `affected_area` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complaint_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fault_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEDIUM',
  `status` enum('PENDING','ASSIGNED','IN_PROGRESS','RESOLVED','CLOSED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `logged_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Payroll ID',
  `logged_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_to` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Payroll ID of technician',
  `assigned_at` datetime DEFAULT NULL,
  `resolution_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resolved_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `closure_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `closed_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `downtime_hours` decimal(10,2) DEFAULT NULL COMMENT 'Calculated downtime',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_complaint_ref` (`complaint_ref`),
  KEY `idx_feeder` (`feeder_code`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_logged_at` (`logged_at`),
  KEY `idx_complaint_search` (`complaint_ref`,`feeder_code`,`status`,`logged_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.complaint_log: ~2 rows (approximately)
INSERT INTO `complaint_log` (`id`, `complaint_ref`, `feeder_code`, `complaint_type`, `complaint_source`, `affected_area`, `customer_phone`, `customer_name`, `complaint_details`, `fault_location`, `priority`, `status`, `logged_by`, `logged_at`, `assigned_to`, `assigned_at`, `resolution_details`, `resolved_by`, `resolved_at`, `closure_remarks`, `closed_by`, `closed_at`, `downtime_hours`) VALUES
	(1, 'CMP-20260121-299587', '24', 'TRANSFORMER_FAULT', 'FIELD_PATROL', 'bvvb v v v ', '08011252144', 'nmvhjvjvjhvhjv', ' b b zn zzn m j sc h z  x m cs', ' bv vvmv ', 'MEDIUM', 'PENDING', '105737', '2026-01-21 17:56:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(2, 'CMP-20260126-8CCEFB', '7', 'LOW_VOLTAGE', 'CUSTOMER_CALL', 'jkbhjhb', '', 'jkbub', 'bhjvhvhvbbkb', 'jbjkbjbjb', 'MEDIUM', 'PENDING', '105421', '2026-01-26 01:26:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- Dumping structure for table load_monitor.corrections_33kv
CREATE TABLE IF NOT EXISTS `corrections_33kv` (
  `correction_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feeder_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'fdr33kv_code',
  `entry_date` date NOT NULL,
  `entry_hour` tinyint unsigned NOT NULL COMMENT 'Hour 1-24',
  `field_to_correct` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'load_read, fault_code, or fault_remark',
  `current_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Current value before correction',
  `new_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Proposed new value',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Reason for correction request',
  `requested_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UL2 Payroll ID',
  `status` enum('PENDING','ANALYST_APPROVED','ANALYST_REJECTED','APPROVED','MANAGER_REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `analyst_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UL3 Payroll ID',
  `analyst_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `analyst_reviewed_at` datetime DEFAULT NULL,
  `manager_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UL4 Payroll ID',
  `manager_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `manager_approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`correction_id`),
  KEY `idx_feeder_date_hour` (`feeder_code`,`entry_date`,`entry_hour`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_corrections_33kv_feeder` FOREIGN KEY (`feeder_code`) REFERENCES `fdr33kv` (`fdr33kv_code`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_corrections_33kv_hour` CHECK (((`entry_hour` >= 1) and (`entry_hour` <= 24)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='33kV load data correction requests and approvals';

-- Dumping data for table load_monitor.corrections_33kv: ~0 rows (approximately)

-- Dumping structure for table load_monitor.correction_requests
CREATE TABLE IF NOT EXISTS `correction_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `data_level` enum('11KV','33KV') NOT NULL,
  `feeder_code` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_hour` int NOT NULL,
  `original_load` decimal(10,2) DEFAULT NULL,
  `requested_load` decimal(10,2) DEFAULT NULL,
  `original_fault` varchar(10) DEFAULT NULL,
  `requested_fault` varchar(10) DEFAULT NULL,
  `original_remark` varchar(255) DEFAULT NULL,
  `requested_remark` varchar(255) DEFAULT NULL,
  `requested_by` varchar(20) NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `analyst_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `analyst_by` varchar(20) DEFAULT NULL,
  `analyst_at` datetime DEFAULT NULL,
  `manager_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `manager_by` varchar(20) DEFAULT NULL,
  `manager_at` datetime DEFAULT NULL,
  `final_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reason` varchar(255) NOT NULL,
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table load_monitor.correction_requests: ~0 rows (approximately)

-- Dumping structure for table load_monitor.fdr11kv
CREATE TABLE IF NOT EXISTS `fdr11kv` (
  `fdr11kv_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fdr11kv_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fdr33kv_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `band` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ao_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iss_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `max_load` decimal(4,2) NOT NULL DEFAULT '12.50',
  PRIMARY KEY (`fdr11kv_code`),
  UNIQUE KEY `uniq_fdr11kv_name` (`fdr11kv_name`),
  KEY `idx_33kv_code` (`fdr33kv_code`),
  CONSTRAINT `fdr11kv_chk_1` CHECK ((`max_load` <= 15.00))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.fdr11kv: ~127 rows (approximately)
INSERT INTO `fdr11kv` (`fdr11kv_code`, `fdr11kv_name`, `fdr33kv_code`, `band`, `ao_code`, `iss_code`, `created_at`, `max_load`) VALUES
	('1', '11Kv Kakuri', '5', 'E', '11', '22', '2026-01-15 14:46:38', 12.50),
	('10', '11Kv Chelco', '52', 'E', '11', '28', '2026-01-15 14:46:38', 12.50),
	('100', '11Kv Arkilla', '59', 'B', '18', '3', '2026-01-15 14:46:38', 12.50),
	('101', '11Kv Industrial Sok', '59', 'B', '18', '3', '2026-01-15 14:46:38', 12.50),
	('102', '11Kv Gwadangwaji', '15', 'A', '7', '8', '2026-01-15 14:46:38', 12.50),
	('103', '11Kv Tudun Wada Kbi', '15', 'E', '7', '8', '2026-01-15 14:46:38', 12.50),
	('104', '11Kv Gra Kbi', '16', 'A', '7', '7', '2026-01-15 14:46:38', 12.50),
	('105', '11Kv Kara', '16', 'C', '7', '7', '2026-01-15 14:46:38', 12.50),
	('106', '11Kv Bulasa', '16', 'E', '7', '31', '2026-01-15 14:46:38', 12.50),
	('107', '11Kv Commercial Kebbi', '16', 'A', '7', '7', '2026-01-15 14:46:38', 12.50),
	('108', '11Kv Nassarawa Kbi', '16', 'E', '7', '31', '2026-01-15 14:46:38', 12.50),
	('109', '11Kv Gra Jega', '24', 'E', '8', '19', '2026-01-15 14:46:38', 12.50),
	('11', '11Kv Nocaco', '52', 'C', '11', '38', '2026-01-15 14:46:38', 12.50),
	('110', '11Kv Sabon Garin Jega', '24', 'E', '8', '19', '2026-01-15 14:46:38', 12.50),
	('111', '11Kv Gra Argungu', '6', 'E', '9', '25', '2026-01-15 14:46:38', 12.50),
	('112', '11Kv Argungu City', '6', 'E', '9', '11', '2026-01-15 14:46:38', 12.50),
	('113', '11Kv Mera', '6', 'E', '9', '11', '2026-01-15 14:46:38', 12.50),
	('114', '11Kv Kanta', '6', 'E', '9', '25', '2026-01-15 14:46:38', 12.50),
	('115', '11Kv Sarkin Fada', '58', 'E', '21', '44', '2026-01-15 14:46:38', 12.50),
	('116', '11Kv Illela Road', '58', 'E', '21', '44', '2026-01-15 14:46:38', 12.50),
	('117', '11Kv Bunza', '10', 'E', '8', '5', '2026-01-15 14:46:38', 12.50),
	('118', '11Kv Yelwa', '80', 'E', '10', '48', '2026-01-15 14:46:38', 12.50),
	('119', '11Kv Yauri', '80', 'C', '10', '48', '2026-01-15 14:46:38', 12.50),
	('12', '11Kv Arewa Bottlers', '52', 'C', '11', '38', '2026-01-15 14:46:38', 12.50),
	('120', '11Kv Barracks Zuru', '70', 'E', '10', '50', '2026-01-15 14:46:38', 12.50),
	('121', '11Kv Rikoto/Zuru', '70', 'E', '10', '50', '2026-01-15 14:46:38', 12.50),
	('122', '11Kv Garage Kafanchan', '27', 'B', '5', '20', '2026-01-15 14:46:38', 12.50),
	('123', '11Kv Bank Kafanchan', '27', 'C', '5', '20', '2026-01-15 14:46:38', 12.50),
	('124', '11Kv Kafanchan (Township)', '27', 'C', '5', '20', '2026-01-15 14:46:38', 12.50),
	('125', '11Kv Kagoro', '27', 'E', '5', '21', '2026-01-15 14:46:38', 12.50),
	('126', '11Kv Manchok', '27', 'E', '5', '21', '2026-01-15 14:46:38', 12.50),
	('127', '11Kv Birnin Gwari', '9', 'C', '12', '2', '2026-01-15 14:46:38', 12.50),
	('13', '11Kv Government House Kd', '41', 'A', '22', '33', '2026-01-15 14:46:38', 12.50),
	('14', '11Kv Poly Road', '41', 'A', '22', '33', '2026-01-15 14:46:38', 12.50),
	('15', '11Kv Leventis', '41', 'A', '22', '33', '2026-01-15 14:46:38', 12.50),
	('16', '11Kv Tudun Wada Rig', '41', 'E', '22', '33', '2026-01-15 14:46:38', 12.50),
	('17', '11Kv Unguwan Yelwa', '18', 'E', '3', '4', '2026-01-15 14:46:38', 12.50),
	('18', '11Kv Federal Housing', '18', 'C', '3', '12', '2026-01-15 14:46:38', 12.50),
	('19', '11Kv Commercial Barnawa', '18', 'A', '3', '4', '2026-01-15 14:46:38', 12.50),
	('2', '11Kv Nortex', '5', 'C', '11', '22', '2026-01-15 14:46:38', 12.50),
	('20', '11Kv Gwari Avenue', '18', 'A', '3', '4', '2026-01-15 14:46:38', 12.50),
	('21', '11Kv Costain', '22', 'E', '2', '17', '2026-01-15 14:46:38', 12.50),
	('22', '11Kv Teaching Hospital Dka', '22', 'A', '2', '17', '2026-01-15 14:46:38', 12.50),
	('23', '11Kv Constitution Road', '22', 'A', '2', '17', '2026-01-15 14:46:38', 12.50),
	('24', '11Kv Commercial Dka', '22', 'A', '2', '17', '2026-01-15 14:46:38', 12.50),
	('25', '11Kv Sabon Tasha', '63', 'E', '1', '46', '2026-01-15 14:46:38', 12.50),
	('26', '11Kv Mahuta', '63', 'E', '1', '46', '2026-01-15 14:46:38', 12.50),
	('27', '11Kv Pama', '63', 'E', '1', '46', '2026-01-15 14:46:38', 12.50),
	('28', '11Kv Kurmin Mashi', '1', 'E', '6', '1', '2026-01-15 14:46:38', 12.50),
	('29', '11Kv Isa Kaita', '1', 'A', '6', '1', '2026-01-15 14:46:38', 12.50),
	('3', '11Kv Commercial Kachia Rd', '5', 'A', '11', '22', '2026-01-15 14:46:38', 12.50),
	('30', '11Kv Nda', '1', 'E', '6', '1', '2026-01-15 14:46:38', 12.50),
	('31', '11Kv Ahmadu Bello Way', '1', 'A', '6', '1', '2026-01-15 14:46:38', 12.50),
	('32', '11Kv Dankande', '61', 'E', '4', '23', '2026-01-15 14:46:38', 12.50),
	('33', '11Kv Katabu', '61', 'E', '4', '23', '2026-01-15 14:46:38', 12.50),
	('34', '11Kv Fifth Chukker', '61', 'A', '4', '23', '2026-01-15 14:46:38', 12.50),
	('35', '11Kv Jaji', '61', 'E', '4', '18', '2026-01-15 14:46:38', 12.50),
	('36', '11Kv Mc (Dedicated)', '61', 'C', '4', '18', '2026-01-15 14:46:38', 12.50),
	('37', '11Kv Nta Dka', '61', 'A', '4', '18', '2026-01-15 14:46:38', 12.50),
	('38', '11Kv Nacb', '67', 'A', '13', '6', '2026-01-15 14:46:38', 12.50),
	('39', '11Kv Unguwan Rimi', '67', 'A', '13', '6', '2026-01-15 14:46:38', 12.50),
	('4', '11Kv Barnawa Mkr', '5', 'A', '11', '22', '2026-01-15 14:46:38', 12.50),
	('40', '11Kv Malali', '67', 'A', '13', '6', '2026-01-15 14:46:38', 12.50),
	('41', '11Kv Dawaki', '67', 'A', '13', '6', '2026-01-15 14:46:38', 12.50),
	('42', '11Kv Urban Shelter', '67', 'A', '13', '32', '2026-01-15 14:46:38', 12.50),
	('43', '11Kv New Millennium City', '67', 'A', '13', '32', '2026-01-15 14:46:38', 12.50),
	('44', '11Kv Keke Leg', '67', 'D', '13', '32', '2026-01-15 14:46:38', 12.50),
	('45', '11Kv Nafbase', '43', 'A', '12', '35', '2026-01-15 14:46:38', 12.50),
	('46', '11Kv Nasfat', '43', 'C', '12', '24', '2026-01-15 14:46:38', 12.50),
	('47', '11Kv Statehouse', '43', 'B', '6', '24', '2026-01-15 14:46:38', 12.50),
	('48', '11Kv Kawo', '43', 'E', '6', '24', '2026-01-15 14:46:38', 12.50),
	('49', '11Kv Zaria Road', '43', 'C', '4', '24', '2026-01-15 14:46:38', 12.50),
	('5', '11Kv Nassarawa Mkr', '5', 'E', '11', '22', '2026-01-15 14:46:38', 12.50),
	('50', '11Kv Rabah Road', '14', 'E', '2', '1', '2026-01-15 14:46:38', 12.50),
	('51', '11Kv Luggard Hall', '14', 'A', '2', '24', '2026-01-15 14:46:38', 12.50),
	('52', '11Kv Sabon Garin Rig', '56', 'E', '14', '41', '2026-01-15 14:46:38', 12.50),
	('53', '11Kv Asikolaye', '56', 'C', '14', '41', '2026-01-15 14:46:38', 12.50),
	('54', '11Kv Hayin Rigasa', '56', 'E', '14', '41', '2026-01-15 14:46:38', 12.50),
	('55', '11Kv Makarfi Road', '56', 'E', '14', '41', '2026-01-15 14:46:38', 12.50),
	('56', '11Kv Rafin Guza', '64', 'E', '6', '47', '2026-01-15 14:46:38', 12.50),
	('57', '11Kv Legislative Quarters', '64', 'A', '6', '47', '2026-01-15 14:46:38', 12.50),
	('58', '11Kv Yantukwane', '30', 'C', '22', '26', '2026-01-15 14:46:38', 12.50),
	('59', '11Kv Unguwan Muazu', '30', 'C', '14', '26', '2026-01-15 14:46:38', 12.50),
	('6', '11Kv Village', '44', 'E', '1', '36', '2026-01-15 14:46:38', 12.50),
	('60', '11Kv Mando Road', '42', 'E', '12', '34', '2026-01-15 14:46:38', 12.50),
	('61', '11Kv Water Resources Rig', '42', 'A', '12', '34', '2026-01-15 14:46:38', 12.50),
	('62', '11Kv Gra Zar', '55', 'E', '15', '29', '2026-01-15 14:46:38', 12.50),
	('63', '11Kv Canteen', '55', 'A', '15', '29', '2026-01-15 14:46:38', 12.50),
	('64', '11Kv Sabon Garin Zar', '55', 'E', '15', '29', '2026-01-15 14:46:38', 12.50),
	('65', '11Kv Wusasa', '31', 'E', '26', '27', '2026-01-15 14:46:38', 12.50),
	('66', '11Kv Zaria City', '31', 'E', '26', '27', '2026-01-15 14:46:38', 12.50),
	('67', '11Kv Teaching Hospital Zar', '31', 'E', '26', '27', '2026-01-15 14:46:38', 12.50),
	('68', '11Kv Gaskiya', '31', 'A', '26', '27', '2026-01-15 14:46:38', 12.50),
	('69', '11Kv Kofan Kibo', '31', 'E', '26', '27', '2026-01-15 14:46:38', 12.50),
	('7', '11Kv Barnawa Gra', '44', 'A', '1', '36', '2026-01-15 14:46:38', 12.50),
	('70', '11Kv Dam', '21', 'E', '15', '49', '2026-01-15 14:46:38', 12.50),
	('71', '11Kv Abu', '21', 'C', '15', '49', '2026-01-15 14:46:38', 12.50),
	('72', '11Kv Nnpc Zar', '21', 'C', '15', '15', '2026-01-15 14:46:38', 12.50),
	('73', '11Kv Samaru', '7', 'E', '16', '43', '2026-01-15 14:46:38', 12.50),
	('74', '11Kv Shika', '7', 'E', '16', '43', '2026-01-15 14:46:38', 12.50),
	('75', '11Kv Makarfi', '39', 'E', '16', '30', '2026-01-15 14:46:38', 12.50),
	('76', '11Kv Gra Zam', '38', 'A', '23', '45', '2026-01-15 14:46:38', 12.50),
	('77', '11Kv Industrial Zam', '38', 'E', '23', '45', '2026-01-15 14:46:38', 12.50),
	('78', '11Kv Tudun Wada Zam', '53', 'E', '23', '39', '2026-01-15 14:46:38', 12.50),
	('79', '11Kv Governmnet House Zam', '53', 'A', '23', '39', '2026-01-15 14:46:38', 12.50),
	('8', '11Kv High Cost', '44', 'A', '1', '36', '2026-01-15 14:46:38', 12.50),
	('80', '11Kv Gada Biyu', '49', 'C', '24', '37', '2026-01-15 14:46:38', 12.50),
	('81', '11Kv Fggc', '49', 'C', '24', '37', '2026-01-15 14:46:38', 12.50),
	('82', '11Kv Sabon Garin Zam', '71', 'E', '23', '13', '2026-01-15 14:46:38', 12.50),
	('83', '11Kv Damba', '72', 'E', '23', '13', '2026-01-15 14:46:38', 12.50),
	('84', '11Kv Commercial Zam', '72', 'A', '23', '13', '2026-01-15 14:46:38', 12.50),
	('85', '11Kv Kaduna Road', '54', 'A', '18', '3', '2026-01-15 14:46:38', 12.50),
	('86', '11Kv Lodge Road', '54', 'A', '18', '14', '2026-01-15 14:46:38', 12.50),
	('87', '11Kv Commercial Sokoto', '54', 'A', '18', '14', '2026-01-15 14:46:38', 12.50),
	('88', '11Kv Mabera', '54', 'E', '19', '14', '2026-01-15 14:46:38', 12.50),
	('89', '11Kv Sultan Palace', '54', 'E', '19', '14', '2026-01-15 14:46:38', 12.50),
	('9', '11Kv Sunglass', '52', 'C', '11', '28', '2026-01-15 14:46:38', 12.50),
	('90', '11Kv Army Barrack', '54', 'E', '19', '40', '2026-01-15 14:46:38', 12.50),
	('91', '11Kv Waterworks Sok', '54', 'E', '19', '10', '2026-01-15 14:46:38', 12.50),
	('92', '11Kv Durbawa', '54', 'E', '19', '10', '2026-01-15 14:46:38', 12.50),
	('93', '11Kv Kueppers', '46', 'E', '20', '16', '2026-01-15 14:46:38', 12.50),
	('94', '11Kv Diori Hammani', '46', 'E', '20', '16', '2026-01-15 14:46:38', 12.50),
	('95', '11Kv Nta Sok', '46', 'A', '20', '42', '2026-01-15 14:46:38', 12.50),
	('96', '11Kv Startimes Sok', '46', 'A', '20', '42', '2026-01-15 14:46:38', 12.50),
	('97', '11Kv Bado', '17', 'C', '21', '9', '2026-01-15 14:46:38', 12.50),
	('98', '11Kv Institute', '17', 'A', '21', '9', '2026-01-15 14:46:38', 12.50),
	('99', '11Kv Town', '59', 'E', '18', '3', '2026-01-15 14:46:38', 12.50);

-- Dumping structure for table load_monitor.fdr11kv_data
CREATE TABLE IF NOT EXISTS `fdr11kv_data` (
  `entry_date` date NOT NULL,
  `fdr11kv_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_hour` tinyint unsigned NOT NULL COMMENT '0=00:xx .. 23=23:xx',
  `load_read` decimal(10,2) NOT NULL,
  `fault_code` enum('FO','BF','OS','DOff','MVR','OT','MS','LS','TF') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fault_remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_date`,`fdr11kv_code`,`entry_hour`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Dumping structure for table load_monitor.fdr33kv
CREATE TABLE IF NOT EXISTS `fdr33kv` (
  `fdr33kv_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fdr33kv_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ts_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `max_load` decimal(4,2) NOT NULL DEFAULT '12.50',
  PRIMARY KEY (`fdr33kv_code`),
  UNIQUE KEY `uniq_fdr33kv_name` (`fdr33kv_name`),
  KEY `idx_ts_code` (`ts_code`),
  CONSTRAINT `fdr33kv_chk_1` CHECK ((`max_load` <= 15.00))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.fdr33kv: ~72 rows (approximately)
INSERT INTO `fdr33kv` (`fdr33kv_code`, `fdr33kv_name`, `ts_code`, `created_at`, `max_load`) VALUES
	('1', '33Kv Abakpa', '6', '2026-01-15 14:43:33', 12.50),
	('10', '33Kv Bunza', '1', '2026-01-15 14:43:33', 12.50),
	('11', '33Kv Ccnn', '7', '2026-01-15 14:43:33', 12.50),
	('12', '33Kv Crown Flour Mills', '4', '2026-01-15 14:43:33', 12.50),
	('13', '33Kv Danmani Leg', '6', '2026-01-15 14:43:33', 12.50),
	('14', '33Kv Doka', '6', '2026-01-15 14:43:33', 12.50),
	('15', '33Kv Fadama 1', '1', '2026-01-15 14:43:33', 12.50),
	('16', '33Kv Fadama 2', '1', '2026-01-15 14:43:33', 12.50),
	('17', '33Kv Farfaru', '7', '2026-01-15 14:43:33', 12.50),
	('18', '33Kv Gonin Gora', '4', '2026-01-15 14:43:33', 12.50),
	('19', '33Kv Gwagwada Leg', '4', '2026-01-15 14:43:33', 12.50),
	('2', '33Kv Airport Road', '6', '2026-01-15 14:43:33', 12.50),
	('20', '33Kv Gwandu', '1', '2026-01-15 14:43:33', 12.50),
	('21', '33Kv Hanwa', '12', '2026-01-15 14:43:33', 12.50),
	('22', '33Kv Independence', '4', '2026-01-15 14:43:33', 12.50),
	('23', '33Kv Jaji', '6', '2026-01-15 14:43:33', 12.50),
	('24', '33Kv Jega', '1', '2026-01-15 14:43:33', 12.50),
	('25', '33Kv Jere', '8', '2026-01-15 14:43:33', 12.50),
	('26', '33Kv Kachia Leg', '4', '2026-01-15 14:43:33', 12.50),
	('27', '33Kv Kafanchan', '5', '2026-01-15 14:43:33', 12.50),
	('28', '33Kv Kamba', '1', '2026-01-15 14:43:33', 12.50),
	('29', '33Kv Kauran Namoda', '2', '2026-01-15 14:43:33', 12.50),
	('3', '33Kv Aliero', '1', '2026-01-15 14:43:33', 12.50),
	('30', '33Kv Kinkinau', '6', '2026-01-15 14:43:33', 12.50),
	('31', '33Kv Kofan Doka', '12', '2026-01-15 14:43:33', 12.50),
	('32', '33Kv Koko', '11', '2026-01-15 14:43:33', 12.50),
	('33', '33Kv Krpc (Dedicated)', '4', '2026-01-15 14:43:33', 12.50),
	('34', '33Kv Kudan', '12', '2026-01-15 14:43:33', 12.50),
	('35', '33Kv Kware/University', '7', '2026-01-15 14:43:33', 12.50),
	('36', '33Kv Labana (Dedicated)', '1', '2026-01-15 14:43:33', 12.50),
	('37', '33Kv Mafara', '9', '2026-01-15 14:43:33', 12.50),
	('38', '33Kv Magami', '2', '2026-01-15 14:43:33', 12.50),
	('39', '33Kv Makarfi', '12', '2026-01-15 14:43:33', 12.50),
	('4', '33Kv Anka', '9', '2026-01-15 14:43:33', 12.50),
	('40', '33Kv Maradun', '9', '2026-01-15 14:43:33', 12.50),
	('41', '33Kv Mogadishu', '4', '2026-01-15 14:43:33', 12.50),
	('42', '33Kv Mother Cat', '6', '2026-01-15 14:43:33', 12.50),
	('43', '33Kv Naf', '6', '2026-01-15 14:43:33', 12.50),
	('44', '33Kv Narayi Village', '4', '2026-01-15 14:43:33', 12.50),
	('45', '33Kv Nasco/Yelwa', '11', '2026-01-15 14:43:33', 12.50),
	('46', '33Kv New Injection', '7', '2026-01-15 14:43:33', 12.50),
	('47', '33Kv New Nda', '6', '2026-01-15 14:43:33', 12.50),
	('48', '33Kv Ngaski', '11', '2026-01-15 14:43:33', 12.50),
	('49', '33Kv Nnpc Gusau', '2', '2026-01-15 14:43:33', 12.50),
	('5', '33Kv Arewa', '4', '2026-01-15 14:43:33', 12.50),
	('50', '33Kv Nnpc Saminaka', '3', '2026-01-15 14:43:33', 12.50),
	('51', '33Kv Olam', '4', '2026-01-15 14:43:33', 12.50),
	('52', '33Kv Pan', '4', '2026-01-15 14:43:33', 12.50),
	('53', '33Kv Power House', '2', '2026-01-15 14:43:33', 12.50),
	('54', '33Kv Power Station', '7', '2026-01-15 14:43:33', 12.50),
	('55', '33Kv Pz', '12', '2026-01-15 14:43:33', 12.50),
	('56', '33Kv Rigasa', '6', '2026-01-15 14:43:33', 12.50),
	('57', '33Kv Soba', '12', '2026-01-15 14:43:33', 12.50),
	('58', '33Kv Tambuwal', '1', '2026-01-15 14:43:33', 12.50),
	('59', '33Kv Township', '7', '2026-01-15 14:43:33', 12.50),
	('6', '33Kv Argungu', '1', '2026-01-15 14:43:33', 12.50),
	('60', '33Kv Transmission Stationafe', '2', '2026-01-15 14:43:33', 12.50),
	('61', '33Kv Turunku', '6', '2026-01-15 14:43:33', 12.50),
	('62', '33Kv Turunku/Igabi Leg', '6', '2026-01-15 14:43:33', 12.50),
	('63', '33Kv Unguwan Boro', '4', '2026-01-15 14:43:33', 12.50),
	('64', '33Kv Ungwan Dosa', '6', '2026-01-15 14:43:33', 12.50),
	('65', '33Kv University (Dedicated)', '1', '2026-01-15 14:43:33', 12.50),
	('66', '33Kv Untl (Dedicated)', '4', '2026-01-15 14:43:33', 12.50),
	('67', '33Kv Water Works', '6', '2026-01-15 14:43:33', 12.50),
	('68', '33Kv Yabo/Shagari', '7', '2026-01-15 14:43:33', 12.50),
	('69', '33Kv Zaria Water Works (Dedicated)', '12', '2026-01-15 14:43:33', 12.50),
	('7', '33Kv Aviation', '12', '2026-01-15 14:43:33', 12.50),
	('70', '33Kv Zuru', '11', '2026-01-15 14:43:33', 12.50),
	('71', 'T2A', '2', '2026-01-15 14:43:33', 12.50),
	('72', 'T2B', '2', '2026-01-15 14:43:33', 12.50),
	('8', '33Kv Bakura', '9', '2026-01-15 14:43:33', 12.50),
	('9', '33Kv Birnin Gwari', '10', '2026-01-15 14:43:33', 12.50);

-- Dumping structure for table load_monitor.fdr33kv_data
CREATE TABLE IF NOT EXISTS `fdr33kv_data` (
  `entry_date` date NOT NULL,
  `fdr33kv_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_hour` tinyint unsigned NOT NULL COMMENT '0=00:xx .. 23=23:xx',
  `load_read` decimal(10,2) NOT NULL,
  `fault_code` enum('FO','BF','OS','DOff','MVR') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fault_remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_date`,`fdr33kv_code`,`entry_hour`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Dumping structure for table load_monitor.feeder_ticket_prefix
CREATE TABLE IF NOT EXISTS `feeder_ticket_prefix` (
  `feeder_code` varchar(30) NOT NULL,
  `voltage_level` enum('11kV','33kV') NOT NULL,
  `slug` char(3) NOT NULL COMMENT '3 uppercase alpha e.g. ABW, JER',
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feeder_code`,`voltage_level`),
  UNIQUE KEY `ux_slug_per_voltage` (`voltage_level`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Permanent 3-char ticket slug per feeder. Written once, never changed.';



-- Dumping structure for table load_monitor.interruptions
CREATE TABLE IF NOT EXISTS `interruptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interruption_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_approval` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NO',
  `approval_note` text COLLATE utf8mb4_unicode_ci,
  `approval_status` enum('PENDING','ANALYST_APPROVED','APPROVED','REJECTED','NOT_REQUIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_REQUIRED',
  `form_status` enum('PENDING_COMPLETION','AWAITING_APPROVAL','PENDING_COMPLETION_APPROVED','COMPLETED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING_COMPLETION',
  `stage` enum('OPEN','COMPLETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `approval_id` int DEFAULT NULL,
  `fdr33kv_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `interruption_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `load_loss` decimal(10,2) DEFAULT NULL,
  `datetime_out` datetime NOT NULL,
  `datetime_in` datetime DEFAULT NULL,
  `duration` decimal(10,6) GENERATED ALWAYS AS ((timestampdiff(SECOND,`datetime_out`,`datetime_in`) / 3600)) STORED,
  `reason_for_interruption` text COLLATE utf8mb4_unicode_ci,
  `resolution` text COLLATE utf8mb4_unicode_ci,
  `weather_condition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_for_delay` enum('DSO communicated late','Lack of vehicle or fuel for patrol','Lack of staff during restoration work','Lack of material','Delay to get security','Line in marshy Area','Technical staff negligence','others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_reasons` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_entry_reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Reason supplied when Time In was logged > 30 min after restoration',
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `started_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_serial` (`serial_number`),
  UNIQUE KEY `ux_ticket_33` (`ticket_number`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_approval_id` (`approval_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dumping structure for table load_monitor.interruptions_11kv
CREATE TABLE IF NOT EXISTS `interruptions_11kv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fdr11kv_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `interruption_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `interruption_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_approval` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NO',
  `approval_note` text COLLATE utf8mb4_unicode_ci,
  `approval_status` enum('PENDING','ANALYST_APPROVED','APPROVED','REJECTED','NOT_REQUIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_REQUIRED',
  `form_status` enum('PENDING_COMPLETION','AWAITING_APPROVAL','PENDING_COMPLETION_APPROVED','COMPLETED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING_COMPLETION',
  `stage` enum('OPEN','COMPLETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `approval_id` int DEFAULT NULL,
  `load_loss` decimal(10,2) DEFAULT NULL,
  `datetime_out` datetime NOT NULL,
  `datetime_in` datetime DEFAULT NULL,
  `duration` decimal(10,6) GENERATED ALWAYS AS ((timestampdiff(SECOND,`datetime_out`,`datetime_in`) / 3600)) STORED,
  `reason_for_interruption` text COLLATE utf8mb4_unicode_ci,
  `resolution` text COLLATE utf8mb4_unicode_ci,
  `weather_condition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_for_delay` enum('DSO communicated late','Lack of vehicle or fuel for patrol','Lack of staff during restoration work','Lack of material','Delay to get security','Line in marshy Area','Technical staff negligence','others') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_reasons` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_entry_reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Reason supplied when Time In was logged > 30 min after restoration',
  `is_late_entry` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = Time In was logged more than 30 min after restoration',
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `started_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_serial_11kv` (`serial_number`),
  UNIQUE KEY `ux_ticket_11` (`ticket_number`),
  KEY `idx_fdr11kv_code` (`fdr11kv_code`),
  KEY `idx_datetime_out` (`datetime_out`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_interruption_type` (`interruption_type`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_approval_id` (`approval_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dumping structure for table load_monitor.interruption_approvals
CREATE TABLE IF NOT EXISTS `interruption_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `interruption_id` int NOT NULL,
  `interruption_type` enum('11kV','33kV') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PENDING','ANALYST_APPROVED','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `requester_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `analyst_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analyst_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analyst_remarks` text COLLATE utf8mb4_unicode_ci,
  `analyst_action` enum('APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analyst_action_at` timestamp NULL DEFAULT NULL,
  `manager_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_remarks` text COLLATE utf8mb4_unicode_ci,
  `manager_action` enum('APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_action_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_interruption` (`interruption_id`,`interruption_type`),
  KEY `idx_status` (`status`),
  KEY `idx_requester` (`requester_id`),
  KEY `idx_analyst` (`analyst_id`),
  KEY `idx_manager` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dumping structure for table load_monitor.interruption_codes
CREATE TABLE IF NOT EXISTS `interruption_codes` (
  `interruption_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `interruption_description` varchar(255) NOT NULL,
  `interruption_type` varchar(50) NOT NULL,
  `interruption_group` varchar(100) NOT NULL,
  `body_responsible` varchar(20) NOT NULL,
  `approval_requirement` enum('YES','NO') NOT NULL,
  PRIMARY KEY (`interruption_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table load_monitor.interruption_codes: ~26 rows (approximately)
INSERT INTO `interruption_codes` (`interruption_code`, `interruption_description`, `interruption_type`, `interruption_group`, `body_responsible`, `approval_requirement`) VALUES
	('B/F KE', 'Breaker Fault KE', 'Emergency', 'Breaker Fault', 'KE', 'NO'),
	('B/F TCN', 'Breaker Fault TCN', 'Emergency', 'Breaker Fault', 'TCN', 'NO'),
	('BRI KE', 'Bucholz relay indication KE', 'Emergency', 'By Transient Faults', 'KE', 'NO'),
	('BRI TCN', 'Bucholz relay indication TCN', 'Emergency', 'By Transient Faults', 'TCN', 'NO'),
	('C33kV Off/T', 'Corresponding 33kV Open', 'Unplanned', 'Forced Outage', 'TCN', 'NO'),
	('C33kV Off/Y', 'Corresponding 33kV Open', 'Unplanned', 'Forced Outage', 'KE', 'NO'),
	('D/R KE', 'Differential Relay KE', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('D/R TCN', 'Differential Relay TCN', 'Unplanned', 'By Transient Faults', 'TCN', 'NO'),
	('E/F', 'Earth fault', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('F/C', 'Frequency Control', 'Unplanned', 'By Transient Faults', 'TCN', 'NO'),
	('Inst. E/F', 'Instantaneous Earth Fault', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('Inst. E/F and H/S', 'Inst. E/F with heavy Surge', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('Inst. O/C', 'Instantaneous Over Current', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('L/S KE', 'Limitation KE', 'Unplanned', 'Limitation', 'KE', 'NO'),
	('L/S TCN', 'Limitation TCN', 'Unplanned', 'Limitation', 'TCN', 'NO'),
	('NRI', 'No relay indication', 'Emergency', 'By Transient Faults', 'KE', 'NO'),
	('O/C', 'Over Current', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('O/C and E/F', 'Over Current and Earth fault', 'Unplanned', 'By Transient Faults', 'KE', 'NO'),
	('O/S KE', 'Out of Supply', 'Unplanned', 'Forced Outage', 'KE', 'NO'),
	('O/S TCN', 'Out of Supply', 'Unplanned', 'Forced Outage', 'TCN', 'NO'),
	('P/O KE', 'Planned Outage KE', 'Planned', 'Planned outage', 'KE', 'YES'),
	('P/O TCN', 'Planned Outage TCN', 'Planned', 'Planned outage', 'TCN', 'YES'),
	('REF', 'Restricted Earth fault', 'Emergency', 'By Transient Faults', 'KE', 'NO'),
	('S/C', 'System Collapse', 'Unplanned', 'By Transient Faults', 'TCN', 'NO'),
	('SBT KE', 'Service Base Tariff (Load Shedding) KE', 'Unplanned', 'Load shedding - Service Base Tariff', 'KE', 'NO'),
	('SBT TCN', 'Service Base Tariff (Load Shedding) TCN', 'Unplanned', 'Load shedding - Service Base Tariff', 'TCN', 'NO');

-- Dumping structure for table load_monitor.iss_locations
CREATE TABLE IF NOT EXISTS `iss_locations` (
  `iss_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iss_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iss_code`),
  UNIQUE KEY `uniq_iss_name` (`iss_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.iss_locations: ~50 rows (approximately)
INSERT INTO `iss_locations` (`iss_code`, `iss_name`, `created_at`) VALUES
	('1', 'Abakpa', '2026-01-15 14:50:08'),
	('10', 'Gagi', '2026-01-15 14:50:08'),
	('11', 'Garkuwa', '2026-01-15 14:50:08'),
	('12', 'Gonin Gora', '2026-01-15 14:50:08'),
	('13', 'Grid', '2026-01-15 14:50:08'),
	('14', 'Gusau Road', '2026-01-15 14:50:08'),
	('15', 'Hanwa', '2026-01-15 14:50:08'),
	('16', 'Illela Road', '2026-01-15 14:50:08'),
	('17', 'Independence', '2026-01-15 14:50:08'),
	('18', 'Jaji', '2026-01-15 14:50:08'),
	('19', 'Jega', '2026-01-15 14:50:08'),
	('2', 'Birnin Gwari', '2026-01-15 14:50:08'),
	('20', 'Kafanchan', '2026-01-15 14:50:08'),
	('21', 'Kagoro', '2026-01-15 14:50:08'),
	('22', 'Kakuri', '2026-01-15 14:50:08'),
	('23', 'Katabu', '2026-01-15 14:50:08'),
	('24', 'Kawo', '2026-01-15 14:50:08'),
	('25', 'Kebbi Gra', '2026-01-15 14:50:08'),
	('26', 'Kinkinau', '2026-01-15 14:50:08'),
	('27', 'Kofan Doka', '2026-01-15 14:50:08'),
	('28', 'Kudendan', '2026-01-15 14:50:08'),
	('29', 'Main Office', '2026-01-15 14:50:08'),
	('3', 'Birnin Kebbi Road', '2026-01-15 14:50:08'),
	('30', 'Makarfi', '2026-01-15 14:50:08'),
	('31', 'Mechanics Village', '2026-01-15 14:50:08'),
	('32', 'Millenium City', '2026-01-15 14:50:08'),
	('33', 'Mogadishu', '2026-01-15 14:50:08'),
	('34', 'Mothercat', '2026-01-15 14:50:08'),
	('35', 'Nafbase', '2026-01-15 14:50:08'),
	('36', 'Narayi', '2026-01-15 14:50:08'),
	('37', 'Nnpc', '2026-01-15 14:50:08'),
	('38', 'Pan', '2026-01-15 14:50:08'),
	('39', 'Power House', '2026-01-15 14:50:08'),
	('4', 'Borstal', '2026-01-15 14:50:08'),
	('40', 'Power Station', '2026-01-15 14:50:08'),
	('41', 'Rigasa', '2026-01-15 14:50:08'),
	('42', 'Runjin Sambo', '2026-01-15 14:50:08'),
	('43', 'Shika', '2026-01-15 14:50:08'),
	('44', 'Tambuwal', '2026-01-15 14:50:08'),
	('45', 'Tsunami', '2026-01-15 14:50:08'),
	('46', 'Unguwan Boro', '2026-01-15 14:50:08'),
	('47', 'Unguwan Dosa', '2026-01-15 14:50:08'),
	('48', 'Yauri', '2026-01-15 14:50:08'),
	('49', 'Zaria Ts', '2026-01-15 14:50:08'),
	('5', 'Bunza', '2026-01-15 14:50:08'),
	('50', 'Zuru', '2026-01-15 14:50:08'),
	('6', 'Dawaki', '2026-01-15 14:50:08'),
	('7', 'Eastern Bye-Pass', '2026-01-15 14:50:08'),
	('8', 'Fadama', '2026-01-15 14:50:08'),
	('9', 'Farfaru', '2026-01-15 14:50:08');

-- Dumping structure for table load_monitor.late_entry_log
CREATE TABLE IF NOT EXISTS `late_entry_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `voltage_level` enum('11kV','33kV') NOT NULL DEFAULT '11kV',
  `user_id` varchar(50) NOT NULL,
  `iss_code` varchar(20) NOT NULL,
  `log_date` date NOT NULL,
  `specific_hour` tinyint unsigned NOT NULL COMMENT '0-23',
  `entry_type` enum('load','fault') NOT NULL DEFAULT 'load',
  `operation` enum('initial','edit') NOT NULL DEFAULT 'initial',
  `explanation` text NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`log_date`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Dumping structure for table load_monitor.load_corrections
CREATE TABLE IF NOT EXISTS `load_corrections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `feeder_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `entry_hour` int NOT NULL,
  `correction_type` enum('11kV','33kV') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '11kV',
  `field_to_correct` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'load_read, fault_code, etc',
  `old_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blank_hour_reason` text COLLATE utf8mb4_unicode_ci,
  `requested_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Payroll ID',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('PENDING','ANALYST_APPROVED','MANAGER_APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `analyst_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UL3/UL4',
  `analyst_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `analyst_action_at` datetime DEFAULT NULL,
  `manager_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UL5/UL6',
  `manager_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `manager_action_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_feeder_date` (`feeder_code`,`entry_date`),
  KEY `idx_requested_by` (`requested_by`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dumping structure for table load_monitor.myto_daily
CREATE TABLE IF NOT EXISTS `myto_daily` (
  `entry_date` date NOT NULL,
  `entry_hour` tinyint unsigned NOT NULL COMMENT '0=00:00-00:59 … 23=23:00-23:59',
  `myto_allocation` decimal(10,2) NOT NULL,
  `formula_version` int unsigned DEFAULT NULL COMMENT 'formula version used when sharing',
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_date`,`entry_hour`),
  KEY `idx_md_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bulk MYTO hourly allocation — one row per hour per day';



-- Dumping structure for table load_monitor.myto_formula_history
CREATE TABLE IF NOT EXISTS `myto_formula_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `version` int unsigned NOT NULL,
  `ts_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(8,4) NOT NULL,
  `changed_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mfh_version` (`version`),
  KEY `idx_mfh_ts` (`ts_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historical snapshots of the MYTO sharing formula';


-- Dumping structure for table load_monitor.myto_sharing_formula
CREATE TABLE IF NOT EXISTS `myto_sharing_formula` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(8,4) NOT NULL COMMENT 'e.g. 30.5000 means 30.5%',
  `version` int unsigned NOT NULL DEFAULT '1' COMMENT 'incremented on every bulk edit',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_msf_ts` (`ts_code`),
  KEY `idx_msf_active` (`is_active`,`ts_code`),
  KEY `idx_msf_version` (`version`),
  CONSTRAINT `fk_msf_ts` FOREIGN KEY (`ts_code`) REFERENCES `transmission_stations` (`ts_code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MYTO load-sharing percentages per transmission station';


-- Dumping structure for table load_monitor.myto_ts_allocation
CREATE TABLE IF NOT EXISTS `myto_ts_allocation` (
  `entry_date` date NOT NULL,
  `ts_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_hour` tinyint unsigned NOT NULL,
  `myto_hour_allocation` decimal(10,2) NOT NULL,
  `formula_version` int unsigned DEFAULT NULL,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_date`,`ts_code`,`entry_hour`),
  KEY `fk_mta_ts` (`ts_code`),
  KEY `idx_mta_date` (`entry_date`),
  KEY `idx_mta_date_hour` (`entry_date`,`entry_hour`),
  CONSTRAINT `fk_mta_ts` FOREIGN KEY (`ts_code`) REFERENCES `transmission_stations` (`ts_code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-TS MYTO allocation derived from myto_daily via sharing formula';


-- Dumping structure for table load_monitor.operational_day_batches
CREATE TABLE IF NOT EXISTS `operational_day_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `op_date` date NOT NULL,
  `voltage_level` enum('11kV','33kV') NOT NULL,
  `closed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blank_cells` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Cells auto-written blank at close',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_batch` (`op_date`,`voltage_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table load_monitor.operational_day_batches: ~0 rows (approximately)

-- Dumping structure for view load_monitor.pending_approvals_view
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `pending_approvals_view` (
	`id` INT NOT NULL,
	`interruption_id` INT NOT NULL,
	`interruption_type` ENUM('11kV','33kV') NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`status` ENUM('PENDING','ANALYST_APPROVED','APPROVED','REJECTED') NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`requester_id` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`requester_name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`requested_at` TIMESTAMP NOT NULL,
	`fdr33kv_code` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`fdr33kv_name` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`datetime_out_33kv` DATETIME NULL,
	`code_33kv` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`fdr11kv_code` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`fdr11kv_name` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`datetime_out_11kv` DATETIME NULL,
	`code_11kv` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`interruption_code` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`feeder_name` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`datetime_out` DATETIME NULL
) ENGINE=MyISAM;

-- Dumping structure for table load_monitor.staff_activity_logs
CREATE TABLE IF NOT EXISTS `staff_activity_logs` (
  `activity_id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` int DEFAULT NULL,
  `activity_type` enum('LOGIN','LOGOUT','DATA_ENTRY','DATA_EDIT','DATA_DELETE','CORRECTION_REQUEST','CORRECTION_APPROVE','CORRECTION_REJECT','INTERRUPTION_LOG','INTERRUPTION_EDIT','INTERRUPTION_APPROVE','REPORT_GENERATE','EXPORT_DATA','VIEW_DASHBOARD','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_description` text COLLATE utf8mb4_unicode_ci,
  `related_table` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_time` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_activity_time` (`activity_time`),
  KEY `idx_date` (`activity_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.staff_activity_logs: ~0 rows (approximately)

-- Dumping structure for table load_monitor.staff_daily_metrics
CREATE TABLE IF NOT EXISTS `staff_daily_metrics` (
  `metric_id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_date` date NOT NULL,
  `total_hours` decimal(10,2) DEFAULT '0.00',
  `data_entries_11kv` int DEFAULT '0',
  `data_entries_33kv` int DEFAULT '0',
  `corrections_requested` int DEFAULT '0',
  `corrections_approved` int DEFAULT '0',
  `interruptions_logged` int DEFAULT '0',
  `reports_generated` int DEFAULT '0',
  `login_time` time DEFAULT NULL,
  `logout_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`metric_id`),
  UNIQUE KEY `uniq_staff_date` (`payroll_id`,`metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  KEY `idx_payroll_id` (`payroll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.staff_daily_metrics: ~0 rows (approximately)

-- Dumping structure for table load_monitor.staff_details
CREATE TABLE IF NOT EXISTS `staff_details` (
  `staff_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payroll_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iss_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_level` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sv_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_33kv_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `role` enum('UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UL1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `email` (`email`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_iss_code` (`iss_code`),
  KEY `idx_assigned_33kv` (`assigned_33kv_code`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.staff_details: ~180 rows (approximately)
INSERT INTO `staff_details` (`staff_name`, `payroll_id`, `iss_code`, `phone`, `staff_level`, `sv_code`, `assigned_33kv_code`, `email`, `password_hash`, `last_login`, `is_active`, `role`, `created_at`, `updated_at`) VALUES
	('Abdulazeez Abdulrazaq', '111305', '27', '08012345678', 'DSO', '100000', '31', 'abdulazeez.abdulrazaq@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Abdulhakeem Muhammed Badamasi', '101337', '12', '08012345678', 'DSO', '100000', '18', 'abdulhakeem.muhammed.badamasi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Abdullahi Tanimu', '697431', '27', '08012345678', 'DSO', '100000', '31', 'abdullahi.tanimu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Abdulrahman Ishaku', '835363', '26', '08012345678', 'DSO', '100000', '30', 'abdulrahman.ishaku@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Abdulrahman Kasim Salihu', '103555', '26', '08012345678', 'DSO', '100000', '30', 'abdulrahman.kasim.salihu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Abdulrasheed Usman', '104461', '27', '08012345678', 'DSO', '100000', '31', 'abdulrasheed.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 17:37:52', 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 17:37:52'),
	('Abubakar Abdullahi', '108055', '13', '08012345678', 'DSO', '100000', '71', 'abubakar.abdullahi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Abubakar Abubakar', '101972', '8', '08012345678', 'DSO', '100000', '15', 'abubakar.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Abubakar Alhassan Aliyu', '577612', '49', '08012345678', 'DSO', '100000', '21', 'abubakar.alhassan.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Abubakar Isa Garba', '102737', '29', '08012345678', 'DSO', '100000', '55', 'abubakar.isa.garba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Abubakar Mikhail', '109469', '1', '08012345678', 'DSO', '100000', '1', 'abubakar.mikhail@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Abubakar Muhammed Baba', '100401', '12', '08012345678', 'DSO', '100000', '18', 'abubakar.muhammed.baba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Abubakar Sahabi Galaudu', '697368', '8', '08012345678', 'DSO', '100000', '15', 'abubakar.sahabi.galaudu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Abubakar Usman Sarkinfada', '111139', '27', '08012345678', 'DSO', '100000', '31', 'abubakar.usman.sarkinfada@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 14:51:52', 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:51:52'),
	('Abubakar Yusuf', '745367', '11', '08012345678', 'DSO', '100000', '6', 'abubakar.yusuf@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Abubakar Yusuf', '745619', '41', '08012345678', 'DSO', '100000', '56', 'abubakar.yusuf2@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Abuldrauf Hamzat', '100587', '38', '08012345678', 'DSO', '100000', '52', 'abuldrauf.hamzat@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Adamu Haruna', '111567', '10', '08012345678', 'DSO', '100000', '54', 'adamu.haruna@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Adamu Ibrahim Isa', '102645', '30', '08012345678', 'DSO', '100000', '39', 'adamu.ibrahim.isa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Ahmed Lawal Gana', '102023', '44', '08012345678', 'DSO', '100000', '58', 'ahmed.lawal.gana@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Alhaji Hassan', '106179', '23', '08012345678', 'DSO', '100000', '61', 'alhaji.hassan@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Alhassan Hussaini', '704872', '22', '08012345678', 'DSO', '100000', '5', 'alhassan.hussaini@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Aliyu Abubakar Aliyu', '101114', '34', '08012345678', 'DSO', '100000', '42', 'aliyu.abubakar.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Aliyu Abubakar', '100715', '26', '08012345678', 'DSO', '100000', '30', 'aliyu.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Aliyu Ahmed Kargi', '111195', '47', '08012345678', 'DSO', '100000', '64', 'aliyu.ahmed.kargi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Aliyu Bello', '837032', '48', '08012345678', 'DSO', '100000', '80', 'aliyu.bello@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Aliyu Ibrahim', '104577', '6', '08012345678', 'DSO', '100000', '67', 'aliyu.ibrahim@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Aliyu Ismail', '111633', '47', '08012345678', 'DSO', '100000', '64', 'aliyu.ismail@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Aliyu Mohammed', '744695', '6', '08012345678', 'DSO', '100000', '46', 'aliyu.mohammed@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-02-24 17:22:05'),
	('Aliyu Muhammad Aliyu', '111189', '24', '08012345678', 'DSO', '100000', '43', 'aliyu.muhammad.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Aliyu Muhammad Aliyu', '106185', '32', '08012345678', 'DSO', '100000', '67', 'aliyu.muhammad.aliyu2@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Aliyu Muhammed Zogirma', '109235', '3', '08012345678', 'DSO', '100000', '59', 'aliyu.muhammed.zogirma@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Almustapha Abubakar', '102081', '11', '08012345678', 'DSO', '100000', '6', 'almustapha.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Aminu Bello Aliyu', '110777', '29', '08012345678', 'DSO', '100000', '55', 'aminu.bello.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Anas Sani', '105649', '39', '08012345678', 'DSO', '100000', '53', 'anas.sani@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Auwal Bashir', '111501', '41', '08012345678', 'DSO', '100000', '56', 'auwal.bashir@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Auwal Haruna', '101821', '45', '08012345678', 'DSO', '100000', '38', 'auwal.haruna@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Babangida Abubakar', '705152', '45', '08012345678', 'DSO', '100000', '38', 'babangida.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 14:52:43', 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:52:43'),
	('Babayo Musa Muhammad', '101329', '23', '08012345678', 'DSO', '100000', '61', 'babayo.musa.muhammad@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Bashar Muhammmad Sama', '104560', '11', '08012345678', 'DSO', '100000', '6', 'bashar.muhammmad.sama@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Bashar Usman', '103753', '14', '08012345678', 'DSO', '100000', '54', 'bashar.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Basiru Alkasim', '105719', '33', '08012345678', 'DSO', '100000', '41', 'basiru.alkasim@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Bello Abubakar Sadiq', '103149', '35', '08012345678', 'DSO', '100000', '43', 'bello.abubakar.sadiq@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Benjamin Chawai', '102701', '12', '08012345678', 'DSO', '100000', '18', 'benjamin.chawai@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Bitrus Ahmadu', '723954', '46', '08012345678', 'DSO', '100000', '63', 'bitrus.ahmadu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 13:47:35'),
	('Bulus Kogi', '769566', '38', '08012345678', 'DSO', '100000', '52', 'bulus.kogi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Bulus Yakubu Namadi', '703983', '28', '08012345678', 'DSO', '100000', '52', 'bulus.yakubu.namadi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Dahiru Musbahu', '104189', '43', '08012345678', 'DSO', '100000', '7', 'dahiru.musbahu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Daniel Buhari Danjuma', '744737', '46', '08012345678', 'DSO', '100000', '63', 'daniel.buhari@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-17 17:47:06', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 13:47:17'),
	('Daniel Chidi Eke', '109209', '20', '08012345678', 'DSO', '100000', '27', 'daniel.chidi.eke@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('David Francis', '105779', '21', '08012345678', 'DSO', '100000', '27', 'david.francis@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Lead Dispatch', '121212', '0', '0800000000', 'TL', '100000', '0', 'dispatchlead@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL8', '2026-02-08 09:37:06', '2026-02-08 15:58:04'),
	('Elizabeth Sunday Daniel', '105421', '36', '08012345678', 'UL1', '100000', '44', 'elizabeth.sunday@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 17:35:43', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 16:58:27'),
	('Emmanuel Otodo Okpokwu', '110605', '43', '08012345678', 'DSO', '100000', '7', 'emmanuel.otodo.okpokwu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 17:39:00', 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 17:39:00'),
	('Esther Magaji', '102483', '39', '08012345678', 'DSO', '100000', '53', 'esther.magaji@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Falalu Namakka', '726509', '10', '08012345678', 'DSO', '100000', '54', 'falalu.namakka@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Falalu Sada Aliyu', '661500', '29', '08012345678', 'DSO', '100000', '55', 'falalu.sada.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Fidelis Thomas', '836059', '21', '08012345678', 'DSO', '100000', '27', 'fidelis.thomas@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Garba Danjume', '577598', '15', '08012345678', 'DSO', '100000', '21', 'garba.danjume@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Garba Kasimu', '856219', '23', '08012345678', 'DSO', '100000', '61', 'garba.kasimu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Hajjah Shehu', '111133', '18', '08012345678', 'DSO', '100000', '61', 'hajjah.shehu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Haledu Ali', '723961', '36', '08012345678', 'DSO', '100000', '44', 'haledu.ali@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 13:47:31'),
	('Haruna Musa Adam', '633766', '47', '08012345678', 'DSO', '100000', '64', 'haruna.musa.adam@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Hassan Muhammad Ruwa', '103875', '3', '08012345678', 'DSO', '100000', '59', 'hassan.muhammad.ruwa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Hassan Suleiman', '103735', '3', '08012345678', 'DSO', '100000', '59', 'hassan.suleiman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Hassan Yusuf Garko', '101101', '18', '08012345678', 'DSO', '100000', '61', 'hassan.yusuf.garko@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Henry Olorunfemi Eniaiyekan', '110559', '38', '08012345678', 'DSO', '100000', '52', 'henry.olorunfemi.eniaiyekan@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Hussaini Aminu', '110525', '37', '08012345678', 'DSO', '100000', '49', 'hussaini.aminu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Ibrahim Abdullahi', '835198', '31', '08012345678', 'DSO', '100000', '16', 'ibrahim.abdullahi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Ibrahim Bala', '110767', '24', '08012345678', 'DSO', '100000', '43', 'ibrahim.bala@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Ibrahim Idris', '106181', '45', '08012345678', 'DSO', '100000', '38', 'ibrahim.idris@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:25:44', 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 21:25:44'),
	('Ibrahim Shehu', '835527', '13', '08012345678', 'DSO', '100000', '72', 'ibrahim.shehu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Ibrahim Suleiman Ali', '110697', '4', '08012345678', 'DSO', '100000', '18', 'ibrahim.suleiman.ali@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Iliyasu Nuhu Shehu', '697389', '48', '08012345678', 'DSO', '100000', '80', 'iliyasu.nuhu.shehu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Imam Abubakar Musa', '111549', '23', '08012345678', 'DSO', '100000', '61', 'imam.abubakar.musa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Imrana Muhammad', '105627', '24', '08012345678', 'DSO', '100000', '43', 'imrana.muhammad@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Imrana Tukur', '104023', '40', '08012345678', 'DSO', '100000', '54', 'imrana.tukur@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Inusa Mohammed Nura', '100317', '6', '08012345678', 'DSO', '100000', '67', 'inusa.mohammed.nura@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Isah Mohammed Baba', '836976', '50', '08012345678', 'DSO', '100000', '70', 'isah.mohammed.baba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Isah Yunanah', '780136', '12', '08012345678', 'DSO', '100000', '18', 'isah.yunanah@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Isiya Sani', '656488', '45', '08012345678', 'DSO', '100000', '38', 'isiya.sani@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:26:39', 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 21:26:39'),
	('Jerimia Emmanuel Kana', '102285', '50', '08012345678', 'DSO', '100000', '70', 'jerimia.emmanuel.kana@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Joshua D Ayuba', '835744', '13', '08012345678', 'DSO', '100000', '72', 'joshua.d.ayuba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Joshua Fain Tanko', '104731', '22', '08012345678', 'DSO', '100000', '5', 'joshua.fain.tanko@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Joshua Wycliffe Dewa', '111265', '28', '08012345678', 'DSO', '100000', '52', 'joshua.wycliffe.dewa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Kabiru Alhassan', '111589', '16', '08012345678', 'DSO', '100000', '46', 'kabiru.alhassan@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Kabiru Lawal', '106099', '7', '08012345678', 'DSO', '100000', '16', 'kabiru.lawal@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Kabiru  Muazu Yeldu', '102497', '25', '08012345678', 'DSO', '100000', '6', 'kabiru.muazu.yeldu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Kabiru Sani', '102797', '2', '08012345678', 'DSO', '100000', '9', 'kabiru.sani@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Lawal Garba Madaki', '100901', '17', '08012345678', 'UL1', '100000', '22', 'lawal.madaki@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 17:34:41', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-01-22 17:34:41'),
	('Lawali Musa Isgogo', '774438', '50', '08012345678', 'DSO', '100000', '70', 'lawali.musa.isgogo@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Mahmood Mohammed Abdulkadir', '100523', '22', '08012345678', 'DSO', '100000', '5', 'mahmood.mohammed.abdulkadir@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Mansur Abdurahaman', '109755', '30', '08012345678', 'DSO', '100000', '39', 'mansur.abdurahaman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Mark Dagama', '577640', '49', '08012345678', 'DSO', '100000', '21', 'mark.dagama@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Mohammed Bashiru Adewale', '102695', '49', '08012345678', 'DSO', '100000', '21', 'mohammed.bashiru.adewale@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Mohammed Bello Usman', '661472', '1', '08012345678', 'DSO', '100000', '1', 'mohammed.bello.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Mohammed Bukar Buratai', '105609', '33', '08012345678', 'DSO', '100000', '41', 'mohammed.bukar.buratai@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Mohammed Suleiman', '103465', '1', '08012345678', 'DSO', '100000', '1', 'mohammed.suleiman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Mohammed Tukur Lawal', '100365', '1', '08012345678', 'DSO', '100000', '1', 'mohammed.tukur.lawal@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Muazu Abubakar', '110327', '25', '08012345678', 'DSO', '100000', '6', 'muazu.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Mubarak Hadi', '102015', '44', '08012345678', 'DSO', '100000', '58', 'mubarak.hadi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Mubarak Muhammed Ambursa', '106161', '5', '08012345678', 'DSO', '100000', '10', 'mubarak.muhammed.ambursa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Muhammad Abdullahi', '107053', '8', '08012345678', 'DSO', '100000', '15', 'muhammad.abdullahi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Muhammad Abubakar', '835394', '49', '08012345678', 'DSO', '100000', '21', 'muhammad.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Muhammad Aliyu Waziri', '111271', '32', '08012345678', 'DSO', '100000', '67', 'muhammad.aliyu.waziri@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Muhammad Elbashir Musa', '100403', '34', '08012345678', 'DSO', '100000', '42', 'muhammad.elbashir.musa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Muhammad Jamil Abdulhamid', '103247', '2', '08012345678', 'DSO', '100000', '9', 'muhammad.jamil.abdulhamid@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Muhammad Shehu', '111557', '16', '08012345678', 'DSO', '100000', '46', 'muhammad.shehu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Muhammad Uthman Ibrahim', '110084', '19', '08012345678', 'DSO', '100000', '24', 'muhammad.uthman.ibrahim@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Murtala Muhammad Bulama', '105737', '17', '08012345678', 'UL1', '100000', '22', 'murtala.bulama@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-21 17:49:05', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-01-21 18:01:10'),
	('Murtala Musa', '704076', '17', '08012345678', 'DSO', '100000', '22', 'murtala.musa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-18 01:35:21', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 14:01:17'),
	('Musa Alhassan Argungu', '103003', '6', '08012345678', 'DSO', '100000', '67', 'musa.alhassan.argungu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Musa Mustapha', '102981', '38', '08012345678', 'DSO', '100000', '52', 'musa.mustapha@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Mustapha Abdullahi Bako', '667219', '40', '08012345678', 'DSO', '100000', '54', 'mustapha.abdullahi.bako@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Mustapha Tukur Jega', '110719', '42', '08012345678', 'DSO', '100000', '46', 'mustapha.tukur.jega@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Nasir Hassan Soje', '100297', '4', '08012345678', 'DSO', '100000', '18', 'nasir.hassan.soje@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Nasir Yusuf Jega', '110507', '31', '08012345678', 'DSO', '100000', '16', 'nasir.yusuf.jega@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Nasiru Umar Kalgo', '630476', '5', '08012345678', 'DSO', '100000', '10', 'nasiru.umar.kalgo@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Nasiru Umar', '100000', '9', '08012345678', 'DSO', '100000', '17', 'nasiru.umar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Nura Garba', '110783', '15', '08012345678', 'DSO', '100000', '21', 'nura.garba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Nura Inuwa', '109341', '30', '08012345678', 'DSO', '100000', '39', 'nura.inuwa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Paul Benedicth', '108061', '28', '08012345678', 'DSO', '100000', '52', 'paul.benedicth@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Pisagih Manu Kasambi', '100599', '4', '08012345678', 'DSO', '100000', '18', 'pisagih.manu.kasambi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Rabi U Mohammed Abdulkadir', '744716', '9', '08012345678', 'DSO', '100000', '17', 'rabi.u.mohammed.abdulkadir@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Rabiu Usman', '100623', '24', '08012345678', 'DSO', '100000', '14', 'rabiu.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Rabiu Yusuf', '779891', '35', '08012345678', 'DSO', '100000', '43', 'rabiu.yusuf@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Saddam Ibrahim Garba Arba', '110773', '15', '08012345678', 'DSO', '100000', '21', 'saddam.ibrahim.garba.arba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Salihu Umar Ibrahim', '110691', '47', '08012345678', 'DSO', '100000', '64', 'salihu.umar.ibrahim@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Salisu Abdulkadir', '103343', '41', '08012345678', 'DSO', '100000', '56', 'salisu.abdulkadir@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Salisu Garba', '106049', '48', '08012345678', 'DSO', '100000', '80', 'salisu.garba@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Sani Abdullahi Makarfi Tl Ps D', '599655', '33', '08012345678', 'DSO', '100000', '41', 'sani.abdullahi.makarfi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Sani Ibrahim Tl', '102299', '19', '08012345678', 'DSO', '100000', '24', 'sani.ibrahim.tl@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Sani Musa Kalgo', '726131', '40', '08012345678', 'DSO', '100000', '54', 'sani.musa.kalgo@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Sanusi Bawale', '102221', '25', '08012345678', 'DSO', '100000', '6', 'sanusi.bawale@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Shamsuddeen Sani', '104235', '43', '08012345678', 'DSO', '100000', '7', 'shamsuddeen.sani@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Shehu Bello', '109289', '44', '08012345678', 'DSO', '100000', '58', 'shehu.bello@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Shehu Umar', '726201', '14', '08012345678', 'DSO', '100000', '54', 'shehu.umar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Shuaibu Ali Jibir', '855883', '31', '08012345678', 'DSO', '100000', '16', 'shuaibu.ali.jibir@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Shuaibu Danladi', '111637', '34', '08012345678', 'DSO', '100000', '42', 'shuaibu.danladi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Sika Comfort Aribi', '100611', '46', '08012345678', 'UL1', '100000', '63', 'sika.aribi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-21 18:01:52', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-01-21 18:02:35'),
	('Simeon Sam-Abeku Ango', '779996', '36', '08012345678', 'DSO', '100000', '44', 'simeon.ango@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 12:22:16', 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 13:46:25'),
	('Sodiq Korede Sanusi', '102133', '42', '08012345678', 'DSO', '100000', '46', 'sodiq.korede.sanusi@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Solomon Ajai Audu', '723954', '46', '08012345678', 'DSO', '100000', '63', 'solomon.audu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-15 16:01:43', '2026-02-10 13:47:42'),
	('Solomon James', '836073', '20', '08012345678', 'DSO', '100000', '27', 'solomon.james@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Star A User 33kv', '666666', '0', '08012345678', 'HDSO', '100000', '0', 'star.a.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 20:54:55', 'Yes', 'UL2', '2026-01-22 17:29:01', '2026-01-22 20:54:55'),
	('SysAdmin', '222222', '0', '08012345678', 'Admin', '100000', '0', 'star.admin.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:21:06', 'Yes', 'UL6', '2026-01-22 17:29:01', '2026-01-23 12:05:54'),
	('Star B User 33kv', '777777', '0', '08012345678', 'HDSO', '100000', '0', 'star.b.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:27:35', 'Yes', 'UL2', '2026-01-22 17:29:01', '2026-01-22 21:27:35'),
	('Star C User 33kv', '888888', '0', '08012345678', 'HDSO', '100000', '0', 'star.c.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL2', '2026-01-22 17:29:01', '2026-01-22 17:29:01'),
	('Star D User 33kv', '999999', '0', '08012345678', 'HDSO', '100000', '0', 'star.d.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:24:31', 'Yes', 'UL2', '2026-01-22 17:29:01', '2026-01-22 21:24:31'),
	('TheAnalyst', '444444', '0', '08012345678', 'Analyst', '100000', '0', 'star.e.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:23:46', 'Yes', 'UL3', '2026-01-22 17:29:01', '2026-01-23 12:06:15'),
	('TheManager', '555555', '0', '08012345678', 'Manager', '100000', '0', 'star.f.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:22:25', 'Yes', 'UL4', '2026-01-22 17:29:01', '2026-01-23 12:06:25'),
	('TheViewer', '333333', '0', '08012345678', 'Viewer', '100000', '0', 'star.g.user33kv@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', '2026-01-22 21:19:44', 'Yes', 'UL5', '2026-01-22 17:29:01', '2026-01-23 12:06:36'),
	('Suleiman Aminu', '110675', '26', '08012345678', 'DSO', '100000', '30', 'suleiman.aminu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Sulieman B Muhammed', '697774', '21', '08012345678', 'DSO', '100000', '27', 'sulieman.b.muhammed@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Surajo Usman', '109325', '16', '08012345678', 'DSO', '100000', '46', 'surajo.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Theophilus Simon', '100613', '20', '08012345678', 'DSO', '100000', '27', 'theophilus.simon@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Umar Abubakar', '111587', '14', '08012345678', 'DSO', '100000', '54', 'umar.abubakar@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Umar Aliyu', '601328', '5', '08012345678', 'DSO', '100000', '10', 'umar.aliyu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Umar Bello', '102291', '10', '08012345678', 'DSO', '100000', '54', 'umar.bello@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Umar Idris Jimeta', '109253', '37', '08012345678', 'DSO', '100000', '49', 'umar.idris.jimeta@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Usman Danjuma', '111547', '18', '08012345678', 'DSO', '100000', '61', 'usman.danjuma@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Usman M.Mohammed', '697361', '19', '08012345678', 'DSO', '100000', '24', 'usman.m.mohammed@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Usman Muhammad Hayatudeen', '111145', '6', '08012345678', 'DSO', '100000', '67', 'usman.muhammad.hayatudeen@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Usman Sani Muhammed', '786604', '41', '08012345678', 'DSO', '100000', '56', 'usman.sani.muhammed@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Usman Umar Ladan', '109109', '7', '08012345678', 'DSO', '100000', '16', 'usman.umar.ladan@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Victor R Ubandoma', '744590', '39', '08012345678', 'DSO', '100000', '53', 'victor.r.ubandoma@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Walid Salihu', '110147', '32', '08012345678', 'DSO', '100000', '67', 'walid.salihu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Yahaya Muhammad', '745640', '17', '08012345678', 'DSO', '100000', '22', 'yahaya.muhammad@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Yakubu Isah Fada', '697382', '7', '08012345678', 'DSO', '100000', '16', 'yakubu.isah.fada@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:31:11', '2026-01-22 14:31:11'),
	('Yakubu Isah', '111479', '32', '08012345678', 'DSO', '100000', '67', 'yakubu.isah@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Yakubu Muhammad Kala', '105941', '35', '08012345678', 'DSO', '100000', '43', 'yakubu.muhammad.kala@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Yohanna Zakariya Pomak', '664272', '22', '08012345678', 'DSO', '100000', '5', 'yohanna.zakariya.pomak@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:33:33', '2026-01-22 14:33:33'),
	('Yusuf Idris', '110737', '34', '08012345678', 'DSO', '100000', '42', 'yusuf.idris@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Yusuf Ilu Mahmud', '110559', '18', '08012345678', 'DSO', '100000', '52', 'yusuf.ilu.mahmud@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:28:00', '2026-01-22 14:28:00'),
	('Yusuf Muhammad', '109273', '29', '08012345678', 'DSO', '100000', '55', 'yusuf.muhammad@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Yusuf Suleman Imam', '704879', '43', '08012345678', 'DSO', '100000', '7', 'yusuf.suleman.imam@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:41:00', '2026-01-22 14:41:00'),
	('Zahraddeen Shuaibu', '111563', '9', '08012345678', 'DSO', '100000', '17', 'zahraddeen.shuaibu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56'),
	('Zakari Usman', '101737', '37', '08012345678', 'DSO', '100000', '49', 'zakari.usman@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:48:27', '2026-01-22 14:48:27'),
	('Zakari Yakubu', '704200', '35', '08012345678', 'DSO', '100000', '43', 'zakari.yakubu@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:36:31', '2026-01-22 14:36:31'),
	('Zulkiflu Bello Mijinyawa', '855897', '33', '08012345678', 'DSO', '100000', '41', 'zulkiflu.bello.mijinyawa@example.com', '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G', NULL, 'Yes', 'UL1', '2026-01-22 14:43:56', '2026-01-22 14:43:56');

-- Dumping structure for table load_monitor.staff_reassignment_log
CREATE TABLE IF NOT EXISTS `staff_reassignment_log` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Staff being reassigned',
  `staff_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_role` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Role before reassignment',
  `new_role` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Role after reassignment',
  `field_changed` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'role, iss_code, or assigned_33kv_code',
  `old_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Previous value',
  `new_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'New value',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Reason for reassignment',
  `reassigned_by` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UL8 Payroll ID',
  `reassigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_reassigned_by` (`reassigned_by`),
  KEY `idx_reassigned_at` (`reassigned_at`),
  KEY `idx_old_role` (`old_role`),
  KEY `idx_new_role` (`new_role`),
  KEY `idx_field_changed` (`field_changed`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all staff reassignments including cross-voltage changes';


-- Dumping structure for table load_monitor.staff_sessions
CREATE TABLE IF NOT EXISTS `staff_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `payroll_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime DEFAULT NULL,
  `session_duration` decimal(10,2) GENERATED ALWAYS AS ((case when (`logout_time` is not null) then (timestampdiff(SECOND,`login_time`,`logout_time`) / 3600) else NULL end)) STORED,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_date` (`login_time`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.staff_sessions: ~0 rows (approximately)

-- Dumping structure for table load_monitor.ticket_edit_cancel_log
CREATE TABLE IF NOT EXISTS `ticket_edit_cancel_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `voltage_level` enum('11kV','33kV') NOT NULL,
  `action_type` enum('EDIT','CANCEL') NOT NULL,
  `action_by` varchar(50) NOT NULL,
  `action_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` text,
  `old_values` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tcl_ticket` (`ticket_number`),
  KEY `ix_tcl_user` (`action_by`),
  KEY `ix_tcl_at` (`action_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Dumping structure for table load_monitor.transmission_stations
CREATE TABLE IF NOT EXISTS `transmission_stations` (
  `ts_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ts_code`),
  UNIQUE KEY `uniq_station_name` (`station_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.transmission_stations: ~12 rows (approximately)
INSERT INTO `transmission_stations` (`ts_code`, `station_name`, `created_at`) VALUES
	('1', 'Birnin Kebbi Transmission Station', '2026-01-15 14:36:40'),
	('10', 'Tegina Transmission Station', '2026-01-15 14:36:40'),
	('11', 'Yauri Transmission Station', '2026-01-15 14:36:40'),
	('12', 'Zaria Transmission Station', '2026-01-15 14:36:40'),
	('2', 'Gusau Transmission Station', '2026-01-15 14:36:40'),
	('3', 'Jos Transmission Station', '2026-01-15 14:36:40'),
	('4', 'Kaduna Town Transmission Station', '2026-01-15 14:36:40'),
	('5', 'Kafanchan Transmission Station', '2026-01-15 14:36:40'),
	('6', 'Mando Transmission Station', '2026-01-15 14:36:40'),
	('7', 'Sokoto Transmission Station', '2026-01-15 14:36:40'),
	('8', 'Suleja Transmission Station', '2026-01-15 14:36:40'),
	('9', 'Talata Mafara Transmission Station', '2026-01-15 14:36:40');

-- Dumping structure for table load_monitor.user_privilege
CREATE TABLE IF NOT EXISTS `user_privilege` (
  `user_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `11kv_level` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `33kv_level` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_write` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_read` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_edit` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_report` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_download` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_user` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `edit_user` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`user_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table load_monitor.user_privilege: ~7 rows (approximately)
INSERT INTO `user_privilege` (`user_level`, `user_code`, `11kv_level`, `33kv_level`, `can_write`, `can_read`, `can_edit`, `view_report`, `can_download`, `create_user`, `edit_user`) VALUES
	('User level 1', 'UL1', 'YES', 'NO', 'YES', 'YES', 'NO', 'YES', 'NO', 'NO', 'NO'),
	('User level 2', 'UL2', 'NO', 'YES', 'YES', 'YES', 'NO', 'YES', 'NO', 'NO', 'NO'),
	('Analyst', 'UL3', 'NO', 'NO', 'NO', 'YES', 'NO', 'YES', 'YES', 'NO', 'NO'),
	('Manager', 'UL4', 'NO', 'NO', 'NO', 'YES', 'NO', 'YES', 'YES', 'NO', 'NO'),
	('Viewer', 'UL5', 'NO', 'NO', 'NO', 'NO', 'NO', 'YES', 'NO', 'NO', 'NO'),
	('Admin', 'UL6', 'NO', 'NO', 'NO', 'NO', 'NO', 'YES', 'NO', 'YES', 'YES'),
	('Lead Dispatch', 'UL8', 'NO', 'NO', 'NO', 'YES', 'NO', 'YES', 'YES', 'NO', 'NO');

-- Dumping structure for view load_monitor.vw_staff_attendance_daily
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `vw_staff_attendance_daily` (
	`payroll_id` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`staff_name` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`role` ENUM('UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8') NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`iss_code` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`iss_name` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`ao_name` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`attendance_date` DATE NULL,
	`first_login` DATETIME NULL,
	`last_logout` DATETIME NULL,
	`total_sessions` BIGINT NOT NULL,
	`total_hours` DECIMAL(32,2) NULL,
	`total_activities` BIGINT NOT NULL
) ENGINE=MyISAM;

-- Dumping structure for trigger load_monitor.trg_staff_login
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_staff_login` AFTER INSERT ON `staff_activity_logs` FOR EACH ROW BEGIN
  IF NEW.activity_type = 'LOGIN' THEN
    INSERT INTO staff_sessions (payroll_id, login_time, ip_address, is_active)
    VALUES (NEW.payroll_id, NEW.activity_time, NEW.ip_address, 'Yes');
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger load_monitor.trg_staff_logout
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_staff_logout` AFTER INSERT ON `staff_activity_logs` FOR EACH ROW BEGIN
  IF NEW.activity_type = 'LOGOUT' THEN
    UPDATE staff_sessions
    SET 
      logout_time = NEW.activity_time,
      is_active = 'No'
    WHERE session_id = (
      SELECT session_id
      FROM staff_sessions
      WHERE payroll_id = NEW.payroll_id
        AND is_active = 'Yes'
      ORDER BY login_time DESC
      LIMIT 1
    );
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `pending_approvals_view`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `pending_approvals_view` AS select `ia`.`id` AS `id`,`ia`.`interruption_id` AS `interruption_id`,`ia`.`interruption_type` AS `interruption_type`,`ia`.`status` AS `status`,`ia`.`requester_id` AS `requester_id`,`ia`.`requester_name` AS `requester_name`,`ia`.`requested_at` AS `requested_at`,`i33`.`fdr33kv_code` AS `fdr33kv_code`,`f33`.`fdr33kv_name` AS `fdr33kv_name`,`i33`.`datetime_out` AS `datetime_out_33kv`,`i33`.`interruption_code` AS `code_33kv`,`i11`.`fdr11kv_code` AS `fdr11kv_code`,`f11`.`fdr11kv_name` AS `fdr11kv_name`,`i11`.`datetime_out` AS `datetime_out_11kv`,`i11`.`interruption_code` AS `code_11kv`,(case when (`ia`.`interruption_type` = '33kV') then `i33`.`interruption_code` else `i11`.`interruption_code` end) AS `interruption_code`,(case when (`ia`.`interruption_type` = '33kV') then `f33`.`fdr33kv_name` else `f11`.`fdr11kv_name` end) AS `feeder_name`,(case when (`ia`.`interruption_type` = '33kV') then `i33`.`datetime_out` else `i11`.`datetime_out` end) AS `datetime_out` from ((((`interruption_approvals` `ia` left join `interruptions` `i33` on(((`ia`.`interruption_id` = `i33`.`id`) and (`ia`.`interruption_type` = '33kV')))) left join `fdr33kv` `f33` on((`i33`.`fdr33kv_code` = `f33`.`fdr33kv_code`))) left join `interruptions_11kv` `i11` on(((`ia`.`interruption_id` = `i11`.`id`) and (`ia`.`interruption_type` = '11kV')))) left join `fdr11kv` `f11` on((`i11`.`fdr11kv_code` = `f11`.`fdr11kv_code`))) where (`ia`.`status` in ('PENDING','ANALYST_APPROVED'));

-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `vw_staff_attendance_daily`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vw_staff_attendance_daily` AS select `s`.`payroll_id` AS `payroll_id`,`s`.`staff_name` AS `staff_name`,`s`.`role` AS `role`,`s`.`iss_code` AS `iss_code`,`iss`.`iss_name` AS `iss_name`,`ao`.`ao_name` AS `ao_name`,cast(`ss`.`login_time` as date) AS `attendance_date`,min(`ss`.`login_time`) AS `first_login`,max(`ss`.`logout_time`) AS `last_logout`,count(distinct `ss`.`session_id`) AS `total_sessions`,sum(`ss`.`session_duration`) AS `total_hours`,count(distinct `sa`.`activity_id`) AS `total_activities` from (((((`staff_details` `s` left join `staff_sessions` `ss` on((`ss`.`payroll_id` = `s`.`payroll_id`))) left join `staff_activity_logs` `sa` on(((`sa`.`payroll_id` = `s`.`payroll_id`) and (cast(`sa`.`activity_time` as date) = cast(`ss`.`login_time` as date))))) left join `iss_locations` `iss` on((`iss`.`iss_code` = `s`.`iss_code`))) left join `fdr11kv` `f` on((`f`.`iss_code` = `iss`.`iss_code`))) left join `area_offices` `ao` on((`ao`.`ao_id` = `f`.`ao_code`))) where (`ss`.`login_time` is not null) group by `s`.`payroll_id`,`s`.`staff_name`,`s`.`role`,`s`.`iss_code`,`iss`.`iss_name`,`ao`.`ao_name`,cast(`ss`.`login_time` as date);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
