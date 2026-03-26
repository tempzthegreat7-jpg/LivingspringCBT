-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: livingspring_cbt_subjects
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agricultural_science_jss1_first_term`
--

DROP TABLE IF EXISTS `agricultural_science_jss1_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agricultural_science_jss1_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agricultural_science_jss1_first_term`
--

LOCK TABLES `agricultural_science_jss1_first_term` WRITE;
/*!40000 ALTER TABLE `agricultural_science_jss1_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `agricultural_science_jss1_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agricultural_science_jss1_second_term`
--

DROP TABLE IF EXISTS `agricultural_science_jss1_second_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agricultural_science_jss1_second_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agricultural_science_jss1_second_term`
--

LOCK TABLES `agricultural_science_jss1_second_term` WRITE;
/*!40000 ALTER TABLE `agricultural_science_jss1_second_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `agricultural_science_jss1_second_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agricultural_science_ss3_assignment_anything`
--

DROP TABLE IF EXISTS `agricultural_science_ss3_assignment_anything`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agricultural_science_ss3_assignment_anything` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agricultural_science_ss3_assignment_anything`
--

LOCK TABLES `agricultural_science_ss3_assignment_anything` WRITE;
/*!40000 ALTER TABLE `agricultural_science_ss3_assignment_anything` DISABLE KEYS */;
/*!40000 ALTER TABLE `agricultural_science_ss3_assignment_anything` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agricultural_science_ss3_first_term`
--

DROP TABLE IF EXISTS `agricultural_science_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agricultural_science_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agricultural_science_ss3_first_term`
--

LOCK TABLES `agricultural_science_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `agricultural_science_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `agricultural_science_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_configs`
--

DROP TABLE IF EXISTS `assessment_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_user_id` int(11) NOT NULL,
  `subject` varchar(80) NOT NULL,
  `student_class` varchar(20) NOT NULL,
  `task_type` varchar(20) NOT NULL,
  `header_text` varchar(160) DEFAULT NULL,
  `term_key` varchar(20) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `question_limit` int(11) DEFAULT NULL,
  `table_name` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_name` (`table_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_configs`
--

LOCK TABLES `assessment_configs` WRITE;
/*!40000 ALTER TABLE `assessment_configs` DISABLE KEYS */;
INSERT INTO `assessment_configs` VALUES (1,1,'mathematics','SS3','exam','1st Term','first_term',NULL,20,'mathematics_ss3_first_term','2026-03-07 00:23:35','2026-03-07 00:23:35');
/*!40000 ALTER TABLE `assessment_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basic_science_jss1_first_term`
--

DROP TABLE IF EXISTS `basic_science_jss1_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `basic_science_jss1_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basic_science_jss1_first_term`
--

LOCK TABLES `basic_science_jss1_first_term` WRITE;
/*!40000 ALTER TABLE `basic_science_jss1_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `basic_science_jss1_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basic_science_jss1_second_term`
--

DROP TABLE IF EXISTS `basic_science_jss1_second_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `basic_science_jss1_second_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basic_science_jss1_second_term`
--

LOCK TABLES `basic_science_jss1_second_term` WRITE;
/*!40000 ALTER TABLE `basic_science_jss1_second_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `basic_science_jss1_second_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basic_science_jss3_classwork_motion`
--

DROP TABLE IF EXISTS `basic_science_jss3_classwork_motion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `basic_science_jss3_classwork_motion` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basic_science_jss3_classwork_motion`
--

LOCK TABLES `basic_science_jss3_classwork_motion` WRITE;
/*!40000 ALTER TABLE `basic_science_jss3_classwork_motion` DISABLE KEYS */;
/*!40000 ALTER TABLE `basic_science_jss3_classwork_motion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `christian_religious_studies_ss3_first_term`
--

DROP TABLE IF EXISTS `christian_religious_studies_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `christian_religious_studies_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `christian_religious_studies_ss3_first_term`
--

LOCK TABLES `christian_religious_studies_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `christian_religious_studies_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `christian_religious_studies_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_subjects`
--

DROP TABLE IF EXISTS `custom_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `label` varchar(120) NOT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_subjects`
--

LOCK TABLES `custom_subjects` WRITE;
/*!40000 ALTER TABLE `custom_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `english_jss1_first_term`
--

DROP TABLE IF EXISTS `english_jss1_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `english_jss1_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `english_jss1_first_term`
--

LOCK TABLES `english_jss1_first_term` WRITE;
/*!40000 ALTER TABLE `english_jss1_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `english_jss1_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `english_ss1_first_term`
--

DROP TABLE IF EXISTS `english_ss1_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `english_ss1_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `english_ss1_first_term`
--

LOCK TABLES `english_ss1_first_term` WRITE;
/*!40000 ALTER TABLE `english_ss1_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `english_ss1_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `english_ss3_assignment_exercise_on_nouns`
--

DROP TABLE IF EXISTS `english_ss3_assignment_exercise_on_nouns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `english_ss3_assignment_exercise_on_nouns` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `english_ss3_assignment_exercise_on_nouns`
--

LOCK TABLES `english_ss3_assignment_exercise_on_nouns` WRITE;
/*!40000 ALTER TABLE `english_ss3_assignment_exercise_on_nouns` DISABLE KEYS */;
/*!40000 ALTER TABLE `english_ss3_assignment_exercise_on_nouns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `english_ss3_assignment_exercise_on_verbs`
--

DROP TABLE IF EXISTS `english_ss3_assignment_exercise_on_verbs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `english_ss3_assignment_exercise_on_verbs` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `english_ss3_assignment_exercise_on_verbs`
--

LOCK TABLES `english_ss3_assignment_exercise_on_verbs` WRITE;
/*!40000 ALTER TABLE `english_ss3_assignment_exercise_on_verbs` DISABLE KEYS */;
/*!40000 ALTER TABLE `english_ss3_assignment_exercise_on_verbs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `english_ss3_first_term`
--

DROP TABLE IF EXISTS `english_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `english_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `english_ss3_first_term`
--

LOCK TABLES `english_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `english_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `english_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_attempts`
--

DROP TABLE IF EXISTS `exam_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(120) NOT NULL,
  `student_class` varchar(20) NOT NULL,
  `subject` varchar(80) NOT NULL,
  `task_type` varchar(20) NOT NULL DEFAULT 'exam',
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `time_spent_seconds` int(11) NOT NULL,
  `timed_out` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_attempts`
--

LOCK TABLES `exam_attempts` WRITE;
/*!40000 ALTER TABLE `exam_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `further_mathematics_ss1_classwork_sets`
--

DROP TABLE IF EXISTS `further_mathematics_ss1_classwork_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `further_mathematics_ss1_classwork_sets` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `further_mathematics_ss1_classwork_sets`
--

LOCK TABLES `further_mathematics_ss1_classwork_sets` WRITE;
/*!40000 ALTER TABLE `further_mathematics_ss1_classwork_sets` DISABLE KEYS */;
/*!40000 ALTER TABLE `further_mathematics_ss1_classwork_sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `further_mathematics_ss2_first_term`
--

DROP TABLE IF EXISTS `further_mathematics_ss2_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `further_mathematics_ss2_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `further_mathematics_ss2_first_term`
--

LOCK TABLES `further_mathematics_ss2_first_term` WRITE;
/*!40000 ALTER TABLE `further_mathematics_ss2_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `further_mathematics_ss2_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `further_mathematics_ss3_first_term`
--

DROP TABLE IF EXISTS `further_mathematics_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `further_mathematics_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `further_mathematics_ss3_first_term`
--

LOCK TABLES `further_mathematics_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `further_mathematics_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `further_mathematics_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `further_mathematics_ss3_others_mock_examination`
--

DROP TABLE IF EXISTS `further_mathematics_ss3_others_mock_examination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `further_mathematics_ss3_others_mock_examination` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `further_mathematics_ss3_others_mock_examination`
--

LOCK TABLES `further_mathematics_ss3_others_mock_examination` WRITE;
/*!40000 ALTER TABLE `further_mathematics_ss3_others_mock_examination` DISABLE KEYS */;
/*!40000 ALTER TABLE `further_mathematics_ss3_others_mock_examination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mathematics_ss1_assignment_functions_and_mapping`
--

DROP TABLE IF EXISTS `mathematics_ss1_assignment_functions_and_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mathematics_ss1_assignment_functions_and_mapping` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mathematics_ss1_assignment_functions_and_mapping`
--

LOCK TABLES `mathematics_ss1_assignment_functions_and_mapping` WRITE;
/*!40000 ALTER TABLE `mathematics_ss1_assignment_functions_and_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `mathematics_ss1_assignment_functions_and_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mathematics_ss1_assignment_logarithms`
--

DROP TABLE IF EXISTS `mathematics_ss1_assignment_logarithms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mathematics_ss1_assignment_logarithms` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mathematics_ss1_assignment_logarithms`
--

LOCK TABLES `mathematics_ss1_assignment_logarithms` WRITE;
/*!40000 ALTER TABLE `mathematics_ss1_assignment_logarithms` DISABLE KEYS */;
/*!40000 ALTER TABLE `mathematics_ss1_assignment_logarithms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mathematics_ss3_first_term`
--

DROP TABLE IF EXISTS `mathematics_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mathematics_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mathematics_ss3_first_term`
--

LOCK TABLES `mathematics_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `mathematics_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `mathematics_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mathematics_ss3_second_term`
--

DROP TABLE IF EXISTS `mathematics_ss3_second_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mathematics_ss3_second_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mathematics_ss3_second_term`
--

LOCK TABLES `mathematics_ss3_second_term` WRITE;
/*!40000 ALTER TABLE `mathematics_ss3_second_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `mathematics_ss3_second_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physics_ss1_assignment_work_energy_and_power`
--

DROP TABLE IF EXISTS `physics_ss1_assignment_work_energy_and_power`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physics_ss1_assignment_work_energy_and_power` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physics_ss1_assignment_work_energy_and_power`
--

LOCK TABLES `physics_ss1_assignment_work_energy_and_power` WRITE;
/*!40000 ALTER TABLE `physics_ss1_assignment_work_energy_and_power` DISABLE KEYS */;
/*!40000 ALTER TABLE `physics_ss1_assignment_work_energy_and_power` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physics_ss3_first_term`
--

DROP TABLE IF EXISTS `physics_ss3_first_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physics_ss3_first_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physics_ss3_first_term`
--

LOCK TABLES `physics_ss3_first_term` WRITE;
/*!40000 ALTER TABLE `physics_ss3_first_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `physics_ss3_first_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physics_ss3_second_term`
--

DROP TABLE IF EXISTS `physics_ss3_second_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physics_ss3_second_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physics_ss3_second_term`
--

LOCK TABLES `physics_ss3_second_term` WRITE;
/*!40000 ALTER TABLE `physics_ss3_second_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `physics_ss3_second_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physics_ss3_third_term`
--

DROP TABLE IF EXISTS `physics_ss3_third_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physics_ss3_third_term` (
  `number` int(11) NOT NULL,
  `question` mediumtext NOT NULL,
  `choice1` mediumtext NOT NULL,
  `choice2` mediumtext NOT NULL,
  `choice3` mediumtext NOT NULL,
  `choice4` mediumtext NOT NULL,
  `correct_answer` mediumtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physics_ss3_third_term`
--

LOCK TABLES `physics_ss3_third_term` WRITE;
/*!40000 ALTER TABLE `physics_ss3_third_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `physics_ss3_third_term` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-06 18:04:22
-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: livingspring_cbt
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_audit_logs`
--

DROP TABLE IF EXISTS `admin_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11) DEFAULT NULL,
  `action_key` varchar(80) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` varchar(120) DEFAULT NULL,
  `summary` varchar(255) NOT NULL,
  `context_json` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_audit_logs`
--

LOCK TABLES `admin_audit_logs` WRITE;
/*!40000 ALTER TABLE `admin_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'general',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `edited_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_subjects`
--

DROP TABLE IF EXISTS `custom_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `label` varchar(120) NOT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_subjects`
--

LOCK TABLES `custom_subjects` WRITE;
/*!40000 ALTER TABLE `custom_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_admin_alert_replies`
--

DROP TABLE IF EXISTS `teacher_admin_alert_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_admin_alert_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_id` int(11) NOT NULL,
  `teacher_user_id` int(11) NOT NULL,
  `admin_user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `teacher_read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_admin_alert_replies`
--

LOCK TABLES `teacher_admin_alert_replies` WRITE;
/*!40000 ALTER TABLE `teacher_admin_alert_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_admin_alert_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_admin_alerts`
--

DROP TABLE IF EXISTS `teacher_admin_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_admin_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_user_id` int(11) NOT NULL,
  `created_by_admin_user_id` int(11) DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `origin_role` varchar(20) NOT NULL DEFAULT 'teacher',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `teacher_read_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_admin_alerts`
--

LOCK TABLES `teacher_admin_alerts` WRITE;
/*!40000 ALTER TABLE `teacher_admin_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_admin_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_users`
--

DROP TABLE IF EXISTS `teacher_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` mediumtext NOT NULL,
  `password` mediumtext NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'teacher',
  `can_set_questions` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `can_manage_students` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_subjects` varchar(255) NOT NULL DEFAULT 'english',
  `assigned_subject_categories` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_users`
--

LOCK TABLES `teacher_users` WRITE;
/*!40000 ALTER TABLE `teacher_users` DISABLE KEYS */;
INSERT INTO `teacher_users` VALUES (1,'Afelumo Adura','$2y$10$/BWCM3ZpKGAMgj8/Di/bje2Lmlr2Jc0bW.ja/QQCJxHFctoBjw0Zu','admin',1,1,1,'mathematics','{}','2026-02-17 04:13:09','2026-03-06 17:32:13',0,NULL),(13,'Chizoba Nwachukwu','$2y$10$Ny2NcDBh9GP/S1ZiODny3uctip5DfvM8TaJx4/1nzpWot7ZBSVHGq','admin',1,1,0,'food_and_nutrition_cat_senior','{\"food_and_nutrition_cat_senior\":\"senior\"}','2026-03-04 04:10:03','2026-03-03 21:58:27',0,NULL);
/*!40000 ALTER TABLE `teacher_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-06 18:04:22
