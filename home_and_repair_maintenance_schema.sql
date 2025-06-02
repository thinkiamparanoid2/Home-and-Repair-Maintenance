CREATE DATABASE DB;

USE DB;

-- Customer Table
CREATE TABLE Customer (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    isadmin BOOLEAN NOT NULL,
    address VARCHAR(255) NOT NULL
);

-- Technician Table
CREATE TABLE Technician (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    technician_type VARCHAR(50) NOT NULL
);

-- Appointment Table
CREATE TABLE Appointment (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Customer_ID INT,
    Technician_ID INT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'price_quoted', 'counter_offered', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    technician_price DECIMAL(10,2) DEFAULT NULL,
    customer_price DECIMAL(10,2) DEFAULT NULL,
    final_price DECIMAL(10,2) DEFAULT NULL,
    customer_counter_count INT DEFAULT 0,
    technician_counter_count INT DEFAULT 0,
    INDEX(Technician_ID, date, time, status),
    FOREIGN KEY (Customer_ID) REFERENCES Customer(ID) ON DELETE CASCADE,
    FOREIGN KEY (Technician_ID) REFERENCES Technician(ID) ON DELETE CASCADE
);

-- Review Table
CREATE TABLE Review (
    Customer_ID INT,
    Technician_ID INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Customer_ID, Technician_ID),
    FOREIGN KEY (Customer_ID) REFERENCES Customer(ID) ON DELETE CASCADE,
    FOREIGN KEY (Technician_ID) REFERENCES Technician(ID) ON DELETE CASCADE
);

-- Chat Table
CREATE TABLE Chat (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Appointment_ID INT NOT NULL,
    sender_type ENUM('customer', 'technician') NOT NULL,
    sender_ID INT NOT NULL,
    message TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (Appointment_ID) REFERENCES Appointment(ID) ON DELETE CASCADE
);
