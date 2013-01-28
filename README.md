isotope_payu Isotope Extension
==============================

A payment gateway PayU for Isotope eCommerce.

### Compatibility
- Isotope 1.3

### Available languages
- English
- Polish

-----

### Instrukcja

##### Panel PayU

W celu konfiguracji modułu, należy utworzyć w panelu PayU nowy punkt POS (punkt płatności). Podczas wypełniania formularza należy zwrócić uwagę na trzy pola tekstowe:

- **Adres powrotu - błąd:**  
Wprowadzamy adres URL naszego sklepu do podstrony z modułem kasy (checkout) z doklejonym *step/failed.html*, np.
http://www.sklep.pl/kasa/step/failed.html  

- **Adres powrotu - poprawnie:**  
Wprowadzamy adres URL naszego sklepu do podstrony z modułem kasy (checkout) z doklejonym *step/complete.html?uid=%orderId%*, np. http://www.sklep.pl/kasa/step/complete.html?uid=%orderId%

- **Adres raportów:**  
Wprowadzamy adres URL naszego sklepu z doklejonym *system/modules/isotope/postsale.php?id=123&mod=pay*, gdzie 123 to ID metody płatności PayU. Przykładowo: http://www.sklep.pl/system/modules/isotope/postsale.php?id=5&mod=pay

Warto również zaznaczyć opcję "Sprawdzaj poprawność sig-a", która zdecydowanie  zwiększy bezpieczeństwo transakcji.

##### Panel Contao

W edycji metody płatności należy wprowadzić następujące dane, które otrzymaliśmy po utworzeniu punktu POS:

- **PayU POS ID:**  
Tutaj wprowadzamy unikalny identyfikator POS, np. *115611*.

- **Klucz autoryzacji:**  
Tutaj wprowadzamy klucz autoryzacji, np. *vop6RWz*.

- **Klucz MD5 1 i 2:**  
Tutaj wprowadzamy klucze MD5 wygenerowane w panelu PayU, np. *8d0ae969112db83f856a1db0ba41f127* i *01d54ec1e27939a1142ca5553f0ce870*.