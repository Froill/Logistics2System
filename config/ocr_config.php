<?php

/**
 * OCR Configuration for Production Deployment
 * Configure your preferred OCR services here
 */

return [
    // Cloud OCR Services Configuration
    'ocr_providers' => [
        // OCR.space - FREE tier (25,000 requests/month)
        'tesseract' => [
            'enabled' => true,
            'api_key' => 'K85225395088957', // Your OCR.space API key
            'priority' => 1, // Try first
            'cost_per_1000' => 0, // Free tier
            'monthly_limit' => 25000
        ],
        
        // Google Cloud Vision - PAID service
        'google' => [
            'enabled' => false,
            'api_key' => '', // Get from Google Cloud Console
            'priority' => 2,
            'cost_per_1000' => 1.50, // $1.50 per 1000 images
            'monthly_limit' => null // No limit (pay as you go)
        ],
        
        // Azure Computer Vision - PAID service
        'azure' => [
            'enabled' => false,
            'api_key' => '', // Get from Azure Portal
            'region' => 'eastus', // Change to your region
            'priority' => 3,
            'cost_per_1000' => 1.00, // Approximate cost
            'monthly_limit' => null
        ],
        
        // AWS Textract - PAID service
        'aws' => [
            'enabled' => false,
            'access_key' => '', // AWS Access Key
            'secret_key' => '', // AWS Secret Key
            'region' => 'us-east-1',
            'priority' => 4,
            'cost_per_1000' => 1.50, // $1.50 per 1000 pages
            'monthly_limit' => 1000 // Free tier
        ]
    ],
    
    // Local Tesseract Configuration
    'local_tesseract' => [
        'enabled' => true, // Fallback option
        'path' => 'tesseract', // System PATH, or specify full path
        'languages' => 'eng+phi', // English + Filipino
        'preprocessing' => true // Use ImageMagick for better results
    ],
    
    // Processing Settings
    'processing' => [
        'timeout_seconds' => 30, // Max time for OCR processing
        'max_file_size_mb' => 10, // Maximum file size
        'supported_formats' => ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'pdf'],
        'confidence_threshold' => 'medium', // minimum confidence for auto-fill
        'fallback_to_local' => true // Use local Tesseract if cloud fails
    ],
    
    // Duplicate Detection Settings
    'duplicate_detection' => [
        'enabled' => true,
        'similarity_threshold' => 85, // Percentage
        'time_window_days' => 7,
        'amount_threshold' => 0.01, // ₱0.01 tolerance
        'check_user_scope' => true // Only check user's own receipts
    ],
    
    // Production Optimization
    'production' => [
        'cache_results' => true, // Cache OCR results
        'queue_processing' => false, // Use job queue for bulk processing
        'rate_limiting' => true, // Prevent abuse
        'audit_logging' => true, // Log all OCR attempts
        'error_notifications' => true // Email admin on errors
    ]
];
