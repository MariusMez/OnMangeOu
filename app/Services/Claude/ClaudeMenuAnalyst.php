<?php

namespace App\Services\Claude;

use Anthropic\Beta\Messages\BetaBase64ImageSource;
use Anthropic\Beta\Messages\BetaImageBlockParam;
use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Beta\Messages\BetaTextBlockParam;
use Anthropic\Client;
use App\Claude\MenuSchemas;
use App\Contracts\MenuAnalyst;
use App\Exceptions\MenuAnalysisException;
use App\Models\Analysis;

/**
 * Lecture et optimisation de cartes avec Claude : vision + sorties structurées.
 * Le repli serveur automatique (`fallbacks: default`) relance la requête sur
 * un autre modèle si le premier refuse.
 */
class ClaudeMenuAnalyst implements MenuAnalyst
{
    private const int MAX_TOKENS = 16000;

    private const array BETAS = ['server-side-fallback-2026-07-01'];

    private const string EXTRACTION_SYSTEM = <<<'PROMPT'
    Tu es un expert en lecture de cartes de restaurant.
    On te donne une ou plusieurs photos d'une même carte (pages, recto/verso, ardoise).
    Transcris fidèlement TOUS les plats, dans l'ordre d'apparition, sans rien inventer.

    Règles :
    - Conserve l'orthographe originale dans "name" et "description" (les fautes sont relevées à part dans "spelling_issues").
    - "price" est un nombre décimal (14,50 € → 14.5). Pour un prix double (ex: "12 / 18") prends le premier et garde le texte complet dans "price_text". Si illisible : null.
    - "category" : entree, plat, dessert, boisson, menu (formules / menus complets), autre (accompagnements, suppléments, enfants…).
    - "is_highlighted" : true seulement si la carte met visiblement le plat en avant (encadré, étoile, "suggestion", "plat du jour", "spécialité").
    - Si une rubrique n'a pas de titre, nomme-la d'après son contenu (ex: "Plats").
    - Déduis "cuisine_type" du contenu (bistrot, pizzeria, brasserie, gastronomique, asiatique…).
    - Signale dans "reading_notes" les zones illisibles ou les doutes, sinon null.
    PROMPT;

    private const string OPTIMIZATION_SYSTEM = <<<'PROMPT'
    Tu es consultant en ingénierie de menu pour restaurants indépendants, spécialisé dans l'augmentation du ticket moyen et de la marge.
    Tu reçois la carte extraite d'un restaurant (JSON), une analyse chiffrée selon les principes d'Omnes et le contexte du restaurateur.
    Produis un diagnostic et une carte optimisée, en français, concrets et actionnables.

