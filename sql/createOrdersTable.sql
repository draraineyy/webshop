--
-- Tabellenstruktur für Tabelle `orders`
--

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  date DATETIME NOT NULL,
  delivery VARCHAR(50) NOT NULL,
  sum DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customer(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;