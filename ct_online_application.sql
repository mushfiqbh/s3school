-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2020 at 05:44 PM
-- Server version: 10.3.16-MariaDB
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dev_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `ct_student`
--

CREATE TABLE `ct_online_application` (
  `studentid` int(11) NOT NULL,
  `stdName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdNameBangla` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdGender` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `stdBldGrp` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `facilities` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `stdImg` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `stdFather` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `fatherLate` int(11) DEFAULT 0,
  `stdFatherProf` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdMother` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `motherLate` int(11) NOT NULL DEFAULT 0,
  `stdMotherProf` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdParentIncome` int(11) NOT NULL,
  `stdlocalGuardian` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdGuardianNID` int(20) NOT NULL,
  `stdPhone` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `stdPermanent` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdPresent` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdBrith` date NOT NULL,
  `stdNationality` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `stdReligion` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdAdmitClass` int(11) NOT NULL COMMENT 'Class Table ID',
  `stdAdmitYear` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `stdTcNumber` varchar(50) COLLATE utf8_unicode_ci NULL,
  `sscRoll` varchar(50) COLLATE utf8_unicode_ci  NULL,
  `sscReg` varchar(50) COLLATE utf8_unicode_ci  NULL,
  `stdPrevSchool` varchar(100) COLLATE utf8_unicode_ci  NULL,
  `stdGPA` varchar(5) COLLATE utf8_unicode_ci NULL,
  `stdIntellectual` varchar(50) COLLATE utf8_unicode_ci  NULL,
  `stdScholarsClass` varchar(10) COLLATE utf8_unicode_ci  NULL,
  `stdScholarsYear` year(4)  NULL,
  `stdScholarsMemo` varchar(50) COLLATE utf8_unicode_ci NULL,
  `stdStatus` int(11) NOT NULL DEFAULT 1,
  `stdCreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `stdUpdatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paymentPaid` int(11)  NULL,
  `paymentDue` int(11)  NULL,
  `stdNote` text COLLATE utf8_unicode_ci  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ct_student`
--
ALTER TABLE `ct_student`
  ADD PRIMARY KEY (`studentid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ct_student`
--
ALTER TABLE `ct_student`
  MODIFY `studentid` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
