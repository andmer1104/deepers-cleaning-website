-- ===== DATABASE SETUP =====
-- Creates the database and table for storing contact form submissions

CREATE DATABASE IF NOT EXISTS deepers_cleaning_db;
USE deepers_cleaning_db;

-- ===== TABLE CREATION =====
-- Drop existing table if it exists (for fresh setup)
DROP TABLE IF EXISTS contact_requests;

-- Create table to store contact form submissions
CREATE TABLE contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);