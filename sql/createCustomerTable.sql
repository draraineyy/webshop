--
-- Tabellenstruktur für Tabelle `customer`
--

DROP TABLE IF EXISTS `customer`;

CREATE TABLE `customer` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  passwort_hash CHAR(128) NOT NULL,
  -- User muss bei erstem Login eigenes Passwort festlegen
  muss_pw_wechseln TINYINT(1) DEFAULT 1,
  2facode VARCHAR(64) NOT NULL,
  erstellt_am DATETIME NOT NULL
) ENGINE=InnoDB;
