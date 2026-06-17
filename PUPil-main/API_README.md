# PUPil Attendance System - REST API Documentation

## Overview

This is a modern REST API implementation for the PUPil Attendance System. The API provides JSON endpoints for authentication, student management, and attendance tracking.

## Directory Structure

```
PUPil-main/
├── config/
│   └── database.php          # Database connection configuration
├── api/
│   ├── config.php            # API configuration and helper functions
│   ├── middleware/
│   │   └── auth.php          # Authentication middleware
│   ├── auth/
│   │   ├── login.php         # User login endpoint
│   │   ├── register.php      # User registration endpoint
│   │   └── logout.php        # User logout endpoint
│   ├── students/
│   │   ├── index.php         # Get all students / Create student
│   │   ├── get.php           # Get single student details
│   │   ├── update.php        # Update student information
│   │   └── delete.php        # Delete student
│   └── attendance/
│       ├── index.php         # Get attendance / Log attendance
│       ├── subjects.php      # Get subjects for attendance
│       └── students.php      # Get students by subject
```

## Setup Instructions

### 1. Database Configuration

Edit `config/database.php` and update your SQL Server credentials:

```php
define('DB_HOST', 'localhost');      // Your SQL Server host
define('DB_NAME', 'attendance_system');  // Database name
define('DB_USER', 'sa');              // Your SQL Server username
define('DB_PASS', '');                // Your SQL Server password
```

### 2. Database Setup

Run the SQL script located at `Database/attendance_system.sql` to create the database schema and tables.

### 3. Web Server Configuration

Ensure your web server (Apache/Nginx/IIS) is configured to:
- Serve PHP files
- Allow POST/GET/PUT/DELETE methods
- Enable session support

### 4. Frontend Integration

The frontend files in `IM_Database-Fronted-main/IM_Database/` have been updated to connect to the API endpoints.

## API Endpoints

### Authentication Endpoints

#### Login
- **Endpoint:** `POST /api/auth/login.php`
- **Description:** Authenticate user and create session
- **Request Body:**
```json
{
  "username": "student1",
  "password": "password123"
}
```
- **Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "username": "student1",
    "role": "student",
    "full_name": "John Doe",
    "profile": {...}
  }
}
```

#### Register
- **Endpoint:** `POST /api/auth/register.php`
- **Description:** Register a new student account
- **Request Body:**
```json
{
  "username": "newstudent",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "course": "BS Information Technology",
  "year_level": 1,
  "middle_name": "",
  "suffix_name": "",
  "section": "A",
  "phone_number": ""
}
```
- **Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user_id": 5,
    "username": "newstudent",
    "account_code": "STU_20240615_1234"
  }
}
```

#### Logout
- **Endpoint:** `POST /api/auth/logout.php`
- **Description:** Destroy user session
- **Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

### Student Endpoints

#### Get All Students
- **Endpoint:** `GET /api/students/index.php`
- **Authentication:** Required
- **Response:**
```json
{
  "success": true,
  "message": "Students retrieved successfully",
  "data": [...]
}
```

#### Get Single Student
- **Endpoint:** `GET /api/students/get.php?id={student_id}`
- **Authentication:** Required
- **Response:**
```json
{
  "success": true,
  "message": "Student retrieved successfully",
  "data": {
    "profile": {...},
    "enrollments": [...],
    "attendance": [...]
  }
}
```

#### Create Student (Admin Only)
- **Endpoint:** `POST /api/students/index.php`
- **Authentication:** Admin role required
- **Request Body:** Same as registration
- **Response:** Same as registration

#### Update Student (Admin Only)
- **Endpoint:** `PUT /api/students/update.php?id={student_id}`
- **Authentication:** Admin role required
- **Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Smith",
  "course": "BS Computer Science",
  "year_level": 2,
  "username": "johnsmith",
  "password": "newpassword"
}
```

#### Delete Student (Admin Only)
- **Endpoint:** `DELETE /api/students/delete.php?id={student_id}`
- **Authentication:** Admin role required
- **Response:**
```json
{
  "success": true,
  "message": "Student deleted successfully"
}
```

### Attendance Endpoints

#### Get Attendance Records
- **Endpoint:** `GET /api/attendance/index.php?student_id={id}&subject_id={id}&date={YYYY-MM-DD}`
- **Authentication:** Required
- **Response:**
```json
{
  "success": true,
  "message": "Attendance records retrieved successfully",
  "data": [...]
}
```

#### Log Attendance (Instructor/Admin Only)
- **Endpoint:** `POST /api/attendance/index.php`
- **Authentication:** Instructor or Admin role required
- **Request Body:**
```json
{
  "student_id": 1,
  "subject_id": 1,
  "record_type": "Log-in",
  "status": "Present"
}
```
- **Response:**
```json
{
  "success": true,
  "message": "Attendance logged successfully",
  "data": {
    "id": 123
  }
}
```

#### Get Subjects for Attendance
- **Endpoint:** `GET /api/attendance/subjects.php`
- **Authentication:** Required
- **Response:**
```json
{
  "success": true,
  "message": "Subjects retrieved successfully",
  "data": [...]
}
```

#### Get Students by Subject
- **Endpoint:** `GET /api/attendance/students.php?subject_id={id}`
- **Authentication:** Required
- **Response:**
```json
{
  "success": true,
  "message": "Students retrieved successfully",
  "data": [...]
}
```

## Response Format

All API responses follow this structure:

```json
{
  "success": true/false,
  "message": "Description of the result",
  "data": {...}  // Optional, present on success
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `405` - Method Not Allowed
- `409` - Conflict (duplicate data)
- `500` - Internal Server Error

## Error Handling

The API includes comprehensive error handling:
- Database connection errors
- Validation errors
- Authentication failures
- Authorization failures
- Duplicate data detection

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control (RBAC)
- SQL injection prevention using PDO prepared statements
- CORS headers for cross-origin requests

## Testing the API

You can test the API using:
- cURL
- Postman
- Browser DevTools
- Any HTTP client library

### Example cURL Commands

**Login:**
```bash
curl -X POST http://localhost/PUPil-main/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"student1","password":"password123"}'
```

**Get Students:**
```bash
curl -X GET http://localhost/PUPil-main/api/students/index.php \
  -H "Cookie: PHPSESSID=your_session_id"
```

## Troubleshooting

### Database Connection Issues
- Verify SQL Server is running
- Check credentials in `config/database.php`
- Ensure SQL Server PHP extensions are installed (`sqlsrv` or `pdo_sqlsrv`)

### Session Issues
- Ensure PHP session directory is writable
- Check session configuration in php.ini

### CORS Issues
- The API includes CORS headers by default
- For production, specify exact origin instead of `*`

## Next Steps

1. Update database credentials in `config/database.php`
2. Run the database schema script
3. Test the authentication endpoints
4. Integrate with your frontend application
5. Add additional endpoints as needed (subjects, enrollment, etc.)

## Support

For issues or questions, refer to the existing PHP files in the `Queries/` directory for business logic reference.
