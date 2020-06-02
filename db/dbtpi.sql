-- MySQL dump 10.13  Distrib 5.7.24, for Win64 (x86_64)
--
-- Host: localhost    Database: dbtpi
-- ------------------------------------------------------
-- Server version	5.7.24

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `isbn` varchar(13) CHARACTER SET latin1 NOT NULL,
  `title` varchar(45) NOT NULL,
  `author` varchar(45) NOT NULL,
  `editor` varchar(45) NOT NULL,
  `summary` longtext NOT NULL,
  `editionDate` date NOT NULL,
  `image` varchar(45) NOT NULL,
  PRIMARY KEY (`isbn`),
  UNIQUE KEY `isbn_UNIQUE` (`isbn`),
  KEY `tilte, author, editor` (`title`,`author`,`editor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES ('9782070339822','L\'Å’uvre','Emile Zola','Henri Mitterand','Une histoire qui parle des problÃ¨mes de peinture sous le Second Empire','2006-09-07','LOeuvre.jpg'),('9782070359400','La Joie de vivre','Emile Zola','Henri Mitterand','Une histoire dans un village normand','2008-06-19','LaJoieDeVivre.jpg'),('9782070372225','L\'Argent','Emile Zola','Henri Mitterand','Un livre qui parle d\'argent','1980-09-12','LArgent.jpg'),('9782070399673','Son Excellence EugÃ¨ne Rougon','Emile Zola','Henri Mitterand','C\'est l\'histoire d\'un Empereur mÃ©chant surnommÃ© Rougon','2009-04-01','SonExcellenceEugeneRougon.jpg'),('9782070411429','Germinal','Emile Zola','Henri Mitterand','Un livre qui dÃ©crit les conditions de travail de l\'Ã©poque.','1999-09-14','Germinal.jpg'),('9782070414659','William Shakespeare','Victor Hugo','Michel Crouzet','Un livre parlant de William Shakespeare.','2018-11-22','WilliamShakespeare.jpg'),('9782072864537','Notre-Dame de Paris','Victor Hugo','Benedikte Andersson','Une magnifique histoire qui se passe Ã  Paris','2019-04-23','NotreDameDeParis.jpg');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `idReview` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `content` longtext,
  `mark` int(11) DEFAULT NULL,
  `isValid` tinyint(4) DEFAULT NULL,
  `isbn` varchar(13) CHARACTER SET latin1 NOT NULL,
  `pseudo` varchar(45) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`idReview`,`isbn`,`pseudo`),
  KEY `fk_reviews_books_idx` (`isbn`),
  KEY `fk_reviews_users1_idx` (`pseudo`),
  CONSTRAINT `fk_reviews_books` FOREIGN KEY (`isbn`) REFERENCES `books` (`isbn`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_reviews_users1` FOREIGN KEY (`pseudo`) REFERENCES `users` (`pseudo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (4,'2020-05-29','TÃ¢che Ã  faire :\r\n - Bouton favori Ã  enlever d\'index et mettre ici !',4,1,'9782070339822','Hello'),(5,'2020-06-02','Bonjour ',4,1,'9782070359400','Hello');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `pseudo` varchar(45) NOT NULL,
  `password` varchar(64) NOT NULL,
  `email` varchar(60) NOT NULL,
  `admin` tinyint(4) NOT NULL,
  PRIMARY KEY (`pseudo`),
  UNIQUE KEY `pseudo_UNIQUE` (`pseudo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('Hello','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3','Hello@gmail.com',0),('Salut','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3','Salut@gmail.com',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_has_books`
--

DROP TABLE IF EXISTS `users_has_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_has_books` (
  `pseudo` varchar(45) NOT NULL,
  `isbn` varchar(13) NOT NULL,
  PRIMARY KEY (`pseudo`,`isbn`),
  KEY `fk_users_has_books_books1_idx` (`isbn`),
  KEY `fk_users_has_books_users1_idx` (`pseudo`),
  CONSTRAINT `fk_users_has_books_books1` FOREIGN KEY (`isbn`) REFERENCES `books` (`isbn`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_users_has_books_users1` FOREIGN KEY (`pseudo`) REFERENCES `users` (`pseudo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_has_books`
--

LOCK TABLES `users_has_books` WRITE;
/*!40000 ALTER TABLE `users_has_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_has_books` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-06-02 10:46:50
