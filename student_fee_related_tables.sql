-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 18, 2026 at 07:47 PM
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
-- Table structure for table `ct_absent_fee`
--

CREATE TABLE `ct_absent_fee` (
  `id` int(11) NOT NULL,
  `fee` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_admission_fee_promoted`
--

CREATE TABLE `ct_admission_fee_promoted` (
  `id` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `admission_start_date` date DEFAULT NULL,
  `admission_end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ct_late_fee`
--

CREATE TABLE `ct_late_fee` (
  `id` int(11) NOT NULL,
  `late_fee` int(11) NOT NULL,
  `day_of_month` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_revenue`
--

CREATE TABLE `ct_revenue` (
  `revId` int(11) NOT NULL,
  `revCat` int(11) NOT NULL,
  `revMemo` varchar(20) NOT NULL,
  `revAmount` int(11) NOT NULL,
  `revNote` varchar(5000) NOT NULL,
  `revDate` date NOT NULL,
  `revEntry` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_revenue_cat`
--

CREATE TABLE `ct_revenue_cat` (
  `rcatid` int(11) NOT NULL,
  `rcatname` varchar(50) NOT NULL,
  `rcattype` varchar(8) NOT NULL DEFAULT 'income'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student`
--

CREATE TABLE `ct_student` (
  `studentid` int(11) NOT NULL,
  `stdUniqueID` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdNameBangla` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdGender` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `stdBldGrp` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `facilities` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `stdImg` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `stdFather` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `fatherLate` int(11) DEFAULT '0',
  `stdFatherProf` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdMother` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `motherLate` int(11) NOT NULL DEFAULT '0',
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
  `stdCurrentClass` int(11) NOT NULL,
  `stdAdmitYear` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `stdCurntYear` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `stdTcNumber` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `sscRoll` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `sscReg` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdPrevSchool` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdGPA` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `stdIntellectual` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdScholarsClass` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `stdScholarsYear` year(4) NOT NULL,
  `stdScholarsMemo` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `createdBy` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdStatus` int(11) NOT NULL DEFAULT '1',
  `stdCreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stdUpdatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `paymentPaid` int(11) NOT NULL,
  `paymentDue` int(11) NOT NULL,
  `stdNote` text COLLATE utf8_unicode_ci NOT NULL,
  `admission_type` int(11) DEFAULT '1' COMMENT '1=new admitted, 2=promoted',
  `birth_reg_no` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `facilities_activation_date` date DEFAULT NULL,
  `transport_required` int(11) DEFAULT NULL,
  `transport_type` int(11) DEFAULT '2' COMMENT '1=one way, 2= two way',
  `transport_fee_id` decimal(10,0) DEFAULT NULL,
  `transport_activation_date` date DEFAULT NULL,
  `monthly_fee` int(11) DEFAULT NULL,
  `uid` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ct_studentinfo`
--

CREATE TABLE `ct_studentinfo` (
  `infoid` int(11) NOT NULL,
  `infoStdid` int(11) NOT NULL,
  `infoClass` int(11) NOT NULL,
  `infoSection` int(11) NOT NULL,
  `infoYear` varchar(11) NOT NULL,
  `infoGroup` int(11) NOT NULL,
  `infoRoll` int(11) NOT NULL,
  `infoOptionals` varchar(500) NOT NULL,
  `info4thSub` int(11) NOT NULL,
  `infoCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_exam_fee_summary`
--

CREATE TABLE `ct_student_exam_fee_summary` (
  `id` int(11) NOT NULL,to be pay 
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
-- Table structure for table `ct_student_fee_collection_details`
--

CREATE TABLE `ct_student_fee_collection_details` (
  `id` int(11) NOT NULL,
  `info_id` int(11) NOT NULL,
  `sub_head_id` int(11) NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `reference` text,
  `date` datetime NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_fee_collection_info`
--

CREATE TABLE `ct_student_fee_collection_info` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_roll` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `section` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `sub_total` decimal(10,2) NOT NULL,
  `remission` decimal(10,2) NOT NULL,
  `remission_category` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `notes` text,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_fee_list`
--

CREATE TABLE `ct_student_fee_list` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `year` varchar(11) NOT NULL,
  `sub_head_id` int(11) NOT NULL,
  `fee` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_student_fee_type`
--

CREATE TABLE `ct_student_fee_type` (
  `id` int(11) NOT NULL,
  `fee_type` varchar(50) NOT NULL
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
-- Table structure for table `ct_student_wise_fee`
--

CREATE TABLE `ct_student_wise_fee` (
  `id` int(11) NOT NULL,
  `fee_type` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_roll` int(11) NOT NULL,
  `year` varchar(50) NOT NULL,
  `month` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `section` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL,
  `notes` varchar(100) DEFAULT NULL,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `transport_required` int(11) DEFAULT NULL,
  `transport_type` int(11) DEFAULT NULL,
  `transport_fee_id` int(11) DEFAULT NULL
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

-- --------------------------------------------------------

--
-- Table structure for table `ct_sub_head`
--

CREATE TABLE `ct_sub_head` (
  `id` int(11) NOT NULL,
  `head_id` int(11) NOT NULL,
  `sub_head_name` varchar(50) NOT NULL,
  `relation_to` int(11) NOT NULL COMMENT 'student=1/school=2/other=3',
  `type` int(11) NOT NULL COMMENT 'monthly=1/yearly=2/exam=3/other=4',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT 'active=1/inactive=0',
  `active_for_collection` int(11) NOT NULL DEFAULT '0',
  `sort_order` int(11) DEFAULT NULL,
  `isHidden` int(11) DEFAULT NULL,
  `is_editable` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ct_transport_fee_list`
--

CREATE TABLE `ct_transport_fee_list` (
  `id` int(11) NOT NULL,
  `fee_name` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `distance` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sm_paystation_transactions`
--

CREATE TABLE `sm_paystation_transactions` (
  `id` bigint(20) NOT NULL,
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` bigint(20) DEFAULT NULL,
  `student_data` longtext COLLATE utf8mb4_unicode_ci,
  `fee_data` longtext COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paystation_response` longtext COLLATE utf8mb4_unicode_ci,
  `payment_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_fee_collection_with_details`
-- (See below for the actual view)
--
CREATE TABLE `view_fee_collection_with_details` (
`id` int(11)
,`student_id` int(11)
,`student_roll` int(11)
,`year` int(11)
,`month` int(11)
,`class_id` int(11)
,`section` int(11)
,`group_id` int(11)
,`sub_total` decimal(10,2)
,`remission` decimal(10,2)
,`total` decimal(10,2)
,`status` int(11)
,`notes` text
,`date` datetime
,`created_by` int(11)
,`created_at` datetime
,`updated_by` int(11)
,`updated_at` datetime
,`sub_head_id` int(11)
,`fee` decimal(10,2)
,`reference` text
,`exam_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_ledger_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_ledger_summary` (
`sub_head_id` int(11)
,`sub_head_name` varchar(50)
,`balance` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_fee_collection_with_details`
--
DROP TABLE IF EXISTS `view_fee_collection_with_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mcnkhs`@`localhost` SQL SECURITY DEFINER VIEW `view_fee_collection_with_details`  AS SELECT `ct_student_fee_collection_info`.`id` AS `id`, `ct_student_fee_collection_info`.`student_id` AS `student_id`, `ct_student_fee_collection_info`.`student_roll` AS `student_roll`, `ct_student_fee_collection_info`.`year` AS `year`, `ct_student_fee_collection_info`.`month` AS `month`, `ct_student_fee_collection_info`.`class_id` AS `class_id`, `ct_student_fee_collection_info`.`section` AS `section`, `ct_student_fee_collection_info`.`group_id` AS `group_id`, `ct_student_fee_collection_info`.`sub_total` AS `sub_total`, `ct_student_fee_collection_info`.`remission` AS `remission`, `ct_student_fee_collection_info`.`total` AS `total`, `ct_student_fee_collection_info`.`status` AS `status`, `ct_student_fee_collection_info`.`notes` AS `notes`, `ct_student_fee_collection_info`.`date` AS `date`, `ct_student_fee_collection_info`.`created_by` AS `created_by`, `ct_student_fee_collection_info`.`created_at` AS `created_at`, `ct_student_fee_collection_info`.`updated_by` AS `updated_by`, `ct_student_fee_collection_info`.`updated_at` AS `updated_at`, `ct_student_fee_collection_details`.`sub_head_id` AS `sub_head_id`, `ct_student_fee_collection_details`.`fee` AS `fee`, `ct_student_fee_collection_details`.`reference` AS `reference`, `ct_student_fee_collection_details`.`exam_id` AS `exam_id` FROM (`ct_student_fee_collection_info` left join `ct_student_fee_collection_details` on((`ct_student_fee_collection_info`.`id` = `ct_student_fee_collection_details`.`info_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_ledger_summary`
--
DROP TABLE IF EXISTS `view_ledger_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`mcnkhs`@`localhost` SQL SECURITY DEFINER VIEW `view_ledger_summary`  AS SELECT `ct_sub_head`.`id` AS `sub_head_id`, `ct_sub_head`.`sub_head_name` AS `sub_head_name`, (sum(`ct_ledger`.`credit`) - sum(`ct_ledger`.`debit`)) AS `balance` FROM (`ct_sub_head` left join `ct_ledger` on((`ct_sub_head`.`id` = `ct_ledger`.`sub_head_id`))) WHERE ((`ct_sub_head`.`relation_to` = 2) AND (`ct_sub_head`.`head_id` in (1,4))) GROUP BY `ct_sub_head`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ct_absent_fee`
--
ALTER TABLE `ct_absent_fee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_admission_fee_promoted`
--
ALTER TABLE `ct_admission_fee_promoted`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_fee` (`class`);

--
-- Indexes for table `ct_late_fee`
--
ALTER TABLE `ct_late_fee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_revenue`
--
ALTER TABLE `ct_revenue`
  ADD PRIMARY KEY (`revId`);

--
-- Indexes for table `ct_revenue_cat`
--
ALTER TABLE `ct_revenue_cat`
  ADD PRIMARY KEY (`rcatid`),
  ADD UNIQUE KEY `rcatid` (`rcatid`);

--
-- Indexes for table `ct_student`
--
ALTER TABLE `ct_student`
  ADD PRIMARY KEY (`studentid`);

--
-- Indexes for table `ct_studentinfo`
--
ALTER TABLE `ct_studentinfo`
  ADD PRIMARY KEY (`infoid`),
  ADD UNIQUE KEY `infoid` (`infoid`),
  ADD KEY `infoStdid` (`infoStdid`);

--
-- Indexes for table `ct_student_exam_fee_summary`
--
ALTER TABLE `ct_student_exam_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_fee_collection_details`
--
ALTER TABLE `ct_student_fee_collection_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_fee_collection_info`
--
ALTER TABLE `ct_student_fee_collection_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_fee_list`
--
ALTER TABLE `ct_student_fee_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_fee_type`
--
ALTER TABLE `ct_student_fee_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_monthly_fee_summary`
--
ALTER TABLE `ct_student_monthly_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_wise_fee`
--
ALTER TABLE `ct_student_wise_fee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_student_yearly_fee_summary`
--
ALTER TABLE `ct_student_yearly_fee_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_sub_head`
--
ALTER TABLE `ct_sub_head`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ct_transport_fee_list`
--
ALTER TABLE `ct_transport_fee_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sm_paystation_transactions`
--
ALTER TABLE `sm_paystation_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD KEY `invoice_number` (`invoice_number`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ct_absent_fee`
--
ALTER TABLE `ct_absent_fee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_admission_fee_promoted`
--
ALTER TABLE `ct_admission_fee_promoted`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_late_fee`
--
ALTER TABLE `ct_late_fee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_revenue`
--
ALTER TABLE `ct_revenue`
  MODIFY `revId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_revenue_cat`
--
ALTER TABLE `ct_revenue_cat`
  MODIFY `rcatid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student`
--
ALTER TABLE `ct_student`
  MODIFY `studentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_studentinfo`
--
ALTER TABLE `ct_studentinfo`
  MODIFY `infoid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_exam_fee_summary`
--
ALTER TABLE `ct_student_exam_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_fee_collection_details`
--
ALTER TABLE `ct_student_fee_collection_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_fee_collection_info`
--
ALTER TABLE `ct_student_fee_collection_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_fee_list`
--
ALTER TABLE `ct_student_fee_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_fee_type`
--
ALTER TABLE `ct_student_fee_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_monthly_fee_summary`
--
ALTER TABLE `ct_student_monthly_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_wise_fee`
--
ALTER TABLE `ct_student_wise_fee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_student_yearly_fee_summary`
--
ALTER TABLE `ct_student_yearly_fee_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_sub_head`
--
ALTER TABLE `ct_sub_head`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_transport_fee_list`
--
ALTER TABLE `ct_transport_fee_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_paystation_transactions`
--
ALTER TABLE `sm_paystation_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ct_admission_fee_promoted`
--
ALTER TABLE `ct_admission_fee_promoted`
  ADD CONSTRAINT `fk_class_fee` FOREIGN KEY (`class`) REFERENCES `ct_class` (`classid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
