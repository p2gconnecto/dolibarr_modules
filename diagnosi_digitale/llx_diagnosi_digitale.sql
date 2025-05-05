-- MariaDB dump 10.19  Distrib 10.7.4-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: dolibarr
-- ------------------------------------------------------
-- Server version	10.7.4-MariaDB-1:10.7.4+maria~focal

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `llx_diagnosi_digitale`
--

DROP TABLE IF EXISTS `llx_diagnosi_digitale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_diagnosi_digitale` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) DEFAULT 1,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_societe` int(11) DEFAULT NULL,
  `azienda_nome` varchar(255) DEFAULT NULL,
  `azienda_ragione_sociale` varchar(255) DEFAULT NULL,
  `referente_nome` varchar(255) DEFAULT NULL,
  `referente_ruolo` varchar(255) DEFAULT NULL,
  `data_compilazione` date DEFAULT NULL,
  `oggetto_valutazione` text DEFAULT NULL,
  `aspetti_osservati` text DEFAULT NULL,
  `tipologia_giudizio` text DEFAULT NULL,
  `strumenti_utilizzati` text DEFAULT NULL,
  `assunzioni_benefici` text DEFAULT NULL,
  `settore_riferimento` varchar(255) DEFAULT NULL,
  `n_dipendenti` int(11) DEFAULT NULL,
  `fatturato_annuo` double(24,8) DEFAULT NULL,
  `ambizioni_crescita` text DEFAULT NULL,
  `prodotti_servizi` text DEFAULT NULL,
  `valutazione_infrastruttura_it` int(11) DEFAULT NULL,
  `valutazione_doc` int(11) DEFAULT NULL,
  `valutazione_vendite` int(11) DEFAULT NULL,
  `valutazione_automazione` int(11) DEFAULT NULL,
  `valutazione_sicurezza` int(11) DEFAULT NULL,
  `punti_forte` text DEFAULT NULL,
  `debolezze` text DEFAULT NULL,
  `opportunita` text DEFAULT NULL,
  `minacce` text DEFAULT NULL,
  `obiettivi` text DEFAULT NULL,
  `budget_disponibile` varchar(20) DEFAULT NULL,
  `cofinanziamenti` text DEFAULT NULL,
  `progetti_gia_gestiti` text DEFAULT NULL,
  `referente_digitale_nome` varchar(255) DEFAULT NULL,
  `referente_digitale_ruolo` varchar(255) DEFAULT NULL,
  `capacita_utilizzo_strumenti` varchar(50) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `tms` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT current_timestamp(),
  `model_pdf` varchar(255) DEFAULT NULL,
  `oggetto_valutazione_ai` text DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_diagnosi_digitale`
--

LOCK TABLES `llx_diagnosi_digitale` WRITE;
/*!40000 ALTER TABLE `llx_diagnosi_digitale` DISABLE KEYS */;
INSERT INTO `llx_diagnosi_digitale` VALUES
(4,NULL,7,82,NULL,NULL,NULL,NULL,'2025-04-01','in che anno siamo?','cccccccccccccccccccccc','ddddddddddddddddddddd','wwwwwwwwwwwwwwwwwwwww','eeeeeeeeeeeeeeeeeeeeeee',NULL,NULL,NULL,'ffffffffffffffffffffffffffffffff','gggggggggggggggggggggggggggg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ffffffffff','ccccccccccbbbbbbbbbb',NULL,NULL,NULL,'2025-03-27 16:49:28','2025-04-06 19:08:30',1,1,NULL,'DiagnosiDigitale',NULL);
/*!40000 ALTER TABLE `llx_diagnosi_digitale` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-09 13:29:48
