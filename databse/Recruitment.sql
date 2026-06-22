/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: employee_management
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-1+b1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `Hj`
--

DROP TABLE IF EXISTS `Hj`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Hj` (
  `Hellow` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Hj`
--

LOCK TABLES `Hj` WRITE;
/*!40000 ALTER TABLE `Hj` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `Hj` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `biological_children`
--

DROP TABLE IF EXISTS `biological_children`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `biological_children` (
  `child_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `full_names` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `school_employer` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`child_id`),
  KEY `fk_biological_children_employee_id` (`employee_id`),
  CONSTRAINT `fk_biological_children_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biological_children`
--

LOCK TABLES `biological_children` WRITE;
/*!40000 ALTER TABLE `biological_children` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `biological_children` VALUES
(1,3,'gggg','2026-02-11','MALE','yyyyyy','6666');
/*!40000 ALTER TABLE `biological_children` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `emergency_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `priority` enum('Primary','Secondary') DEFAULT 'Primary',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`emergency_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_contacts`
--

LOCK TABLES `emergency_contacts` WRITE;
/*!40000 ALTER TABLE `emergency_contacts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `emergency_contacts` VALUES
(2,3,'hhhhh','hhhhh','77777','44444','Primary','2026-02-17 10:35:22');
/*!40000 ALTER TABLE `emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `doc_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size_bytes` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`doc_id`),
  KEY `fk_employee_documents_employee_id` (`employee_id`),
  CONSTRAINT `fk_employee_documents_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_documents`
--

LOCK TABLES `employee_documents` WRITE;
/*!40000 ALTER TABLE `employee_documents` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `employee_documents` VALUES
(1,1,'Passport Photo','uploads/photos/passport_photo_1_1771309996.png','Screenshot 2025-11-11 132447.png','image/png',368840,'2026-02-17 09:33:16'),
(2,1,'Curriculum Vitae','uploads/cv/cv_1_1771309996.pdf','Data Analyst Roadmap - data-analyst.pdf','application/pdf',179471,'2026-02-17 09:33:16'),
(3,1,'National ID','uploads/ids/national_id_1_1771309996.jpg','IMG_20251119_130259_243.jpg','image/jpeg',120590,'2026-02-17 09:33:16'),
(4,1,'Birth Certificate','uploads/birth_certificates/birth_certificate_1_1771309996.png','zoom-meeting-desktop-1762769644532.png','image/png',61972,'2026-02-17 09:33:16'),
(5,1,'Professional Certificate','uploads/professional_certificates/prof_cert_1_1771309996_0.png','Screenshot 2025-11-11 132447.png','image/png',368840,'2026-02-17 09:33:16'),
(6,1,'Academic Certificate','uploads/certificates/acad_cert_1_1771309996_0.png','zoom-meeting-desktop-1762769644532.png','image/png',61972,'2026-02-17 09:33:16'),
(13,3,'Passport Photo','uploads/photos/passport_photo_3_1771313722.jpeg','WhatsApp Image 2025-10-28 at 12.52.05.jpeg','image/jpeg',42430,'2026-02-17 10:35:22'),
(14,3,'Curriculum Vitae','uploads/cv/cv_3_1771313722.pdf','NehemiaMushi_.pdf','application/pdf',147033,'2026-02-17 10:35:22'),
(15,3,'National ID','uploads/ids/national_id_3_1771313722.png','Screenshot From 2025-11-10 11-51-04.png','image/png',40878,'2026-02-17 10:35:22'),
(16,3,'Birth Certificate','uploads/birth_certificates/birth_certificate_3_1771313722.pdf','SGR_API_by_ ISAIAH.pdf','application/pdf',690491,'2026-02-17 10:35:22'),
(17,3,'Professional Certificate','uploads/professional_certificates/prof_cert_3_1771313722_0.png','zoom-meeting-desktop-1762769644532.png','image/png',61972,'2026-02-17 10:35:22'),
(18,3,'Academic Certificate','uploads/certificates/acad_cert_3_1771313722_0.pdf','pdf.pdf','application/pdf',388838,'2026-02-17 10:35:22');
/*!40000 ALTER TABLE `employee_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `surname` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `other_names` varchar(255) DEFAULT NULL,
  `maiden_name` varchar(100) DEFAULT NULL,
  `home_phone` varchar(50) DEFAULT NULL,
  `mobile_phone` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `residential_address` text NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `identification_no` varchar(50) NOT NULL,
  `id_place_of_issue` varchar(150) DEFAULT NULL,
  `id_expiry_date` date DEFAULT NULL,
  `driving_permit_no` varchar(50) DEFAULT NULL,
  `driving_place_of_issue` varchar(150) DEFAULT NULL,
  `driving_expiry_date` date DEFAULT NULL,
  `nssf_no` varchar(50) DEFAULT NULL,
  `position` varchar(150) NOT NULL,
  `work_station` varchar(150) DEFAULT NULL,
  `has_disabilities` varchar(50) DEFAULT 'No',
  `disabilities_details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `tin_no` varchar(50) DEFAULT NULL,
  `nhif_no` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT 'CRDB',
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `mobile_money_number` varchar(50) DEFAULT NULL,
  `arrest_record` enum('Yes','No') DEFAULT 'No',
  `arrest_details` text DEFAULT NULL,
  `misconduct_record` enum('Yes','No') DEFAULT 'No',
  `misconduct_details` text DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  KEY `idx_employees_mobile_phone` (`mobile_phone`),
  KEY `idx_employees_nssf_no` (`nssf_no`),
  KEY `idx_employees_identification_no` (`identification_no`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `employees` VALUES
(1,'Nehe','hjjj','jjjjjjjj','jjjj','jjj','666666','666666','nmushi249@gmail.com','jksyufcdjqwc','2026-02-11','jj',0,'MALE',NULL,'jjjjjj','1111111111111111111111','jjjjjjjjjjjjjjjjj','2002-06-06','5555555555','hhhhhhh','2000-09-08','555555','hhhhhh','hhhhhhh','No','','2026-02-17 09:33:16','2026-02-17 09:33:16','5555555','555555','CRDB','777777','7777777','6666666','No',NULL,'No',NULL),
(3,'hhhh','hhhh','hhh','hhh','','jguded','66666','nnn2ff@jj.l','fr77r','2026-02-18','hhhhhhhhhhh',0,'MALE','Single','Tanzanian','1111111111111','111111111','2026-02-26','6666661','6666666','2026-02-25','333333','tttttt','jjjj','No','','2026-02-17 10:35:22','2026-02-17 10:35:22','777','88888','CRDB','jjjjj','77777777','ttttttt','No','','No','');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `employment_history`
--

DROP TABLE IF EXISTS `employment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `employer` varchar(255) NOT NULL,
  `position` varchar(150) NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `leaving_reason` text DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `fk_employment_history_employee_id` (`employee_id`),
  CONSTRAINT `fk_employment_history_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_history`
--

LOCK TABLES `employment_history` WRITE;
/*!40000 ALTER TABLE `employment_history` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `employment_history` VALUES
(1,3,'hhhhhh','hhhhhh','2026-02-10','2026-02-11','dttttttttttt');
/*!40000 ALTER TABLE `employment_history` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `family_contacts`
--

DROP TABLE IF EXISTS `family_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `family_contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`contact_id`),
  KEY `fk_family_contacts_employee_id` (`employee_id`),
  CONSTRAINT `fk_family_contacts_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `family_contacts`
--

LOCK TABLES `family_contacts` WRITE;
/*!40000 ALTER TABLE `family_contacts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `family_contacts` VALUES
(1,3,'Father','ttttt','12345','555555','ggggg');
/*!40000 ALTER TABLE `family_contacts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `qualifications`
--

DROP TABLE IF EXISTS `qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qualifications` (
  `qual_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `level` varchar(100) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `year_obtained` text DEFAULT NULL,
  `document_ref` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`qual_id`),
  KEY `fk_qualifications_employee_id` (`employee_id`),
  CONSTRAINT `fk_qualifications_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qualifications`
--

LOCK TABLES `qualifications` WRITE;
/*!40000 ALTER TABLE `qualifications` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `qualifications` VALUES
(1,1,'Degree','YYYYYYY','88888','2024',NULL),
(3,3,'Certificate','ggggg','hhhhh','2004',NULL);
/*!40000 ALTER TABLE `qualifications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `references`
--

DROP TABLE IF EXISTS `references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `references` (
  `ref_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `reference_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`ref_id`),
  KEY `fk_references_employee_id` (`employee_id`),
  CONSTRAINT `fk_references_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `references`
--

LOCK TABLES `references` WRITE;
/*!40000 ALTER TABLE `references` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `references` VALUES
(1,1,'YYYYYY','YYYY','8888888','nmushi@hhhh.co','hhhhhh',NULL),
(2,3,'fffffff777','7777','77777','nmm@hhh.c','TTT',1),
(3,3,'444444','gggggg','55555555555','nttttt@jj.com','rrrrrr',2),
(4,3,'rrrrrrr','hhhhhhh','44444','nmmuu@hhhh.nnn','ttttttt',3);
/*!40000 ALTER TABLE `references` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `relatives_employed`
--

DROP TABLE IF EXISTS `relatives_employed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `relatives_employed` (
  `rel_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `relationship` varchar(100) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `work_station` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`rel_id`),
  KEY `fk_relatives_employed_employee_id` (`employee_id`),
  CONSTRAINT `fk_relatives_employed_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `relatives_employed`
--

LOCK TABLES `relatives_employed` WRITE;
/*!40000 ALTER TABLE `relatives_employed` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `relatives_employed` VALUES
(1,3,'gggg','ggggg','jjjj','gggg');
/*!40000 ALTER TABLE `relatives_employed` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `spouses`
--

DROP TABLE IF EXISTS `spouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `spouses` (
  `spouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `employer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`spouse_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  CONSTRAINT `fk_spouses_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spouses`
--

LOCK TABLES `spouses` WRITE;
/*!40000 ALTER TABLE `spouses` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `spouses` VALUES
(1,1,'ggg','88888888','FFFFFFFF','FFFFFFF'),
(3,3,'ttttt','222222','hhhhhh','hhhhhh');
/*!40000 ALTER TABLE `spouses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','hr_manager','viewer') DEFAULT 'viewer',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'admin','$2y$12$DN7qtNMdgtNdJKBJCgOPrOO2.EJqI.nkaBemGx9UgShBxC7di3wC6','admin@silverleaf.ac.tz','System Administrator','admin',1,'2026-03-03 12:09:24','2026-02-17 11:10:13','2026-03-03 12:09:24'),
(2,'hr_manager','$2y$12$sWvSRbydn9/AVjGG0b/SienPjzQEhF7J5gYkHPb7tWeJVFcVfFEMS','hr@silverleaf.ac.tz','HR Manager','hr_manager',1,NULL,'2026-02-17 11:10:13','2026-02-17 11:10:13'),
(3,'viewer','$2y$12$X5cB9AZiZbRWVfvcXrpjUOyXHv4U2/NgBYIo3nEyeG9K5zbuIuKAi','viewer@silverleaf.ac.tz','Report Viewer','viewer',1,NULL,'2026-02-17 11:10:13','2026-02-17 11:10:13'),
(4,'john_doe','$2y$12$0/vreXUMeEN8ZDGFW/JWj.jqoPWsjKNU99Zeso8NY/qDynNHOk/GG','john.doe@silverleaf.ac.tz','John Doe','hr_manager',1,NULL,'2026-02-17 11:10:13','2026-02-17 11:10:13'),
(5,'jane_smith','$2y$12$28GUbf1a/DW67km2b5lzXu/rUP.iZVEaa/n8GgaE7/SIr.t5theGy','jane.smith@silverleaf.ac.tz','Jane Smith','viewer',1,NULL,'2026-02-17 11:10:13','2026-02-17 11:10:13');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-03-03 12:20:14
