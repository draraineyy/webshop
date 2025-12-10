--
-- Tabellenstruktur für Tabelle `points`
--

DROP TABLE IF EXISTS `points`;

CREATE TABLE `points` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  activity VARCHAR(100) NOT NULL,
  points INT NOT NULL,
  date DATETIME NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;