--
-- Tabellenstruktur für Tabelle `online_status`
--

DROP TABLE IF EXISTS `online_status`;

CREATE TABLE `online_status` (
  customer_id INT PRIMARY KEY,
  last_seen DATETIME NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)ENGINE=InnoDB;