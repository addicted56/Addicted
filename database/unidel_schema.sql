-- =====================================================
--  UNIDEL – Faculty of Computing
--  Web-Based Student Academic Record Management System
--  FULL DATABASE SCHEMA (RUN THIS ON A FRESH MySQL DB)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- =====================================================
--  1. USERS TABLE (updated – adds 'staff' role)
-- =====================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `user`                  VARCHAR(100) NOT NULL UNIQUE,
  `pass`                  VARCHAR(255) NOT NULL,
  `email`                 VARCHAR(150) DEFAULT NULL,
  `role`                  ENUM('admin','student','staff') NOT NULL DEFAULT 'student',
  `must_change_password`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  2. STUDENTS TABLE (unchanged structure)
-- =====================================================

CREATE TABLE IF NOT EXISTS `students` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT NOT NULL,
  `name`     VARCHAR(150) NOT NULL,
  `roll_no`  VARCHAR(50)  NOT NULL,
  `branch`   VARCHAR(100) DEFAULT NULL,
  `year`     INT DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  3. ACADEMIC STAFF TABLE
-- =====================================================

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

-- =====================================================
--  4. DEPARTMENTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `departments` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `dept_code`       VARCHAR(20)  NOT NULL UNIQUE,
  `dept_name`       VARCHAR(150) NOT NULL,
  `faculty`         VARCHAR(150) NOT NULL DEFAULT 'Faculty of Computing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `departments` (`dept_code`, `dept_name`) VALUES
  ('CSC', 'Computer Science'),
  ('CYB', 'Cyber Security'),
  ('SEN', 'Software Engineering'),
  ('IFT', 'Information Technology')
ON DUPLICATE KEY UPDATE `dept_name`=VALUES(`dept_name`);

-- =====================================================
--  5. COURSES TABLE
-- =====================================================

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

-- =====================================================
--  6. COURSE REGISTRATION TABLE
-- =====================================================

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

-- =====================================================
--  7. GRADES TABLE (per-course results by staff)
-- =====================================================

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
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`) REFERENCES `staff`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  8. STAFF-COURSE ASSIGNMENT (which staff teaches what)
-- =====================================================

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

-- =====================================================
--  9. SEMESTER_MARKS TABLE (legacy – kept for compat)
-- =====================================================

CREATE TABLE IF NOT EXISTS `semester_marks` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`     INT NOT NULL,
  `semester`       INT NOT NULL,
  `obtained_marks` INT DEFAULT 0,
  `sgpa`           DECIMAL(4,2) DEFAULT 0.00,
  UNIQUE KEY `unique_sem` (`student_id`, `semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  10. ATTENDANCE TABLE (legacy – kept for compat)
-- =====================================================

CREATE TABLE IF NOT EXISTS `attendance` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`          INT NOT NULL,
  `attendance_percent`  DECIMAL(5,2) DEFAULT 0.00,
  UNIQUE KEY `unique_att` (`student_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  11. ACADEMIC SESSIONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `academic_sessions` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `session_name`  VARCHAR(20) NOT NULL UNIQUE,
  `is_current`    TINYINT(1) DEFAULT 0,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `academic_sessions` (`session_name`, `is_current`) VALUES
  ('2025/2026', 1)
ON DUPLICATE KEY UPDATE `is_current`=VALUES(`is_current`);

-- =====================================================
--  12. DEFAULT ADMIN ACCOUNT
-- =====================================================

INSERT INTO `users` (`user`, `pass`, `role`) VALUES
  ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE `role`='admin';
-- default password: password (bcrypt hash)

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
