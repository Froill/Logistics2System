# OCR Receipt Processing Installation Guide

## Overview
This guide explains how to install and configure OCR (Optical Character Recognition) functionality for automatic receipt data extraction in the TCAO (Transport Cost Analysis and Optimization) module.

## Prerequisites
- PHP 7.4 or higher
- Access to server command line
- ImageMagick (optional, for better preprocessing)

## Step 1: Install Tesseract OCR

### Windows
1. Download Tesseract installer from: https://github.com/UB-Mannheim/tesseract/wiki
2. Run the installer and note the installation path (usually `C:\Program Files\Tesseract-OCR`)
3. Add Tesseract to your system PATH:
   - Go to System Properties → Environment Variables
   - Add `C:\Program Files\Tesseract-OCR` to PATH
4. Restart your web server

### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install tesseract-ocr
sudo apt install tesseract-ocr-eng  # English language
sudo apt install tesseract-ocr-fil  # Filipino language (optional)
```

### Linux (CentOS/RHEL)
```bash
sudo yum install epel-release
sudo yum install tesseract
sudo yum install tesseract-langpack-eng
```

### macOS
```bash
brew install tesseract
brew install tesseract-lang
```

## Step 2: Install ImageMagick (Optional but Recommended)

### Windows
1. Download from: https://imagemagick.org/script/download.php#windows
2. Run the installer and ensure "Add application directory to your system path" is checked

### Linux
```bash
# Ubuntu/Debian
sudo apt install imagemagick

# CentOS/RHEL
sudo yum install ImageMagick
```

### macOS
```bash
brew install imagemagick
```

## Step 3: Update Database

Run the SQL script to add OCR columns to the transport_costs table:

```sql
-- Run this in your MySQL database
ALTER TABLE transport_costs ADD COLUMN ocr_data TEXT NULL COMMENT 'JSON data from OCR processing of receipt';
ALTER TABLE transport_costs ADD COLUMN ocr_confidence VARCHAR(10) NULL COMMENT 'OCR confidence level (high/medium/low)';
```

## Step 4: Configure Tesseract Path (if needed)

If Tesseract is not in your system PATH, update the path in `includes/receipt_ocr.php`:

```php
private $tesseractPath = 'C:\Program Files\Tesseract-OCR\tesseract.exe'; // Windows
// or
private $tesseractPath = '/usr/bin/tesseract'; // Linux
```

## Step 5: Set Directory Permissions

Ensure the following directories are writable by the web server:

```bash
# Linux/macOS
chmod 755 temp/
chmod 755 uploads/
chmod 755 uploads/ocr_results/
chown www-data:www-data temp/ uploads/ uploads/ocr_results/  # Adjust user/group as needed
```

## Step 6: Test OCR Functionality

1. Upload a receipt image through the TCAO module
2. Check if OCR data is extracted and displayed
3. Verify the OCR confidence indicator appears in the table

## Step 7: Troubleshooting

### OCR Not Working
1. Check if Tesseract is accessible: Run `tesseract --version` in command line
2. Verify PHP can execute shell commands: Check `exec()` is not disabled in php.ini
3. Check error logs: `tail -f /var/log/apache2/error.log` or your web server log

### Poor OCR Accuracy
1. Ensure ImageMagick is installed for image preprocessing
2. Use high-quality receipt images (good lighting, clear text)
3. Supported formats: JPG, PNG, BMP, TIFF, PDF

### Permission Issues
1. Check directory permissions for temp/ and uploads/
2. Ensure web server user has write access
3. Check safe_mode and open_basedir restrictions in php.ini

## Step 8: Supported Receipt Types

The OCR system is optimized for:
- Gas station receipts (fuel purchases)
- Toll receipts
- General expense receipts
- Philippine receipts (PHP currency, local merchant names)

## Step 9: Performance Considerations

- OCR processing may take 5-30 seconds per receipt
- Large images are automatically preprocessed for better performance
- OCR results are cached in the database for future reference
- Consider implementing queue processing for bulk uploads

## Step 10: Security Notes

- All uploaded files are scanned and validated
- Temporary files are automatically cleaned up
- OCR processing runs in isolated environment
- Raw text is stored securely in database

## Support

For issues with OCR functionality:
1. Check server error logs first
2. Verify Tesseract installation
3. Test with simple receipt images
4. Ensure all prerequisites are met

The OCR system will gracefully degrade if Tesseract is not available - receipts will still upload but won't be automatically processed.
