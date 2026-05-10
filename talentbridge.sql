-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 05:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `talentbridge`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','interviewed','hired','rejected') DEFAULT 'pending',
  `employer_note` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `user_id`, `cover_letter`, `cv_file`, `status`, `employer_note`, `applied_at`, `updated_at`) VALUES
(1, 2, 4, 'I am very interested in the UI/UX Designer position. While my background is in development, I have a strong passion for design and have completed several design courses. I believe I can bring a unique perspective combining technical and design skills.', NULL, 'reviewed', NULL, '2026-05-10 02:55:48', '2026-05-10 02:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'briefcase',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `created_at`) VALUES
(1, 'Information Technology', 'information-technology', 'laptop', '2026-05-10 02:55:48'),
(2, 'Marketing & Sales', 'marketing-sales', 'trending-up', '2026-05-10 02:55:48'),
(3, 'Design & Creative', 'design-creative', 'pen-tool', '2026-05-10 02:55:48'),
(4, 'Finance & Accounting', 'finance-accounting', 'dollar-sign', '2026-05-10 02:55:48'),
(5, 'Healthcare', 'healthcare', 'heart', '2026-05-10 02:55:48'),
(6, 'Education', 'education', 'book-open', '2026-05-10 02:55:48'),
(7, 'Engineering', 'engineering', 'settings', '2026-05-10 02:55:48'),
(8, 'Human Resources', 'human-resources', 'users', '2026-05-10 02:55:48'),
(9, 'Legal', 'legal', 'shield', '2026-05-10 02:55:48'),
(10, 'Customer Service', 'customer-service', 'headphones', '2026-05-10 02:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `size` enum('1-10','11-50','51-200','201-500','500+') DEFAULT '1-10',
  `founded_year` year(4) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Bangladesh',
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `name`, `logo`, `website`, `email`, `phone`, `industry`, `size`, `founded_year`, `address`, `city`, `country`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Tech Innovations BD', NULL, 'https://techinno.com', 'info@techinno.com', '+8801712345678', 'Software Development', '51-200', '2015', NULL, 'Dhaka', 'Bangladesh', 'Leading software company in Bangladesh specializing in web and mobile solutions.', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(2, 3, 'GreenFuture Corp', NULL, 'https://greenfuture.com', 'info@greenfuture.com', '+8801812345678', 'Environmental Consulting', '11-50', '2018', NULL, 'Chittagong', 'Bangladesh', 'We build sustainable business solutions for a greener tomorrow.', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(250) NOT NULL,
  `description` longtext NOT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `type` enum('full-time','part-time','remote','contract','internship','freelance') DEFAULT 'full-time',
  `location` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `salary_type` enum('monthly','yearly','hourly','negotiable') DEFAULT 'monthly',
  `experience_level` enum('entry','mid','senior','lead','executive') DEFAULT 'entry',
  `education_level` varchar(100) DEFAULT NULL,
  `skills_required` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','closed','draft') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `employer_id`, `company_id`, `category_id`, `title`, `slug`, `description`, `requirements`, `responsibilities`, `type`, `location`, `city`, `salary_min`, `salary_max`, `salary_type`, `experience_level`, `education_level`, `skills_required`, `deadline`, `status`, `is_featured`, `views`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 'Senior PHP Developer', 'senior-php-developer-tech-innovations', 'We are looking for an experienced PHP developer to join our growing team. You will work on exciting projects using modern PHP practices and frameworks.', 'Minimum 3 years PHP experience, knowledge of Laravel or CodeIgniter, MySQL expertise, REST API development', NULL, 'full-time', 'Dhaka, Bangladesh', 'Dhaka', 50000.00, 80000.00, 'monthly', 'senior', NULL, NULL, '2026-06-09', 'approved', 1, 1, '2026-05-10 02:55:48', '2026-05-10 03:01:11'),
(2, 2, 1, 3, 'UI/UX Designer', 'ui-ux-designer-tech-innovations', 'Join our creative team as a UI/UX Designer. You will create beautiful, user-friendly interfaces for our web and mobile products.', 'Proficiency in Figma or Adobe XD, understanding of UX principles, portfolio required', NULL, 'full-time', 'Dhaka, Bangladesh', 'Dhaka', 35000.00, 55000.00, 'monthly', 'mid', NULL, NULL, '2026-06-04', 'approved', 0, 0, '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(3, 3, 2, 2, 'Digital Marketing Manager', 'digital-marketing-manager-greenfuture', 'Lead our digital marketing efforts and grow our online presence across multiple channels.', 'Google Analytics certification preferred, 4+ years marketing experience, team leadership skills', NULL, 'full-time', 'Chittagong, Bangladesh', 'Chittagong', 45000.00, 70000.00, 'monthly', 'senior', NULL, NULL, '2026-05-30', 'approved', 1, 0, '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(4, 3, 2, 8, 'HR Executive', 'hr-executive-greenfuture', 'We need a dedicated HR Executive to manage our growing team and implement HR best practices.', 'HR degree or equivalent, knowledge of Bangladesh labor law, HRIS experience preferred', NULL, 'full-time', 'Chittagong, Bangladesh', 'Chittagong', 25000.00, 40000.00, 'monthly', 'entry', NULL, NULL, '2026-05-25', 'approved', 0, 0, '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(5, 2, 1, 1, 'Junior React Developer', 'junior-react-developer-tech-innovations', 'Exciting opportunity for fresh graduates! Join our frontend team and work on cutting-edge React applications.', 'Basic React knowledge, JavaScript ES6+, willing to learn', NULL, 'full-time', 'Dhaka, Bangladesh', 'Dhaka', 20000.00, 30000.00, 'monthly', 'entry', NULL, NULL, '2026-06-14', 'approved', 0, 1, '2026-05-10 02:55:48', '2026-05-10 03:19:09');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `saved_at`) VALUES
(1, 4, 1, '2026-05-10 02:55:48'),
(2, 4, 3, '2026-05-10 02:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('jobseeker','employer','admin') NOT NULL DEFAULT 'jobseeker',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin TalentBridge', 'admin@talentbridge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2026-05-10 02:55:48', '2026-05-10 03:17:49'),
(2, 'Tech Innovations BD', 'employer@techinno.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(3, 'GreenFuture Corp', 'employer@greenfuture.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(4, 'Rafiq Ahmed', 'rafiq@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jobseeker', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(5, 'Sanjida Islam', 'sanjida@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jobseeker', 'active', '2026-05-10 02:55:48', '2026-05-10 02:55:48'),
(6, 'Md Hasibul Bashar Polok', 'hasibulpolok.bdn@gmail.com', '$2y$10$BbMHMXrxlPzlqGniiVHGUe4vmVv3bZMes38nfoUS97eS.hW4rd/Ki', 'jobseeker', 'active', '2026-05-10 03:01:38', '2026-05-10 03:01:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Bangladesh',
  `bio` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `phone`, `address`, `city`, `country`, `bio`, `skills`, `experience`, `education`, `cv_file`, `profile_photo`, `linkedin`, `website`, `updated_at`) VALUES
(1, 4, '+8801900001111', NULL, 'Dhaka', 'Bangladesh', 'Passionate web developer with 3 years of experience.', 'PHP, JavaScript, MySQL, HTML, CSS', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 02:55:48'),
(2, 5, '+8801900002222', NULL, 'Sylhet', 'Bangladesh', 'Digital marketing specialist with proven track record.', 'SEO, Social Media, Content Writing, Google Ads', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 02:55:48'),
(3, 6, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 03:01:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `employer_id` (`employer_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved` (`user_id`,`job_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
