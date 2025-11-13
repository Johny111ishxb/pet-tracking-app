-- PostgreSQL version of pawsitive_patrol database schema
-- Converted from MySQL for Render deployment

-- Table structure for table found_reports
CREATE TABLE IF NOT EXISTS found_reports (
  report_id SERIAL PRIMARY KEY,
  pet_id INTEGER DEFAULT NULL,
  finder_name VARCHAR(100) DEFAULT NULL,
  finder_contact VARCHAR(20) DEFAULT NULL,
  message TEXT DEFAULT NULL,
  attached_photo VARCHAR(255) DEFAULT NULL,
  reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table owners
CREATE TABLE IF NOT EXISTS owners (
  owner_id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table pets
CREATE TABLE IF NOT EXISTS pets (
  pet_id SERIAL PRIMARY KEY,
  owner_id INTEGER NOT NULL,
  name VARCHAR(100) NOT NULL,
  species VARCHAR(50) NOT NULL,
  breed VARCHAR(100) DEFAULT NULL,
  color VARCHAR(100) DEFAULT NULL,
  age INTEGER DEFAULT NULL,
  gender VARCHAR(10) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  qr_code VARCHAR(255) DEFAULT NULL,
  qr_token VARCHAR(100) NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES owners(owner_id) ON DELETE CASCADE
);

-- Table structure for table scans
CREATE TABLE IF NOT EXISTS scans (
  scan_id SERIAL PRIMARY KEY,
  pet_id INTEGER NOT NULL,
  scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  location VARCHAR(255) DEFAULT NULL,
  scanner_ip VARCHAR(45) DEFAULT NULL,
  FOREIGN KEY (pet_id) REFERENCES pets(pet_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_pets_owner_id ON pets(owner_id);
CREATE INDEX IF NOT EXISTS idx_pets_qr_token ON pets(qr_token);
CREATE INDEX IF NOT EXISTS idx_pets_status ON pets(status);
CREATE INDEX IF NOT EXISTS idx_scans_pet_id ON scans(pet_id);
CREATE INDEX IF NOT EXISTS idx_found_reports_pet_id ON found_reports(pet_id);

-- Insert a default admin user (password: admin123)
INSERT INTO owners (name, email, password, phone) 
VALUES ('Admin User', 'admin@pettracking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890')
ON CONFLICT (email) DO NOTHING;
