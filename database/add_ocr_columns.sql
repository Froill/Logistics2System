-- Add OCR data column to transport_costs table
ALTER TABLE transport_costs ADD COLUMN ocr_data TEXT NULL COMMENT 'JSON data from OCR processing of receipt';

-- Add OCR confidence column for quick reference
ALTER TABLE transport_costs ADD COLUMN ocr_confidence VARCHAR(10) NULL COMMENT 'OCR confidence level (high/medium/low)';
