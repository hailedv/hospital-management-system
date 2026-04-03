-- Hospital Management System Database
-- Staff-only system (patients registered by receptionists)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `hospital_management_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hospital_management_system`;

-- --------------------------------------------------------
-- STAFF USER TABLES (No patient login - they are registered by staff)
-- --------------------------------------------------------

-- Admins table
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Doctors table
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 500.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nurses table
CREATE TABLE `nurses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `shift` enum('morning','afternoon','night') DEFAULT 'morning',
  `license_number` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Receptionists table
CREATE TABLE `receptionists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `shift` enum('morning','afternoon','night') DEFAULT 'morning',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pharmacists table
CREATE TABLE `pharmacists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accountants table
CREATE TABLE `accountants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Patients table (now with login access)
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `registered_by` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  UNIQUE KEY `username` (`username`),
  KEY `registered_by` (`registered_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lab Technicians table
CREATE TABLE `lab_technicians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- FUNCTIONAL TABLES
-- --------------------------------------------------------

-- Lab Tests table
CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `test_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `ordered_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `sample_collected_at` timestamp NULL DEFAULT NULL,
  `result` text DEFAULT NULL,
  `result_file` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_id` (`test_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Appointments table
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `booked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `booked_by` (`booked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prescriptions table
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prescription_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `medications` text NOT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('pending','dispensed','cancelled') DEFAULT 'pending',
  `dispensed_by` int(11) DEFAULT NULL,
  `dispensed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescription_id` (`prescription_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `dispensed_by` (`dispensed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Patient Vitals table
CREATE TABLE `patient_vitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `oxygen_saturation` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `nurse_id` (`nurse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Medicines table
CREATE TABLE `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bills table
CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `medicine_cost` decimal(10,2) DEFAULT 0.00,
  `lab_charges` decimal(10,2) DEFAULT 0.00,
  `other_charges` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_id` (`bill_id`),
  KEY `patient_id` (`patient_id`),
  KEY `generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_type` (`user_type`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- TEST DATA INSERTION
-- --------------------------------------------------------

-- Insert test staff users (password: 123456 for all)
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`, `phone`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@hospital.com', '+1234567890', 'active');

INSERT INTO `doctors` (`username`, `password`, `full_name`, `email`, `phone`, `specialization`, `license_number`, `consultation_fee`, `status`) VALUES
('doctor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'john.smith@hospital.com', '+1234567891', 'Cardiology', 'DOC001', 500.00, 'active'),
('doctor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson', 'sarah.johnson@hospital.com', '+1234567892', 'Pediatrics', 'DOC002', 450.00, 'active');

INSERT INTO `nurses` (`username`, `password`, `full_name`, `email`, `phone`, `shift`, `license_number`, `status`) VALUES
('nurse1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Wilson', 'mary.wilson@hospital.com', '+1234567893', 'morning', 'NUR001', 'active'),
('nurse2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Brown', 'lisa.brown@hospital.com', '+1234567894', 'afternoon', 'NUR002', 'active');

INSERT INTO `receptionists` (`username`, `password`, `full_name`, `email`, `phone`, `shift`, `status`) VALUES
('receptionist1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Davis', 'emma.davis@hospital.com', '+1234567895', 'morning', 'active'),
('receptionist2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Miller', 'anna.miller@hospital.com', '+1234567896', 'afternoon', 'active');

INSERT INTO `pharmacists` (`username`, `password`, `full_name`, `email`, `phone`, `license_number`, `status`) VALUES
('pharmacist1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Garcia', 'michael.garcia@hospital.com', '+1234567897', 'PHA001', 'active'),
('pharmacist2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jennifer Martinez', 'jennifer.martinez@hospital.com', '+1234567898', 'PHA002', 'active');

INSERT INTO `accountants` (`username`, `password`, `full_name`, `email`, `phone`, `status`) VALUES
('accountant1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert Taylor', 'robert.taylor@hospital.com', '+1234567899', 'active'),
('accountant2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jessica Anderson', 'jessica.anderson@hospital.com', '+1234567800', 'active');

INSERT INTO `lab_technicians` (`username`, `password`, `full_name`, `email`, `phone`, `license_number`, `specialization`, `status`) VALUES
('labtech1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Alex Johnson', 'alex.johnson@hospital.com', '+1234567810', 'LAB001', 'Clinical Chemistry', 'active'),
('labtech2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Santos', 'maria.santos@hospital.com', '+1234567811', 'LAB002', 'Hematology', 'active');

-- Insert sample patients (now with login credentials)
INSERT INTO `patients` (`patient_id`, `username`, `password`, `full_name`, `email`, `phone`, `date_of_birth`, `gender`, `blood_group`, `address`, `emergency_contact`, `emergency_phone`, `registered_by`, `status`) VALUES
('PAT001', 'patient1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Wilson', 'james.wilson@email.com', '+1234567801', '1990-05-15', 'male', 'O+', '123 Main St, City', 'Jane Wilson', '+1234567802', 1, 'active'),
('PAT002', 'patient2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Rodriguez', 'maria.rodriguez@email.com', '+1234567803', '1985-08-22', 'female', 'A+', '456 Oak Ave, City', 'Carlos Rodriguez', '+1234567804', 1, 'active'),
('PAT003', 'patient3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Chen', 'david.chen@email.com', '+1234567805', '1992-12-10', 'male', 'B+', '789 Pine St, City', 'Lisa Chen', '+1234567806', 2, 'active');

-- Insert sample medicines
INSERT INTO `medicines` (`medicine_name`, `generic_name`, `manufacturer`, `category`, `unit_price`, `stock_quantity`, `min_stock_level`, `expiry_date`, `status`) VALUES
('Paracetamol 500mg', 'Acetaminophen', 'PharmaCorp', 'Analgesic', 2.50, 500, 50, '2025-12-31', 'active'),
('Amoxicillin 250mg', 'Amoxicillin', 'MediLab', 'Antibiotic', 5.00, 200, 20, '2025-10-15', 'active'),
('Ibuprofen 400mg', 'Ibuprofen', 'HealthPharma', 'Anti-inflammatory', 3.75, 300, 30, '2025-11-20', 'active'),
('Aspirin 100mg', 'Acetylsalicylic Acid', 'CardioMed', 'Antiplatelet', 1.25, 400, 40, '2025-09-30', 'active'),
('Metformin 500mg', 'Metformin HCl', 'DiabetesCare', 'Antidiabetic', 4.50, 250, 25, '2025-11-15', 'active');

-- Insert sample appointments
INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `booked_by`) VALUES
('APT001', 1, 1, '2026-01-20', '10:00:00', 'Regular checkup', 'confirmed', 1),
('APT002', 2, 2, '2026-01-21', '14:30:00', 'Pediatric consultation', 'pending', 1),
('APT003', 3, 1, '2026-01-22', '09:15:00', 'Follow-up visit', 'confirmed', 2);

-- Insert sample prescriptions
INSERT INTO `prescriptions` (`prescription_id`, `patient_id`, `doctor_id`, `medications`, `instructions`, `status`) VALUES
('PRE001', 1, 1, 'Paracetamol 500mg - 2 tablets twice daily for 3 days', 'Take after meals', 'pending'),
('PRE002', 2, 2, 'Amoxicillin 250mg - 1 tablet three times daily for 7 days', 'Complete the full course', 'dispensed'),
('PRE003', 3, 1, 'Ibuprofen 400mg - 1 tablet when needed for pain', 'Do not exceed 3 tablets per day', 'pending');

-- Insert sample bills
INSERT INTO `bills` (`bill_id`, `patient_id`, `consultation_fee`, `medicine_cost`, `lab_charges`, `other_charges`, `total_amount`, `discount`, `final_amount`, `status`, `generated_by`) VALUES
('BIL001', 1, 500.00, 25.00, 100.00, 0.00, 625.00, 25.00, 600.00, 'pending', 1),
('BIL002', 2, 450.00, 35.00, 75.00, 20.00, 580.00, 0.00, 580.00, 'paid', 1),
('BIL003', 3, 500.00, 15.00, 0.00, 10.00, 525.00, 0.00, 525.00, 'pending', 2);

-- Insert sample lab tests
INSERT INTO `lab_tests` (`test_id`, `patient_id`, `doctor_id`, `test_name`, `test_type`, `description`, `status`, `ordered_by`, `assigned_to`) VALUES
('LAB001', 1, 1, 'Complete Blood Count', 'Hematology', 'Routine blood work', 'pending', 1, 1),
('LAB002', 2, 2, 'Blood Sugar Test', 'Clinical Chemistry', 'Fasting glucose level', 'completed', 2, 2),
('LAB003', 3, 1, 'Lipid Profile', 'Clinical Chemistry', 'Cholesterol and triglycerides', 'in_progress', 1, 1);

COMMIT;

-- Note: All staff passwords are hashed version of "123456"
-- Patient login credentials: patient1/123456, patient2/123456, patient3/123456