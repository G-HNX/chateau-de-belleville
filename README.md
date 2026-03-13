# Château de Belleville

Plateforme e-commerce pour le Château de Belleville, domaine viticole familial situé en Anjou (Val de Loire).
Vente de vins en ligne et réservation de dégustations.

## Stack technique

- **Backend** : PHP 8.3+, Symfony 7.2, Doctrine ORM
- **Frontend** : Tailwind CSS v4, Stimulus, Turbo, UX Live Components
- **Base de données** : SQLite (dev), MariaDB (prod)
- **Paiement** : Stripe (PaymentIntent + Webhooks)
- **IA** : Sommelier virtuel (Google Gemini API)
- **Emails** : Symfony Mailer (templates Twig)
- **PDF** : Factures et bons de commande (Dompdf)

## Installation

```bash
# Cloner le projet
git clone <url> && cd chateau-de-belleville

# Installer les dépendances
composer install

# Configurer les variables d'environnement
cp .env.local.dist .env.local
# Remplir les valeurs dans .env.local

# Créer la base de données et charger les fixtures
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Compiler les assets
php bin/console tailwind:build

# Lancer le serveur de développement
symfony serve
```

## Comptes de test (fixtures)

| Rôle   | Email                          | Mot de passe |
|--------|--------------------------------|--------------|
| Admin  | gabriel.heneaux@gmail.com      | admin123     |
| Client | client@example.com             | client123    |

## Variables d'environnement

Voir [`.env.local.dist`](.env.local.dist) pour la liste complète. Les principales :

| Variable               | Description                          |
|------------------------|--------------------------------------|
| `APP_SECRET`           | Clé secrète Symfony                  |
| `DATABASE_URL`         | Connexion base de données            |
| `MAILER_DSN`           | Configuration SMTP                   |
| `STRIPE_SECRET_KEY`    | Clé secrète Stripe                   |
| `STRIPE_PUBLIC_KEY`    | Clé publique Stripe                  |
| `STRIPE_WEBHOOK_SECRET`| Secret du webhook Stripe             |
| `SOMMELIER_AI_API_KEY` | Clé Google AI Studio (Gemini)        |
| `SOMMELIER_AI_MODEL`   | Modèle Gemini (gemini-2.0-flash)     |

## Commandes utiles

```bash
# Lancer les tests
php bin/phpunit

# Compiler Tailwind (watch)
php bin/console tailwind:build --watch

# Recharger les fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

## Déploiement (O2switch)

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console asset-map:compile
php bin/console tailwind:build --minify
php bin/console cache:clear --env=prod
```

Le `DocumentRoot` doit pointer vers le dossier `public/`.
Activer Let's Encrypt via cPanel pour le HTTPS, puis décommenter la ligne HSTS dans `public/.htaccess`.

## Architecture

```
src/
├── Controller/         # 15 controllers (+ EasyAdmin CRUDs)
├── Entity/
│   ├── Booking/        # Tasting, TastingSlot, Reservation
│   ├── Catalog/        # Wine, WineImage, WineCategory, DomainPhoto
│   ├── Customer/       # Address, Cart, CartItem, Review
│   ├── Order/          # Order, OrderItem
│   └── User/           # User
├── Enum/               # OrderStatus, ReservationStatus, WineType
├── Service/            # CartService, OrderService, ReservationService,
│                       # StripeService, SommelierService, EmailService
├── EventSubscriber/    # CacheHeaders, TwoFactorRateLimit
└── Repository/         # Repositories Doctrine
```

## Tests

102 tests, 188 assertions (unitaires + fonctionnels) couvrant :
- Entités et logique métier
- Services (panier, commandes, réservations, emails)
- Controllers (pages publiques, authentification, admin, webhooks, ownership)

## Fonctionnalités

- **Boutique** : catalogue de vins avec filtres (type, prix), fiches produit, panier, checkout Stripe
- **Dégustations** : 3 formules (Découverte, Prestige, Exception), réservation en ligne avec paiement
- **Sommelier IA** : chatbot contextuel (Google Gemini) avec catalogue dynamique
- **Espace client** : commandes, réservations, adresses, favoris, profil
- **Administration** : EasyAdmin avec dashboard, gestion complète (vins, commandes, réservations, newsletter)
- **Emails** : confirmation de commande/réservation, vérification email, reset password, contact, newsletter
- **SEO** : sitemap XML dynamique, Open Graph, Twitter Cards, canonical URLs
- **PWA** : manifest.json, icônes, theme-color
- **Sécurité** : 2FA admin, rate limiting, CSRF, pessimistic locks, headers sécurité, sanitisation HTML
- **PDF** : factures et bons de commande

## Branches

- `main` — branche principale (production)
- `dev` — branche de développement

## Informations légales

- **Raison sociale** : EARL Maison Baron
- **SIRET** : 329 416 978 000 24
- **TVA** : FR08 329416978
- **Adresse** : 36 rue de la Garde, 79100 Sainte-Verge

## Auteur

**Gabriel Héneaux** — [gabriel-heneaux.fr](https://gabriel-heneaux.fr)
