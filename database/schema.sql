CREATE DATABASE IF NOT EXISTS cerdas_cermat
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cerdas_cermat;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'operator',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    location VARCHAR(180) NULL,
    event_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rounds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    correct_score INT NOT NULL,
    wrong_score INT NOT NULL DEFAULT 0,
    answer_seconds INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_round_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_round_event (event_id, display_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    members TEXT NULL,
    access_code VARCHAR(32) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_team_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY uq_team_code_per_event (event_id, access_code),
    INDEX idx_team_event (event_id, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    round_id BIGINT UNSIGNED NOT NULL,
    current_question INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'ready',
    buzzer_team_id BIGINT UNSIGNED NULL,
    question_opened_at DATETIME(6) NULL,
    buzzed_at DATETIME(6) NULL,
    finished_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_round FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE RESTRICT,
    CONSTRAINT fk_match_buzzer_team FOREIGN KEY (buzzer_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_match_round_status (round_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS match_teams (
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    score INT NOT NULL DEFAULT 0,
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (match_id, team_id),
    CONSTRAINT fk_match_team_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_team_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS buzz_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    accepted TINYINT(1) NOT NULL DEFAULT 0,
    reason VARCHAR(120) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT fk_buzz_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_buzz_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    INDEX idx_buzz_match_time (match_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS score_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    operator_id BIGINT UNSIGNED NOT NULL,
    score_before INT NOT NULL,
    delta INT NOT NULL,
    score_after INT NOT NULL,
    reason VARCHAR(160) NOT NULL,
    reversed_at DATETIME NULL,
    reversed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_score_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_score_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    CONSTRAINT fk_score_operator FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_score_reversed_by FOREIGN KEY (reversed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_score_match_time (match_id, id)
) ENGINE=InnoDB;

INSERT INTO users (name, username, password_hash, role)
SELECT 'Operator Demo', 'admin', '$2y$12$fc6vT4ergl/1KC5ICds98.42BQxx4s.fjsDz9m3D7gQ6uw1p/HhLu', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');
