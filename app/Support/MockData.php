<?php

namespace App\Support;

/** Carte fictive utilisée en mode démo (OPTICARTE_MOCK=true), avec quelques fautes volontaires. */
final class MockData
{
    /** @return array<string, mixed> */
    public static function menu(): array
    {
        return [
            'restaurant_name' => 'Chez Lucien',
            'cuisine_type' => 'bistrot',
            'currency' => 'EUR',
            'language' => 'fr',
            'sections' => [
                [
                    'name' => 'Entrées',
                    'items' => [
                        self::item("Soupe a l'oignon", 'gratinée', 8, 'entree'),
                        self::item('Oeufs mayonnaise', null, 6, 'entree'),
                        self::item('Terrine de campagne', 'maison, cornichons', 9, 'entree', ['fait maison']),
                        self::item('Salade de chèvre chaud', null, 11, 'entree', ['végétarien']),
                        self::item('Foie gras mi-cuit', 'chutney de figue', 19, 'entree'),
                    ],
                ],
                [
                    'name' => 'Plats',
                    'items' => [
                        self::item('Steak frites', 'sauce au poivre', 17, 'plat'),
                        self::item('Confit de canard', 'pommes sarladaises', 19, 'plat', [], true),
                        self::item('Poisson du jour', null, 21, 'plat'),
                        self::item('Burger maison', 'cheddar, bacon, frites', 16, 'plat', ['fait maison']),
                        self::item('Risotto aux champignon', null, 15, 'plat', ['végétarien']),
                        self::item('Côte de boeuf pour 2', '1 kg, gratin dauphinois', 58, 'plat', [], true),
                        self::item('Blanquette de veau', 'riz pilaf', 18, 'plat'),
                        self::item('Tartare de boeuf', 'coupé au couteau', 18, 'plat'),
                    ],
                ],
                [
                    'name' => 'Desserts',
                    'items' => [
                        self::item('Crème brulée', null, 7, 'dessert'),
                        self::item('Tarte tatin', 'crème fraîche', 8, 'dessert'),
                        self::item('Mousse au chocolat', null, 7, 'dessert'),
                        self::item('Café gourmand', null, 9, 'dessert'),
                    ],
                ],
            ],
            'spelling_issues' => self::corrections(),
            'reading_notes' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function optimization(): array
    {
        return [
            'summary' => "Carte de bistrot lisible mais sous-exploitée : les descriptions sont rares, les prix sont alignés avec le symbole € ce qui pousse le client à comparer, et la côte de bœuf à 58 € écrase la gamme des plats. En recentrant l'offre, en enrichissant les descriptions et en mettant en avant les plats à forte marge (risotto, blanquette), le ticket moyen peut progresser sans changer la cuisine.",
            'overall_score' => 58,
            'strengths' => [
                'Rubriques claires et peu nombreuses',
                'Mentions « fait maison » déjà présentes',
                'Prix des entrées et desserts bien resserrés',
            ],
            'corrections' => self::corrections(),
            'description_rewrites' => [
                [
                    'section' => 'Plats',
                    'original_name' => 'Risotto aux champignon',
                    'new_name' => 'Risotto crémeux aux champignons des bois',
                    'new_description' => 'Parmesan affiné 24 mois, huile de truffe, persillade',
                    'rationale' => 'Plat à forte marge : une description sensorielle justifie le prix et attire les végétariens.',
                ],
                [
                    'section' => 'Plats',
                    'original_name' => 'Blanquette de veau',
                    'new_name' => 'Blanquette de veau de nos grands-mères',
                    'new_description' => 'Mijotée 4 heures, champignons de Paris, riz pilaf au beurre',
                    'rationale' => 'Le registre affectif renforce le plat signature du bistrot.',
                ],
                [
                    'section' => 'Entrées',
                    'original_name' => 'Oeufs mayonnaise',
                    'new_name' => 'Œufs mayonnaise comme à Paris',
                    'new_description' => 'Mayonnaise montée minute, ciboulette',
                    'rationale' => 'Un classique bon marché qui gagne à être raconté.',
                ],
            ],
            'layout_recommendations' => [
                [
                    'title' => "Supprimer le symbole € et l'alignement des prix",
                    'detail' => 'Écrire les prix juste après la description, sans colonne alignée ni symbole monétaire, pour réduire la sensibilité au prix.',
                    'priority' => 'haute',
                ],
                [
                    'title' => 'Isoler la côte de bœuf dans un encart « À partager »',
                    'detail' => "Sortie de la liste des plats, elle ne fausse plus l'ouverture de gamme et devient un produit d'appel.",
                    'priority' => 'haute',
                ],
                [
                    'title' => 'Placer le confit et le risotto en tête et en fin de rubrique',
                    'detail' => 'Les premières et dernières positions sont les plus lues : y placer les plats les plus rentables.',
                    'priority' => 'moyenne',
                ],
                [
                    'title' => 'Ajouter une suggestion du chef encadrée',
                    'detail' => 'Un seul encadré par page, pour un plat à forte marge dans la zone de prix médiane.',
                    'priority' => 'moyenne',
                ],
            ],
            'pricing_recommendations' => [
                ['item' => 'Foie gras mi-cuit', 'current_price' => 19, 'suggested_price' => 16, 'rationale' => "À 19 € l'entrée dépasse l'ouverture de gamme (×3,2). 16 € reste premium sans casser la cohérence."],
                ['item' => 'Risotto aux champignon', 'current_price' => 15, 'suggested_price' => 16.5, 'rationale' => 'Plat à forte marge sous-tarifé par rapport au burger : +1,50 € reste dans la zone médiane.'],
                ['item' => 'Café gourmand', 'current_price' => 9, 'suggested_price' => 8.5, 'rationale' => 'Ramener le dessert le plus cher au niveau de la tatin pour resserrer la gamme.'],
            ],
            'menu_engineering' => [
                ['item' => 'Confit de canard', 'classification' => 'etoile', 'estimated_margin' => 'forte', 'estimated_popularity' => 'forte', 'action' => 'Garder en avant, position de tête de rubrique.'],
                ['item' => 'Steak frites', 'classification' => 'vache_a_lait', 'estimated_margin' => 'faible', 'estimated_popularity' => 'forte', 'action' => 'Conserver, mais ne plus le mettre en avant ; proposer un accompagnement payant.'],
                ['item' => 'Risotto aux champignon', 'classification' => 'enigme', 'estimated_margin' => 'forte', 'estimated_popularity' => 'moyenne', 'action' => 'Réécrire la description et le placer en fin de rubrique.'],
                ['item' => 'Blanquette de veau', 'classification' => 'enigme', 'estimated_margin' => 'forte', 'estimated_popularity' => 'moyenne', 'action' => 'En faire la suggestion du chef encadrée.'],
                ['item' => 'Côte de boeuf pour 2', 'classification' => 'poids_mort', 'estimated_margin' => 'faible', 'estimated_popularity' => 'faible', 'action' => 'Déplacer dans un encart « À partager » hors de la liste des plats.'],
                ['item' => 'Salade de chèvre chaud', 'classification' => 'vache_a_lait', 'estimated_margin' => 'moyenne', 'estimated_popularity' => 'forte', 'action' => 'Conserver telle quelle.'],
            ],
            'optimized_menu' => [
                'title' => 'Chez Lucien',
                'tagline' => 'Cuisine de bistrot, faite maison, depuis 1987',
                'sections' => [
                    [
                        'name' => 'Pour commencer',
                        'intro' => null,
                        'items' => [
                            self::optimized("Soupe à l'oignon gratinée", 'Oignons confits au beurre, croûtons, comté', 8),
                            self::optimized('Œufs mayonnaise comme à Paris', 'Mayonnaise montée minute, ciboulette', 6),
                            self::optimized('Terrine de campagne maison', 'Cornichons, pain de campagne grillé', 9, true),
                            self::optimized('Salade de chèvre chaud', "Miel de l'Yonne, noix, jeunes pousses", 11),
                            self::optimized('Foie gras mi-cuit', 'Chutney de figue, brioche toastée', 16),
                        ],
                    ],
                    [
                        'name' => 'Nos plats',
                        'intro' => null,
                        'items' => [
                            self::optimized('Confit de canard du Sud-Ouest', "Pommes sarladaises, salade d'herbes", 19, true),
                            self::optimized('Steak frites', 'Pièce du boucher, frites maison, sauce au poivre', 17),
                            self::optimized('Poisson du jour', "Selon l'arrivage, beurre blanc, légumes de saison", 21),
                            self::optimized('Burger maison', 'Bœuf charolais, cheddar affiné, bacon fumé, frites', 16),
                            self::optimized('Tartare de bœuf coupé au couteau', 'Condiments maison, frites', 18),
                            self::optimized('Blanquette de veau de nos grands-mères', 'Mijotée 4 heures, champignons de Paris, riz pilaf au beurre', 18, true),
                            self::optimized('Risotto crémeux aux champignons des bois', 'Parmesan affiné 24 mois, huile de truffe, persillade', 16.5),
                        ],
                    ],
                    [
                        'name' => 'À partager',
                        'intro' => 'Pour deux gourmands',
                        'items' => [
                            self::optimized('Côte de bœuf maturée (1 kg)', 'Gratin dauphinois, salade, sauce béarnaise', 58),
                        ],
                    ],
                    [
                        'name' => 'Douceurs',
                        'intro' => null,
                        'items' => [
                            self::optimized('Crème brûlée à la vanille de Madagascar', null, 7),
                            self::optimized("Tarte tatin, crème fraîche d'Isigny", null, 8, true),
                            self::optimized('Mousse au chocolat noir 70 %', null, 7),
                            self::optimized('Café gourmand', 'Trois mignardises du jour', 8.5),
                        ],
                    ],
                ],
                'footer_note' => 'Prix nets, service compris. Liste des allergènes disponible sur demande.',
            ],
        ];
    }

    /** @return list<array{original: string, corrected: string, reason: string}> */
    private static function corrections(): array
    {
        return [
            ['original' => "Soupe a l'oignon", 'corrected' => "Soupe à l'oignon", 'reason' => 'accent manquant sur « à »'],
            ['original' => 'Risotto aux champignon', 'corrected' => 'Risotto aux champignons', 'reason' => 'accord du pluriel'],
            ['original' => 'Crème brulée', 'corrected' => 'Crème brûlée', 'reason' => 'accent circonflexe'],
        ];
    }

    /**
     * @param  list<string>  $tags
     * @return array<string, mixed>
     */
    private static function item(string $name, ?string $description, float $price, string $category, array $tags = [], bool $highlighted = false): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'price_text' => Price::menu($price).'€',
            'category' => $category,
            'tags' => $tags,
            'is_highlighted' => $highlighted,
        ];
    }

    /** @return array<string, mixed> */
    private static function optimized(string $name, ?string $description, float $price, bool $highlight = false): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'highlight' => $highlight,
        ];
    }
}
