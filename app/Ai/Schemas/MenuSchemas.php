<?php

namespace App\Ai\Schemas;

use App\Enums\DishCategory;
use App\Enums\MenuEngineeringClass;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;

/**
 * Schémas des sorties structurées demandées aux agents. Toutes les propriétés
 * sont requises ; les champs facultatifs sont nullables, conformément aux
 * contraintes des sorties structurées de l'API Anthropic.
 */
final class MenuSchemas
{
    public const array PRIORITIES = ['haute', 'moyenne', 'basse'];

    public const array LEVELS = ['faible', 'moyenne', 'forte'];

    /** @return array<string, Type> */
    public static function extractedMenu(JsonSchema $s): array
    {
        $item = self::object($s, [
            'name' => $s->string()->description("Nom du plat tel qu'écrit sur la carte")->required(),
            'description' => $s->string()->description("Description ou liste d'ingrédients si présente, sinon null")->nullable()->required(),
            'price' => $s->number()->description('Prix numérique en unité monétaire (14,50 € → 14.5). null si absent ou illisible')->nullable()->required(),
            'price_text' => $s->string()->description("Prix tel qu'écrit (ex: '14,50 €', '12/18'). null si absent")->nullable()->required(),
            'category' => $s->string()->enum(DishCategory::values())->description('Catégorie fonctionnelle du plat')->required(),
            'tags' => self::list($s, $s->string())->description('Mentions : végétarien, épicé, fait maison, suggestion, nouveau…'),
            'is_highlighted' => $s->boolean()->description("true si le plat est mis en avant (encadré, 'suggestion du chef', 'plat du jour'…)")->required(),
        ]);

        $section = self::object($s, [
            'name' => $s->string()->description("Titre de la rubrique tel qu'écrit sur la carte")->required(),
            'items' => self::list($s, $item),
        ]);

        return [
            'restaurant_name' => $s->string()->nullable()->required(),
            'cuisine_type' => $s->string()->description('Type de cuisine déduit de la carte (ex: bistrot, pizzeria, gastronomique)')->nullable()->required(),
            'currency' => $s->string()->description('Code devise ISO, EUR par défaut')->required(),
            'language' => $s->string()->description('Langue principale de la carte, code ISO (fr, en…)')->required(),
            'sections' => self::list($s, $section),
            'spelling_issues' => self::list($s, self::correction($s))->description("Fautes d'orthographe, de typographie ou d'accord relevées"),
            'reading_notes' => $s->string()->description('Difficultés de lecture : zones floues, prix illisibles…')->nullable()->required(),
        ];
    }

    /** @return array<string, Type> */
    public static function optimization(JsonSchema $s): array
    {
        return [
            'summary' => $s->string()->description("Diagnostic global en 3 à 5 phrases, orienté chiffre d'affaires")->required(),
            'overall_score' => $s->number()->description('Note globale de la carte actuelle de 0 à 100')->required(),
            'strengths' => self::list($s, $s->string())->description('Points forts de la carte actuelle'),
            'corrections' => self::list($s, self::correction($s))->description('Corrections orthographiques et typographiques'),
            'description_rewrites' => self::list($s, self::object($s, [
                'section' => $s->string()->required(),
                'original_name' => $s->string()->required(),
                'new_name' => $s->string()->required(),
                'new_description' => $s->string()->required(),
                'rationale' => $s->string()->required(),
            ]))->description('Réécritures de noms et descriptions pour donner envie et justifier le prix'),
            'layout_recommendations' => self::list($s, self::object($s, [
                'title' => $s->string()->required(),
                'detail' => $s->string()->required(),
                'priority' => $s->string()->enum(self::PRIORITIES)->required(),
            ]))->description('Recommandations de structure et de mise en page'),
            'pricing_recommendations' => self::list($s, self::object($s, [
                'item' => $s->string()->required(),
                'current_price' => $s->number()->nullable()->required(),
                'suggested_price' => $s->number()->nullable()->required(),
                'rationale' => $s->string()->required(),
            ]))->description("Ajustements de prix, y compris pour corriger les écarts aux principes d'Omnes"),
            'menu_engineering' => self::list($s, self::object($s, [
                'item' => $s->string()->required(),
                'classification' => $s->string()->enum(MenuEngineeringClass::values())->required(),
                'estimated_margin' => $s->string()->enum(self::LEVELS)->description("Marge estimée d'après les ingrédients et le prix")->required(),
                'estimated_popularity' => $s->string()->enum(self::LEVELS)->required(),
                'action' => $s->string()->description('Action concrète : mettre en avant, repositionner, retirer…')->required(),
            ]))->description("Matrice d'ingénierie de menu estimée (sans données de ventes)"),
            'optimized_menu' => self::object($s, [
                'title' => $s->string()->description("Titre de la carte (nom du restaurant ou 'La Carte')")->required(),
                'tagline' => $s->string()->description("Phrase d'accroche courte, ou null")->nullable()->required(),
                'sections' => self::list($s, self::object($s, [
                    'name' => $s->string()->required(),
                    'intro' => $s->string()->description("Courte phrase d'intro de rubrique, ou null")->nullable()->required(),
                    'items' => self::list($s, self::object($s, [
                        'name' => $s->string()->required(),
                        'description' => $s->string()->nullable()->required(),
                        'price' => $s->number()->nullable()->required(),
                        'highlight' => $s->boolean()->description('true pour les plats à mettre en avant visuellement')->required(),
                    ])),
                ])),
                'footer_note' => $s->string()->description("Mentions légales / allergènes / 'prix nets, service compris'…")->nullable()->required(),
            ]),
        ];
    }

    private static function correction(JsonSchema $s): ObjectType
    {
        return self::object($s, [
            'original' => $s->string()->required(),
            'corrected' => $s->string()->required(),
            'reason' => $s->string()->required(),
        ]);
    }

    /** @param  array<string, Type>  $properties */
    private static function object(JsonSchema $s, array $properties): ObjectType
    {
        return $s->object($properties)->withoutAdditionalProperties()->required();
    }

    private static function list(JsonSchema $s, Type $items): ArrayType
    {
        return $s->array()->items($items)->required();
    }
}
