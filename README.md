# OptiCarte — optimisation de cartes de restaurant par IA

MVP de l'idée « App optimisation des cartes & menus des restaurants » : un restaurateur
photographie sa carte **ou indique le site de son restaurant**, l'application transcrit la carte
avec Claude (vision), applique les principes d'Omnes et l'ingénierie de menu, propose
corrections, descriptions, mise en page et prix, puis génère une carte optimisée en PDF.
Chaque analyse alimente une base de plats (prix, année, ville, type de restaurant) qui sert
d'observatoire des prix.

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
- **Observatoire des prix** : prix moyens par catégorie, ville, type de restaurant et année,
  évolution annuelle, plats les plus fréquents.
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

### Variables d'environnement

| Variable | Rôle | Défaut |
| --- | --- | --- |
| `ANTHROPIC_API_KEY` | Clé API Anthropic | — |
| `AI_PROVIDER` | Fournisseur Laravel AI | `anthropic` |
| `OPTICARTE_MODEL` | Modèle Claude des deux agents | `claude-opus-5-5` |
| `OPTICARTE_MOCK` | `true` pour le mode démo sans appel API | `false` |
| `OPTICARTE_DISK` | Disque de stockage des photos (`config/filesystems.php`) | `local` |
| `QUEUE_CONNECTION` | File de tâches (Horizon requiert `redis`) | `redis` |
| `REVERB_*`, `VITE_REVERB_*` | Serveur WebSocket Reverb | voir `.env.example` |
| `LARAVEL_PDF_CHROME_PATH` | Binaire Chrome/Chromium pour le PDF et la capture de sites | Chrome de Puppeteer |
| `LARAVEL_PDF_NO_SANDBOX` | Désactive le sandbox Chrome (Docker, root) | `false` |

### Tests et qualité

```bash
php artisan test        # unitaires + fonctionnels (mode démo, queue synchrone, PDF simulé)
vendor/bin/pint         # formatage du code
```

Les tests n'ont besoin ni de Redis, ni de Chromium, ni de clé API : les agents Laravel AI et le
PDF sont simulés avec les fakes des paquets.

## Architecture

```
app/
  Ai/Agents/MenuReader.php             agent vision → carte structurée
  Ai/Agents/MenuOptimizer.php          agent ingénierie de menu → diagnostic + carte optimisée
  Ai/Schemas/MenuSchemas.php           schémas JSON des sorties structurées
  Actions/StartMenuAnalysis.php        normalise les photos, crée l'analyse, lance le job
  Jobs/RunMenuAnalysis.php             pipeline en file de tâches (Horizon)
  Events/AnalysisUpdated.php           diffusion Reverb des changements de statut
  Contracts/MenuAnalyst.php            frontière avec le modèle (AiMenuAnalyst / MockMenuAnalyst)
  Contracts/WebsiteCapture.php         capture de site (BrowsershotWebsiteCapture / fake)
  Services/Browser/                    BrowsershotFactory, capture et découpage en tuiles
  Services/Omnes/OmnesAnalyzer.php     principes d'Omnes
  Services/ImageNormalizer.php         normalisation des photos (intervention/image, GD)
  Services/ImageStore.php              stockage des photos (disque Laravel)
  Services/DishImporter.php            base de plats
  Services/PriceObservatory.php        statistiques de prix
  Services/MenuPdfRenderer.php         export PDF (laravel-pdf / Browsershot)
  Http/Controllers/                    AnalysisController, AnalysisPdfController,
                                       AnalysisImageController, StatsController
  Models/Analysis.php, Models/Dish.php
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

## Limites du MVP et prochaines étapes

- **Collecte Google Places** : non implémentée. La base de plats est alimentée par les cartes
  uploadées et les sites capturés. Piste : une commande Artisan qui récupère les photos et sites
  des restaurants d'une zone via l'API Places et les passe dans le pipeline.
- **Capture de site** : une page d'accueil et une page « carte » au maximum ; les menus en PDF
  liés depuis le site ne sont pas encore téléchargés.
- **Rapport prix demandé / prix offert** : affiché à titre informatif, il nécessite les données
  de ventes (export de caisse). La matrice d'ingénierie de menu est donc une estimation du
  modèle, pas une mesure.
- **Pas d'authentification** : toutes les analyses sont visibles par tous les utilisateurs du
  serveur, et Horizon n'est ouvert qu'en environnement local. À ajouter avant toute mise en ligne.
- **PDF** : une mise en page unique. Pistes : plusieurs gabarits, logo, format carte pliée.
