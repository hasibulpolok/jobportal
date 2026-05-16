-- ============================================================
-- TalentBridge Job Portal - Full Database Schema
-- Developer: Hasibul Polok
-- Version: 2.1 (Fixed password hashes)
-- ============================================================

CREATE DATABASE IF NOT EXISTS talentbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE talentbridge;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS saved_jobs;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    icon       VARCHAR(50)  DEFAULT 'briefcase',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('jobseeker','employer','admin') NOT NULL DEFAULT 'jobseeker',
    status     ENUM('active','inactive','banned')   NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: user_profiles
-- ============================================================
CREATE TABLE user_profiles (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT          NOT NULL,
    phone         VARCHAR(20),
    address       TEXT,
    city          VARCHAR(100),
    country       VARCHAR(100) DEFAULT 'Bangladesh',
    bio           TEXT,
    skills        TEXT,
    experience    TEXT,
    education     TEXT,
    cv_file       VARCHAR(255),
    profile_photo VARCHAR(255),
    linkedin      VARCHAR(255),
    website       VARCHAR(255),
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: companies
-- ============================================================
CREATE TABLE companies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    name         VARCHAR(200) NOT NULL,
    logo         VARCHAR(255),
    website      VARCHAR(255),
    email        VARCHAR(150),
    phone        VARCHAR(20),
    industry     VARCHAR(100),
    size         ENUM('1-10','11-50','51-200','201-500','500+') DEFAULT '1-10',
    founded_year YEAR,
    address      TEXT,
    city         VARCHAR(100),
    country      VARCHAR(100) DEFAULT 'Bangladesh',
    description  TEXT,
    status       ENUM('active','inactive') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: jobs
-- ============================================================
CREATE TABLE jobs (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    employer_id      INT          NOT NULL,
    company_id       INT          NOT NULL,
    category_id      INT          NOT NULL,
    title            VARCHAR(200) NOT NULL,
    slug             VARCHAR(250) NOT NULL UNIQUE,
    description      LONGTEXT     NOT NULL,
    requirements     TEXT,
    responsibilities TEXT,
    type             ENUM('full-time','part-time','remote','contract','internship','freelance') DEFAULT 'full-time',
    location         VARCHAR(200),
    city             VARCHAR(100),
    salary_min       DECIMAL(10,2),
    salary_max       DECIMAL(10,2),
    salary_type      ENUM('monthly','yearly','hourly','negotiable') DEFAULT 'monthly',
    experience_level ENUM('entry','mid','senior','lead','executive') DEFAULT 'entry',
    education_level  VARCHAR(100),
    skills_required  TEXT,
    deadline         DATE,
    status           ENUM('pending','approved','rejected','closed','draft') DEFAULT 'pending',
    is_featured      TINYINT(1)   DEFAULT 0,
    views            INT          DEFAULT 0,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id)  REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (company_id)   REFERENCES companies(id)  ON DELETE CASCADE,
    FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: applications
-- ============================================================
CREATE TABLE applications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    job_id        INT  NOT NULL,
    user_id       INT  NOT NULL,
    cover_letter  TEXT,
    cv_file       VARCHAR(255),
    status        ENUM('pending','reviewed','shortlisted','interviewed','hired','rejected') DEFAULT 'pending',
    employer_note TEXT,
    applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: saved_jobs
-- ============================================================
CREATE TABLE saved_jobs (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    job_id   INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
    UNIQUE KEY unique_saved (user_id, job_id)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES
-- ============================================================
INSERT INTO categories (name, slug, icon) VALUES
('Information Technology', 'information-technology', 'laptop'),
('Marketing & Sales',      'marketing-sales',        'trending-up'),
('Design & Creative',      'design-creative',        'pen-tool'),
('Finance & Accounting',   'finance-accounting',     'dollar-sign'),
('Healthcare',             'healthcare',             'heart'),
('Education',              'education',              'book-open'),
('Engineering',            'engineering',            'settings'),
('Human Resources',        'human-resources',        'users'),
('Legal',                  'legal',                  'shield'),
('Customer Service',       'customer-service',       'headphones');

-- ============================================================
-- USERS
-- Admin    password: admin123
-- Others   password: password123
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
('Admin TalentBridge', 'admin@talentbridge.com', '$2y$10$Pnsf/LmcnEBblQ7KblVVJei0L6u4Vy/cRyvGPGYCjX6F4D8.8zOPm', 'admin');

INSERT INTO users (name, email, password, role) VALUES
('Tech Innovations BD', 'employer@techinno.com',    '$2y$10$AYNsdVD60VYQKY6p1PmKTu/bEc2RluPG90VwDbKX7vO4v4eNfbJ1G', 'employer'),
('GreenFuture Corp',    'employer@greenfuture.com', '$2y$10$AYNsdVD60VYQKY6p1PmKTu/bEc2RluPG90VwDbKX7vO4v4eNfbJ1G', 'employer');

INSERT INTO users (name, email, password, role) VALUES
('Rafiq Ahmed',   'rafiq@example.com',   '$2y$10$AYNsdVD60VYQKY6p1PmKTu/bEc2RluPG90VwDbKX7vO4v4eNfbJ1G', 'jobseeker'),
('Sanjida Islam', 'sanjida@example.com', '$2y$10$AYNsdVD60VYQKY6p1PmKTu/bEc2RluPG90VwDbKX7vO4v4eNfbJ1G', 'jobseeker');

-- ============================================================
-- COMPANIES
-- ============================================================
INSERT INTO companies
    (user_id, name, website, email, phone, industry, size, founded_year, city, country, description)
VALUES
(2, 'Tech Innovations BD', 'https://techinno.com', 'info@techinno.com', '+8801712345678',
 'Software Development', '51-200', 2015, 'Dhaka', 'Bangladesh',
 'Leading software company in Bangladesh specialising in web and mobile solutions.'),
(3, 'GreenFuture Corp', 'https://greenfuture.com', 'info@greenfuture.com', '+8801812345678',
 'Environmental Consulting', '11-50', 2018, 'Chittagong', 'Bangladesh',
 'We build sustainable business solutions for a greener tomorrow.');

-- ============================================================
-- USER PROFILES
-- ============================================================
INSERT INTO user_profiles (user_id, phone, city, country, bio, skills, experience, education) VALUES
(4, '+8801900001111', 'Dhaka', 'Bangladesh',
 'Passionate web developer with 3 years of experience.',
 'PHP, JavaScript, MySQL, HTML, CSS, Laravel',
 'Junior Developer at CodeSoft BD (2022-2024)',
 'Diploma in Computer Engineering, RIIT Rangpur (2019-2022)'),
(5, '+8801900002222', 'Sylhet', 'Bangladesh',
 'Digital marketing specialist with proven track record.',
 'SEO, Social Media, Content Writing, Google Ads',
 'Marketing Executive at BrandBuilders (2021-2023)',
 'BBA in Marketing, Shahjalal University (2017-2021)');

-- ============================================================
-- JOBS
-- ============================================================
INSERT INTO jobs
    (employer_id, company_id, category_id, title, slug,
     description, requirements, responsibilities,
     type, location, city, salary_min, salary_max, salary_type,
     experience_level, education_level, skills_required,
     deadline, status, is_featured)
VALUES
(2, 1, 1,
 'Senior PHP Developer', 'senior-php-developer-tech-innovations',
 'We are looking for an experienced PHP developer to join our growing team. You will work on exciting web projects using modern PHP practices.',
 'Minimum 3 years PHP experience
Knowledge of Laravel or CodeIgniter
MySQL expertise
REST API development',
 'Design and build PHP web applications
Write clean testable code
Review junior code
Participate in daily stand-ups',
 'full-time', 'Banani, Dhaka, Bangladesh', 'Dhaka',
 50000, 80000, 'monthly', 'senior', 'BSc in Computer Science or equivalent',
 'PHP, Laravel, MySQL, REST API, Git',
 DATE_ADD(NOW(), INTERVAL 30 DAY), 'approved', 1),

(2, 1, 3,
 'UI/UX Designer', 'ui-ux-designer-tech-innovations',
 'Join our creative team as a UI/UX Designer. Create beautiful user-friendly interfaces for our web and mobile products.',
 'Proficiency in Figma or Adobe XD
UX research skills
Strong portfolio required',
 'Create wireframes and prototypes
Conduct user research
Maintain design system',
 'full-time', 'Gulshan, Dhaka, Bangladesh', 'Dhaka',
 35000, 55000, 'monthly', 'mid', 'Degree in Design or related field',
 'Figma, Adobe XD, UI Design, UX Research',
 DATE_ADD(NOW(), INTERVAL 25 DAY), 'approved', 0),

(3, 2, 2,
 'Digital Marketing Manager', 'digital-marketing-manager-greenfuture',
 'Lead our digital marketing efforts and grow our online presence across multiple channels.',
 'Google Analytics certification preferred
4+ years marketing experience
Team leadership skills',
 'Develop digital marketing strategy
Manage SEO and paid ads
Produce monthly reports',
 'full-time', 'Agrabad, Chittagong, Bangladesh', 'Chittagong',
 45000, 70000, 'monthly', 'senior', 'BBA/MBA in Marketing',
 'SEO, Google Ads, Facebook Ads, Google Analytics',
 DATE_ADD(NOW(), INTERVAL 20 DAY), 'approved', 1),

(3, 2, 8,
 'HR Executive', 'hr-executive-greenfuture',
 'We need a dedicated HR Executive to manage our growing team and implement HR best practices.',
 'HR degree or equivalent
Knowledge of Bangladesh labour law
HRIS experience preferred',
 'Manage recruitment process
Handle onboarding
Maintain employee records',
 'full-time', 'Agrabad, Chittagong, Bangladesh', 'Chittagong',
 25000, 40000, 'monthly', 'entry', 'BBA/MBA in HRM',
 'Recruitment, Onboarding, HRIS, Labour Law',
 DATE_ADD(NOW(), INTERVAL 15 DAY), 'approved', 0),

(2, 1, 1,
 'Junior React Developer', 'junior-react-developer-tech-innovations',
 'Exciting opportunity for fresh graduates! Join our frontend team and work on cutting-edge React applications.',
 'Basic React.js knowledge
JavaScript ES6+
Willingness to learn',
 'Build UI components
Integrate with APIs
Fix bugs and improve performance',
 'full-time', 'Banani, Dhaka, Bangladesh', 'Dhaka',
 20000, 30000, 'monthly', 'entry', 'BSc in CSE or Diploma in Computer Engineering',
 'React.js, JavaScript, HTML, CSS, Git',
 DATE_ADD(NOW(), INTERVAL 35 DAY), 'approved', 0);

-- ============================================================
-- SAVED JOBS
-- ============================================================
INSERT INTO saved_jobs (user_id, job_id) VALUES (4, 1), (4, 3), (5, 3);

-- ============================================================
-- SAMPLE APPLICATION
-- ============================================================
INSERT INTO applications (job_id, user_id, cover_letter, status) VALUES
(2, 4, 'I am very interested in the UI/UX Designer position. While my background is in development, I have a strong passion for design and have completed several UI/UX courses. I believe I can bring a unique technical and design perspective.', 'reviewed');
