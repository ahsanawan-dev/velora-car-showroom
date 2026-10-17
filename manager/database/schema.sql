-- Import into a NEW, empty velora_manager database using phpMyAdmin.
-- Compatible with MySQL 8 and MariaDB 10.4+ (XAMPP).
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cars (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(30) NOT NULL,
    model_year SMALLINT UNSIGNED NOT NULL,
    price_usd DECIMAL(12,2) NULL,
    description TEXT NOT NULL,
    image_key VARCHAR(30) NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX cars_published (is_published, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id INT UNSIGNED NULL,
    car_name VARCHAR(120) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT enquiries_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE SET NULL,
    INDEX enquiries_status (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE request_limits (
    bucket CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    window_start BIGINT UNSIGNED NOT NULL,
    INDEX limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Draft entries: review all details and imagery before publishing.
-- Demonstration data, not real inventory or offers.
INSERT INTO cars (name, category, model_year, price_usd, description, image_key, is_published) VALUES
('Lamborghini Revuelto', 'hybrid', 2024, NULL, 'A demonstration vehicle entry for the VELORA showroom concept.', 'revuelto', 0),
('Ferrari SF90 Stradale', 'hybrid', 2024, NULL, 'A demonstration vehicle entry for the VELORA showroom concept.', 'sf90', 0),
('Porsche 911 GT3 RS', 'performance', 2024, NULL, 'A demonstration vehicle entry for the VELORA showroom concept.', 'gt3rs', 0),
('McLaren 750S Spider', 'performance', 2024, NULL, 'A demonstration vehicle entry for the VELORA showroom concept.', 'mclaren750s', 0),
('Mercedes-AMG GT 63', 'touring', 2024, NULL, 'A demonstration vehicle entry for the VELORA showroom concept.', 'amggt', 0);
