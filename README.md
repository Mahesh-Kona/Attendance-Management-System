#  Attendance Management System

A PHP-based web application for managing student attendance in a structured and role-based manner.  
This system provides dedicated dashboards for **Students, Faculty, HOD, Dean, and Department Office** to streamline attendance tracking and reporting.

---

## Features

### Faculty
- Login with secure credentials
- Mark and manage attendance for assigned subjects
- View attendance statistics of students

###  Students
- Login with secure student credentials
- View personal attendance records and statistics
- Access subject-wise attendance breakdown

### Department Office
- Manage faculty and student information
- Maintain faculty allotment history
- Register new subjects and assign faculty
- View all registered subjects, students, and faculty details
- Download attendance reports in Excel format

###  Head of Department (HOD)
- Access department-wide attendance statistics
- Monitor overall student performance


###  Dean
- View consolidated attendance statistics across departments
- Monitor overall academic performance

---

## 🛠️ Technologies Used
- **Frontend:** HTML, CSS, Bootstrap,Java Script 
- **Backend:** PHP (Core PHP)  
- **Database:** MySQL  
- **Libraries:** [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/) for Excel export  

---

## 📂 Project Structure
Attendance Management System/
│── all_faculty_info.php
│── all_students_info.php
│── all_subjects_info.php
│── attendance_dashboard_for_faculty.php
│── dean_dashboard.php
│── dept_office_dashboard.php
│── faculty_allotment_history.php
│── faculty_dashboard.php
│── hod_dashboard.php
│── student_dashboard.php
│── subject_register.php
│── manage_attendance.php
│── view_attendance_records.php
│── download_attendance_excel.php
│── register.php
│── forgot_password.php
│── index.php
│── db_connect.php
└── ...

`

---
User Roles

Student → Access own attendance

Faculty → Manage assigned classes

Dept Office → Manage faculty, students, subjects

HOD → Department-wide monitoring

Dean → University-level monitoring

Key Functionalities
Attendance marking and updating

Department-wise and faculty-wise statistics

Export attendance records to Excel

Faculty allotment history tracking

Secure authentication and role-based access


License
This project is for educational purposes only.
Special thanks to our guide " KK Singh Sir ".
Thank you!