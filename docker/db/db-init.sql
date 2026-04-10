DROP DATABASE IF EXISTS sfir_pz1;
CREATE DATABASE IF NOT EXISTS sfir_pz1;
USE sfir_pz1;

CREATE TABLE IF NOT EXISTS simulation_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(32) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    machines_count INT NOT NULL,
    repairmen_count INT NOT NULL,
    k_work FLOAT NOT NULL,
    k_repair FLOAT NOT NULL,
    theta_work FLOAT NOT NULL,
    theta_repair FLOAT NOT NULL,
    step_hours INT NOT NULL,
    revenue_rate FLOAT NOT NULL,
    status VARCHAR(100) DEFAULT 'active',
    UNIQUE KEY unique_session (session_token)
);

CREATE TABLE IF NOT EXISTS simulation_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    step_number INT NOT NULL,
    total_time INT NOT NULL,
    working_machines INT NOT NULL,
    repair_machines INT NOT NULL,
    waiting_machines INT NOT NULL,
    queue_length INT NOT NULL,
    total_repairs INT NOT NULL,
    total_downtime FLOAT NOT NULL,
    total_profit FLOAT NOT NULL,
    period_profit FLOAT NOT NULL,
    avg_remaining FLOAT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES simulation_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_step (session_id, step_number)
);

CREATE TABLE IF NOT EXISTS machines_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_id INT NOT NULL,
    machine_number INT NOT NULL,
    u_work FLOAT NOT NULL,
    total_lifetime FLOAT NOT NULL,
    remaining FLOAT NOT NULL,
    status VARCHAR(100) NOT NULL,
    repair_count INT NOT NULL,
    total_downtime FLOAT NOT NULL,
    repair_remaining FLOAT DEFAULT 0,
    repair_time FLOAT DEFAULT 0,
    failure_step INT DEFAULT NULL,
    FOREIGN KEY (step_id) REFERENCES simulation_steps(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS repair_queue_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_id INT NOT NULL,
    machine_number INT NOT NULL,
    repair_time FLOAT NOT NULL,
    repair_remaining FLOAT NOT NULL,
    added_step INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    queue_position INT NOT NULL,
    FOREIGN KEY (step_id) REFERENCES simulation_steps(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS machine_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_number INT NOT NULL,
    session_id INT NOT NULL,
    step_number INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES simulation_sessions(id) ON DELETE CASCADE,
    INDEX idx_machine_session (machine_number, session_id)
);