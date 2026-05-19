-- MotoRent Database Schema
-- Import this file via phpMyAdmin or run: mysql -u root -p < motorent.sql

CREATE DATABASE IF NOT EXISTS motorent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE motorent;

-- LOCATIONS
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100)
);

-- STAFF
CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin','staff') DEFAULT 'staff',
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MOTORCYCLES
CREATE TABLE motorcycles (
    motorcycle_id INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    brand VARCHAR(80) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT,
    type ENUM('Scooter','Underbone','Adventure','Sport','Naked') DEFAULT 'Scooter',
    status ENUM('available','rented','maintenance','damage_review') DEFAULT 'available',
    daily_rate DECIMAL(10,2) NOT NULL,
    deposit_amount DECIMAL(10,2) DEFAULT 1500.00,
    location_id INT,
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- CUSTOMERS
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(30),
    address TEXT,
    date_of_birth DATE,
    license_number VARCHAR(50),
    doc_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- RESERVATIONS
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    motorcycle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('confirmed','active','returned','cancelled') DEFAULT 'confirmed',
    total_amount DECIMAL(10,2),
    deposit_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(motorcycle_id)
);

-- PAYMENTS
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Cash','GCash','Card','Maya') DEFAULT 'Cash',
    status ENUM('pending','paid','refunded') DEFAULT 'pending',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- RENTALS
CREATE TABLE rentals (
    rental_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    staff_id INT,
    checkout_time DATETIME,
    return_time DATETIME,
    condition_out TEXT,
    condition_in TEXT,
    extra_charges DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
);

-- DAMAGE REPORTS
CREATE TABLE damage_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    description TEXT,
    repair_cost DECIMAL(10,2) DEFAULT 0.00,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
);

-- MAINTENANCE
CREATE TABLE maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    motorcycle_id INT NOT NULL,
    type VARCHAR(150),
    scheduled_date DATE,
    completed_date DATE,
    status ENUM('scheduled','in_progress','completed') DEFAULT 'scheduled',
    notes TEXT,
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(motorcycle_id)
);

-- REVIEWS
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

-- =====================
-- SAMPLE DATA
-- =====================

INSERT INTO locations (name, address, city) VALUES
('Branch A', 'Lacson Street', 'Bacolod City'),
('Branch B', 'Araneta Street', 'Bacolod City'),
('Branch C', 'Burgos Street', 'Bacolod City');

-- Password: admin123 (bcrypt)
INSERT INTO staff (name, role, email, password) VALUES
('Admin User', 'admin', 'admin@motorent.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Carlos Reyes', 'staff', 'carlos@motorent.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO motorcycles (plate_number, brand, model, year, type, status, daily_rate, deposit_amount, location_id) VALUES
('MCB-001', 'Honda', 'Click 125i', 2023, 'Scooter', 'available', 550.00, 1500.00, 1),
('MCB-002', 'Yamaha', 'NMAX 155', 2023, 'Scooter', 'available', 750.00, 1500.00, 1),
('MCB-003', 'Yamaha', 'NMAX 155', 2022, 'Scooter', 'damage_review', 750.00, 1500.00, 1),
('MCB-004', 'Suzuki', 'Skydrive 125', 2022, 'Scooter', 'rented', 600.00, 1500.00, 2),
('MCB-005', 'Honda', 'BeAT 125', 2023, 'Underbone', 'rented', 480.00, 1000.00, 2),
('MCB-006', 'Honda', 'ADV 160', 2023, 'Adventure', 'available', 950.00, 2000.00, 3),
('MCB-007', 'Kawasaki', 'Rouser 135', 2022, 'Sport', 'available', 700.00, 1500.00, 3),
('MCB-008', 'Honda', 'ADV 160', 2022, 'Adventure', 'maintenance', 950.00, 2000.00, 1);

INSERT INTO customers (first_name, last_name, email, phone, license_number, doc_status) VALUES
('Maria', 'Santos', 'maria.santos@email.com', '+63 917 555 0142', 'D20-12-034571', 'verified'),
('Juan', 'Dela Cruz', 'juan.dc@email.com', '+63 918 555 0199', 'D21-08-012345', 'verified'),
('Ana', 'Reyes', 'ana.reyes@email.com', '+63 919 555 0177', 'D19-03-098765', 'verified'),
('Pedro', 'Lim', 'p.lim@email.com', '+63 916 555 0188', 'D26-04-000111', 'pending'),
('Rosa', 'Fernandez', 'rosaf@email.com', '+63 915 555 0133', 'D22-07-055432', 'verified');

INSERT INTO reservations (customer_id, motorcycle_id, start_date, end_date, status, total_amount, deposit_amount) VALUES
(1, 2, '2026-04-17', '2026-04-20', 'confirmed', 3750.00, 1500.00),
(2, 5, '2026-04-15', '2026-04-17', 'active', 1440.00, 1000.00),
(3, 4, '2026-04-14', '2026-04-16', 'returned', 2100.00, 1500.00),
(4, 1, '2026-04-13', '2026-04-15', 'returned', 1650.00, 1500.00),
(5, 6, '2026-04-12', '2026-04-14', 'returned', 2850.00, 2000.00);

INSERT INTO payments (reservation_id, amount, method, status) VALUES
(1, 3750.00, 'GCash', 'paid'),
(2, 1440.00, 'Cash', 'pending'),
(3, 2100.00, 'Card', 'paid'),
(4, 1650.00, 'GCash', 'paid'),
(5, 2850.00, 'Card', 'paid');

INSERT INTO maintenance (motorcycle_id, type, scheduled_date, status, notes) VALUES
(8, 'Oil change + brake check', '2026-04-16', 'in_progress', 'Est. done by EOD'),
(3, 'Damage repair', '2026-04-17', 'scheduled', 'Left fairing dent'),
(1, 'General PMS', '2026-04-22', 'scheduled', '5,000km service'),
(6, 'Tire replacement', '2026-04-25', 'scheduled', 'Rear tire worn'),
(2, 'Oil change', '2026-03-28', 'completed', 'Done and cleared');
