--
-- Tabellenstruktur für Tabelle `logs`
--

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kunde_id INT NOT NULL,
  login_datum DATETIME NOT NULL,
  operating_system VARCHAR(50) NOT NULL,
  aufloesung VARCHAR(20) NOT NULL,
  FOREIGN KEY (kunde_id) REFERENCES kunden(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;