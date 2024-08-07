
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Aniversus implementation : © Leo Cheung <leocheung1718@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):
-- DATABASE for Decks of two players
CREATE TABLE IF NOT EXISTS `cat_deck` (
    `card_id` int(10) unsigned NOT NULL Auto_increment,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    `card_status` varchar(16) NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `squirrel_deck` (
    `card_id` int(10) unsigned NOT NULL Auto_increment,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    `card_status` varchar(16) NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- DATABASE for playmats for two players
-- if card_row and column = 0, this means the card in discard pile

-- DATABASE




-- add info about first player
-- ALTER TABLE `player` ADD `player_first` BOOLEAN NOT NULL DEFAULT '0';

-- Below is some modification to the player table
ALTER TABLE `player` ADD `player_team` VARCHAR(16) NOT NULL;
ALTER TABLE `player` ADD `player_productivity_limit` INT(11) NOT NULL;
ALTER TABLE `player` ADD `player_productivity` INT(11) NOT NULL;
ALTER TABLE `player` ADD `player_action_limit` INT(11) NOT NULL;
ALTER TABLE `player` ADD `player_action` INT(11) NOT NULL;
ALTER TABLE `player` ADD `player_power` INT(11) NOT NULL;
ALTER TABLE `player` ADD `player_status` TEXT;
ALTER TABLE `player` ADD `shooting_number` TEXT;


-- Playing Card DATABASE
CREATE TABLE IF NOT EXISTS `playing_card` (
    `id` int(10) unsigned NOT NULL Auto_increment,
    `player_id` int(20),
    `card_id` int(11),
    `card_type` varchar(16),
    `card_type_arg` int(11),
    `card_launch` BOOLEAN,
    `card_status` varchar(16),
    `card_info` TEXT,
    `disabled` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

