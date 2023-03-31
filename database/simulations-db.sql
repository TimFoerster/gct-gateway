-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema app
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `simulations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simulations` ;

CREATE TABLE IF NOT EXISTS `simulations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scenario` VARCHAR(45) NOT NULL,
  `seed` INT NOT NULL,
  `start` DATETIME NULL,
  `end` DATETIME NULL,
  `end_time` FLOAT NULL,
  `device_name` VARCHAR(179) NULL,
  `version` VARCHAR(45) NULL,
  `status` VARCHAR(45) NULL,
  `ip` VARCHAR(45) NOT NULL,
  `algorithm` VARCHAR(45) NOT NULL,
  `mode` ENUM('headless', 'render') NULL,
  `recording` VARCHAR(45) NULL,
  `platform` VARCHAR(45) NULL,
  `os` VARCHAR(100) NULL,
  `broadcast_interval` FLOAT NULL,
  `app_interval` FLOAT NULL,
  `person_count` INT NULL,
  `simulation_options` JSON NULL,
  `processes_count` INT NULL DEFAULT 0,
  `receive_accuracy` FLOAT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `simulations_scenario_index` (`scenario` ASC),
  INDEX `simulations_algorithm_index` (`algorithm` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `calculations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `calculations` ;

CREATE TABLE IF NOT EXISTS `calculations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `simulation_id` INT UNSIGNED NOT NULL,
  `timestep` FLOAT NULL,
  `status` VARCHAR(45) NULL DEFAULT 'started',
  `start` DATETIME NULL DEFAULT NOW(),
  `end` DATETIME NULL,
  `calculated_time` FLOAT NULL,
  PRIMARY KEY (`id`),
  INDEX `calculation_simulation_idx` (`simulation_id` ASC),
  CONSTRAINT `calculation_simulation`
    FOREIGN KEY (`simulation_id`)
    REFERENCES `simulations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `devices`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `devices` ;

CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `simulation_id` INT UNSIGNED NOT NULL,
  `global_name` VARCHAR(45) NULL,
  `global_id` INT UNSIGNED NULL,
  `local_id` INT UNSIGNED NULL,
  `type` ENUM('d', 'b', 'g') NOT NULL,
  `send_status` VARCHAR(45) NULL,
  `send_count` INT NULL,
  `received_status` VARCHAR(45) NULL,
  `received_count` INT NULL,
  `group_status` VARCHAR(45) NULL,
  `group_count` INT NULL,
  `stats` JSON NULL,
  `updated_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `device_sim_idx` (`simulation_id` ASC),
  INDEX `device_uuid` (`global_name` ASC, `global_id` ASC, `local_id` ASC),
  UNIQUE INDEX `device_uq` (`simulation_id` ASC, `global_name` ASC, `global_id` ASC, `local_id` ASC),
  CONSTRAINT `device_sim`
    FOREIGN KEY (`simulation_id`)
    REFERENCES `simulations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `send_messages`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `send_messages` ;

CREATE TABLE IF NOT EXISTS `send_messages` (
  `device_id` INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `time` FLOAT NOT NULL,
  `uuid` SMALLINT UNSIGNED NOT NULL,
  `x` FLOAT NOT NULL,
  `y` FLOAT NOT NULL,
  `z` FLOAT NOT NULL,
  `value` VARCHAR(128) NOT NULL,
  `generated` TINYINT(1) NOT NULL,
  PRIMARY KEY (`device_id`, `package_id`),
  INDEX `send_time-idx` (`time` ASC),
  INDEX `send_pos-idx` (`x` ASC, `y` ASC, `z` ASC),
  INDEX `send-device-time` (`device_id` ASC, `time` ASC),
  CONSTRAINT `send_device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `series`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `series` ;

CREATE TABLE IF NOT EXISTS `series` (
  `series_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `calculation_id` INT UNSIGNED NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `start_time` FLOAT NOT NULL,
  `duration` FLOAT NULL,
  `statistic_count` INT UNSIGNED NULL,
  `avg_length` DOUBLE NULL,
  `sum_length` DOUBLE NULL,
  `median_length` DOUBLE NULL,
  `avg_standard_deviation` DOUBLE NULL,
  `median_standard_deviation` DOUBLE NULL,
  `min_standard_deviation` DOUBLE NULL,
  `max_standard_deviation` DOUBLE NULL,
  PRIMARY KEY (`series_id`),
  INDEX `series-calc_idx` (`calculation_id` ASC),
  INDEX `series-device_idx` (`device_id` ASC),
  CONSTRAINT `series-calc`
    FOREIGN KEY (`calculation_id`)
    REFERENCES `calculations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `series-device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `statistics`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `statistics` ;

CREATE TABLE IF NOT EXISTS `statistics` (
  `calculation_id` INT UNSIGNED NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `timestep` INT UNSIGNED NOT NULL,
  `time` FLOAT NULL,
  `x` FLOAT NULL,
  `y` FLOAT NULL,
  `value` VARCHAR(128) NULL,
  `direction` DOUBLE NULL,
  `length` DOUBLE NULL,
  `variance` DOUBLE NULL,
  `standard_deviation` DOUBLE NULL,
  `packages` INT UNSIGNED NULL,
  `unique_packages` INT UNSIGNED NULL,
  `min` VARCHAR(128) NULL,
  `max` VARCHAR(128) NULL,
  `series_id` INT UNSIGNED NULL,
  PRIMARY KEY (`calculation_id`, `device_id`, `timestep`),
  INDEX `statistics_device_idx` (`device_id` ASC),
  INDEX `statistics_device-calc-index` (`device_id` ASC, `calculation_id` ASC, `timestep` ASC),
  INDEX `statistics_series_idx` (`series_id` ASC),
  CONSTRAINT `statistics_calculation`
    FOREIGN KEY (`calculation_id`)
    REFERENCES `calculations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `statistics_device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `statistics_series`
    FOREIGN KEY (`series_id`)
    REFERENCES `series` (`series_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `received_messages`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `received_messages` ;

CREATE TABLE IF NOT EXISTS `received_messages` (
  `device_id` INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `time` FLOAT NOT NULL,
  `uuid` SMALLINT UNSIGNED NOT NULL,
  `continuation` INT UNSIGNED NOT NULL,
  `x` FLOAT NOT NULL,
  `y` FLOAT NOT NULL,
  `z` FLOAT NOT NULL,
  `value` VARCHAR(128) NOT NULL,
  `distance` FLOAT NOT NULL,
  PRIMARY KEY (`device_id`, `package_id`),
  INDEX `recieved_time-idx` (`time` ASC),
  INDEX `received_pos-idx` (`x` ASC, `y` ASC, `z` ASC),
  INDEX `received-device-time` (`device_id` ASC, `time` ASC),
  CONSTRAINT `received_device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `calculation_device`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `calculation_device` ;

CREATE TABLE IF NOT EXISTS `calculation_device` (
  `calculation_id` INT UNSIGNED NOT NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `statistic_count` INT UNSIGNED NULL,
  `avg_length` DOUBLE NULL,
  `sum_length` DOUBLE NULL,
  `median_length` DOUBLE NULL,
  PRIMARY KEY (`calculation_id`, `device_id`),
  INDEX `fk_calculations_has_device_device1_idx` (`device_id` ASC),
  INDEX `fk_calculations_has_device_calculations1_idx` (`calculation_id` ASC),
  CONSTRAINT `fk_calculation_device-calculations`
    FOREIGN KEY (`calculation_id`)
    REFERENCES `calculations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_calculation_device-device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `group_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `group_logs` ;

CREATE TABLE IF NOT EXISTS `group_logs` (
  `device_id` INT UNSIGNED NOT NULL,
  `entry` INT UNSIGNED NOT NULL,
  `t` INT UNSIGNED NOT NULL,
  `time` FLOAT NOT NULL,
  `gid` VARCHAR(128) NOT NULL,
  `devices` INT UNSIGNED NOT NULL,
  `x` FLOAT NOT NULL,
  `y` FLOAT NOT NULL,
  `z` FLOAT NOT NULL,
  PRIMARY KEY (`device_id`, `entry`),
  INDEX `group_logs-entry_idx` (`entry` ASC),
  INDEX `group_logs-t_idx` (`t` ASC),
  INDEX `group_logs-time_idx` (`time` ASC),
  CONSTRAINT `group_log-device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `group_logs_receivers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `group_logs_receivers` ;

CREATE TABLE IF NOT EXISTS `group_logs_receivers` (
  `device_id` INT UNSIGNED NOT NULL,
  `group_entry_id` INT UNSIGNED NOT NULL,
  `index` INT UNSIGNED NOT NULL,
  `sender_id` INT NOT NULL,
  `message_count` INT NULL,
  `sender_device_id` INT UNSIGNED NULL,
  PRIMARY KEY (`device_id`, `group_entry_id`, `index`, `sender_id`),
  INDEX `group_logs_r-group_entry_id_idx` (`group_entry_id` ASC),
  INDEX `group_logs_r-device_id-idx` (`device_id` ASC),
  INDEX `group_logs_r-device_sender_idx` (`sender_device_id` ASC),
  INDEX `group_logs-_r-sender_id` (`sender_id` ASC),
  CONSTRAINT `group_logs_r-device`
    FOREIGN KEY (`device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `group_logs_r-group_entry_id`
    FOREIGN KEY (`group_entry_id`)
    REFERENCES `group_logs` (`entry`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `group_logs_r-device_sender`
    FOREIGN KEY (`sender_device_id`)
    REFERENCES `devices` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `accuracies`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `accuracies` ;

CREATE TABLE IF NOT EXISTS `accuracies` (
  `simulation_id` INT UNSIGNED NOT NULL,
  `timestep` INT UNSIGNED NOT NULL,
  `time` FLOAT NOT NULL,
  `accuracy` DOUBLE NOT NULL,
  `person_count` INT NULL,
  PRIMARY KEY (`simulation_id`, `timestep`),
  CONSTRAINT `accuarcy-simulation`
    FOREIGN KEY (`simulation_id`)
    REFERENCES `simulations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `simulation_log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `simulation_log` ;

CREATE TABLE IF NOT EXISTS `simulation_log` (
  `simulation_id` INT UNSIGNED NOT NULL,
  `index` INT UNSIGNED NOT NULL,
  `timestep` INT NOT NULL,
  `time` FLOAT NOT NULL,
  `level` VARCHAR(8) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  PRIMARY KEY (`simulation_id`, `index`),
  INDEX `simulation_log-timestep-idx` (`timestep` ASC),
  INDEX `simulation_log-time-idx` (`time` ASC),
  CONSTRAINT `simulation_log-simulation`
    FOREIGN KEY (`simulation_id`)
    REFERENCES `simulations` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
