# OptiCarte — optimisation de cartes de restaurant par IA

MVP de l'idée « App optimisation des cartes & menus des restaurants » : un restaurateur
photographie sa carte, l'application la transcrit avec Claude (vision), applique les principes
d'Omnes et l'ingénierie de menu, propose corrections, descriptions, mise en page et prix, puis
génère une carte optimisée en PDF. Chaque analyse alimente une base de plats (prix, année,
ville, type de restaurant) qui sert d'observatoire des prix.

Application Laravel 13 (PHP 8.3+, conçue pour PHP 8.5), Blade + Tailwind CSS 4, SQLite.

## Fonctionnalités

- **Lecture de la carte** : 1 à 6 photos (JPG, PNG, WEBP), redimensionnées côté serveur, transcrites
  en JSON structuré (rubriques, plats, descriptions, prix, catégories, plats mis en avant, fautes).
- **Principes d'Omnes** (calcul déterministe, sans IA) par catégorie : ouverture de gamme,
  dispersion des prix en trois zones, rapport prix demandé / offert (nécessite les ventes),
  cohérence des promotions.
- **Diagnostic IA** : note globale, points forts, corrections orthographiques, descriptions
  réécrites, recommandations de mise en page, prix recommandés, matrice d'ingénierie de menu
  (étoiles, vaches à lait, énigmes, poids morts) estimée d'après les ingrédients et les prix.
- **Carte optimisée** : aperçu à l'écran et export PDF A4 (prix sans symbole monétaire, plats
  mis en avant).
- **Observatoire des prix** : prix moyens par catégorie, ville, type de restaurant et année,
  évolution annuelle, plats les plus fréquents.
- **Historique** des analyses.

## Démarrage

Prérequis : PHP 8.3 ou plus (8.5 recommandé) avec les extensions `gd`, `sqlite3`, `mbstring`,
`intl`, Composer et Node.js 22+.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build        # ou `npm run dev` pendant le développement
```

Renseigner `ANTHROPIC_API_KEY` dans `.env`, puis lancer les processus de développement
(serveur HTTP, worker de queue, logs, Vite) :

```bash
composer run dev
```

Ouvrir http://localhost:8000.

Les analyses tournent dans une file de tâches (`QUEUE_CONNECTION=database`) : le worker
`php artisan queue:listen` est indispensable, `composer run dev` le démarre.

### Mode démo

Sans clé API, le mode démo permet de parcourir toute l'interface avec une carte fictive :

```bash
OPTICARTE_MOCK=true composer run dev
```

### Variables d'environnement

| Variable | Rôle | Défaut |
| --- | --- | --- |
| `ANTHROPIC_API_KEY` | Clé API Anthropic (ou profil `ant auth login`) | — |
| `OPTICARTE_MODEL` | Modèle Claude utilisé | `claude-opus-5-5` |
| `OPTICARTE_MOCK` | `true` pour le mode démo sans appel API | `false` |
| `OPTICARTE_DISK` | Disque de stockage des photos (`config/filesystems.php`) | `local` |

### Tests et qualité

```bash
php artisan test        # tests unitaires et fonctionnels (mode démo, queue synchrone)
vendor/bin/pint         # formatage du code
```

## Architecture

```
app/
  Actions/StartMenuAnalysis.php        normalise les photos, crée l'analyse, lance le job
  Jobs/RunMenuAnalysis.php             pipeline en file de tâches
  Contracts/MenuAnalyst.php            frontière avec le modèle de langage
  Services/Claude/ClaudeMenuAnalyst.php  appels Claude : extraction (vision) puis optimisation
  Services/Claude/MockMenuAnalyst.php  mode démo
  Claude/MenuSchemas.php               schémas JSON des sorties structurées
  Services/Omnes/OmnesAnalyzer.php     principes d'Omnes
  Services/ImageNormalizer.php         normalisation des photos (intervention/image, GD)
  Services/ImageStore.php              stockage des photos (disque Laravel)
  Services/DishImporter.php            base de plats
  Services/PriceObservatory.php        statistiques de prix
  Services/MenuPdfRenderer.php         export PDF (dompdf)
  Http/Controllers/                    AnalysisController, AnalysisPdfController,
                                       AnalysisImageController, StatsController
  Models/Analysis.php, Models/Dish.php
resources/views/                       Blade : analyses/*, stats/*, pdf/menu
tests/Unit, tests/Feature
```

Pipeline d'une analyse (`RunMenuAnalysis`) :

1. Les photos sont normalisées (orientation EXIF, 1600 px max, JPEG) et stockées dans
   `storage/app/private/analyses/<id>/`.
2. `MenuAnalyst::extract` envoie les images à Claude avec une sortie structurée
   (`MenuSchemas::extractedMenu`).
3. `OmnesAnalyzer` calcule les principes d'Omnes sur les prix lus.
4. `DishImporter` insère les plats dans la table `dishes` (base de prix datée et localisée).
5. `MenuAnalyst::optimize` envoie la carte, le rapport Omnes et le contexte du restaurateur à
   Claude, qui renvoie le diagnostic et la carte optimisée (`MenuSchemas::optimization`).
6. La page de résultat se rafraîchit automatiquement jusqu'à la fin du traitement.

Les appels Claude utilisent `claude-opus-5-5`, les sorties structurées (schéma JSON) et le repli
serveur automatique en cas de refus (`fallbacks: default`). Le prompt d'optimisation est mis en
cache (prompt caching).

## Limites du MVP et prochaines étapes

- **Collecte Google Places** : non implémentée. La base de plats est alimentée uniquement par
  les cartes uploadées. Piste : une commande Artisan qui récupère les photos de menus via l'API
  Places, les passe dans `MenuAnalyst::extract` et les insère avec le type et la localisation.
- **Rapport prix demandé / prix offert** : affiché à titre informatif, il nécessite les données
  de ventes (export de caisse). La matrice d'ingénierie de menu est donc une estimation du
  modèle, pas une mesure.
- **Pas d'authentification** : toutes les analyses sont visibles par tous les utilisateurs du
  serveur. À ajouter avant toute mise en ligne.
- **PDF** : une mise en page unique. Pistes : plusieurs gabarits, logo, format carte pliée.
