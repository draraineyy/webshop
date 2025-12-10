--
-- Tabellenstruktur für Tabelle `invoices`
--

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  date DATETIME NOT NULL,
  sum DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)ENGINE=InnoDB;