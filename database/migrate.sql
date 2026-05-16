-- =====================================================
--  MIGRATION SCRIPT – Run on EXISTING database
--  Upgrades the original SMS tables for UNIDEL
-- =====================================================

-- 1. Add 'staff' to users role ENUM and add email column
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','student','staff') NOT NULL DEFAULT 'student',
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) DEFAULT NULL AFTER `pass`,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 2. Create staff table
CREATE TABLE IF NOT EXISTS `staff` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL,
  `full_name`   VARCHAR(150) NOT NULL,
  `staff_id`    VARCHAR(50)  NOT NULL UNIQUE,
  `department`  VARCHAR(100) NOT NULL DEFAULT 'Computer Science',
  `designation` VARCHAR(100) DEFAULT 'Lecturer',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create departments table
CREATE TABLE IF NOT EXISTS `departments` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `dept_code`  VARCHAR(20)  NOT NULL UNIQUE,
  `dept_name`  VARCHAR(150) NOT NULL,
  `faculty`    VARCHAR(150) NOT NULL DEFAULT 'Faculty of Computing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `departments` (`dept_code`, `dept_name`) VALUES
  ('CSC', 'Computer Science'),
  ('CYB', 'Cyber Security'),
  ('SEN', 'Software Engineering'),
  ('IFT', 'Information Technology')
ON DUPLICATE KEY UPDATE `dept_name`=VALUES(`dept_name`);

-- 4. Create courses table
CREATE TABLE IF NOT EXISTS `courses` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `course_code`   VARCHAR(20)  NOT NULL UNIQUE,
  `course_title`  VARCHAR(200) NOT NULL,
  `credit_units`  INT          NOT NULL DEFAULT 3,
  `semester`      INT          NOT NULL,
  `level`         INT          NOT NULL DEFAULT 100,
  `dept_id`       INT          NOT NULL,
  FOREIGN KEY (`dept_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create course_registrations table
CREATE TABLE IF NOT EXISTS `course_registrations` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `course_id`     INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester`      INT NOT NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_reg` (`student_id`, `course_id`, `academic_year`, `semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create grades table
CREATE TABLE IF NOT EXISTS `grades` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `course_id`     INT NOT NULL,
  `academic_year` VARCHAR(20)  NOT NULL,
  `semester`      INT          NOT NULL,
  `ca_score`      DECIMAL(5,2) DEFAULT 0,
  `exam_score`    DECIMAL(5,2) DEFAULT 0,
  `total_score`   DECIMAL(5,2) DEFAULT 0,
  `grade`         VARCHAR(2)   DEFAULT NULL,
  `grade_point`   DECIMAL(3,1) DEFAULT 0,
  `entered_by`    INT DEFAULT NULL,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_grade` (`student_id`, `course_id`, `academic_year`, `semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`) REFERENCES `staff`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Create staff_courses table
CREATE TABLE IF NOT EXISTS `staff_courses` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id`      INT NOT NULL,
  `course_id`     INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester`      INT NOT NULL,
  UNIQUE KEY `unique_assign` (`staff_id`, `course_id`, `academic_year`, `semester`),
  FOREIGN KEY (`staff_id`)  REFERENCES `staff`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Create academic_sessions table
CREATE TABLE IF NOT EXISTS `academic_sessions` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `session_name`  VARCHAR(20) NOT NULL UNIQUE,
  `is_current`    TINYINT(1) DEFAULT 0,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `academic_sessions` (`session_name`, `is_current`) VALUES
  ('2025/2026', 1)
ON DUPLICATE KEY UPDATE `is_current`=VALUES(`is_current`);
