-- 9 Artikel (Poster) in Datenbank einfügen
INSERT INTO products (title, description, price, picture_path, stock) VALUES
('Sonnenaufgang', 'Warme Farben und klare Linien.', 14.90, '../images/sonnenuntergang.png', 50),
('Urban Nights', 'Lichter der Stadt.', 19.90, '../images/urbanNights.png', 30),
('Bergblick', 'Minimalistische Berge.', 16.50, '../images/bergblick.png', 40),
('Frühling', 'Blüten & Farben.', 12.00, '../images/frühling.png', 60),
('OceanBlue', 'Tiefe Blautöne.', 18.75, '../images/oceanBlue.png', 25),
('Waldpfad', 'Grüne Ruhe.', 15.20, '../images/waldpfad.png', 35),
('Retro Shapes', 'Geometrie im Vintage-Stil.', 13.40, '../images/retroShapes.png', 45),
('München Skyline', 'Abendlicht über Münchner Dächer.', 21.00, '../images/münchenSkyline.png', 20),
('Nordlicht', 'Leuchtende Nacht.', 22.90, '../images/nordlicht.png', 15);

UPDATE products SET number='POST-0001' WHERE id=1;
UPDATE products SET number='POST-0002' WHERE id=2;
UPDATE products SET number='POST-0003' WHERE id=3;
UPDATE products SET number='POST-0004' WHERE id=4;
UPDATE products SET number='POST-0005' WHERE id=5;
UPDATE products SET number='POST-0006' WHERE id=6;
UPDATE products SET number='POST-0007' WHERE id=7;
UPDATE products SET number='POST-0008' WHERE id=8;
UPDATE products SET number='POST-0009' WHERE id=9;