    Principes d'Omnes (rappel) :
    1. Ouverture de gamme : prix max / prix min d'une rubrique ≤ 2,5 (≤ 3 si plus de 9 plats).
    2. Dispersion : en découpant la gamme en 3 zones de prix égales, la zone médiane doit contenir au moins autant de plats que les deux autres réunies.
    3. Rapport prix demandé / prix offert entre 0,9 et 1 (nécessite les ventes ; sinon, resserrer l'offre autour du prix moyen).
    4. Promotion : les plats mis en avant (suggestion, plat du jour, menu) doivent être dans la zone médiane.

    Psychologie de la carte :
    - Pas de symbole monétaire ni de colonne de prix alignée ; prix écrit à la suite de la description.
    - 5 à 7 plats par rubrique maximum ; les positions de tête et de fin sont les plus lues.
    - Un seul encadré / mise en avant par page, réservé à un plat à forte marge dans la zone médiane.
    - Descriptions sensorielles courtes (origine, technique, texture) qui justifient le prix ; nommer les produits (race, terroir, maison).
    - Ancrage : un plat cher bien placé rend les autres abordables, mais hors liste s'il écrase la gamme (encart « à partager »).
    - Les plats à forte marge et faible popularité (énigmes) gagnent une meilleure description ou position ; les poids morts sont retirés ou repositionnés.

    Contraintes de sortie :
    - "corrections" reprend et complète les fautes relevées.
    - "menu_engineering" : estime marge (coût matière probable vs prix) et popularité (plat classique attendu vs plat de niche) pour les plats principaux, au moins 5 plats si possible.
    - "pricing_recommendations" : uniquement des changements justifiés (Omnes, cohérence, marge), rester réaliste (±15 % max), arrondis commerciaux (x,50 / x,90 / entier).
    - "optimized_menu" : la carte complète réécrite (tous les plats conservés sauf retrait explicitement recommandé), ordre optimisé, nouvelles descriptions, prix recommandés appliqués, 1 à 2 "highlight" par rubrique maximum.
    - Reste fidèle au style du restaurant (un bistrot ne devient pas gastronomique). Si les photos étaient partiellement illisibles, ne complète pas avec des plats inventés.
    PROMPT;

    public function __construct(
        private readonly Client $client,
        private readonly string $model,
    ) {}

    public function extract(array $images): array
    {
        if ($images === []) {
            throw new MenuAnalysisException('Aucune photo exploitable pour cette analyse.');
        }

        $content = [];
        foreach ($images as $binary) {
            $content[] = BetaImageBlockParam::with(
                source: BetaBase64ImageSource::with(data: base64_encode($binary), mediaType: 'image/jpeg'),
            );
        }
        $content[] = BetaTextBlockParam::with(
            text: sprintf('Voici %d photo(s) de la carte. Transcris-la intégralement au format demandé.', count($images)),
        );

        $message = $this->client->beta->messages->create(
            maxTokens: self::MAX_TOKENS,
            messages: [['role' => 'user', 'content' => $content]],
            model: $this->model,
            fallbacks: 'default',
            outputConfig: [
                'effort' => 'medium',
                'format' => ['type' => 'json_schema', 'schema' => MenuSchemas::extractedMenu()],
            ],
            system: self::EXTRACTION_SYSTEM,
            betas: self::BETAS,
        );

        $menu = $this->decode($message, 'la lecture de la carte');

        if (! is_array($menu['sections'] ?? null)) {
            throw new MenuAnalysisException('Réponse du modèle inexploitable lors de la lecture de la carte.');
        }

        return $menu;
    }

    public function optimize(array $menu, array $omnes, Analysis $analysis): array
    {
        $context = array_filter([
            'Restaurant : '.$analysis->restaurant_name,
            'Ville : '.$analysis->city,
            'Type déclaré : '.$analysis->restaurant_type,
            $analysis->goal ? 'Objectif du restaurateur : '.$analysis->goal : null,
        ]);

        $message = $this->client->beta->messages->create(
            maxTokens: self::MAX_TOKENS,
            messages: [[
                'role' => 'user',
                'content' => [
                    BetaTextBlockParam::with(text: "## Contexte\n".implode("\n", $context)),
                    BetaTextBlockParam::with(text: "## Carte extraite (JSON)\n".self::json($menu)),
                    BetaTextBlockParam::with(text: "## Analyse Omnes (JSON)\n".self::json($omnes)."\n\nRédige le diagnostic et la carte optimisée au format demandé."),
                ],
            ]],
            model: $this->model,
            fallbacks: 'default',
            outputConfig: [
                'effort' => 'high',
                'format' => ['type' => 'json_schema', 'schema' => MenuSchemas::optimization()],
            ],
            system: [
                BetaTextBlockParam::with(text: self::OPTIMIZATION_SYSTEM, cacheControl: ['type' => 'ephemeral']),
            ],
            betas: self::BETAS,
        );

        $optimization = $this->decode($message, "l'optimisation");

        if (! is_array($optimization['optimized_menu'] ?? null)) {
            throw new MenuAnalysisException("Réponse du modèle inexploitable lors de l'optimisation.");
        }

        return $optimization;
    }

    /** @return array<string, mixed> */
    private function decode(BetaMessage $message, string $step): array
    {
        if ($message->stopReason === 'refusal') {
            $category = $message->stopDetails?->category ?? 'raison inconnue';

            throw new MenuAnalysisException(sprintf('Le modèle a refusé %s (%s).', $step, $category));
        }

        if ($message->stopReason === 'max_tokens') {
            throw new MenuAnalysisException(sprintf('La réponse a été tronquée pendant %s : réessayez avec une carte plus courte.', $step));
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $decoded = json_decode($block->text, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        throw new MenuAnalysisException(sprintf('Réponse du modèle inexploitable pendant %s.', $step));
    }

    /** @param  array<string, mixed>  $data */
    private static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
