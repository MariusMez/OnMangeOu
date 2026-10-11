<?php

namespace App\Ai\Agents;

use App\Ai\Schemas\MenuSchemas;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\CacheInstructions;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Consultant en ingénierie de menu : diagnostic et carte optimisée à partir de la
 * carte extraite, du rapport d'Omnes et du contexte du restaurateur.
 */
#[Provider(Lab::Anthropic)]
#[MaxTokens(32000)]
#[Timeout(900)]
#[CacheInstructions]
class MenuOptimizer implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
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
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return MenuSchemas::optimization($schema);
    }

    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        return [
            'fallbacks' => 'default',
            'output_config' => ['effort' => 'medium'],
        ];
    }
}
