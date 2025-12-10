--
-- Tabellenstruktur für Tabelle `coupons`
--

DROP TABLE IF EXISTS `coupons`;

CREATE TABLE `coupons` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_percent DECIMAL(5,2)DEFAULT NULL, -- Prozent
  discount_amount DECIMAL(10,2) DEFAULT NULL, -- fixer Betrag
  is_active TINYINT(1) NOT NULL DEFAULT 1 -- 1=aktiv, 0=inaktiv
)ENGINE=InnoDB;