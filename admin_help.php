<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pomoc i dokumentacja — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Pomoc i dokumentacja administratora</h2>
  
  <div class="panel">
    <h3>Witamy w panelu pomocy</h3>
    <p class="muted">Tutaj znajdziesz informacje dotyczące zarządzania systemem wynajmu mieszkań.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zarządzanie ofertami</h3>
    <p><strong>Dodawanie oferty:</strong> Przejdź do sekcji "Dodaj ofertę" z panelu administratora. Wypełnij formularz podając tytuł, opis, miasto, cenę i opcjonalnie dodaj zdjęcie mieszkania.</p>
    <p><strong>Edycja oferty:</strong> W sekcji "Zarządzanie ofertami" kliknij przycisk "Edytuj" przy wybranej ofercie. Możesz zmienić wszystkie dane oraz zaktualizować zdjęcie.</p>
    <p><strong>Usuwanie oferty:</strong> W sekcji "Zarządzanie ofertami" kliknij przycisk "Usuń" przy wybranej ofercie. Pamiętaj, że usunięcie oferty jest nieodwracalne.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zarządzanie wynajmami</h3>
    <p><strong>Przypisanie mieszkania:</strong> Przejdź do sekcji "Zarządzanie wynajmami" i wybierz mieszkanie z listy dostępnych. Określ najemcę, okres wynajmu i cenę.</p>
    <p><strong>Historia wynajmów:</strong> Wszystkie rezerwacje są dostępne w sekcji "Historia" gdzie możesz przeglądać szczegóły każdej transakcji.</p>
    <p><strong>Status mieszkania:</strong> Po wynajęciu mieszkania automatycznie zmienia status na "wynajęte" i przestaje być widoczne w publicznej liście dostępnych ofert.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zarządzanie użytkownikami</h3>
    <p><strong>Lista użytkowników:</strong> W sekcji "Zarządzaj użytkownikami" możesz przeglądać wszystkich zarejestrowanych użytkowników.</p>
    <p><strong>Role użytkowników:</strong> System obsługuje dwie role: "user" (zwykły użytkownik) i "admin" (administrator z pełnymi uprawnieniami).</p>
    <p><strong>Aktywność użytkowników:</strong> W logach aktywności możesz śledzić wszystkie ważne działania użytkowników w systemie.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Raporty i statystyki</h3>
    <p><strong>Generowanie raportów:</strong> W sekcji "Raporty" możesz wygenerować zestawienia dotyczące przychodów i rezerwacji w wybranym okresie.</p>
    <p><strong>Eksport CSV:</strong> Każdy raport może zostać wyeksportowany do formatu CSV. Kliknij przycisk "Eksportuj CSV" po wygenerowaniu raportu.</p>
    <p><strong>Statystyki:</strong> Panel główny administratora pokazuje kluczowe statystyki: liczba użytkowników, ofert, wynajmów oraz całkowity przychód.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zgłoszenia support i usterki</h3>
    <p><strong>Przeglądanie zgłoszeń:</strong> W sekcji "Zgłoszenia support" znajdziesz wszystkie zgłoszenia od użytkowników.</p>
    <p><strong>Obsługa zgłoszeń:</strong> Możesz przypisywać zgłoszenia do administratorów, zmieniać ich status (otwarte/w toku/zamknięte) oraz odpowiadać użytkownikom.</p>
    <p><strong>Zgłoszenia usterek:</strong> Najemcy mogą zgłaszać usterki w wynajmowanych mieszkaniach przez system zgłoszeń.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Ustawienia serwisu</h3>
    <p><strong>Podstawowe dane:</strong> W sekcji "Ustawienia serwisu" możesz zmienić nazwę serwisu, opis, dane kontaktowe i adres.</p>
    <p><strong>Bezpieczeństwo:</strong> Wszystkie hasła są szyfrowane za pomocą algorytmu bcrypt. Nigdy nie przechowujemy haseł w postaci jawnej.</p>
    <p><strong>Logi aktywności:</strong> System automatycznie rejestruje wszystkie ważne akcje w logach aktywności dostępnych w sekcji "Logi".</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Najczęstsze pytania (FAQ)</h3>
    <p><strong>Jak zmienić hasło administratora?</strong><br>Przejdź do swoich ustawień konta (link w górnym menu), gdzie możesz zmienić hasło i dane osobowe.</p>
    <p><strong>Jak dodać nowego administratora?</strong><br>W sekcji "Zarządzaj użytkownikami" znajdź użytkownika i zmień jego rolę na "admin".</p>
    <p><strong>Co zrobić jeśli system działa wolno?</strong><br>Sprawdź logi aktywności oraz stan bazy danych. Skontaktuj się z zespołem technicznym w razie potrzeby.</p>
    <p><strong>Jak przywrócić usuniętą ofertę?</strong><br>Usunięcia są trwałe. Należy dodać ofertę ponownie ręcznie.</p>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Pomoc techniczna</h3>
    <p class="muted">W razie problemów technicznych lub pytań skontaktuj się z zespołem wsparcia technicznego.</p>
    <p><a class="btn" href="mailto:admin@example.com">Kontakt: admin@example.com</a></p>
  </div>
  
  <div class="form-actions" style="margin-top:18px">
    <a class="btn" href="admin_panel.php">Powrót do panelu</a>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
