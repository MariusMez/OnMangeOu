<?php

namespace App\Claude;

use App\Enums\DishCategory;
use App\Enums\MenuEngineeringClass;

/**
 * Schémas JSON des sorties structurées demandées à Claude.
 * Toutes les propriétés sont requises ; les champs facultatifs sont nullables,
 * conformément aux contraintes des sorties structurées de l'API.
 */
final class MenuSchemas
{
    public const array PRIORITIES = ['haute', 'moyenne', 'basse'];

    public const array LEVELS = ['faible', 'moyenne', 'forte'];

    /** @return array<string, mixed> */
    public static function extractedMenu(): array
    {
        $item = self::object([
            'name' => self::string("Nom du plat tel qu'écrit sur la carte"),
            'description' => self::nullable(self::string("Description ou liste d'ingrédients si présente, sinon null")),
            'price' => self::nullable(self::number('Prix numérique en unité monétaire (14,50 € → 14.5). null si absent ou illisible')),
            'price_text' => self::nullable(self::string("Prix tel qu'écrit (ex: '14,50 €', '12/18'). null si absent")),
            'category' => self::enum(DishCategory::values(), 'Catégorie fonctionnelle du plat'),
            'tags' => self::array(self::string(), 'Mentions : végétarien, épicé, fait maison, suggestion, nouveau…'),
            'is_highlighted' => self::boolean("true si le plat est mis en avant (encadré, 'suggestion du chef', 'plat du jour'…)"),
        ]);

        $section = self::object([
            'name' => self::string("Titre de la rubrique tel qu'écrit sur la carte"),
            'items' => self::array($item),
        ]);

        return self::object([
            'restaurant_name' => self::nullable(self::string()),
            'cuisine_type' => self::nullable(self::string('Type de cuisine déduit de la carte (ex: bistrot, pizzeria, gastronomique)')),
            'currency' => self::string('Code devise ISO, EUR par défaut'),
            'language' => self::string('Langue principale de la carte, code ISO (fr, en…)'),
            'sections' => self::array($section),
            'spelling_issues' => self::array(self::correction(), "Fautes d'orthographe, de typographie ou d'accord relevées"),
            'reading_notes' => self::nullable(self::string('Difficultés de lecture : zones floues, prix illisibles…')),
        ]);
    }

    /** @return array<string, mixed> */
    public static function optimization(): array
    {
        return self::object([
            'summary' => self::string("Diagnostic global en 3 à 5 phrases, orienté chiffre d'affaires"),
            'overall_score' => self::number('Note globale de la carte actuelle de 0 à 100'),
            'strengths' => self::array(self::string(), 'Points forts de la carte actuelle'),
            'corrections' => self::array(self::correction(), 'Corrections orthographiques et typographiques'),
            'description_rewrites' => self::array(self::object([
                'section' => self::string(),
                'original_name' => self::string(),
                'new_name' => self::string(),
                'new_description' => self::string(),
                'rationale' => self::string(),
            ]), 'Réécritures de noms et descriptions pour donner envie et justifier le prix'),
            'layout_recommendations' => self::array(self::object([
                'title' => self::string(),
                'detail' => self::string(),
                'priority' => self::enum(self::PRIORITIES),
            ]), 'Recommandations de structure et de mise en page'),
            'pricing_recommendations' => self::array(self::object([
                'item' => self::string(),
                'current_price' => self::nullable(self::number()),
                'suggested_price' => self::nullable(self::number()),
                'rationale' => self::string(),
            ]), "Ajustements de prix, y compris pour corriger les écarts aux principes d'Omnes"),
            'menu_engineering' => self::array(self::object([
                'item' => self::string(),
                'classification' => self::enum(MenuEngineeringClass::values()),
                'estimated_margin' => self::enum(self::LEVELS, "Marge estimée d'après les ingrédients et le prix"),
                'estimated_popularity' => self::enum(self::LEVELS),
                'action' => self::string('Action concrète : mettre en avant, repositionner, retirer…'),
            ]), "Matrice d'ingénierie de menu estimée (sans données de ventes)"),
            'optimized_menu' => self::optimizedMenu(),
        ]);
    }

    /** @return array<string, mixed> */
    public static function optimizedMenu(): array
    {
        return self::object([
            'title' => self::string("Titre de la carte (nom du restaurant ou 'La Carte')"),
            'tagline' => self::nullable(self::string("Phrase d'accroche courte, ou null")),
            'sections' => self::array(self::object([
                'name' => self::string(),
                'intro' => self::nullable(self::string("Courte phrase d'intro de rubrique, ou null")),
                'items' => self::array(self::object([
                    'name' => self::string(),
                    'description' => self::nullable(self::string()),
                    'price' => self::nullable(self::number()),
                    'highlight' => self::boolean('true pour les plats à mettre en avant visuellement'),
                ])),
            ])),
            'footer_note' => self::nullable(self::string("Mentions légales / allergènes / 'prix nets, service compris'…")),
        ]);
    }

    /** @return array<string, mixed> */
    private static function correction(): array
    {
        return self::object([
            'original' => self::string(),
            'corrected' => self::string(),
            'reason' => self::string(),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @return array<string, mixed>
     */
    private static function object(array $properties): array
    {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private static function array(array $items, ?string $description = null): array
    {
        return self::describe(['type' => 'array', 'items' => $items], $description);
    }

    /** @return array<string, mixed> */
    private static function string(?string $description = null): array
    {
        return self::describe(['type' => 'string'], $description);
    }

    /** @return array<string, mixed> */
    private static function number(?string $description = null): array
    {
        return self::describe(['type' => 'number'], $description);
    }

    /** @return array<string, mixed> */
    private static function boolean(?string $description = null): array
    {
        return self::describe(['type' => 'boolean'], $description);
    }

    /**
     * @param  list<string>  $values
     * @return array<string, mixed>
     */
    private static function enum(array $values, ?string $description = null): array
    {
        return self::describe(['type' => 'string', 'enum' => $values], $description);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private static function nullable(array $schema): array
    {
        $schema['type'] = [$schema['type'], 'null'];

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private static function describe(array $schema, ?string $description): array
    {
        if ($description !== null) {
            $schema['description'] = $description;
        }

        return $schema;
    }
}
