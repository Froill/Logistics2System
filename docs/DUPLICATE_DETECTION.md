# Receipt Duplicate Detection System

## Overview
The duplicate detection system uses OCR technology to identify potentially fraudulent or duplicate receipt submissions in the TCAO module. It analyzes receipt content, amounts, merchants, and dates to prevent multiple submissions of the same receipt.

## How It Works

### 1. OCR Processing
- Receipt images are processed using Tesseract OCR
- Extracts text, merchant names, amounts, and dates
- Calculates confidence score for extraction quality

### 2. Duplicate Detection Algorithm
The system uses multiple detection methods:

#### Text Similarity (40% weight)
- Compares raw OCR text using Levenshtein distance
- Identifies similar receipt layouts and content
- Threshold: 85% similarity

#### Amount Matching (30% weight)
- Compares total amounts within ₱1.00 tolerance
- Detects exact or near-exact amount duplicates
- Prevents resubmission of same amounts

#### Date Proximity (20% weight)
- Compares receipt dates within 7-day window
- Same date = full points, within 7 days = partial points
- Accounts for same-day duplicate submissions

#### Merchant Matching (10% weight)
- Compares merchant names using string similarity
- Identifies receipts from same establishments
- 80% similarity threshold for merchant names

### 3. Pattern Detection
- **Text Duplicates**: Similar OCR content found
- **Amount Duplicates**: Same amounts in recent receipts
- **Pattern Duplicates**: Same merchant + amount combinations

## Configuration

### Thresholds (in `includes/receipt_duplicate_detector.php`)
```php
private $similarityThreshold = 85;    // Text similarity percentage
private $timeWindowDays = 7;         // Days to look back for duplicates
private $amountThreshold = 0.01;      // Amount difference in pesos
```

## Database Schema

### New Tables

#### `receipt_duplicate_checks`
- Logs all duplicate detection attempts
- Stores similarity scores and detection results
- Used for audit and analysis

#### Updated `transport_costs`
- Added `ocr_data` column (JSON)
- Added `ocr_confidence` column (VARCHAR)

## User Experience

### When Duplicate is Detected
1. **Upload Blocked**: Receipt upload is prevented
2. **Error Message**: Clear warning about duplicate detection
3. **Detailed Feedback**: Shows which detection method triggered
4. **File Cleanup**: Uploaded file is automatically deleted

### Detection Details Display
- **Similar text content**: Shows percentage match
- **Same amount detected**: Indicates amount duplicates
- **Similar pattern**: Merchant + amount combination

## Security Features

### Fraud Prevention
- **Multi-factor detection**: Uses multiple algorithms
- **Time-based analysis**: Prevents timing-based fraud
- **Content analysis**: Detects manipulated receipts
- **Audit logging**: All checks are recorded

### Privacy Protection
- **User-scoped**: Only checks against user's own receipts
- **Time-limited**: Only looks back 7 days
- **Secure storage**: OCR data encrypted in database

## Installation

### 1. Database Setup
```sql
-- Run the provided SQL scripts
SOURCE database/add_ocr_columns.sql;
SOURCE database/create_duplicate_check_table.sql;
```

### 2. File Permissions
```bash
# Ensure directories are writable
chmod 755 temp/
chmod 755 uploads/
chmod 755 uploads/ocr_results/
```

### 3. Dependencies
- Tesseract OCR must be installed
- ImageMagick (optional, for preprocessing)
- PHP GD library for image processing

## Performance Considerations

### Processing Time
- **OCR Processing**: 5-30 seconds per receipt
- **Duplicate Check**: 1-3 seconds
- **Total Upload Time**: 6-33 seconds

### Optimization Tips
- **Image Quality**: Clear, well-lit receipts process faster
- **File Size**: Optimize images before upload
- **Server Resources**: Monitor CPU usage during peak times

## Monitoring and Analytics

### Duplicate Detection Metrics
- **False Positive Rate**: Monitor legitimate receipts flagged
- **Detection Accuracy**: Track successful fraud prevention
- **Processing Time**: Average time per receipt
- **User Feedback**: Collect user reports of issues

### Audit Reports
Access duplicate detection logs:
```sql
SELECT * FROM receipt_duplicate_checks 
WHERE is_duplicate = 1 
ORDER BY created_at DESC 
LIMIT 100;
```

## Troubleshooting

### Common Issues

#### High False Positives
- **Solution**: Adjust similarity threshold to 90%
- **Location**: `includes/receipt_duplicate_detector.php`

#### Slow Processing
- **Solution**: Optimize server resources or limit concurrent uploads
- **Check**: Tesseract performance and image sizes

#### OCR Not Working
- **Solution**: Verify Tesseract installation and PATH
- **Command**: `tesseract --version`

#### Permission Errors
- **Solution**: Check directory permissions
- **Command**: `ls -la temp/ uploads/`

### Debug Mode
Enable detailed logging:
```php
// In receipt_duplicate_detector.php
error_log("Duplicate check result: " . print_r($result, true));
```

## Advanced Configuration

### Custom Detection Rules
Add business-specific rules in `ReceiptDuplicateDetector` class:

```php
// Example: Block receipts over ₱10,000
if ($ocrData['total_amount'] > 10000) {
    return ['is_duplicate' => true, 'reason' => 'Amount exceeds limit'];
}
```

### Integration with External Systems
- **API Integration**: Connect to external fraud detection
- **Machine Learning**: Implement ML-based detection
- **Blockchain**: Immutable receipt verification

## Best Practices

### For Administrators
1. **Regular Monitoring**: Review duplicate detection logs
2. **Threshold Tuning**: Adjust based on false positive rates
3. **User Training**: Educate users on proper receipt submission
4. **Performance Monitoring**: Track system impact

### For Users
1. **Quality Images**: Submit clear, readable receipts
2. **Single Submission**: Avoid multiple uploads of same receipt
3. **Timely Submission**: Submit receipts promptly after trips
4. **Report Issues**: Notify admins of false positives

## Future Enhancements

### Planned Features
- **Machine Learning**: AI-powered duplicate detection
- **Image Hashing**: Perceptual hash comparison
- **Geolocation**: GPS-based receipt validation
- **Real-time Processing**: Queue-based OCR processing

### Scalability
- **Distributed Processing**: Multiple OCR servers
- **Caching**: Redis for frequent comparisons
- **Load Balancing**: Distribute processing load

## Support

For technical support:
1. Check error logs: `/var/log/apache2/error.log`
2. Verify prerequisites: Tesseract, ImageMagick, permissions
3. Test with known receipts: Use sample images
4. Contact system administrator for server issues

The duplicate detection system significantly reduces fraud while maintaining user experience through clear feedback and fast processing.
