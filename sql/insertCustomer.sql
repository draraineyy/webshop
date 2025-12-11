INSERT INTO customer (name, email, password_hash, must_change_password, twofacode, created_at)
 VALUES (
'Test User',
 'test@example.com',
 SHA2('Test12345', 512), -- Passwort wird als SHA512 gespeichert
 0,
  'JBSWY3DPEHPK3PXP',     -- Beispiel-Secret für Google Authenticator
  NOW()
);