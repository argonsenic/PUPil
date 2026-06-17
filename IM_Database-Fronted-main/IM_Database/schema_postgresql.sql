-- PostgreSQL Schema for PUP Attendance System
-- This file creates the database structure for PostgreSQL

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    role VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INTEGER NOT NULL,
    account_code VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Create student_profiles table
CREATE TABLE IF NOT EXISTS student_profiles (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix_name VARCHAR(20),
    course VARCHAR(100),
    year_level INTEGER,
    student_number VARCHAR(50),
    phone_number VARCHAR(20),
    section VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create instructor_profiles table
CREATE TABLE IF NOT EXISTS instructor_profiles (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix_name VARCHAR(20),
    department VARCHAR(100),
    employee_number VARCHAR(50),
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id SERIAL PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_name VARCHAR(200) NOT NULL,
    units DECIMAL(3,1) DEFAULT 3.0,
    schedule VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id SERIAL PRIMARY KEY,
    student_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'present',
    marked_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (marked_by) REFERENCES users(id)
);

-- Create qr_codes table for dynamic QR codes
CREATE TABLE IF NOT EXISTS qr_codes (
    id SERIAL PRIMARY KEY,
    subject_id INTEGER NOT NULL,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT true,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- Create attendance_records table for detailed tracking
CREATE TABLE IF NOT EXISTS attendance_records (
    id SERIAL PRIMARY KEY,
    qr_code_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    student_number VARCHAR(50),
    student_name VARCHAR(255),
    submission_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    status VARCHAR(20) DEFAULT 'present',
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);

-- Insert default roles
INSERT INTO roles (role) VALUES ('admin'), ('student'), ('instructor')
ON CONFLICT (role) DO NOTHING;

-- Insert default subjects
INSERT INTO subjects (subject_code, subject_name, units, schedule) VALUES 
('IM 101', 'Information Management', 3.0, 'MWF 8:00 AM - 10:00 AM'),
('QMMS 201', 'Quantitative Methods with Modeling and Simulation', 3.0, 'TTH 1:00 PM - 3:00 PM')
ON CONFLICT (subject_code) DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_username ON users(user_name);
CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_student_profiles_account_id ON student_profiles(account_id);
CREATE INDEX IF NOT EXISTS idx_instructor_profiles_account_id ON instructor_profiles(account_id);
CREATE INDEX IF NOT EXISTS idx_attendance_student_id ON attendance(student_id);
CREATE INDEX IF NOT EXISTS idx_attendance_subject_id ON attendance(subject_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date);
