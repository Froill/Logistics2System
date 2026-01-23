# OCR Production Deployment Guide

## Overview
This guide explains how to deploy the OCR receipt processing system to a production domain, including both local Tesseract and cloud-based OCR services.

## Deployment Options

### Option 1: Local Tesseract (Recommended for Small/Medium Sites)
**Pros:**
- Free and open source
- No API costs
- Full control over data
- No external dependencies

**Cons:**
- Requires server access for installation
- Higher server resource usage
- Manual maintenance

**Best for:** VPS, dedicated servers, internal systems

### Option 2: Cloud OCR Services (Recommended for Shared Hosting)
**Pros:**
- No server installation required
- Better accuracy and performance
- Scalable and reliable
- Easy to set up

**Cons:**
- API costs for high volume
- Data sent to third parties
- Requires internet connection

**Best for:** Shared hosting, SaaS applications, high-volume sites

### Option 3: Hybrid Approach (Recommended)
- Use cloud OCR as primary service
- Local Tesseract as fallback
- Best balance of cost and reliability

## Step-by-Step Deployment

### Step 1: Choose Your OCR Strategy

#### For Shared Hosting (No Shell Access)
```php
// Edit config/ocr_config.php
return [
    'ocr_providers' => [
        'tesseract' => [
            'enabled' => true,
            'api_key' => 'your_ocr_space_key', // Get free key
            'priority' => 1
        ]
    ],
    'local_tesseract' => [
        'enabled' => false // Can't install on shared hosting
    ]
];
```

#### For VPS/Dedicated Server
```bash
# Install Tesseract
sudo apt update && sudo apt install tesseract-ocr tesseract-ocr-eng

# Install ImageMagick (optional but recommended)
sudo apt install imagemagick

# Verify installation
tesseract --version
```

```php
// Edit config/ocr_config.php
return [
    'ocr_providers' => [
        'tesseract' => [
            'enabled' => true,
            'api_key' => 'your_ocr_space_key',
            'priority' => 1
        ]
    ],
    'local_tesseract' => [
        'enabled' => true,
        'path' => '/usr/bin/tesseract'
    ]
];
```

### Step 2: Get Cloud OCR API Keys

#### OCR.space (FREE - Recommended Start)
1. Visit https://ocr.space/
2. Sign up for free account
3. Get API key from dashboard
4. Free tier: 25,000 requests/month

#### Google Cloud Vision (Paid)
1. Go to Google Cloud Console
2. Enable Vision API
3. Create service account key
4. Cost: ~$1.50 per 1000 images

#### Azure Computer Vision (Paid)
1. Create Azure account
2. Create Computer Vision resource
3. Get API key and endpoint
4. Cost: ~$1.00 per 1000 images

### Step 3: Configure Production Settings

```php
// config/ocr_config.php - Production optimized
return [
    'ocr_providers' => [
        'tesseract' => [
            'enabled' => true,
            'api_key' => 'your_production_api_key',
            'priority' => 1
        ]
    ],
    
    'processing' => [
        'timeout_seconds' => 15, // Faster timeout for production
        'max_file_size_mb' => 5, // Smaller limit for performance
        'confidence_threshold' => 'high', // Higher threshold for accuracy
        'fallback_to_local' => true
    ],
    
    'production' => [
        'cache_results' => true,
        'rate_limiting' => true,
        'audit_logging' => true
    ]
];
```

### Step 4: Set Up Database

```sql
-- Run these SQL commands on your production database
SOURCE database/add_ocr_columns.sql;
SOURCE database/create_duplicate_check_table.sql;
```

### Step 5: Configure File Permissions

```bash
# For Linux servers
chmod 755 uploads/
chmod 755 temp/
chmod 755 uploads/ocr_results/
chown www-data:www-data uploads/ temp/ uploads/ocr_results/

# For Windows servers
# Ensure IIS_IUSRS has write permissions to uploads/ and temp/
```

### Step 6: Test Production Deployment

1. **Upload Test Receipt**
   - Use a clear gas station receipt
   - Check if OCR processes correctly
   - Verify duplicate detection works

2. **Check Error Logs**
   ```bash
   tail -f /var/log/apache2/error.log
   # Look for OCR-related messages
   ```

3. **Monitor Performance**
   - Check processing time
   - Monitor server resources
   - Verify API usage limits

## Production Optimization

### Performance Tuning

#### 1. Image Optimization
```php
// Add to receipt upload processing
function optimizeImage($sourcePath, $targetPath) {
    $maxWidth = 1200;
    $maxHeight = 1200;
    $quality = 85;
    
    list($width, $height) = getimagesize($sourcePath);
    
    if ($width > $maxWidth || $height > $maxHeight) {
        // Resize image for faster processing
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Resize logic here...
    }
}
```

