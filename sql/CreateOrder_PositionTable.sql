--
-- Tabellenstruktur für Tabelle `order_position`
--

DROP TABLE IF EXISTS `order_position`;

CREATE TABLE `order_position` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL, -- bereits inkl. Rabatt
  discount DECIMAL(4,2) NOT NULL,
  FOREIGN KEY(order_id) REFERENCES orders(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;