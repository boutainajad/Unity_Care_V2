CREATE TABLE
    departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE
    patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        gender ENUM ('M', 'F'),
        date_of_birth DATE,
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

CREATE TABLE
    doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        specialization VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        department_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_doctor_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
    );

CREATE TABLE
    medications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE
    appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        time TIME NOT NULL,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        reason TEXT,
        status ENUM ('scheduled', 'done', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_appointment_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE,
        CONSTRAINT fk_appointment_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE
    );

CREATE TABLE
    prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        medication_id INT NOT NULL,
        dosage_instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_prescription_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE,
        CONSTRAINT fk_prescription_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE,
        CONSTRAINT fk_prescription_medication FOREIGN KEY (medication_id) REFERENCES medications (id) ON DELETE CASCADE
    );