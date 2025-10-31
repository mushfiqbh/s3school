/* =============================================
|        INSERT NEW PAGES UNDER ACADEMIC        |
============================================== */
-- SET THE URL OF YOUR SITE
-- Must have trailing slash at the end, example: https://www.yoursite.com/
SELECT "https://www.ziisc.com/" INTO @yoursite_url;



SELECT ID INTO @academic_page_id
FROM sm_posts
WHERE post_title = 'Academic' AND post_type = 'page'
LIMIT 1;

SET @pages = JSON_ARRAY(
    'Admin - Commitee Management', 'Admin - Staff Management', 'Admin Applicants',
    'Committees', 'Concern Letter', 'Former Staffs', 'Former Teachers',
    'Import Export DB -EXCEL - CSV', 'Our Staffs', 'Our Teachers',
    'Our Lecturers', 'Teacher Profile'
);

SET @i = 0;
WHILE @i < JSON_LENGTH(@pages) DO
    SET @title = JSON_UNQUOTE(JSON_EXTRACT(@pages, CONCAT('$[', @i, ']')));
    SET @slug = LOWER(REPLACE(@title, ' ', '-'));

    SET @stmt = IF(
        NOT EXISTS(SELECT * FROM sm_posts WHERE post_title = @title AND post_type = 'page'),
        CONCAT(
            "INSERT INTO sm_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count) VALUES (1, NOW(), NOW(), '', '",
            @title,
            "', '', 'publish', 'closed', 'closed', '', '",
            @slug,
            "', '', '', NOW(), NOW(), '', ",
            @academic_page_id,
            ", CONCAT('",
            @yoursite_url,
            "', '?page_id=', UUID_SHORT()), 0, 'page', '', 0)"
        ),
        CONCAT('SELECT "Page ', @title, ' already exists";')
    );

    PREPARE stmt FROM @stmt;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @i = @i + 1;
END WHILE;



/* =============================================
|            INSERT NEW OPTIONS SAFELY          |
============================================== */
-- Use INSERT IGNORE to skip existing options
INSERT IGNORE INTO `sm_options` (`option_name`, `option_value`, `autoload`) VALUES
('institute_name', 'Not Set', 'yes'),
('inst_head_title', 'Not Set', 'yes'),
('inst_head_name', 'Not Set', 'yes'),
('institute_address', 'Not Set', 'yes'),
('institute_email', 'barnomala@gmail.com', 'yes'),
('institute_phone', 'Not Set', 'yes'),
('institute_eiin', '......', 'yes'),
('institute_code', '......', 'yes'),
('instLogo', '', 'yes'),
('center_code', '....', 'yes'),
('estd_year', '....', 'yes'),
('headmasterSpeechTitle', 'প্রধান শিক্ষক মহোদয়ের বাণী', 'yes'),
('chairmanSpeechTitle', 'সভাপতি মহোদয়ের বাণী', 'yes'),
('layout_visibility', '{"teachers":1,"committees":1,"gallery":1,"classwise_students":1,"student_demographics":1}', 'yes'),
('class_wise_students', '{"class_six":120,"class_seven":100,"class_eight":90,"class_nine":80,"class_ten":80,"class_eleven":50,"class_twelve":50,"class_play":40,"class_one":30,"class_kg":20}', 'yes'),
('student_demographics', '{"total_students":660,"boys":400,"girls":260,"gender_other":0,"muslim":600,"hinduism":60,"buddhist":0,"christian":0,"other":0}', 'yes'),
('totalStudent', '0', 'yes'),
('totalClasses', '10', 'yes'),
('totalStudents', '660', 'yes'),
('totalTeachers', '18', 'yes'),
('totalStaffs', '5', 'yes');



