--
-- Tabellenstruktur für Tabelle `invoice_position`
--

DROP TABLE IF EXISTS `invoice_position`;

CREATE TABLE `invoice_position` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  discount DECIMAL(4,2),
  FOREIGN KEY(invoice_id) REFERENCES invoices(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY(product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)ENGINE=InnoDB;