-- ============================================================
-- Online Salon Appointment Booking System - MySQL Schema
-- ============================================================
-- Run: mysql -u root -p < database/schema.sql
-- ============================================================

DROP DATABASE IF EXISTS salon_db;
CREATE DATABASE salon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE salon_db;

-- ----------------------------
-- users
-- ----------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    phone       VARCHAR(30),
    password    VARCHAR(255) NOT NULL,
    role        ENUM('customer','salon_admin','designer') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ----------------------------
-- salons
-- ----------------------------
CREATE TABLE salons (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    owner_id      INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    description   TEXT,
    address       VARCHAR(255),
    phone         VARCHAR(30),
    opening_time  TIME NOT NULL DEFAULT '09:00:00',
    closing_time  TIME NOT NULL DEFAULT '19:00:00',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB;

-- ----------------------------
-- designers
-- ----------------------------
CREATE TABLE designers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    salon_id      INT NOT NULL,
    user_id       INT NOT NULL,
    specialization VARCHAR(150),
    description   TEXT,
    profile_image VARCHAR(255),
    status        ENUM('active','inactive') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (salon_id) REFERENCES salons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    INDEX idx_salon (salon_id),
    INDEX idx_user  (user_id)
) ENGINE=InnoDB;

-- ----------------------------
-- services
-- ----------------------------
CREATE TABLE services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    salon_id    INT NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration    INT NOT NULL DEFAULT 30,  -- minutes
    status      ENUM('active','inactive') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (salon_id) REFERENCES salons(id) ON DELETE CASCADE,
    INDEX idx_salon (salon_id)
) ENGINE=InnoDB;

-- ----------------------------
-- designer_services (many-to-many)
-- ----------------------------
CREATE TABLE designer_services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    designer_id INT NOT NULL,
    service_id  INT NOT NULL,
    FOREIGN KEY (designer_id) REFERENCES designers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id)  REFERENCES services(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_designer_service (designer_id, service_id)
) ENGINE=InnoDB;

-- ----------------------------
-- designer_availability  (weekly recurring)
-- ----------------------------
CREATE TABLE designer_availability (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    designer_id  INT NOT NULL,
    day_of_week  TINYINT NOT NULL,           -- 0=Sunday ... 6=Saturday
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    FOREIGN KEY (designer_id) REFERENCES designers(id) ON DELETE CASCADE,
    INDEX idx_designer_day (designer_id, day_of_week)
) ENGINE=InnoDB;

-- ----------------------------
-- appointments
-- ----------------------------
CREATE TABLE appointments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    salon_id        INT NOT NULL,
    designer_id     INT NOT NULL,
    service_id      INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    normal_price    DECIMAL(10,2) NOT NULL,
    appointment_type ENUM('normal','urgent') DEFAULT 'normal',
    status          ENUM(
                        'pending',
                        'accepted',
                        'rejected',
                        'cancelled',
                        'completed',
                        'urgent_pending',
                        'urgent_accepted',
                        'urgent_rejected'
                    ) DEFAULT 'pending',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (salon_id)    REFERENCES salons(id),
    FOREIGN KEY (designer_id) REFERENCES designers(id),
    FOREIGN KEY (service_id)  REFERENCES services(id),
    INDEX idx_customer (customer_id),
    INDEX idx_designer_date (designer_id, appointment_date),
    INDEX idx_salon_date    (salon_id,    appointment_date)
) ENGINE=InnoDB;

-- ----------------------------
-- urgent_requests
-- ----------------------------
CREATE TABLE urgent_requests (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id        INT NOT NULL,
    customer_id           INT NOT NULL,
    designer_id           INT NOT NULL,
    original_price        DECIMAL(10,2) NOT NULL,
    customer_extra_offer  DECIMAL(10,2) DEFAULT 0.00,
    salon_extra_offer     DECIMAL(10,2) DEFAULT 0.00,
    final_price           DECIMAL(10,2) DEFAULT 0.00,
    status                ENUM(
                              'pending',
                              'customer_proposed',
                              'salon_countered',
                              'accepted',
                              'rejected'
                          ) DEFAULT 'pending',
    message               TEXT,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id)    REFERENCES users(id),
    FOREIGN KEY (designer_id)    REFERENCES designers(id),
    INDEX idx_appt (appointment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;