/* =============================================
|          ALTER COLUMNS TO TABLES SAFELY      |
============================================== */
-- Ensure assignSection is JSON: add if missing, or modify if existing but not JSON
SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_teacher' AND COLUMN_NAME='assignSection'
    ),
    'ALTER TABLE ct_teacher ADD COLUMN assignSection JSON NULL;',
    IF(
        (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_teacher' AND COLUMN_NAME='assignSection') <> 'json',
        'ALTER TABLE ct_teacher MODIFY COLUMN assignSection JSON NULL;',
        'SELECT "Column assignSection already exists and is JSON";'
    )
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Check before adding columns
SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_class' AND COLUMN_NAME='classOrder'
    ),
    'ALTER TABLE ct_class ADD COLUMN classOrder INT NOT NULL DEFAULT 0;',
    'SELECT "Column classOrder already exists in ct_class";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_teacher' AND COLUMN_NAME='status'
    ),
    'ALTER TABLE ct_teacher ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT "Present";',
    'SELECT "Column status already exists in ct_teacher";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_teacher' AND COLUMN_NAME='teacherOfClass'
    ),
    'ALTER TABLE ct_teacher ADD COLUMN teacherOfClass INT NULL DEFAULT NULL;',
    'SELECT "Column teacherOfClass already exists in ct_teacher";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    NOT EXISTS(
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='ct_teacher' AND COLUMN_NAME='teacherOfSection'
    ),
    'ALTER TABLE ct_teacher ADD COLUMN teacherOfSection INT NULL DEFAULT NULL;',
    'SELECT "Column teacherOfSection already exists in ct_teacher";'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;



/* =============================================
|       REMOVE OLD PAGE + MENU ENTRY SAFELY     |
============================================== */
-- Delete only if they exist
DELETE pm, p, tr
FROM sm_posts p
LEFT JOIN sm_postmeta pm ON p.ID = pm.post_id
LEFT JOIN sm_term_relationships tr ON p.ID = tr.object_id
WHERE p.post_type = 'nav_menu_item'
  AND (
      p.post_title = 'Teachers & Staffs'
      OR pm.meta_value = 'teachers-staffs'
      OR pm.meta_value = 'Teachers & Staffs'
  );

DELETE pm, p
FROM sm_posts p
LEFT JOIN sm_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'page'
  AND (p.post_name = 'teachers-staffs' OR p.post_title = 'Teachers & Staffs');


/* =============================================
|              CREATE NEW TABLES               |
============================================== */

DROP TABLE IF EXISTS `ct_staff`;
DROP TABLE IF EXISTS `ct_committee`;
DROP TABLE IF EXISTS `ct_online_application`;

CREATE TABLE `ct_staff` (
  `staffid` int(11) NOT NULL AUTO_INCREMENT,
  `staffUserId` int(11) DEFAULT NULL,
  `staffName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `staffSQuali` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffImg` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffFather` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffMother` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffDesignation` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffBirth` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffBlood` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffJoining` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffPhone` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffNid` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffPresent` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffPermanent` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffMpo` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffQualificarion` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffTraining` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffNote` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staffCreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `staff_serial` int(11) DEFAULT NULL,
  `assignSection` int(11) DEFAULT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Present',
  PRIMARY KEY (`staffid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
COMMIT;

CREATE TABLE `ct_committee` (
  `committeeid` int(11) NOT NULL AUTO_INCREMENT,
  `committeeName` varchar(255) NOT NULL,
  `committeeFather` varchar(255) NOT NULL,
  `committeeMother` varchar(255) NOT NULL,
  `committeeDesignation` varchar(255) NOT NULL,
  `committeeSession` varchar(100) NOT NULL,
  `committeeStatus` enum('active','inactive') NOT NULL DEFAULT 'active',
  `committeeNote` text,
  `committee_serial` int(11) DEFAULT NULL,
  `committeeImg` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`committeeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
COMMIT;

CREATE TABLE `ct_online_application` (
  `applicationid` int(11) NOT NULL AUTO_INCREMENT,
  `studentid` int(11) NOT NULL,
  `stdName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdNameBangla` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdGender` int(11) NOT NULL DEFAULT '1',
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
  `stdGuardianNID` bigint(20) NOT NULL,
  `stdPhone` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `stdPermanent` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdPresent` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `stdBrith` date NOT NULL,
  `stdNationality` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `stdReligion` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stdAdmitClass` int(11) NOT NULL COMMENT 'Class Table ID',
  `stdSection` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdRoll` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdAdmitYear` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `stdTcNumber` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sscRoll` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sscReg` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdPrevSchool` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdGPA` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdIntellectual` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdScholarsClass` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdScholarsYear` year(4) DEFAULT NULL,
  `stdScholarsMemo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stdStatus` int(11) NOT NULL DEFAULT '1',
  `stdCreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stdUpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paymentPaid` int(11) DEFAULT NULL,
  `paymentDue` int(11) DEFAULT NULL,
  `stdNote` text COLLATE utf8_unicode_ci,
  `approve_status` enum('Submitted','Under Review','Approved','Registered','Rejected') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Submitted',
  PRIMARY KEY (`applicationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
COMMIT;
