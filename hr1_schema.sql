-- Create the database
CREATE DATABASE IF NOT EXISTS gweens_hr_db;
USE gweens_hr_db;

-- 1. Create Jobs & Applications Tables
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    location VARCHAR(100) DEFAULT 'Quezon City',
    type VARCHAR(50) NOT NULL,
    salary VARCHAR(100),
    description TEXT,
    status ENUM('Pending Approval', 'Approved', 'Rejected', 'Active', 'Closed') DEFAULT 'Pending Approval',
    submitted_by VARCHAR(100), -- Department that submitted the job posting
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Rename/align applications => `applicants` (used by api.php/admin_api.php)
CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    cover_letter TEXT,
    resume_path VARCHAR(255),
    status ENUM('Screening', 'Interview', 'Training', 'Performance Check', 'Hired', 'Rejected') DEFAULT 'Screening',
    password_hash VARCHAR(255) NULL,
    requesting_department VARCHAR(100), -- Department requesting the applicant
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- 2. Create Security & Admin Tables
CREATE TABLE IF NOT EXISTS hr_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(100) DEFAULT 'HR Admin',
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES hr_admins(id) ON DELETE CASCADE
);

-- 3. Create Module Tables (Onboarding, Performance, Recognition)
-- Onboarding tasks reference `applicants` (aligned with admin_api.php)
CREATE TABLE IF NOT EXISTS onboarding_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
);

-- Add target_applicant_id to link recognition posts to applicants
CREATE TABLE IF NOT EXISTS recognition_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('welcome', 'kudos') NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    target_applicant_id INT NULL,
    target_name VARCHAR(150) NULL,
    role VARCHAR(100) NULL,
    badge VARCHAR(100) NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'award',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (target_applicant_id) REFERENCES applicants(id) ON DELETE SET NULL
);

-- Performance evaluation tables (created because admin_api.php references them)
CREATE TABLE IF NOT EXISTS performance_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    overall_score DECIMAL(4,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS performance_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    skill_name VARCHAR(150) NOT NULL,
    rating TINYINT NULL,
    FOREIGN KEY (evaluation_id) REFERENCES performance_evaluations(id) ON DELETE CASCADE
);