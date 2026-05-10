# 🌉 TalentBridge — Job Portal Web Application

**Developer:** Hasibul Polok  
**Stack:** Core PHP · MySQL · HTML5 · CSS3 · JavaScript  
**Version:** 1.0.0

---

## 📋 Project Overview

TalentBridge is a complete, professional job portal web application built with
Core PHP (no frameworks), MySQL, HTML, CSS, and JavaScript. It supports three
user roles: Job Seeker, Employer, and Admin.

---

## ⚡ Quick Setup (XAMPP)

### Step 1 — Place files
Copy the `talentbridge` folder into:
```
C:\xampp\htdocs\talentbridge\
```

### Step 2 — Create the database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Click **New** → name it `talentbridge` → click **Create**
4. Click the `talentbridge` database → click **Import**
5. Choose `talentbridge.sql` → click **Go**

### Step 3 — Configure database connection
Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL user
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'talentbridge');
define('BASE_URL', 'http://localhost/talentbridge');
```

### Step 4 — Set folder permissions
Make sure `uploads/cvs/` is writable:
- On Windows (XAMPP): it works by default
- On Linux: `chmod -R 755 uploads/`

### Step 5 — Open in browser
Visit: `http://localhost/talentbridge`

---

## 🔑 Demo Login Credentials

| Role       | Email                        | Password  |
|------------|------------------------------|-----------|
| Admin      | admin@talentbridge.com       | password  |
| Employer   | employer@techinno.com        | password  |
| Employer 2 | employer@greenfuture.com     | password  |
| Job Seeker | rafiq@example.com            | password  |
| Job Seeker | sanjida@example.com          | password  |

> **Note:** The sample passwords are `password` (plain text). They are stored
> as bcrypt hashes in the database.

---

## 📂 Full Project Structure

```
talentbridge/
├── index.php                  ← Homepage
├── jobs.php                   ← Browse / search jobs
├── job.php                    ← Single job detail + apply
├── talentbridge.sql           ← Full database schema + sample data
├── .htaccess                  ← Security rules
│
├── config/
│   └── db.php                 ← Database connection + constants
│
├── includes/
│   ├── functions.php          ← All helper functions
│   ├── header.php             ← Global navbar + HTML head
│   └── footer.php             ← Global footer
│
├── auth/
│   ├── login.php              ← Login page
│   ├── register.php           ← Register (seeker + employer)
│   └── logout.php             ← Session destroy + redirect
│
├── user/                      ← Job Seeker section
│   ├── dashboard.php          ← Seeker dashboard
│   ├── profile.php            ← Edit profile + upload CV
│   ├── applications.php       ← View all applications
│   ├── saved-jobs.php         ← Saved/bookmarked jobs
│   └── save-job.php           ← AJAX: save/unsave job
│
├── employer/                  ← Employer section
│   ├── dashboard.php          ← Employer dashboard
│   ├── company.php            ← Company profile
│   ├── post-job.php           ← Post new job
│   ├── edit-job.php           ← Edit existing job
│   ├── jobs.php               ← Manage all jobs
│   ├── applicants.php         ← View all applicants
│   ├── view-application.php   ← Single application + status update
│   └── download-cv.php        ← Secure CV file download
│
├── admin/                     ← Admin panel
│   ├── header.php             ← Admin HTML header + sidebar
│   ├── footer.php             ← Admin footer
│   ├── dashboard.php          ← Admin overview + quick actions
│   ├── jobs.php               ← Approve/reject/manage all jobs
│   ├── users.php              ← Manage users (ban/unban/delete)
│   ├── categories.php         ← Add/edit/delete categories
│   └── applications.php       ← View all applications platform-wide
│
├── uploads/
│   ├── .htaccess              ← Block PHP execution in uploads
│   └── cvs/                   ← Uploaded CV files (PDF/DOCX)
│
└── assets/
    ├── css/
    │   └── style.css          ← Complete responsive stylesheet
    └── js/
        └── main.js            ← All JavaScript interactions
```

---

## 🗄️ ERD (Entity Relationship Overview)

```
users (id, name, email, password, role, status)
  │
  ├──< user_profiles (user_id → users.id)
  │     phone, city, bio, skills, experience, education, cv_file
  │
  ├──< companies (user_id → users.id)
  │     name, industry, size, city, description
  │
  ├──< jobs (employer_id → users.id, company_id → companies.id, category_id → categories.id)
  │     title, slug, description, type, location, salary, deadline, status
  │
  └──< applications (job_id → jobs.id, user_id → users.id)
  │     cover_letter, cv_file, status, employer_note
  │
  └──< saved_jobs (user_id → users.id, job_id → jobs.id)

categories (id, name, slug, icon)
```

---

## 🛡️ Security Features

- **PDO Prepared Statements** — All DB queries use `?` placeholders, zero SQL injection risk
- **password_hash() / password_verify()** — Industry-standard bcrypt password hashing
- **CSRF Tokens** — All forms include and verify tokens via `hash_equals()`
- **XSS Protection** — All output wrapped in `htmlspecialchars()` via `e()` helper
- **Session Hardening** — `session.cookie_httponly`, `session.use_only_cookies`, `session_regenerate_id()` on login
- **Secure File Upload** — MIME type validated with `finfo`, extension whitelist, random filenames, 5MB limit
- **Path Traversal Prevention** — `basename()` used before all file reads
- **Uploads Protected** — `.htaccess` blocks PHP execution inside `uploads/`
- **Role-based Access** — `requireRole()` enforced at top of every protected page
- **Directory Listing Disabled** — `Options -Indexes` in `.htaccess`

---

## ✨ Feature Summary

### Job Seeker
- Register & login
- Create/edit profile with bio, skills, experience, education
- Upload CV (PDF/DOCX, max 5MB)
- Browse and search jobs (keyword, category, location, type)
- View full job detail
- Apply with cover letter + optional new CV upload
- View all applications with status tracking
- Save/unsave jobs (AJAX, no page reload)

### Employer
- Register & login
- Create & manage company profile
- Post new job (pending admin approval)
- Edit & delete posted jobs
- View all applicants per job or across all jobs
- Review full application (cover letter, skills, experience)
- Download applicant CVs securely
- Update application status (pending → reviewed → shortlisted → interviewed → hired/rejected)
- Add employer notes per application

### Admin
- Dashboard with platform statistics
- Approve or reject job postings (with featured toggle)
- Manage all users (ban/unban, delete)
- Manage categories (add, edit, delete)
- View all applications platform-wide

---

## 🎨 Design System

- **Fonts:** Syne (display) + DM Sans (body)
- **Colors:** Navy `#1a56db` primary, Amber `#f59e0b` accent
- **Layout:** CSS Grid + Flexbox, fully responsive
- **Components:** Cards, badges, modals, flash messages, pagination

---

*Developed by **Hasibul Polok** — TalentBridge © 2025*
