# SQL Sample Data Script

## Plik: insert_sample_data.sql

Ten skrypt SQL dodaje dane przykładowe do bazy danych smartrent.

### Zawartość:

#### 1. Użytkownicy (3)
Wszyscy użytkownicy mają hasło: **1233321#**

- **Anna Kowalska** (anna@example.com)
- **Jan Nowak** (jan@example.com)  
- **Maria Lewandowska** (maria@example.com)

#### 2. Nieruchomości (10)
Wszystkie nieruchomości należą do użytkownika admin (user_id = 1)

| Nr | Nazwa | Miasto | Cena/dzień | Zdjęcie |
|----|-------|--------|------------|---------|
| 1 | Elegancki Apartament w Centrum Warszawy | Warszawa | 150 PLN | zdjecie1.png |
| 2 | Nowoczesny Loft w Krakowie | Kraków | 120 PLN | zdjecie2.png |
| 3 | Przytulny Pokój w Gdańsku | Gdańsk | 80 PLN | zdjecie3.png |
| 4 | Luksusowa Willa w Wrocławiu | Wrocław | 200 PLN | zdjecie4.png |
| 5 | Studio w Poznaniu | Poznań | 95 PLN | zdjecie5.png |
| 6 | Rodzinny Dom w Łodzi | Łódź | 180 PLN | zdjecie6.png |
| 7 | Biznesowy Apartament w Warszawie | Warszawa | 160 PLN | zdjecie7.png |
| 8 | Przystanowisko Artysty w Krakowie | Kraków | 110 PLN | zdjecie8.png |
| 9 | Plaża Apartament w Gdyni | Gdynia | 140 PLN | zdjecie9.png |
| 10 | Horyzont Apartament we Wrocławiu | Wrocław | 175 PLN | zdjecie10.png |

### Jak użyć:

#### Metoda 1: Z linii poleceń MySQL
```bash
mysql -u root -p smartrent < sql/insert_sample_data.sql
```

#### Metoda 2: Z phpMyAdmin
1. Zaloguj się do phpMyAdmin
2. Wybierz bazę danych `smartrent`
3. Przejdź do zakładki "SQL"
4. Skopiuj i wklej zawartość pliku `insert_sample_data.sql`
5. Kliknij "Wykonaj"

#### Metoda 3: Z MySQL Workbench
1. Otwórz MySQL Workbench
2. Połącz się z bazą danych
3. Otwórz plik `sql/insert_sample_data.sql`
4. Wykonaj skrypt (⚡ ikona lub Ctrl+Shift+Enter)

### Uwagi:

- **Hasło użytkowników**: Wszystkie konta używają hasła `1233321#` (hash: `$2y$10$tdSmr4iuty2d3SFPayKLweib9dgm.NkD2MQPNBmJhRz4r36gAbn/S`)
- **Zdjęcia**: Upewnij się, że pliki zdjecie1.png do zdjecie10.png istnieją w katalogu `uploads/` lub zaktualizuj ścieżki w bazie danych
- **Admin**: Do właściwego działania wymagane jest, aby użytkownik o ID=1 był administratorem
- **Bezpieczeństwo**: Zmień hasła w środowisku produkcyjnym!

### Sprawdzenie po wykonaniu:

```sql
-- Sprawdź dodanych użytkowników
SELECT id, name, email, role FROM users 
WHERE email IN ('anna@example.com', 'jan@example.com', 'maria@example.com');

-- Sprawdź dodane nieruchomości
SELECT id, title, city, price, owner_id FROM properties 
ORDER BY id DESC LIMIT 10;
```

### Czyszczenie danych testowych:

Jeśli chcesz usunąć dane testowe:

```sql
-- UWAGA: To usunie dane nieodwracalnie!
DELETE FROM users WHERE email IN ('anna@example.com', 'jan@example.com', 'maria@example.com');
DELETE FROM properties WHERE title LIKE '%Elegancki Apartament%' 
   OR title LIKE '%Nowoczesny Loft%' 
   OR title LIKE '%Przytulny Pokój%'
   OR title LIKE '%Luksusowa Willa%'
   OR title LIKE '%Studio w Poznaniu%'
   OR title LIKE '%Rodzinny Dom%'
   OR title LIKE '%Biznesowy Apartament%'
   OR title LIKE '%Przystanowisko Artysty%'
   OR title LIKE '%Plaża Apartament%'
   OR title LIKE '%Horyzont Apartament%';
```
