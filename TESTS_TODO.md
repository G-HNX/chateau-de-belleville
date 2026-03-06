# Tests critiques à écrire

Liste priorisée des tests manquants, classés par criticité métier.

---

## CRITIQUE — Flux de commande

### `CheckoutFlowTest` (fonctionnel, `WebTestCase`)
- [ ] Panier vide → redirect vers panier avec flash warning
- [ ] Client connecté avec panier → affichage formulaire checkout
- [ ] Invité avec panier → affichage formulaire checkout (champs email/prénom/nom/tél)
- [ ] Soumission checkout valide → création commande + redirect vers paiement
- [ ] Soumission checkout → âge < 18 → formulaire rechargé avec erreur
- [ ] Soumission checkout → stock insuffisant → redirect panier avec erreur
- [ ] Double soumission simultanée → une seule commande créée (PESSIMISTIC_WRITE)

### `WebhookStripeTest` (fonctionnel, `WebTestCase`)
- [ ] `payment_intent.succeeded` avec signature valide → commande PAID
- [ ] `payment_intent.succeeded` avec signature invalide → 400
- [ ] `payment_intent.succeeded` répété (idempotence) → commande non re-payée
- [ ] `payment_intent.succeeded` pour commande inexistante → 200 (silencieux)
- [ ] Commande déjà payée → log "already paid" sans double transition

---

## HAUTE — Sécurité & ownership

### `OrderOwnershipTest` (fonctionnel, `WebTestCase`)
- [ ] Client A ne peut pas voir la commande du client B (`/compte/commandes/{ref}`)
- [ ] Invité avec token de session peut voir SA commande `/paiement/{ref}`
- [ ] Invité sans token ne peut pas accéder à `/paiement/{ref}` → 403

### `AddressOwnershipTest` (fonctionnel, `WebTestCase`)
- [ ] Client A ne peut pas modifier l'adresse du client B → 403
- [ ] Client A ne peut pas supprimer l'adresse du client B → 403
- [ ] Token CSRF invalide sur suppression adresse → 403

### `AdminAccessTest` (fonctionnel, `WebTestCase`)
- [ ] `/admin` inaccessible sans authentification → redirect login
- [ ] `/admin` inaccessible avec `ROLE_USER` → 403
- [ ] `/admin` accessible avec `ROLE_ADMIN` → 200

---

## HAUTE — Stock & concurrence

### `StockRaceConditionTest` (unitaire + fonctionnel)
- [ ] `CartService::addWine()` — quantité > stock → erreur retournée
- [ ] `CartService::addWine()` — vin non disponible → erreur retournée
- [ ] `CartService::addWine()` — ajout OK → item dans panier
- [ ] `OrderService::createOrderFromCart()` — stock insuffisant au checkout → RuntimeException
- [ ] `Wine::setPriceInCents()` — prix négatif → InvalidArgumentException
- [ ] `Wine::setPrice()` — prix négatif → InvalidArgumentException

---

## HAUTE — Réservations

### `ReservationServiceTest` (unitaire, `MockObject`)
- [ ] `createReservation()` — créneau complet → message d'erreur retourné
- [ ] `createReservation()` — créneau OK → snapshot prix enregistré
- [ ] `createReservation()` — snapshot = `$slot->getTasting()->getPriceInCents()`
- [ ] `createReservation()` — email service lève TransportException → pas d'exception propagée
- [ ] `Reservation::getTotalPrice()` — utilise `pricePerPersonInCents` (pas le slot courant)

### `ReservationFullFlowTest` (fonctionnel)
- [ ] Formulaire réservation → soumission valide → réservation créée en BDD
- [ ] Champ `price_per_person_in_cents` renseigné après création
- [ ] Créneau complet → formulaire rechargé avec erreur

---

## MOYENNE — Formulaires & validation

### `WineValidationTest` (unitaire)
- [ ] Prix négatif via setter → exception
- [ ] `hasEnoughStock(0)` → true (stock OK pour quantité nulle)
- [ ] `hasEnoughStock(stock + 1)` → false
- [ ] `isAvailable()` → false si stock = 0

### `CartServiceTest` (unitaire, `MockObject`)
- [ ] `getCart(null)` → recherche par sessionId
- [ ] `getCart($user)` → recherche par user
- [ ] `updateItemQuantity()` — quantité > stock → erreur
- [ ] `removeItem()` — supprime l'item du panier

---

## MOYENNE — Rate limiting & sécurité

### `RateLimitRegistrationTest` (fonctionnel)
- [ ] 5 soumissions d'inscription → 5e passe
- [ ] 6e soumission → flash error "Trop de tentatives"

### `LoginThrottlingTest` (fonctionnel)
- [ ] 5 tentatives de connexion échouées → 6e bloquée

---

## FAIBLE — Emails

### `EmailServiceTest` (unitaire, `MailerInterface` mocké)
- [ ] `sendPaymentConfirmation()` → email envoyé avec la bonne référence
- [ ] `sendOrderShipped()` → email envoyé avec numéro de suivi
- [ ] `sendReservationConfirmation()` → email envoyé au bon destinataire

---

## Commandes utiles

```bash
# Lancer tous les tests
php bin/phpunit

# Lancer un groupe spécifique
php bin/phpunit tests/Service/
php bin/phpunit tests/Controller/

# Couverture (nécessite Xdebug)
php bin/phpunit --coverage-html var/coverage
```

## Structure suggérée

```
tests/
├── Controller/
│   ├── CheckoutFlowTest.php
│   ├── WebhookStripeTest.php
│   ├── OrderOwnershipTest.php
│   ├── AddressOwnershipTest.php
│   ├── AdminAccessTest.php
│   ├── RateLimitRegistrationTest.php
│   └── LoginThrottlingTest.php
├── Service/
│   ├── CartServiceTest.php
│   ├── OrderServiceTest.php
│   ├── ReservationServiceTest.php
│   └── EmailServiceTest.php
└── Entity/
    ├── WineValidationTest.php
    └── ReservationTest.php
```
