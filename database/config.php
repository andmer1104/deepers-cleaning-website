<?php
/**
 * Database and Application Configuration
 * 
 * This file contains all configuration settings for the database connection
 * and external services like Cloudflare Turnstile.
 */

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'deepers_cleaning_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===== CLOUDFLARE TURNSTILE (CAPTCHA) =====
define('TURNSTILE_SECRET_KEY', 'your_real_turnstile_secret_key');

// ===== EMAIL CONFIGURATION =====
define('NOTIFICATION_EMAIL', 'deeperscleaning@gmail.com');
define('EMAIL_FROM', 'noreply@deeperscleaning.com');