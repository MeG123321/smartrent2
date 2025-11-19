-- ============================================
-- SQL Script dla smartrent
-- Dodaje 10 nieruchomości i 3 użytkowników
-- ============================================

USE smartrent;

-- ============================================
-- CZĘŚĆ 1: Dodanie 3 użytkowników
-- ============================================
-- Hasło dla wszystkich użytkowników: 1233321#
-- Hash wygenerowany przez password_hash('1233321#', PASSWORD_DEFAULT)

INSERT INTO users (name, email, password, role, created_at) VALUES
('Anna Kowalska', 'anna@example.com', '$2y$10$tdSmr4iuty2d3SFPayKLweib9dgm.NkD2MQPNBmJhRz4r36gAbn/S', 'user', NOW()),
('Jan Nowak', 'jan@example.com', '$2y$10$tdSmr4iuty2d3SFPayKLweib9dgm.NkD2MQPNBmJhRz4r36gAbn/S', 'user', NOW()),
('Maria Lewandowska', 'maria@example.com', '$2y$10$tdSmr4iuty2d3SFPayKLweib9dgm.NkD2MQPNBmJhRz4r36gAbn/S', 'user', NOW());

-- ============================================
-- CZĘŚĆ 2: Dodanie 10 nieruchomości
-- ============================================
-- Wszystkie nieruchomości należą do user_id = 1 (admin)
-- Zdjęcia: zdjecie1.png do zdjecie10.png

INSERT INTO properties (title, description, price, city, image, owner_id, created_at) VALUES
(
    'Elegancki Apartament w Centrum Warszawy',
    'Luksusowy apartament w samym sercu stolicy. Przestronny, dwupokojowy lokal z nowoczesnymi meblami, w pełni wyposażoną kuchnią i łazienką z prysznicem. Idealna lokalizacja blisko metra, restauracji i atrakcji turystycznych. Doskonały zarówno dla biznesmenów jak i turystów.',
    150.00,
    'Warszawa',
    'zdjecie1.png',
    1,
    NOW()
),
(
    'Nowoczesny Loft w Krakowie',
    'Stylowy loft w industrialnej części Krakowa. Wysoki sufit, duże okna i unikalne wykończenie nadają temu miejscu wyjątkowy charakter. Blisko Starego Miasta, w otoczeniu galerii sztuki i klimatycznych kawiarni. Szybki internet i w pełni wyposażona kuchnia.',
    120.00,
    'Kraków',
    'zdjecie2.png',
    1,
    NOW()
),
(
    'Przytulny Pokój w Gdańsku',
    'Komfortowy pokój w centrum Gdańska, idealny dla osób podróżujących solo lub par. Cicha okolica, blisko starówki i plaży. Pokój wyposażony w wygodne łóżko, biurko do pracy oraz dostęp do wspólnej kuchni. Świetna komunikacja miejska.',
    80.00,
    'Gdańsk',
    'zdjecie3.png',
    1,
    NOW()
),
(
    'Luksusowa Willa w Wrocławiu',
    'Ekskluzywna willa w prestiżowej dzielnicy Wrocławia. Ogromne wnętrza, prywatny ogród, garaż na dwa samochody i luksusowe wykończenie. Idealna dla rodzin lub grup biznesowych szukających wyjątkowego komfortu. Pełna prywatność i cisza.',
    200.00,
    'Wrocław',
    'zdjecie4.png',
    1,
    NOW()
),
(
    'Studio w Poznaniu',
    'Nowoczesne studio w centrum Poznania. Otwarta przestrzeń z aneksem kuchennym, łazienką i wygodną sofą. Idealne dla studentów i młodych profesjonalistów. W pobliżu uczelnie, biura i centrum handlowe. Doskonała infrastruktura miejska.',
    95.00,
    'Poznań',
    'zdjecie5.png',
    1,
    NOW()
),
(
    'Rodzinny Dom w Łodzi',
    'Przestronny dom jednorodzinny w spokojnej okolicy Łodzi. Cztery sypialnie, duży salon z kominkiem, ogród z miejscem do grillowania. Idealne dla rodzin z dziećmi. W pobliżu szkoły, przedszkola i parki. Bezpieczna, przyjazna okolica.',
    180.00,
    'Łódź',
    'zdjecie6.png',
    1,
    NOW()
),
(
    'Biznesowy Apartament w Warszawie',
    'Elegancki apartament w dzielnicy biznesowej Warszawy. Nowoczesne wnętrze z biurem, szybkim internetem i dostępem do siłowni w budynku. Blisko centrów biurowych, restauracji i komunikacji miejskiej. Perfekcyjny dla osób w podróży służbowej.',
    160.00,
    'Warszawa',
    'zdjecie7.png',
    1,
    NOW()
),
(
    'Przystanowisko Artysty w Krakowie',
    'Urokliwe mieszkanie w artystycznej dzielnicy Krakowa. Pełne światła wnętrze z wysokimi oknami, idealne dla twórców i miłośników sztuki. Przestronna pracownia, galeria w budynku i atmosfera sprzyjająca kreatywności. Blisko Kazimierza i Wisły.',
    110.00,
    'Kraków',
    'zdjecie8.png',
    1,
    NOW()
),
(
    'Plaża Apartament w Gdyni',
    'Apartament z widokiem na morze w Gdyni. Zaledwie 5 minut spacerem do plaży. Przestronny balkon, salon z kuchnią otwartą i dwie sypialnie. Idealne dla rodzin szukających wypoczynku nad morzem. W pobliżu promenada i restauracje z owocami morza.',
    140.00,
    'Gdynia',
    'zdjecie9.png',
    1,
    NOW()
),
(
    'Horyzont Apartament we Wrocławiu',
    'Apartament na wysokim piętrze z panoramicznym widokiem na Wrocław. Nowoczesne wykończenie, przestronne wnętrze i fantastyczne widoki. Blisko Rynku i głównych atrakcji miasta. Parking podziemny w cenie. Idealne dla osób ceniących luksus i wygodę.',
    175.00,
    'Wrocław',
    'zdjecie10.png',
    1,
    NOW()
);

-- ============================================
-- Podsumowanie wstawionych danych:
-- ============================================
-- Użytkownicy: 3 (Anna Kowalska, Jan Nowak, Maria Lewandowska)
-- Nieruchomości: 10 (wszystkie należące do admin user_id = 1)
-- Miasta: Warszawa, Kraków, Gdańsk, Wrocław, Poznań, Łódź, Gdynia
-- Ceny wynajmu: od 80 PLN do 200 PLN za dzień
-- ============================================
