<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Booking\Tasting;
use App\Entity\Booking\TastingSlot;
use App\Entity\Catalog\Appellation;
use App\Entity\Catalog\FoodPairing;
use App\Entity\Catalog\GrapeVariety;
use App\Entity\Catalog\Wine;
use App\Entity\Catalog\WineCategory;
use App\Entity\Catalog\WineImage;
use App\Entity\Customer\Review;
use App\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // === GRAPE VARIETIES ===
        $grapeVarieties = $this->createGrapeVarieties($manager);

        // === FOOD PAIRINGS ===
        $foodPairings = $this->createFoodPairings($manager);

        // === APPELLATIONS ===
        $appellations = $this->createAppellations($manager);

        // === WINE CATEGORIES ===
        $categories = $this->createCategories($manager);

        // === WINES ===
        $wines = $this->createWines($manager, $categories, $appellations, $grapeVarieties, $foodPairings);

        // === TASTINGS ===
        $this->createTastings($manager);

        // === USERS ===
        $users = $this->createUsers($manager);

        // === REVIEWS ===
        $this->createReviews($manager, $wines, $users);

        // === FAVORITES ===
        $this->createFavorites($wines, $users);

        $manager->flush();
    }

    /**
     * @return array<string, GrapeVariety>
     */
    private function createGrapeVarieties(ObjectManager $manager): array
    {
        $varieties = [
            'chenin-blanc' => [
                'name' => 'Chenin Blanc',
                'description' => 'Cépage emblématique de la Loire, offrant des vins aux arômes de pomme, coing et miel.',
            ],
            'cabernet-franc' => [
                'name' => 'Cabernet Franc',
                'description' => 'Cépage rouge élégant aux notes de fruits rouges et de violette.',
            ],
            'cabernet-sauvignon' => [
                'name' => 'Cabernet Sauvignon',
                'description' => 'Cépage puissant apportant structure et arômes de cassis.',
            ],
            'grolleau' => [
                'name' => 'Grolleau',
                'description' => 'Cépage ligérien traditionnel, idéal pour les rosés fruités.',
            ],
        ];

        $entities = [];
        foreach ($varieties as $slug => $data) {
            $variety = new GrapeVariety();
            $variety->setName($data['name']);
            $variety->setSlug($slug);
            $variety->setDescription($data['description']);
            $manager->persist($variety);
            $entities[$slug] = $variety;
        }

        return $entities;
    }

    /**
     * @return array<string, FoodPairing>
     */
    private function createFoodPairings(ObjectManager $manager): array
    {
        $pairings = [
            'aperitif'               => ['name' => 'Apéritif',               'icon' => '🥂'],
            'fruits-de-mer'          => ['name' => 'Fruits de mer',           'icon' => '🦐'],
            'poissons-grilles'       => ['name' => 'Poissons grillés',        'icon' => '🐟'],
            'fromages-de-chevre'     => ['name' => 'Fromages de chèvre',      'icon' => '🧀'],
            'salades-composees'      => ['name' => 'Salades composées',       'icon' => '🥗'],
            'grillades'              => ['name' => 'Grillades',               'icon' => '🥩'],
            'cuisine-mediterraneenne'=> ['name' => 'Cuisine méditerranéenne', 'icon' => '🌿'],
            'desserts-fruites'       => ['name' => 'Desserts fruités',        'icon' => '🍓'],
            'cuisine-exotique'       => ['name' => 'Cuisine exotique',        'icon' => '🌶️'],
            'viandes-rouges'         => ['name' => 'Viandes rouges',          'icon' => '🥩'],
            'gibier'                 => ['name' => 'Gibier',                  'icon' => '🦌'],
            'fromages-affines'       => ['name' => 'Fromages affinés',        'icon' => '🧀'],
            'charcuterie'            => ['name' => 'Charcuterie',             'icon' => '🥓'],
            'volailles-roties'       => ['name' => 'Volailles rôties',        'icon' => '🍗'],
            'legumes-grilles'        => ['name' => 'Légumes grillés',         'icon' => '🥦'],
            'desserts'               => ['name' => 'Desserts',                'icon' => '🍰'],
        ];

        $entities = [];
        foreach ($pairings as $slug => $data) {
            $pairing = new FoodPairing();
            $pairing->setName($data['name']);
            $pairing->setSlug($slug);
            $pairing->setIcon($data['icon']);
            $manager->persist($pairing);
            $entities[$slug] = $pairing;
        }

        return $entities;
    }

    /**
     * @return array<string, Appellation>
     */
    private function createAppellations(ObjectManager $manager): array
    {
        $appellations = [
            'anjou-blanc' => [
                'name' => 'Anjou Blanc',
                'region' => 'Val de Loire',
                'description' => 'Appellation produisant des vins blancs secs et élégants.',
            ],
            'anjou-rouge' => [
                'name' => 'Anjou Rouge',
                'region' => 'Val de Loire',
                'description' => 'Appellation de vins rouges fruités et souples.',
            ],
            'rose-de-loire' => [
                'name' => 'Rosé de Loire',
                'region' => 'Val de Loire',
                'description' => 'Appellation de rosés secs et rafraîchissants.',
            ],
            'cabernet-anjou' => [
                'name' => 'Cabernet d\'Anjou',
                'region' => 'Val de Loire',
                'description' => 'Appellation de rosés demi-secs aux arômes de fruits rouges.',
            ],
            'cremant-de-loire' => [
                'name' => 'Crémant de Loire',
                'region' => 'Val de Loire',
                'description' => 'Appellation de vins effervescents élaborés selon la méthode traditionnelle.',
            ],
        ];

        $entities = [];
        foreach ($appellations as $slug => $data) {
            $appellation = new Appellation();
            $appellation->setName($data['name']);
            $appellation->setSlug($slug);
            $appellation->setRegion($data['region']);
            $appellation->setDescription($data['description']);
            $manager->persist($appellation);
            $entities[$slug] = $appellation;
        }

        return $entities;
    }

    /**
     * @return array<string, WineCategory>
     */
    private function createCategories(ObjectManager $manager): array
    {
        $categories = [
            'vins-blancs' => ['name' => 'Vins Blancs', 'description' => 'Nos vins blancs secs et aromatiques'],
            'vins-roses' => ['name' => 'Vins Rosés', 'description' => 'Nos rosés frais et fruités'],
            'vins-rouges' => ['name' => 'Vins Rouges', 'description' => 'Nos vins rouges élégants'],
            'effervescents' => ['name' => 'Effervescents', 'description' => 'Nos bulles festives'],
        ];

        $entities = [];
        $position = 0;
        foreach ($categories as $slug => $data) {
            $category = new WineCategory();
            $category->setName($data['name']);
            $category->setSlug($slug);
            $category->setDescription($data['description']);
            $category->setPosition($position++);
            $manager->persist($category);
            $entities[$slug] = $category;
        }

        return $entities;
    }

    /**
     * @param array<string, WineCategory> $categories
     * @param array<string, Appellation> $appellations
     * @param array<string, GrapeVariety> $grapeVarieties
     * @param array<string, FoodPairing> $foodPairings
     * @return array<string, Wine>
     */
    private function createWines(
        ObjectManager $manager,
        array $categories,
        array $appellations,
        array $grapeVarieties,
        array $foodPairings,
    ): array {
        $wines = [
            [
                'name' => 'Escapade',
                'slug' => 'escapade',
                'vintage' => 2023,
                'priceInCents' => 1250,
                'stock' => 150,
                'alcoholDegree' => '12.5',
                'servingTemperature' => '10-12°C',
                'agingPotential' => '3-5 ans',
                'volumeCl' => 75,
                'category' => 'vins-blancs',
                'appellation' => 'anjou-blanc',
                'grapes' => ['chenin-blanc'],
                'terroir' => 'Sols argilo-calcaires exposés sud-ouest',
                'foodPairings' => ['fruits-de-mer', 'poissons-grilles', 'fromages-de-chevre'],
                'tastingNotes' => "Robe : Or pâle aux reflets verts.\nNez : Agrumes, fleurs blanches, notes minérales.\nBouche : Fraîche et vive, belle longueur.",
                'description' => 'Un blanc sec et élégant, parfait pour l\'apéritif.',
                'isFeatured' => true,
                'image' => 'escapade.jpg',
            ],
            [
                'name' => 'Estival',
                'slug' => 'estival',
                'vintage' => 2023,
                'priceInCents' => 1100,
                'stock' => 200,
                'alcoholDegree' => '12.0',
                'servingTemperature' => '8-10°C',
                'agingPotential' => '1-2 ans',
                'volumeCl' => 75,
                'category' => 'vins-roses',
                'appellation' => 'rose-de-loire',
                'grapes' => ['grolleau', 'cabernet-franc'],
                'terroir' => 'Sols schisteux et sablonneux',
                'foodPairings' => ['salades-composees', 'grillades', 'cuisine-mediterraneenne'],
                'tastingNotes' => "Robe : Rose pâle, reflets saumonés.\nNez : Fruits rouges frais, agrumes.\nBouche : Légère et désaltérante.",
                'description' => 'Le rosé parfait pour les journées ensoleillées.',
                'isFeatured' => true,
                'image' => 'estival.jpg',
            ],
            [
                'name' => 'Escale',
                'slug' => 'escale',
                'vintage' => 2023,
                'priceInCents' => 1150,
                'stock' => 180,
                'alcoholDegree' => '11.5',
                'servingTemperature' => '8-10°C',
                'agingPotential' => '1-2 ans',
                'volumeCl' => 75,
                'category' => 'vins-roses',
                'appellation' => 'cabernet-anjou',
                'grapes' => ['cabernet-franc', 'cabernet-sauvignon'],
                'terroir' => 'Coteaux argilo-calcaires',
                'foodPairings' => ['aperitif', 'desserts-fruites', 'cuisine-exotique'],
                'tastingNotes' => "Robe : Rose soutenu aux reflets framboise.\nNez : Fruits rouges mûrs, bonbon anglais.\nBouche : Ronde et gourmande, finale fraîche.",
                'description' => 'Un rosé demi-sec gourmand et convivial.',
                'isFeatured' => false,
                'image' => 'escale.jpg',
            ],
            [
                'name' => 'L\'Invitée',
                'slug' => 'l-invitee',
                'vintage' => 2022,
                'priceInCents' => 1450,
                'stock' => 120,
                'alcoholDegree' => '13.0',
                'servingTemperature' => '16-18°C',
                'agingPotential' => '5-8 ans',
                'volumeCl' => 75,
                'category' => 'vins-rouges',
                'appellation' => 'anjou-rouge',
                'grapes' => ['cabernet-franc'],
                'terroir' => 'Sols de schistes et grès',
                'foodPairings' => ['viandes-rouges', 'gibier', 'fromages-affines'],
                'tastingNotes' => "Robe : Rubis profond aux reflets violacés.\nNez : Fruits noirs, épices douces, violette.\nBouche : Élégante et structurée, tanins soyeux.",
                'description' => 'Un rouge de caractère pour les grandes occasions.',
                'isFeatured' => true,
                'image' => 'invitee.jpg',
            ],
            [
                'name' => 'Évasion',
                'slug' => 'evasion',
                'vintage' => 2022,
                'priceInCents' => 1350,
                'stock' => 100,
                'alcoholDegree' => '12.5',
                'servingTemperature' => '14-16°C',
                'agingPotential' => '3-5 ans',
                'volumeCl' => 75,
                'category' => 'vins-rouges',
                'appellation' => 'anjou-rouge',
                'grapes' => ['cabernet-franc', 'cabernet-sauvignon'],
                'terroir' => 'Argiles à silex sur socle schisteux',
                'foodPairings' => ['charcuterie', 'volailles-roties', 'legumes-grilles'],
                'tastingNotes' => "Robe : Grenat brillant.\nNez : Fruits rouges, poivre, sous-bois léger.\nBouche : Souple et fruitée, accessible.",
                'description' => 'Un rouge gourmand pour tous les jours.',
                'isFeatured' => false,
                'image' => 'evasion.jpg',
            ],
            [
                'name' => 'Les Festives',
                'slug' => 'les-festives',
                'vintage' => 2022,
                'priceInCents' => 1650,
                'stock' => 80,
                'alcoholDegree' => '12.0',
                'servingTemperature' => '6-8°C',
                'agingPotential' => '2-4 ans',
                'volumeCl' => 75,
                'category' => 'effervescents',
                'appellation' => 'cremant-de-loire',
                'grapes' => ['chenin-blanc'],
                'terroir' => 'Tuffeau et calcaire',
                'foodPairings' => ['aperitif', 'fruits-de-mer', 'desserts'],
                'tastingNotes' => "Robe : Or pâle, fines bulles persistantes.\nNez : Brioche, pomme verte, fleurs blanches.\nBouche : Crémeuse et fraîche, finale citronnée.",
                'description' => 'Des bulles fines pour célébrer chaque instant.',
                'isFeatured' => true,
                'image' => 'festives.jpg',
            ],
        ];

        $wineEntities = [];
        foreach ($wines as $wineData) {
            $wine = new Wine();
            $wine->setName($wineData['name']);
            $wine->setSlug($wineData['slug']);
            $wine->setVintage($wineData['vintage']);
            $wine->setPriceInCents($wineData['priceInCents']);
            $wine->setStock($wineData['stock']);
            $wine->setAlcoholDegree($wineData['alcoholDegree']);
            $wine->setServingTemperature($wineData['servingTemperature']);
            $wine->setAgingPotential($wineData['agingPotential']);
            $wine->setVolumeCl($wineData['volumeCl']);
            $wine->setTerroir($wineData['terroir']);
            $wine->setTastingNotes($wineData['tastingNotes']);
            $wine->setDescription($wineData['description']);
            $wine->setIsActive(true);
            $wine->setIsFeatured($wineData['isFeatured']);
            $wine->setCategory($categories[$wineData['category']]);
            $wine->setAppellation($appellations[$wineData['appellation']]);

            foreach ($wineData['grapes'] as $grapeSlug) {
                $wine->addGrapeVariety($grapeVarieties[$grapeSlug]);
            }

            foreach ($wineData['foodPairings'] as $pairingSlug) {
                $wine->addFoodPairing($foodPairings[$pairingSlug]);
            }

            // Create wine image
            $image = new WineImage();
            $image->setFilename($wineData['image']);
            $image->setAltText($wineData['name'] . ' - Château de Belleville');
            $image->setPosition(0);
            $image->setIsMain(true);
            $image->setWine($wine);
            $manager->persist($image);

            $manager->persist($wine);
            $wineEntities[$wineData['slug']] = $wine;
        }

        return $wineEntities;
    }

    private function createTastings(ObjectManager $manager): void
    {
        $tastings = [
            [
                'name' => 'Découverte',
                'slug' => 'decouverte',
                'description' => 'Une initiation parfaite à nos vins. Visite guidée des vignes et de la cave, suivie d\'une dégustation de 3 cuvées.',
                'priceInCents' => 1500,
                'durationMinutes' => 60,
                'maxParticipants' => 12,
                'includedItems' => ['Visite des vignes', 'Visite de la cave', 'Dégustation de 3 vins'],
            ],
            [
                'name' => 'Prestige',
                'slug' => 'prestige',
                'description' => 'Une expérience approfondie avec dégustation de 5 cuvées accompagnées de produits locaux.',
                'priceInCents' => 2500,
                'durationMinutes' => 90,
                'maxParticipants' => 8,
                'includedItems' => ['Visite des vignes', 'Visite de la cave', 'Dégustation de 5 vins', 'Planche de fromages et charcuterie'],
            ],
            [
                'name' => 'Exception',
                'slug' => 'exception',
                'description' => 'Notre formule la plus complète. Visite privée, dégustation de notre gamme complète et déjeuner au domaine.',
                'priceInCents' => 5500,
                'durationMinutes' => 180,
                'maxParticipants' => 6,
                'includedItems' => ['Visite privée', 'Dégustation de 7 vins', 'Déjeuner gastronomique', 'Coffret souvenir'],
            ],
        ];

        foreach ($tastings as $tastingData) {
            $tasting = new Tasting();
            $tasting->setName($tastingData['name']);
            $tasting->setSlug($tastingData['slug']);
            $tasting->setDescription($tastingData['description']);
            $tasting->setPriceInCents($tastingData['priceInCents']);
            $tasting->setDurationMinutes($tastingData['durationMinutes']);
            $tasting->setMaxParticipants($tastingData['maxParticipants']);
            $tasting->setIncludedItems($tastingData['includedItems']);
            $tasting->setIsActive(true);

            // Create sample slots for the next 30 days
            $startDate = new \DateTime('tomorrow');
            for ($i = 0; $i < 30; $i++) {
                $date = (clone $startDate)->modify("+{$i} days");

                // Skip Sundays
                if ($date->format('N') === '7') {
                    continue;
                }

                // Morning slot at 10:00
                $morningSlot = new TastingSlot();
                $morningSlot->setTasting($tasting);
                $morningSlot->setDate($date);
                $morningSlot->setStartTime((clone $date)->setTime(10, 0));
                $morningSlot->setAvailableSpots($tastingData['maxParticipants']);
                $morningSlot->setIsAvailable(true);
                $manager->persist($morningSlot);

                // Afternoon slot at 15:00
                $afternoonSlot = new TastingSlot();
                $afternoonSlot->setTasting($tasting);
                $afternoonSlot->setDate($date);
                $afternoonSlot->setStartTime((clone $date)->setTime(15, 0));
                $afternoonSlot->setAvailableSpots($tastingData['maxParticipants']);
                $afternoonSlot->setIsAvailable(true);
                $manager->persist($afternoonSlot);
            }

            $manager->persist($tasting);
        }
    }

    /**
     * @return array<string, User>
     */
    private function createUsers(ObjectManager $manager): array
    {
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@chateau-belleville.fr');
        $admin->setFirstName('Admin');
        $admin->setLastName('Belleville');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // Test customer
        $customer = new User();
        $customer->setEmail('client@example.com');
        $customer->setFirstName('Jean');
        $customer->setLastName('Dupont');
        $customer->setPhone('06 12 34 56 78');
        $customer->setRoles(['ROLE_USER']);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, 'client123'));
        $customer->setIsVerified(true);
        $manager->persist($customer);

        // Additional reviewer
        $reviewer = new User();
        $reviewer->setEmail('marie@example.com');
        $reviewer->setFirstName('Marie');
        $reviewer->setLastName('Laurent');
        $reviewer->setRoles(['ROLE_USER']);
        $reviewer->setPassword($this->passwordHasher->hashPassword($reviewer, 'marie123'));
        $reviewer->setIsVerified(true);
        $manager->persist($reviewer);

        return [
            'admin' => $admin,
            'client' => $customer,
            'reviewer' => $reviewer,
        ];
    }

    /**
     * @param array<string, Wine> $wines
     * @param array<string, User> $users
     */
    private function createReviews(ObjectManager $manager, array $wines, array $users): void
    {
        $reviewsData = [
            // Escapade - 2 avis approuvés
            [
                'wine' => 'escapade',
                'user' => 'client',
                'rating' => 5,
                'title' => 'Un blanc exceptionnel',
                'content' => 'Frais, minéral, avec une belle longueur en bouche. Parfait avec des huîtres !',
                'isApproved' => true,
            ],
            [
                'wine' => 'escapade',
                'user' => 'reviewer',
                'rating' => 4,
                'title' => 'Très agréable',
                'content' => 'Un chenin bien travaillé, belle expression du terroir angevin.',
                'isApproved' => true,
            ],
            // L'Invitée - 3 avis (2 approuvés, 1 en attente)
            [
                'wine' => 'l-invitee',
                'user' => 'client',
                'rating' => 5,
                'title' => 'Coup de coeur',
                'content' => 'Des tanins soyeux, une finale élégante. Ce cabernet franc est magnifique.',
                'isApproved' => true,
            ],
            [
                'wine' => 'l-invitee',
                'user' => 'reviewer',
                'rating' => 4,
                'title' => null,
                'content' => 'Très bon rouge, idéal avec un plateau de fromages.',
                'isApproved' => true,
            ],
            [
                'wine' => 'l-invitee',
                'user' => 'admin',
                'rating' => 5,
                'title' => 'Notre fierté',
                'content' => null,
                'isApproved' => false,
            ],
            // Les Festives - 2 avis approuvés
            [
                'wine' => 'les-festives',
                'user' => 'reviewer',
                'rating' => 5,
                'title' => 'Bulles parfaites',
                'content' => 'Fines bulles, arômes de brioche et de pomme verte. Meilleur que certains champagnes !',
                'isApproved' => true,
            ],
            [
                'wine' => 'les-festives',
                'user' => 'client',
                'rating' => 4,
                'title' => null,
                'content' => 'Excellent crémant pour les fêtes.',
                'isApproved' => true,
            ],
            // Estival - 1 avis approuvé
            [
                'wine' => 'estival',
                'user' => 'reviewer',
                'rating' => 4,
                'title' => 'Le rosé de l\'été',
                'content' => 'Frais et fruité, parfait pour les grillades en terrasse.',
                'isApproved' => true,
            ],
            // Évasion - 1 avis approuvé
            [
                'wine' => 'evasion',
                'user' => 'client',
                'rating' => 3,
                'title' => 'Correct',
                'content' => 'Un rouge honnête pour le quotidien, bon rapport qualité-prix.',
                'isApproved' => true,
            ],
        ];

        foreach ($reviewsData as $data) {
            $review = new Review();
            $review->setWine($wines[$data['wine']]);
            $review->setUser($users[$data['user']]);
            $review->setRating($data['rating']);
            $review->setTitle($data['title']);
            $review->setContent($data['content']);
            $review->setIsApproved($data['isApproved']);
            $manager->persist($review);
        }
    }

    /**
     * @param array<string, Wine> $wines
     * @param array<string, User> $users
     */
    private function createFavorites(array $wines, array $users): void
    {
        $users['client']->addFavoriteWine($wines['escapade']);
        $users['client']->addFavoriteWine($wines['l-invitee']);
        $users['client']->addFavoriteWine($wines['les-festives']);
    }
}