#### 2. Caching Strategy
```php
// Cache OCR results for identical images
function getOCRCacheKey($imagePath) {
    return 'ocr_' . md5_file($imagePath);
}
```

#### 3. Rate Limiting
```php
// Prevent abuse
function checkRateLimit($userId) {
    $key = "ocr_rate_" . $userId;
    $count = apcu_fetch($key) ?: 0;
    
    if ($count > 50) { // 50 requests per hour
        throw new Exception("Rate limit exceeded");
    }
    
    apcu_store($key, $count + 1, 3600);
}
```

### Security Considerations

#### 1. API Key Protection
```php
// Store API keys in environment variables
putenv('OCR_SPACE_API_KEY=your_actual_key');
putenv('GOOGLE_VISION_KEY=your_actual_key');

// In config:
'api_key' => getenv('OCR_SPACE_API_KEY') ?: ''
```

#### 2. Input Validation
```php
// Validate uploaded files
function validateReceiptUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type");
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File too large");
    }
}
```

#### 3. Data Privacy
```php
// Optional: Encrypt OCR data in database
function encryptOCRData($data) {
    $key = openssl_random_pseudo_bytes(32);
    $encrypted = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key);
    return base64_encode($encrypted . '::' . $key);
}
```

## Monitoring and Maintenance

### 1. Health Checks
```php
// Create health check endpoint
function ocrHealthCheck() {
    $providers = ['tesseract', 'google', 'azure'];
    $status = [];
    
    foreach ($providers as $provider) {
        try {
            $ocr = new CloudOCR($provider);
            $status[$provider] = $ocr->isAvailable() ? 'OK' : 'FAILED';
        } catch (Exception $e) {
            $status[$provider] = 'ERROR: ' . $e->getMessage();
        }
    }
    
    return $status;
}
```

### 2. Usage Analytics
```sql
-- Monitor OCR usage
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_requests,
    AVG(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) * 100 as duplicate_rate,
    provider
FROM receipt_duplicate_checks 
GROUP BY DATE(created_at), provider
ORDER BY date DESC;
```

### 3. Cost Monitoring
```php
// Track API costs
function calculateOCRCosts($provider, $requests) {
    $costs = [
        'tesseract' => 0, // Free
        'google' => 1.50, // $1.50 per 1000
        'azure' => 1.00,  // $1.00 per 1000
        'aws' => 1.50     // $1.50 per 1000
    ];
    
    return ($requests / 1000) * $costs[$provider];
}
```

## Troubleshooting Production Issues

### Common Problems

#### 1. OCR Not Working
**Symptoms:** No text extracted, confidence = 'low'
**Solutions:**
- Check API keys in config
- Verify internet connection (cloud services)
- Test with different image formats
- Check error logs

#### 2. Slow Performance
**Symptoms:** Uploads taking >30 seconds
**Solutions:**
- Reduce image size before upload
- Enable caching
- Use cloud OCR services
- Optimize server resources

#### 3. High False Positives
**Symptoms:** Legitimate receipts flagged as duplicates
**Solutions:**
- Adjust similarity threshold to 90%
- Review time window settings
- Check merchant name extraction

#### 4. API Rate Limits
**Symptoms:** "Rate limit exceeded" errors
**Solutions:**
- Implement rate limiting
- Upgrade API plan
- Use multiple API keys
- Add retry logic

### Emergency Procedures

#### 1. OCR Service Down
```php
// Emergency fallback to manual entry
if ($ocr_failed) {
    $_SESSION['tcao_warning'] = 'OCR temporarily unavailable. Please enter amounts manually.';
    // Continue with manual entry flow
}
```

#### 2. High Server Load
```php
// Disable OCR temporarily
define('OCR_EMERGENCY_DISABLE', true);

// In processing logic:
if (defined('OCR_EMERGENCY_DISABLE') && OCR_EMERGENCY_DISABLE) {
    // Skip OCR processing
}
```

## Scaling Considerations

### For High Volume Sites
1. **Queue Processing**: Use Redis/Beanstalk for background jobs
2. **Load Balancing**: Distribute OCR across multiple servers
3. **CDN**: Cache receipt images globally
4. **Database Sharding**: Split OCR data across servers

### Cost Optimization
1. **Batch Processing**: Process multiple receipts together
2. **Smart Caching**: Cache similar receipt patterns
3. **Provider Rotation**: Use cheapest available provider
4. **Compression**: Reduce image sizes before processing

## Support and Maintenance

### Regular Tasks
- Weekly: Check API usage and costs
- Monthly: Review duplicate detection accuracy
- Quarterly: Update OCR models and configurations
- Annually: Evaluate new OCR providers and technologies

### Monitoring Dashboard
Create a simple dashboard to track:
- Daily OCR requests
- Success/failure rates
- API costs by provider
- Duplicate detection accuracy
- Processing times

This deployment guide ensures your OCR system works reliably in production while maintaining security and performance.
