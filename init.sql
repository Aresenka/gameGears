-- Create database and table
CREATE DATABASE IF NOT EXISTS game_analytics;
USE game_analytics;

CREATE TABLE IF NOT EXISTS game_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    event_time DATETIME NOT NULL,
    registration_date DATE NOT NULL,
    INDEX idx_player_time (player_id, event_time),
    INDEX idx_registration (registration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO game_events (player_id, event_time, registration_date) VALUES
-- Player 1 (registered 2024-01-01)
(1, '2024-01-01 10:00:00', '2024-01-01'), -- First session start
(1, '2024-01-01 10:05:00', '2024-01-01'),
(1, '2024-01-01 10:08:00', '2024-01-01'), -- First session end
(1, '2024-01-01 10:30:00', '2024-01-01'), -- New session (gap > 10 min)

-- Player 2 (registered 2024-01-01)
(2, '2024-01-01 11:00:00', '2024-01-01'), -- First session start
(2, '2024-01-01 11:07:00', '2024-01-01'),
(2, '2024-01-01 11:15:00', '2024-01-01'), -- First session end

-- Player 3 (registered 2024-01-02)
(3, '2024-01-02 09:00:00', '2024-01-02'), -- First session start
(3, '2024-01-02 09:03:00', '2024-01-02'),
(3, '2024-01-02 09:20:00', '2024-01-02'), -- New session

-- Player 4 (registered 2024-01-02)
(4, '2024-01-02 15:00:00', '2024-01-02'), -- First session start
(4, '2024-01-02 15:08:00', '2024-01-02'),
(4, '2024-01-02 15:09:00', '2024-01-02'); -- First session end
