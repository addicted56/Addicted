# Student Academic Record Management System

> A polished role-based academic portal for managing students, staff, courses, grades, attendance, and PDF transcripts for the Faculty of Computing, University of Delta, Agbor.

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Composer](https://img.shields.io/badge/Composer-Dependency_Manager-885630?style=for-the-badge&logo=composer&logoColor=white)](https://getcomposer.org/)

## Overview

This project is a web-based Student Academic Record Management System built with PHP and MySQL. It provides a clean and responsive interface for three major roles:

- Admin: manage students, staff, courses, and course assignments
- Staff: enter and update student grades for assigned courses
- Student: register courses, view dashboard summaries, and download transcripts

The database scripts are included in the repository under the `database/` folder for easy setup in XAMPP/phpMyAdmin.

## Highlights

- Role-based login for admin, staff, and students
- Beautiful dashboard UI with responsive cards, tables, and banner sections
- Student course registration for the current academic session
- Staff course assignment and grade entry workflow
- Automatic grade, GPA, and CGPA calculations
- PDF transcript and report generation
- MySQL schema and migration scripts included in the repo
- XAMPP-friendly setup for local development

## Live Demo

https://studentprofile.gt.tc/

## Tech Stack

- PHP
- MySQL
- Bootstrap 5
- Bootstrap Icons
- JavaScript
- Dompdf
- Composer

## Project Structure

```text
Student-Management-System/
|-- add_attendance.php
|-- add_marks.php
|-- add_student.php
|-- admin_login.php
|-- assign_courses.php
|-- change_password.php
|-- course_registration.php
|-- dashboard.php
|-- db.php
|-- download_pdf.php
|-- download_transcript.php
|-- edit_student.php
|-- footer.php
|-- forgot_password.php
|-- header.php
|-- login.php
|-- manage_courses.php
|-- manage_grades.php
|-- manage_staff.php
|-- staff_dashboard.php
|-- staff_login.php
|-- student_dashboard.php
|-- transcript.php
|-- styles.css
|-- database/
|   |-- migrate.sql
|   |-- unidel_schema.sql
```

## Screens and Modules

| Module | Purpose |
| --- | --- |
| Admin dashboard | Overview of records, staff, and course management |
| Staff dashboard | View assigned courses and enter grades |
| Student dashboard | Course registration, transcript access, report download |
| Course registration | Register courses for the current session |
| Transcript | View GPA, CGPA, and class of degree |
| PDF export | Download clean printable academic reports |

## Database Setup

The project includes two database scripts:

- `database/unidel_schema.sql` for a fresh MySQL database
- `database/migrate.sql` for upgrading an existing student management database

If you are using XAMPP:

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin.
3. Create a new database, for example `unidel_sarms`.
4. Import `database/unidel_schema.sql` for a fresh install, or `database/migrate.sql` if you are upgrading an existing schema.
5. Update `db.php` with your local database credentials if needed.

## Local Installation

1. Clone or copy the project into your XAMPP `htdocs` directory.
2. Run `composer install` inside the project folder.
3. Import the SQL file into MySQL using phpMyAdmin.
4. Open `db.php` and confirm the host, username, password, and database name.
5. Visit the project in your browser through `http://localhost/Student-Management-System/`.

## Default Access

The schema creates a default admin account:

- Username: `admin`
- Password: `password`

## Key Features in Detail

- Admin can add students, staff, departments, and courses.
- Admin can assign courses to staff for a given semester and session.
- Staff can enter CA and exam scores, and the system calculates totals and letter grades automatically.
- Students can register courses and review their academic history in a transcript-style interface.
- PDF output is supported for transcripts and student reports.

## Notes

- The UI has been styled with a modern Bootstrap-based theme in `styles.css`.
- Database tables and relationships were designed for role separation and academic record integrity.
- This project is intended for academic and demonstration use.

## Live Data and Demo

If you want to test the system locally with sample data, import the schema first and then add student, staff, and course records through the admin interface.

## License

This project is released for educational and demonstration purposes.
