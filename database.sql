CREATE DATABASE dept_employee_db;
USE dept_employee_db;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL UNIQUE,         
    password    VARCHAR(255) NOT NULL,                 
    full_name   VARCHAR(100) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DEPARTMENTS TABLE 
-- ============================================================
CREATE TABLE departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    dept_code   VARCHAR(20) NOT NULL UNIQUE,         
    dept_name   VARCHAR(100) NOT NULL,
    location    VARCHAR(100),
    budget      DECIMAL(15,2) DEFAULT 0.00,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- EMPLOYEES TABLE
-- ============================================================
CREATE TABLE employees (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    dept_id         INT NOT NULL,
    emp_number      VARCHAR(20) NOT NULL UNIQUE,     
    first_name      VARCHAR(50) NOT NULL,
    last_name       VARCHAR(50) NOT NULL,
    email           VARCHAR(100) NOT NULL,
    position        VARCHAR(100) NOT NULL,
    salary          DECIMAL(12,2) DEFAULT 0.00,
    hire_date       DATE NOT NULL,
    status          ENUM('Active','Inactive','On Leave') DEFAULT 'Active',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- ============================================================
-- ACTIVITY LOGS TABLE 
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    username        VARCHAR(50) NOT NULL,              -- denormalized for easy display
    action          ENUM('CREATE','READ','UPDATE','DELETE') NOT NULL,
    entity_type     ENUM('Department','Employee') NOT NULL,
    entity_id       INT,                               -- which record was affected
    entity_name     VARCHAR(200),                      -- human-readable name of record
    details         TEXT,                              -- full description of what changed
    performed_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (username, password, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Sample departments
INSERT INTO departments (dept_code, dept_name, location, budget) VALUES
('CSIT', 'Computer Science & IT', 'Building A, Floor 3', 5000000.00),
('HR',   'Human Resources',        'Building B, Floor 1', 2000000.00),
('FIN',  'Finance & Accounting',   'Building C, Floor 2', 3500000.00);

-- Sample employees
INSERT INTO employees (dept_id, emp_number, first_name, last_name, email, position, salary, hire_date) VALUES
(1, 'EMP-001', 'Juan',   'Dela Cruz', 'juan@company.com',   'Senior Developer',    65000.00, '2021-03-15'),
(1, 'EMP-002', 'Maria',  'Santos',    'maria@company.com',  'Junior Developer',    38000.00, '2022-07-01'),
(2, 'EMP-003', 'Pedro',  'Reyes',     'pedro@company.com',  'HR Manager',          55000.00, '2020-01-10'),
(3, 'EMP-004', 'Ana',    'Garcia',    'ana@company.com',    'Accountant',          45000.00, '2021-09-20');
