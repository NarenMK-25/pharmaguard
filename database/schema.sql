-- PharmaGuard schema + demo data (MySQL 8)
CREATE DATABASE IF NOT EXISTS pharmaguard CHARACTER SET utf8mb4;
USE pharmaguard;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(40) NOT NULL UNIQUE,
  full_name VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ADMIN','PHARMACIST') NOT NULL DEFAULT 'PHARMACIST'
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  supplier_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  phone VARCHAR(20)
) ENGINE=InnoDB;

CREATE TABLE medicines (
  medicine_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  generic_name VARCHAR(80),
  category VARCHAR(40),
  unit VARCHAR(15) NOT NULL DEFAULT 'tablet',
  reorder_level INT NOT NULL DEFAULT 50,
  supplier_id INT,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
  INDEX idx_med_name (name)
) ENGINE=InnoDB;

CREATE TABLE batches (
  batch_id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_id INT NOT NULL,
  batch_no VARCHAR(30) NOT NULL,
  expiry_date DATE NOT NULL,
  quantity INT NOT NULL CHECK (quantity >= 0),
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  received_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_med_batch (medicine_id, batch_no),
  FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE CASCADE,
  INDEX idx_expiry (expiry_date)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  move_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT,
  medicine_id INT NOT NULL,
  move_type ENUM('RECEIVE','DISPENSE') NOT NULL,
  qty INT NOT NULL,
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_move_med_time (medicine_id, move_type, created_at)
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
  rx_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_name VARCHAR(80) NOT NULL,
  doctor_name VARCHAR(80) NOT NULL,
  file_name VARCHAR(80) NOT NULL,
  file_mime VARCHAR(30) NOT NULL,
  status ENUM('PENDING','DISPENSED') NOT NULL DEFAULT 'PENDING',
  uploaded_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo data
INSERT INTO suppliers (name, phone) VALUES
 ('MediSupply Coimbatore','0422-2345678'),('Sri Ganesh Pharma Distributors','0422-2456789');

INSERT INTO medicines (name, generic_name, category, unit, reorder_level, supplier_id) VALUES
 ('Paracetamol 500mg','Paracetamol','Analgesic','tablet',200,1),
 ('Amoxicillin 500mg','Amoxicillin','Antibiotic','capsule',100,1),
 ('Metformin 500mg','Metformin','Antidiabetic','tablet',150,2),
 ('Cetirizine 10mg','Cetirizine','Antihistamine','tablet',80,2),
 ('ORS Sachet','Oral rehydration salts','Electrolyte','sachet',60,1),
 ('Insulin Glargine Pen','Insulin glargine','Antidiabetic','pen',20,2);

INSERT INTO batches (medicine_id, batch_no, expiry_date, quantity, cost_price) VALUES
 (1,'PCM-A1',DATE_ADD(CURDATE(), INTERVAL 12 DAY),120,1.20),
 (1,'PCM-B2',DATE_ADD(CURDATE(), INTERVAL 300 DAY),400,1.25),
 (2,'AMX-A1',DATE_ADD(CURDATE(), INTERVAL 25 DAY),60,6.50),
 (2,'AMX-B1',DATE_SUB(CURDATE(), INTERVAL 5 DAY),30,6.40),
 (3,'MET-A1',DATE_ADD(CURDATE(), INTERVAL 180 DAY),500,1.80),
 (4,'CET-A1',DATE_ADD(CURDATE(), INTERVAL 7 DAY),50,0.90),
 (4,'CET-B1',DATE_ADD(CURDATE(), INTERVAL 200 DAY),20,0.95),
 (5,'ORS-A1',DATE_ADD(CURDATE(), INTERVAL 90 DAY),150,8.00),
 (6,'INS-A1',DATE_ADD(CURDATE(), INTERVAL 40 DAY),8,420.00);

-- Past dispensing history (drives the reorder suggestion)
INSERT INTO stock_movements (batch_id, medicine_id, move_type, qty, created_at) VALUES
 (1,1,'DISPENSE',150,NOW() - INTERVAL 20 DAY),(2,1,'DISPENSE',200,NOW() - INTERVAL 8 DAY),
 (3,2,'DISPENSE',90,NOW() - INTERVAL 15 DAY),(6,4,'DISPENSE',70,NOW() - INTERVAL 10 DAY),
 (7,4,'DISPENSE',40,NOW() - INTERVAL 3 DAY),(9,6,'DISPENSE',12,NOW() - INTERVAL 6 DAY);
-- Users are created by web/install.php (so passwords are hashed by PHP).
