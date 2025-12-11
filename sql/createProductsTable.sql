--
-- Tabellenstruktur für Tabelle `products`
--

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200)NOT NULL,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  picture_path VARCHAR(255) NOT NULL,
  stock INT NOT NULL
) ENGINE=InnoDB;

ALTER TABLE products
  ADD COLUMN number VARCHAR(64) AFTER id;

ALTER TABLE products
  MODIFY COLUMN number VARCHAR(64) NOT NULL;

ALTER TABLE products
  ADD UNIQUE KEY products_number(number);