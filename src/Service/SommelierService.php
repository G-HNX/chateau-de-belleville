<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Catalog\WineRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service du sommelier virtuel propulsé par l'IA (Google Gemini).
 *
 * Fournit un chatbot conversationnel qui recommande les vins du domaine,
 * donne des conseils d'accords mets-vins et répond aux questions sur le domaine.
 * Le catalogue complet des vins actifs est injecté dans le prompt système.
 */
class SommelierService
{
    /** URL de l'API Google Gemini (generateContent) */
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly WineRepository $wineRepository,
        private readonly LoggerInterface $logger,
        private readonly string $sommelierApiKey,
        private readonly string $sommelierModel,
    ) {}

    /**
     * Envoie un message au sommelier IA et retourne sa réponse.
     *
     * @param string $userMessage      Le message de l'utilisateur
     * @param array  $conversationHistory Historique de la conversation (rôle + contenu)
     */
    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        // Chargement du catalogue des vins actifs pour le contexte du prompt
        $wines = $this->wineRepository->findBy(['isActive' => true]);

        $catalogContext = $this->buildCatalogContext($wines);

        $systemPrompt = <<<PROMPT
Tu es le sommelier virtuel du Château de Belleville, un domaine viticole familial situé en Anjou (Val de Loire), à Sainte-Verge (79100).

Tu es chaleureux, passionné et expert en vin. Tu tutoies pas les clients. Tu réponds toujours en français.

Voici le catalogue actuel des vins du domaine :
{$catalogContext}

Informations sur le domaine :
- Certifié Terra Vitis et Haute Valeur Environnementale
- Cépages principaux : Cabernet Franc, Chenin Blanc, Grolleau
- Terroir : sols argilo-calcaires, exposition sud
- Le domaine propose aussi des dégustations : Découverte (15€, 1h, 3 vins), Prestige (25€, 1h30, 5 vins + fromages), Exception (55€, 3h, 7 vins + déjeuner)
- Adresse : 36 rue de la Garde, 79100 Sainte-Verge
- Horaires : Vendredi 10h-12h / 15h-18h30, autres jours sur rendez-vous
- Livraison gratuite au-dessus de 150€

Règles :
- Recommande UNIQUEMENT les vins du catalogue ci-dessus, jamais d'autres.
- Si un vin est en rupture de stock (stock 0), mentionne-le mais suggère une alternative.
- Donne des conseils d'accords mets-vins basés sur les foodPairings du catalogue.
- Sois concis mais informatif (2-4 phrases max par réponse).
- Si la question ne concerne pas le vin ou le domaine, redirige poliment vers le sujet.
PROMPT;

        // Construction de l'historique de conversation au format attendu par Gemini
        $messages = [];

        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : $msg['role'],
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $messages[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $url = sprintf(self::GEMINI_API_URL, $this->sommelierModel);

        // Appel à l'API Google Gemini avec le prompt système et l'historique
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'x-goog-api-key' => $this->sommelierApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $messages,
                    'generationConfig' => [
                        'maxOutputTokens' => 300,
                    ],
                ],
            ]);

            $data = $response->toArray();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolé, je n\'ai pas pu formuler une réponse. Réessayez !';
        } catch (\Exception $e) {
            $this->logger->error('Sommelier API error.', ['error' => $e->getMessage()]);

            return 'Notre sommelier est momentanément indisponible. N\'hésitez pas à explorer notre catalogue de vins ou à nous contacter directement !';
        }
    }

    /**
     * Construit le contexte textuel du catalogue pour le prompt système.
     * Chaque vin est formaté sur une ligne avec ses caractéristiques principales.
     */
    private function buildCatalogContext(array $wines): string
    {
        if (empty($wines)) {
            return 'Aucun vin disponible actuellement.';
        }

        $lines = [];
        foreach ($wines as $wine) {
            $grapes = $wine->getGrapeVarietiesAsString();
            $pairings = $wine->getFoodPairings()->isEmpty() ? 'Non précisé' : $wine->getFoodPairingsAsString();
            $tastingNotes = $wine->getTastingNotes();
            $notesStr = $tastingNotes ? 'Notes: ' . $tastingNotes : '';

            $line = sprintf(
                '- %s (%s%s) : %.2f€ | Cépages: %s | Accords: %s | %s°, servir à %s | Stock: %d%s',
                $wine->getName(),
                $wine->getCategory()?->getName() ?? 'Non précisé',
                $wine->getVintage() ? ', ' . $wine->getVintage() : '',
                $wine->getPrice(),
                $grapes ?: 'Non précisé',
                $pairings,
                $wine->getAlcoholDegree() ?? '?',
                $wine->getServingTemperature() ?? 'Non précisé',
                $wine->getStock(),
                $notesStr ? ' | ' . $notesStr : '',
            );
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
