<?php

/**
 * Security Configuration Settings
 * 
 * This file contains all security-related configuration parameters.
 * Customize these settings based on your security requirements.
 */

// ============================================
// ACCOUNT LOCKOUT SETTINGS
// ============================================

// Maximum number of failed login attempts before lockout
// Recommended: 3-5 for high security, 5-10 for balanced
define('SECURITY_MAX_FAILED_ATTEMPTS', 3);

// Duration of account lockout in minutes
// Recommended: 15-30 minutes for user-friendly experience
define('SECURITY_LOCKOUT_DURATION_MINUTES', 60);

// ============================================
// IP RATE LIMITING SETTINGS
// ============================================

// Maximum login attempts per IP in the rate limit window
// Recommended: 20-30 for balanced security
define('SECURITY_IP_RATE_LIMIT_ATTEMPTS', 20);

// Time window for IP rate limiting in minutes
// Recommended: 60 minutes (1 hour)
define('SECURITY_IP_RATE_LIMIT_WINDOW_MINUTES', 60);

// Block IP when rate limit exceeded
define('SECURITY_BLOCK_IP_ON_LIMIT', true);

// IP block duration in minutes (if SECURITY_BLOCK_IP_ON_LIMIT is true)
define('SECURITY_IP_BLOCK_DURATION_MINUTES', 15);

// ============================================
// LOGIN ATTEMPT TRACKING
// ============================================

// Keep failed login attempt history for X days
define('SECURITY_FAILED_ATTEMPT_RETENTION_DAYS', 30);

// Keep IP rate limit history for X days
define('SECURITY_IP_LIMIT_RETENTION_DAYS', 7);

// ============================================
// HELPER FUNCTIONS
// ============================================
