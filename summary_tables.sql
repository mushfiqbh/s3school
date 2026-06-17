-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2026 at 07:12 PM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mcnkhs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_exam_fee_summary`
--

CREATE TABLE `ct_student_exam_fee_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) DEFAULT NULL,
  `section` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `sub_head_id` int(11) NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `notes` text,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_monthly_fee_summary`
--

CREATE TABLE `ct_student_monthly_fee_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `section` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `sub_head_id` int(11) NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `notes` text,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_yearly_fee_summary`
--

CREATE TABLE `ct_student_yearly_fee_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `year` varchar(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `sub_head_id` int(11) NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `notes` text,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ct_student_exam_fee_summary`
--
ALTER TABLE `ct_student_exam_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_monthly_fee_summary`
--
ALTER TABLE `ct_student_monthly_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_yearly_fee_summary`
--
ALTER TABLE `ct_student_yearly_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ct_student_exam_fee_summary`
--
ALTER TABLE `ct_student_exam_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_monthly_fee_summary`
--
ALTER TABLE `ct_student_monthly_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_yearly_fee_summary`
--
ALTER TABLE `ct_student_yearly_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
