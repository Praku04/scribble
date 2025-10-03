-- Scribble Game Database Schema
-- MySQL Database

-- Players/Users Table
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    session_id VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    total_games INT DEFAULT 0,
    total_wins INT DEFAULT 0,
    total_points INT DEFAULT 0,
    total_correct_guesses INT DEFAULT 0,
    total_drawings INT DEFAULT 0,
    theme_preference VARCHAR(10) DEFAULT 'light',
    INDEX idx_username (username),
    INDEX idx_session (session_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game Rooms Table
CREATE TABLE IF NOT EXISTS game_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(10) UNIQUE NOT NULL,
    host_id INT,
    room_name VARCHAR(100),
    num_rounds INT DEFAULT 3,
    timer_duration INT DEFAULT 80,
    max_players INT DEFAULT 12,
    current_round INT DEFAULT 0,
    current_drawer_id INT NULL,
    current_word VARCHAR(100) NULL,
    round_start_time INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'waiting',
    is_public BOOLEAN DEFAULT true,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_code (room_code),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (host_id) REFERENCES players(id) ON DELETE SET NULL,
    FOREIGN KEY (current_drawer_id) REFERENCES players(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room Players (participants in a room)
CREATE TABLE IF NOT EXISTS room_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT NOT NULL,
    score INT DEFAULT 0,
    guessed BOOLEAN DEFAULT FALSE,
    is_connected BOOLEAN DEFAULT TRUE,
    join_order INT DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_room_player (room_id, player_id),
    INDEX idx_room (room_id),
    INDEX idx_player (player_id),
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drawing Data (stores drawing strokes)
CREATE TABLE IF NOT EXISTS drawing_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    round_number INT NOT NULL,
    stroke_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_round (room_id, round_number),
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT,
    username VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(20) DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_time (room_id, created_at),
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game History Table
CREATE TABLE IF NOT EXISTS game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    total_rounds INT,
    winner_id INT,
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (winner_id) REFERENCES players(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Round History Table
CREATE TABLE IF NOT EXISTS round_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    round_number INT,
    drawer_id INT,
    word VARCHAR(100),
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    INDEX idx_game (game_id),
    FOREIGN KEY (game_id) REFERENCES game_history(id) ON DELETE CASCADE,
    FOREIGN KEY (drawer_id) REFERENCES players(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player Game Participation
CREATE TABLE IF NOT EXISTS game_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    player_id INT,
    final_score INT DEFAULT 0,
    correct_guesses INT DEFAULT 0,
    drawings_made INT DEFAULT 0,
    placement INT,
    INDEX idx_game (game_id),
    INDEX idx_player (player_id),
    FOREIGN KEY (game_id) REFERENCES game_history(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Round Scores
CREATE TABLE IF NOT EXISTS round_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT,
    player_id INT,
    points_earned INT DEFAULT 0,
    guessed_correctly BOOLEAN DEFAULT FALSE,
    guess_time DECIMAL(5,2),
    FOREIGN KEY (round_id) REFERENCES round_history(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Achievements Table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    requirement_type VARCHAR(50),
    requirement_value INT,
    points_reward INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player Achievements
CREATE TABLE IF NOT EXISTS player_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT,
    achievement_id INT,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_player_achievement (player_id, achievement_id),
    INDEX idx_player (player_id),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Word Categories
CREATE TABLE IF NOT EXISTS word_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Words Table
CREATE TABLE IF NOT EXISTS words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    category_id INT,
    difficulty VARCHAR(20) DEFAULT 'medium',
    times_used INT DEFAULT 0,
    INDEX idx_category (category_id),
    FOREIGN KEY (category_id) REFERENCES word_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom Word Lists
CREATE TABLE IF NOT EXISTS custom_word_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    word VARCHAR(100) NOT NULL,
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game State Updates (for polling)
CREATE TABLE IF NOT EXISTS game_state_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    update_type VARCHAR(50) NOT NULL,
    update_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_time (room_id, created_at),
    FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
