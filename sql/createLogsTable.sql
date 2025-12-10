--
-- Tabellenstruktur für Tabelle `logs`
--

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  login_date DATETIME NOT NULL,
  operating_system VARCHAR(50) NOT NULL,
  aufloesung VARCHAR(20) NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;