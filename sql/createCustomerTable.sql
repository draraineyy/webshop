--
-- Tabellenstruktur für Tabelle `customer`
--

DROP TABLE IF EXISTS `customer`;

CREATE TABLE `customer` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password_hash CHAR(128) NOT NULL,
  -- User muss bei erstem Login eigenes Passwort festlegen
  must_change_password TINYINT(1) DEFAULT 1,
  twofacode VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

INSERT INTO customer (name, email, password_hash, must_change_password, 2facode, created_at)
 VALUES (
'Test User',
 'test@example.com',
 SHA2('Test12345', 512), -- Passwort wird als SHA512 gespeichert
 0,
  'JBSWY3DPEHPK3PXP',     -- Beispiel-Secret für Google Authenticator
  NOW()
);