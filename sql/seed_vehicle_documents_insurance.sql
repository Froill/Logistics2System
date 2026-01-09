-- Seed data for vehicle_documents and vehicle_insurance
-- Adjust vehicle_id values to match your fleet_vehicles table before importing if needed

-- Vehicle Documents (Registration)
INSERT INTO `vehicle_documents` (vehicle_id, doc_type, doc_name, file_path, expiry_date, uploaded_by, uploaded_at)
VALUES
(1, 'Registration', 'OR-CR-vehicle1.pdf', 'uploads/vehicles/1/orcr1.png', '2026-08-15', 1, NOW()),
(2, 'Registration', 'OR-CR-vehicle2.pdf', 'uploads/vehicles/2/orcr2.png', '2025-12-01', 1, NOW()),
(3, 'Registration', 'OR-CR-vehicle3.pdf', 'uploads/vehicles/3/orcr3.png', '2026-03-20', 1, NOW()),
(4, 'Registration', 'OR-CR-vehicle4.pdf', 'uploads/vehicles/4/orcr4.png', '2027-05-10', 1, NOW()),
(5, 'Registration', 'OR-CR-vehicle5.pdf', 'uploads/vehicles/5/orcr5.png', '2025-11-30', 1, NOW());
-- Vehicle Insurance
INSERT INTO `vehicle_insurance` (vehicle_id, insurer, policy_number, coverage_type, coverage_start, coverage_end, premium, document_path, created_at)
VALUES
(1, 'Charter Ping An Insurance Corporation', 'POL-CP-0001', 'Comprehensive', '2025-01-01', '2026-01-01', 12000.00, 'uploads/vehicles/1/insurance_policy_1.pdf', NOW()),
(2, 'Malayan Insurance Company', 'MI-2025-9876', 'Third-Party', '2024-12-10', '2025-12-10', 8000.00, 'uploads/vehicles/2/insurance_policy_2.pdf', NOW()),
(3, 'BPI/MS Insurance Company', 'BPI-3030-555', 'Comprehensive', '2025-04-01', '2026-04-01', 11000.00, 'uploads/vehicles/3/insurance_policy_3.pdf', NOW()),
(4, 'FPG Insurance Philippines', 'FPG-4400-772', 'Comprehensive', '2026-05-11', '2027-05-11', 13000.00, 'uploads/vehicles/4/insurance_policy_4.pdf', NOW()),
(5, 'AXA Philippines', 'AXA-5599-210', 'Third-Party', '2024-12-01', '2025-12-01', 7500.00, 'uploads/vehicles/5/insurance_policy_5.pdf', NOW());

-- Notes:
-- 1) If your `fleet_vehicles` table does not have vehicles with id 1 or 2, update the `vehicle_id` values accordingly.
-- 2) The file paths above are references; ensure the files exist under the given paths in `uploads/vehicles/{id}/` if you want the download links to work.
-- 3) To import: from your MySQL client or phpMyAdmin run this file against your Logistics2System database.
