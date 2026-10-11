# OptiCarte — optimisation de cartes de restaurant par IA

MVP de l'idée « App optimisation des cartes & menus des restaurants » : un restaurateur
photographie sa carte **ou indique le site de son restaurant**, l'application transcrit la carte
avec Claude (vision), applique les principes d'Omnes et l'ingénierie de menu, propose
corrections, descriptions, mise en page et prix, puis génère une carte optimisée en PDF.
Chaque analyse alimente une base de plats (prix, année, localisation, type de restaurant) qui
sert d'observatoire des prix. Une commande collecte aussi les cartes photographiées des
restaurants d'une zone française sur Google Places pour suivre l'évolution des prix par zone.

Application **Laravel 13** (PHP 8.4+, conçue pour PHP 8.5) avec :

- [Laravel AI](https://laravel.com/docs/ai) (fournisseur Anthropic, agents à sortie structurée),
- [Laravel Horizon](https://laravel.com/docs/horizon) (file de tâches Redis),
- [Laravel Reverb](https://laravel.com/docs/reverb) (mise à jour en direct de la page d'analyse),
- [spatie/laravel-pdf](https://spatie.be/docs/laravel-pdf) et
  [Browsershot](https://spatie.be/docs/browsershot) (PDF et capture de sites via Chromium),
- Blade + Tailwind CSS 4, SQLite, [Laravel Boost](https://laravel.com/docs/boost) pour le
  développement assisté par IA,
- interface inspirée du design Stitch « Saffron Slate » (polices Noto Serif et Manrope
  auto-hébergées, composants Blade `x-icon`, `x-chip`, `x-nav-link`).

## Fonctionnalités

- **Deux sources** : 1 à 6 photos (JPG, PNG, WEBP) redimensionnées côté serveur, ou l'URL du
  site du restaurant. Dans ce cas Chromium capture la page, suit le premier lien « carte » ou
  « menu » du même site, et découpe les captures en tuiles lisibles par le modèle.
- **Lecture de la carte** par l'agent `MenuReader` : rubriques, plats, descriptions, prix,
  catégories, plats mis en avant, fautes, en JSON structuré.
- **Principes d'Omnes** (calcul déterministe, sans IA) par catégorie : ouverture de gamme,
  dispersion des prix en trois zones, rapport prix demandé / offert (nécessite les ventes),
  cohérence des promotions.
- **Diagnostic** par l'agent `MenuOptimizer` : note globale, points forts, corrections,
  descriptions réécrites, recommandations de mise en page, prix recommandés, matrice
  d'ingénierie de menu (étoiles, vaches à lait, énigmes, poids morts) estimée d'après les
  ingrédients et les prix.
- **Carte optimisée** : aperçu à l'écran et export PDF A4 rendu par Chromium (prix sans symbole
  monétaire, plats mis en avant).
- **Suivi en direct** : la page de résultat se met à jour dès qu'une étape se termine (Reverb),
  avec un rafraîchissement périodique en secours.
- **Collecte d'une zone** (`opticarte:collect`) : restaurants d'un code postal, d'une commune,
  d'un département, d'une région ou d'un rayon (Google Places), tri de leurs photos par IA,
  lecture des photos de carte, classification du restaurant.
- **Observatoire des prix** : prix moyens par catégorie, ville, type de restaurant et année,
  filtres par département, région et code postal, évolution à périmètre constant, restaurants
  collectés de la zone, plats les plus fréquents. Les mêmes chiffres en ligne de commande avec
  `opticarte:stats`.
- **Historique** des analyses et **tableau de bord Horizon** (`/horizon`, ouvert en local).

## Démarrage

Prérequis : PHP 8.4 ou plus (8.5 recommandé) avec les extensions `gd`, `sqlite3`, `mbstring`,
`intl`, `redis` (ou `predis`), Composer, Node.js 22+, un serveur **Redis** et un Chromium
(téléchargé par Puppeteer lors de `npm install`, ou indiqué par `LARAVEL_PDF_CHROME_PATH`).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build        # ou `npm run dev` pendant le développement
```

Renseigner `ANTHROPIC_API_KEY` dans `.env`, démarrer Redis, puis lancer tous les processus de
développement (serveur HTTP, Horizon, Reverb, logs, Vite) :

```bash
composer run dev
```

Ouvrir http://localhost:8000.

### Mode démo

Sans clé API ni Chromium, le mode démo permet de parcourir toute l'interface avec une carte
fictive (la capture de site est simulée) :

```bash
OPTICARTE_MOCK=true composer run dev
```

### Collecter une zone

La commande cherche les restaurants de la zone sur Google Places, examine jusqu'à dix photos de
chaque fiche, demande à l'agent `MenuPhotoClassifier` lesquelles montrent une carte aux prix
lisibles, puis fait lire ces photos par `MenuReader`. Les plats rejoignent la base avec le code
postal, le département, la région, le type du restaurant et l'année de la collecte.

```bash
php artisan opticarte:collect --code-postal=69001 --dry-run   # liste les restaurants, sans rien enregistrer
php artisan opticarte:collect --code-postal=69001 --limit=20  # 20 collectes envoyées à Horizon
php artisan opticarte:collect --commune=Rennes --sync         # collecte immédiate, sans file
php artisan opticarte:collect --departement="Ille-et-Vilaine" --max-recherches=50
php artisan opticarte:collect --region=Bretagne --limit=100
php artisan opticarte:collect --code-postal=75011 --rayon=2   # 2 km autour du 11e arrondissement
php artisan opticarte:collect --lat=45.764 --lng=4.835 --rayon=1.5

php artisan opticarte:stats --departement=69                  # évolution des prix de la zone
```

| Option | Rôle | Défaut |
| --- | --- | --- |
| `--code-postal` | Un ou plusieurs codes postaux (arrondissement pour Paris, Lyon, Marseille) | — |
| `--commune` | Nom ou code INSEE | — |
| `--departement` | Code ou nom (`69`, `Rhône`, `2A`) | — |
| `--region` | Code INSEE ou nom (`53`, `Bretagne`) | — |
| `--lat`, `--lng`, `--rayon` | Cercle en km ; le centre peut aussi être un code postal ou une commune | — |
| `--limit` | Restaurants à collecter au plus | `20` |
| `--photos` | Photos examinées par restaurant (10 au plus) | `10` |
| `--max-recherches` | Zones de recherche Google Places (60 résultats au plus chacune) | `30` |
| `--refresh` / `--force` | Délai avant de recollecter un restaurant, en jours / l'ignorer | `180` |
| `--dry-run` | Recherche seule : rien n'est enregistré, téléchargé ni envoyé à l'IA | — |
| `--sync` | Collecte dans la commande, sans Horizon | — |

Les zones étendues sont parcourues de la commune la plus peuplée à la moins peuplée, chaque
commune étant pavée de cercles de 1,5 km. Seuls les restaurants situés en France (département
reconnu) et dans la zone sont retenus ; les restaurants fermés définitivement sont enregistrés
mais pas collectés. Une photo déjà examinée n'est plus envoyée à l'IA : relancer la collecte
l'année suivante n'ajoute que les nouvelles photos de carte, ce qui alimente l'évolution des
prix. Les photos de carte ne sont conservées sur disque qu'avec `OPTICARTE_KEEP_PLACE_PHOTOS=true`.

En mode démo, la géographie, Google Places et le tri des photos sont simulés : la commande
fonctionne sans clé ni réseau.

### Variables d'environnement

| Variable | Rôle | Défaut |
| --- | --- | --- |
| `ANTHROPIC_API_KEY` | Clé API Anthropic | — |
| `AI_PROVIDER` | Fournisseur Laravel AI | `anthropic` |
| `OPTICARTE_MODEL` | Modèle Claude des deux agents | `claude-opus-5-5` |
| `OPTICARTE_MOCK` | `true` pour le mode démo sans appel API | `false` |
| `OPTICARTE_DISK` | Disque de stockage des photos (`config/filesystems.php`) | `local` |
| `GOOGLE_PLACES_API_KEY` | Clé Google Maps Platform avec Places API (New) activée | — |
| `OPTICARTE_CLASSIFIER_MODEL` | Modèle Claude du tri des photos de la collecte | `claude-opus-5-5` |
| `OPTICARTE_KEEP_PLACE_PHOTOS` | Conserve les photos de carte collectées sur le disque | `false` |
| `OPTICARTE_GEO_API_URL` | API du découpage administratif | `https://geo.api.gouv.fr` |
| `QUEUE_CONNECTION` | File de tâches (Horizon requiert `redis`) | `redis` |
| `REVERB_*`, `VITE_REVERB_*` | Serveur WebSocket Reverb | voir `.env.example` |
| `LARAVEL_PDF_CHROME_PATH` | Binaire Chrome/Chromium pour le PDF et la capture de sites | Chrome de Puppeteer |
| `LARAVEL_PDF_NO_SANDBOX` | Désactive le sandbox Chrome (Docker, root) | `false` |

### Tests et qualité

```bash
php artisan test        # unitaires + fonctionnels (mode démo, queue synchrone, PDF simulé)
vendor/bin/pint         # formatage du code
```

Les tests n'ont besoin ni de Redis, ni de Chromium, ni de clé API, ni de réseau : les agents
Laravel AI et le PDF sont simulés avec les fakes des paquets, Google Places et geo.api.gouv.fr
avec `Http::fake()`.

## Architecture

```
app/
  Ai/Agents/MenuReader.php             agent vision → carte structurée
  Ai/Agents/MenuOptimizer.php          agent ingénierie de menu → diagnostic + carte optimisée
  Ai/Agents/MenuPhotoClassifier.php    agent de tri des photos d'une fiche (carte, plat, salle…)
  Ai/Schemas/MenuSchemas.php           schémas JSON des sorties structurées
  Actions/StartMenuAnalysis.php        normalise les photos, crée l'analyse, lance le job
  Jobs/RunMenuAnalysis.php             pipeline en file de tâches (Horizon)
  Events/AnalysisUpdated.php           diffusion Reverb des changements de statut
  Contracts/MenuAnalyst.php            frontière avec le modèle (AiMenuAnalyst / MockMenuAnalyst)
  Contracts/WebsiteCapture.php         capture de site (BrowsershotWebsiteCapture / fake)
  Contracts/PlacesClient.php           annuaire de lieux (GooglePlacesClient / DemoPlacesClient)
  Contracts/FrenchGeography.php        découpage administratif (GeoApiGeography / DemoGeography)
  Contracts/PhotoClassifier.php        tri des photos (AiPhotoClassifier / MockPhotoClassifier)
  Console/Commands/                    opticarte:collect et opticarte:stats
  Jobs/CollectRestaurantMenu.php       collecte d'un restaurant en file (Horizon)
  Services/Geo/Zones.php               zones : résolution, pavage en cercles, appartenance
  Services/Collect/                    MenuCollector, RestaurantImporter, RestaurantTypeClassifier
  Support/France.php, Support/GeoMath.php   départements, régions, distances, pavage
  Services/Browser/                    BrowsershotFactory, capture et découpage en tuiles
  Services/Omnes/OmnesAnalyzer.php     principes d'Omnes
  Services/ImageNormalizer.php         normalisation des photos (intervention/image, GD)
  Services/ImageStore.php              stockage des photos (disque Laravel)
  Services/DishImporter.php            base de plats
  Services/PriceObservatory.php        statistiques de prix
  Services/MenuPdfRenderer.php         export PDF (laravel-pdf / Browsershot)
  Http/Controllers/                    AnalysisController, AnalysisPdfController,
                                       AnalysisImageController, StatsController
  Models/Analysis.php, Models/Dish.php, Models/Restaurant.php, Models/RestaurantPhoto.php
resources/views/                       Blade : layouts/app, components/*, analyses/*, stats/*, pdf/menu
resources/css/app.css                  thème Tailwind 4 (@theme) : palette safran, polices
resources/js/app.js                    Laravel Echo : écoute du canal analyses.{id}
config/opticarte.php, config/ai.php, config/horizon.php, config/reverb.php, config/laravel-pdf.php
tests/Unit, tests/Feature
```

Pipeline d'une analyse (`RunMenuAnalysis`) :

1. Si une URL est fournie, `WebsiteCapture` capture le site et stocke les tuiles ; sinon les
   photos normalisées (orientation EXIF, 1600 px max, JPEG) sont déjà dans
   `storage/app/private/analyses/<id>/`.
2. `MenuReader` reçoit les images en pièces jointes et renvoie la carte structurée.
3. `OmnesAnalyzer` calcule les principes d'Omnes sur les prix lus.
4. `DishImporter` insère les plats dans la table `dishes` (base de prix datée et localisée).
5. `MenuOptimizer` reçoit la carte, le rapport Omnes et le contexte du restaurateur, et renvoie
   le diagnostic et la carte optimisée.
6. Chaque changement de statut diffuse `AnalysisUpdated` ; la page de résultat se recharge.

Les deux agents utilisent `claude-opus-5-5`, les sorties structurées (schéma JSON) et le repli
serveur automatique en cas de refus (`fallbacks: default`). Les instructions de l'optimiseur
sont mises en cache (prompt caching).

Pipeline d'une collecte (`MenuCollector`, dans le job `CollectRestaurantMenu`) :

1. Les photos de la fiche sont redemandées à Google au moment du traitement (leurs noms
   expirent et ne doivent pas être conservés), puis téléchargées en miniatures de 512 px.
2. Les miniatures déjà examinées (empreinte SHA-1 dans `restaurant_photos`) sont ignorées ; les
   autres sont triées en un seul appel par `MenuPhotoClassifier`.
3. Les photos de carte aux prix lisibles sont retéléchargées en 1600 px et lues ensemble par
   `MenuReader` ; le type de cuisine lu affine un restaurant classé « Autre ».
4. `DishImporter` insère les plats de l'année ; les photos examinées ne sont enregistrées
   qu'après une lecture réussie, pour être réessayées en cas d'échec.

## Limites du MVP et prochaines étapes

- **Conditions Google Maps Platform** : elles encadrent strictement la mise en cache et la
  réutilisation des contenus Google (fiches, photos). La collecte conserve l'identifiant de lieu,
  la localisation et les prix lus, pas les photos par défaut ; vérifiez la conformité de cet
  usage avec Google avant toute exploitation. Le contrat `PlacesClient` permet de brancher une
  autre source (OpenStreetMap, base SIRENE, partenariats).
- **Année des prix** : Google ne date pas les photos ; un prix est daté de l'année où sa photo
  est examinée pour la première fois. Une vieille carte encore en ligne peut donc être datée
  trop tard.
- **Coûts** : chaque restaurant collecté coûte un appel Place Details, jusqu'à dix
  téléchargements de miniatures, un appel de tri et, s'il y a une carte, une lecture par le
  modèle. Les options `--limit`, `--photos` et `--max-recherches` plafonnent la dépense.
- **Couverture** : Google renvoie 60 restaurants au plus par zone de recherche ; dans un centre
  très dense, certains restaurants peuvent manquer malgré le pavage de 1,5 km.
- **Analyses des restaurateurs** : elles ne connaissent que la ville ; leurs plats n'entrent pas
  dans les filtres par département, région ou code postal.
- **Capture de site** : une page d'accueil et une page « carte » au maximum ; les menus en PDF
  liés depuis le site ne sont pas encore téléchargés.
- **Rapport prix demandé / prix offert** : affiché à titre informatif, il nécessite les données
  de ventes (export de caisse). La matrice d'ingénierie de menu est donc une estimation du
  modèle, pas une mesure.
- **Pas d'authentification** : toutes les analyses sont visibles par tous les utilisateurs du
  serveur, et Horizon n'est ouvert qu'en environnement local. À ajouter avant toute mise en ligne.
- **PDF** : une mise en page unique. Pistes : plusieurs gabarits, logo, format carte pliée.
