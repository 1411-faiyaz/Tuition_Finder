-- =====================================================
-- Tuition Finder - Database Schema
-- Import this file in phpMyAdmin (XAMPP) to set up the DB
-- =====================================================

CREATE DATABASE IF NOT EXISTS tuition_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tuition_finder;

-- ---------------------------------------------------
-- Users table (tutors, guardians, admin)
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('tutor','guardian','admin') NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tutor profile details (1-1 with users where role=tutor)
-- ---------------------------------------------------
CREATE TABLE tutor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    education VARCHAR(100) DEFAULT NULL,
    institution VARCHAR(150) DEFAULT NULL,
    department VARCHAR(150) DEFAULT NULL,
    experience_years VARCHAR(50) DEFAULT NULL,
    curriculum VARCHAR(100) DEFAULT NULL,
    subjects VARCHAR(255) DEFAULT NULL,
    preferred_type VARCHAR(50) DEFAULT NULL,
    preferred_gender VARCHAR(20) DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    expected_salary INT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tuition posts (created by guardians)
-- ---------------------------------------------------
CREATE TABLE tuition_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardian_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    class_level VARCHAR(50) NOT NULL,
    curriculum VARCHAR(100) DEFAULT NULL,
    subject VARCHAR(150) NOT NULL,
    medium VARCHAR(50) DEFAULT NULL,
    location VARCHAR(150) NOT NULL,
    tuition_type VARCHAR(50) DEFAULT NULL,
    days_per_week VARCHAR(20) DEFAULT NULL,
    budget INT DEFAULT NULL,
    student_gender VARCHAR(20) DEFAULT 'Any',
    description TEXT DEFAULT NULL,
    status ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardian_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Applications (tutor applies to a tuition post)
-- ---------------------------------------------------
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tuition_id INT NOT NULL,
    tutor_id INT NOT NULL,
    cover_message TEXT DEFAULT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_application (tuition_id, tutor_id),
    FOREIGN KEY (tuition_id) REFERENCES tuition_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Direct messages between users
-- ---------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    tuition_id INT DEFAULT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Seed data
-- ---------------------------------------------------

-- Admin account -> phone: 01700000000 | password: admin123
INSERT INTO users (role, name, phone, email, password, status) VALUES
('admin', 'Site Administrator', '01700000000', 'admin@tuitionfinder.test',
 '$2b$10$Iuk5dFTPStEqoTZ3kdCZheBYTHvCWIsFj2nGiTLJYFAt.j6M5zYsq', 'approved');
-- NOTE: the hash above corresponds to password "admin123"

-- A couple of sample guardians & tutors so the app has content immediately
INSERT INTO users (role, name, phone, email, password, status) VALUES
('guardian', 'Rasel Mahmud', '01710000001', 'rasel@example.com',
 '$2b$10$Iuk5dFTPStEqoTZ3kdCZheBYTHvCWIsFj2nGiTLJYFAt.j6M5zYsq', 'approved'),
('tutor', 'Md. Siyam Hasan', '01710000002', 'siyam@example.com',
 '$2b$10$Iuk5dFTPStEqoTZ3kdCZheBYTHvCWIsFj2nGiTLJYFAt.j6M5zYsq', 'approved'),
('tutor', 'Abhijit Das Uday', '01710000003', 'abhijit@example.com',
 '$2b$10$Iuk5dFTPStEqoTZ3kdCZheBYTHvCWIsFj2nGiTLJYFAt.j6M5zYsq', 'pending');

INSERT INTO tutor_profiles (user_id, education, institution, department, experience_years, curriculum, subjects, preferred_type, preferred_gender, location, expected_salary, bio) VALUES
(3, 'Bachelor', 'Bangladesh University of Textiles', 'Industrial and Production Engineering', '1-2 years', 'Bangla Medium', 'Physics, Chemistry, Higher Mathematics', 'In-Person', 'No Preference', 'Banani, Dhaka', 7000, 'Experienced tutor focused on building strong fundamentals for HSC students.'),
(4, 'Bachelor', 'University of Dhaka', 'Dental Surgery', '2-3 years', 'English Version', 'Biology, Chemistry', 'Online', 'No Preference', 'Kalyanpur, Dhaka', 12000, 'Patient and result oriented tutor with admission test coaching experience.');

INSERT INTO tuition_posts (guardian_id, title, class_level, curriculum, subject, medium, location, tuition_type, days_per_week, budget, student_gender, description, status) VALUES
(2, 'Tutor needed for HSC 1st Year', 'HSC 1st Year', 'Bangla Medium', 'Physics, Chemistry and Higher Mathematics', 'Bangla Medium', 'Rampura, Dhaka', 'In-Person', '3-4 days/week', 9000, 'Any', 'Looking for a dedicated tutor to help with HSC first year science subjects.', 'active'),
(2, 'Female tutor needed for Class 1', 'Class 1', 'English Version', 'English and Math', 'English Version', 'Badda, Dhaka', 'In-Person', '3 days/week', 4000, 'Female', 'Need a friendly female tutor for a young learner.', 'active');
