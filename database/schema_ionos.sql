
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nom VARCHAR(120) NOT NULL,
 email VARCHAR(180) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','superviseur','agent') NOT NULL DEFAULT 'agent',
 actif TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teams (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nom VARCHAR(120) NOT NULL,
 secteur VARCHAR(120) DEFAULT NULL,
 statut ENUM('disponible','en_intervention','indisponible','sos') DEFAULT 'disponible',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE team_members (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 team_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_member(team_id,user_id),
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE interventions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference VARCHAR(30) NOT NULL UNIQUE,
 type VARCHAR(100) NOT NULL,
 description TEXT,
 priority ENUM('normal','urgent','tres_urgent') DEFAULT 'normal',
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 address VARCHAR(255) DEFAULT NULL,
 team_id INT UNSIGNED DEFAULT NULL,
 created_by INT UNSIGNED DEFAULT NULL,
 status ENUM('nouvelle','affectee','en_route','sur_place','terminee','annulee') DEFAULT 'nouvelle',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE SET NULL,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_intervention_status(status),
 INDEX idx_intervention_team(team_id)
);

CREATE TABLE sos_alerts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 team_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED DEFAULT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(10,2) DEFAULT NULL,
 message VARCHAR(500) DEFAULT NULL,
 status ENUM('active','prise_en_charge','resolue','fausse_alerte') DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 acknowledged_at DATETIME DEFAULT NULL,
 resolved_at DATETIME DEFAULT NULL,
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_sos_status(status)
);

CREATE TABLE team_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 team_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED DEFAULT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(10,2) DEFAULT NULL,
 recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_location_team_time(team_id,recorded_at)
);

CREATE TABLE notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED DEFAULT NULL,
 intervention_id BIGINT UNSIGNED DEFAULT NULL,
 sos_id BIGINT UNSIGNED DEFAULT NULL,
 type ENUM('intervention','urgent','sos','system') NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 is_read TINYINT(1) DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
 FOREIGN KEY(sos_id) REFERENCES sos_alerts(id) ON DELETE CASCADE,
 INDEX idx_notification_user_read(user_id,is_read)
);

INSERT INTO users(nom,email,password,role) VALUES
('Administrateur','admin@police-bf.local', '$2y$12$5OcMGTRLkRnUTAbzgFqe8uGykndih6PGzZ1ZMdFtl3WYlJAtdZazm', 'admin'),
('Superviseur','superviseur@police-bf.local', '$2y$12$5OcMGTRLkRnUTAbzgFqe8uGykndih6PGzZ1ZMdFtl3WYlJAtdZazm', 'superviseur'),
('Agent','agent@police-bf.local', '$2y$12$5OcMGTRLkRnUTAbzgFqe8uGykndih6PGzZ1ZMdFtl3WYlJAtdZazm', 'agent');

INSERT INTO teams(nom,secteur) VALUES
('Équipe Alpha','Ouagadougou Centre'),
('Équipe Bravo','Ouaga Nord'),
('Équipe Charlie','Bobo-Dioulasso');
