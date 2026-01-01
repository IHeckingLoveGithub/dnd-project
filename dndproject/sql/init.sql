-- Database Initialization Script

CREATE DATABASE IF NOT EXISTS dnd_project;
USE dnd_project;

-- 1. Tables

CREATE TABLE IF NOT EXISTS users (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS campaigns (
    campaign_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dm_id INT NOT NULL,
    invite_code VARCHAR(20) UNIQUE,
    map_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dm_id) REFERENCES users(player_id)
);

CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    base_hp INT DEFAULT 10
);

CREATE TABLE IF NOT EXISTS races (
    race_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    speed INT DEFAULT 30,
    description TEXT
);

CREATE TABLE IF NOT EXISTS characters (
    char_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    campaign_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    race_id INT NOT NULL,
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    hp INT DEFAULT 10,
    pos_x INT DEFAULT 0,
    pos_y INT DEFAULT 0,
    token_image VARCHAR(255) DEFAULT 'default_token.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(player_id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(campaign_id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    FOREIGN KEY (race_id) REFERENCES races(race_id)
);

CREATE TABLE IF NOT EXISTS quests (
    quest_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    reward_xp INT DEFAULT 100,
    min_level INT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS character_quests (
    cq_id INT AUTO_INCREMENT PRIMARY KEY,
    char_id INT NOT NULL,
    quest_id INT NOT NULL,
    status ENUM('active', 'completed', 'failed') DEFAULT 'active',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (char_id) REFERENCES characters(char_id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('weapon', 'armor', 'potion', 'misc') NOT NULL,
    value INT DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 0.0
);

CREATE TABLE IF NOT EXISTS inventory (
    inv_id INT AUTO_INCREMENT PRIMARY KEY,
    char_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (char_id) REFERENCES characters(char_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS monsters (
    monster_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    hp INT DEFAULT 50,
    xp_value INT DEFAULT 50
);

CREATE TABLE IF NOT EXISTS locations (
    loc_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    coordinates VARCHAR(50),
    description TEXT
);

-- Indexes for performance
CREATE INDEX idx_char_player ON characters(player_id);
CREATE INDEX idx_inv_char ON inventory(char_id);
CREATE INDEX idx_inv_item ON inventory(item_id);
CREATE INDEX idx_cq_char ON character_quests(char_id);
CREATE INDEX idx_cq_quest ON character_quests(quest_id);

-- 2. Stored Procedures

DELIMITER //

CREATE PROCEDURE sp_assign_quest(IN p_char_id INT, IN p_quest_id INT)
BEGIN
    INSERT INTO character_quests (char_id, quest_id) VALUES (p_char_id, p_quest_id);
END //

CREATE PROCEDURE sp_complete_quest(IN p_char_id INT, IN p_quest_id INT)
BEGIN
    DECLARE v_reward INT;
    
    -- Update status
    UPDATE character_quests 
    SET status = 'completed' 
    WHERE char_id = p_char_id AND quest_id = p_quest_id;
    
    -- Get XP reward
    SELECT reward_xp INTO v_reward FROM quests WHERE quest_id = p_quest_id;
    
    -- Add XP to character
    UPDATE characters 
    SET xp = xp + v_reward 
    WHERE char_id = p_char_id;
END //

DELIMITER ;

-- 3. Triggers

DELIMITER //

CREATE TRIGGER trg_level_up
BEFORE UPDATE ON characters
FOR EACH ROW
BEGIN
    -- Simple rule: Level up every 1000 XP
    IF NEW.xp >= OLD.level * 1000 THEN
        SET NEW.level = OLD.level + 1;
        -- Optional: Increase HP on level up
        SET NEW.hp = NEW.hp + 10;
    END IF;
END //

DELIMITER ;

-- 4. Views

CREATE VIEW view_character_growth AS
SELECT c.char_id, c.name AS character_name, u.username AS player_name, 
       cl.name AS class_name, r.name AS race_name, c.level, c.xp
FROM characters c
JOIN users u ON c.player_id = u.player_id
JOIN classes cl ON c.class_id = cl.class_id
JOIN races r ON c.race_id = r.race_id;

CREATE VIEW view_quest_status AS
SELECT c.name AS character_name, q.title AS quest_title, cq.status
FROM character_quests cq
JOIN characters c ON cq.char_id = c.char_id
JOIN quests q ON cq.quest_id = q.quest_id;

CREATE VIEW view_inventory_summary AS
SELECT c.name AS character_name, i.name AS item_name, inv.quantity, i.type, i.value
FROM inventory inv
JOIN characters c ON inv.char_id = c.char_id
JOIN items i ON inv.item_id = i.item_id;

-- 5. Seed Data

-- Classes
INSERT INTO classes (name, description, base_hp) VALUES
('Fighter', 'Master of martial combat', 12),
('Wizard', 'Scholarly magic user', 6),
('Rogue', 'Sneaky and precise', 8),
('Cleric', 'Divine spellcaster', 10),
('Ranger', 'Warrior of the wild', 10);

-- Races
INSERT INTO races (name, speed, description) VALUES
('Human', 30, 'Versatile and ambitious'),
('Elf', 35, 'Agile and magical'),
('Dwarf', 25, 'Tough and sturdy'),
('Halfling', 25, 'Small and lucky'),
('Orc', 30, 'Strong and aggressive');

-- Users (passwords would be hashed in real app, using plaintext for seed clarity or simple hash)
-- note: in production app PHP logic should verify these using password_verify()
INSERT INTO users (username, password, email) VALUES
('dm_master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dm@example.com'), -- password: password
('player_one', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'p1@example.com'),
('player_two', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'p2@example.com'),
('player_three', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'p3@example.com');

-- Characters
INSERT INTO characters (player_id, name, class_id, race_id, level, xp, hp, pos_x, pos_y) VALUES
(2, 'Aragorn', 1, 1, 5, 5000, 60, 10, 10),
(2, 'Legolas', 5, 2, 5, 5000, 50, 12, 10),
(3, 'Gimli', 1, 3, 5, 5000, 70, 10, 12),
(3, 'Gandalf', 2, 1, 20, 100000, 100, 0, 0),
(4, 'Frodo', 3, 4, 1, 0, 10, 5, 5),
(4, 'Sam', 1, 4, 1, 100, 12, 6, 5),
(2, 'Boromir', 1, 1, 4, 4000, 55, 15, 15),
(3, 'Thorin', 1, 3, 10, 10000, 120, 100, 100),
(4, 'Bilbo', 3, 4, 10, 10000, 40, 50, 50),
(2, 'Saruman', 2, 1, 20, 100000, 90, 99, 99);

-- Items
INSERT INTO items (name, type, value, weight) VALUES
('Longsword', 'weapon', 15, 3.0),
('Shortbow', 'weapon', 25, 2.0),
('Plate Armor', 'armor', 1500, 65.0),
('Health Potion', 'potion', 50, 0.5),
('Rope', 'misc', 1, 10.0),
('Dagger', 'weapon', 2, 1.0),
('Staff', 'weapon', 5, 4.0),
('Shield', 'armor', 10, 6.0),
('Lockpick', 'misc', 25, 0.1),
('Torch', 'misc', 1, 1.0);

-- Inventory
INSERT INTO inventory (char_id, item_id, quantity) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 8, 1),
(4, 7, 1),
(5, 6, 1),
(5, 4, 2),
(1, 4, 5),
(2, 5, 1),
(3, 4, 1),
(4, 9, 3);

-- Quests
INSERT INTO quests (title, description, reward_xp, min_level) VALUES
('Save the Village', 'Goblins are attacking!', 500, 1),
('Find the Ring', 'A powerful artifact is lost.', 5000, 5),
('Slay the Dragon', 'A red dragon terrorizes the north.', 10000, 10),
('Escort the Merchant', 'Protect the caravan.', 200, 1),
('Retrieve the Amulet', 'Lost in the crypt.', 800, 3);

-- Character Quests
INSERT INTO character_quests (char_id, quest_id, status) VALUES
(1, 2, 'active'),
(2, 2, 'active'),
(3, 2, 'active'),
(4, 2, 'active'),
(5, 2, 'active'),
(1, 1, 'completed'),
(5, 1, 'completed'),
(1, 3, 'failed'),
(2, 4, 'active'),
(3, 5, 'active');

-- Monsters
INSERT INTO monsters (name, type, hp, xp_value) VALUES
('Goblin', 'Humanoid', 7, 50),
('Orc', 'Humanoid', 15, 100),
('Dragon', 'Dragon', 200, 5000),
('Skeleton', 'Undead', 13, 50),
('Zombie', 'Undead', 22, 50),
('Bandit', 'Humanoid', 11, 25),
('Wolf', 'Beast', 11, 50),
('Bear', 'Beast', 34, 200),
('Troll', 'Giant', 84, 1800),
('Ghost', 'Undead', 45, 1100);

-- Locations
INSERT INTO locations (name, coordinates, description) VALUES
('The Shire', '10,10', 'A peaceful land of halflings.'),
('Rivendell', '50,10', 'Elven sanctuary.'),
('Mordor', '100,100', 'Land of shadow.'),
('Minas Tirith', '80,80', 'City of Kings.'),
('Moria', '60,60', 'Dwarven mines.');
