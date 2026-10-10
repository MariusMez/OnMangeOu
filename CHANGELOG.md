# Changelog

Toutes les évolutions notables de ce projet sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet adhère au
[Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

## [0.3.0] - 2026-10-10

### Ajouté
- Analyse à partir du **site web du restaurant** : Browsershot (Chromium) navigue sur la page
  donnée, repère la page « carte / menu » liée et capture les pages en tuiles analysées par le
  modèle de vision.
- **Mise à jour en direct** de la page de résultat avec Laravel Reverb (événement
  `AnalysisUpdated` diffusé à chaque changement de statut), avec rafraîchissement périodique en
  secours.
- **Laravel Horizon** pour la supervision des analyses en file de tâches (Redis), tableau de bord
  sur `/horizon` en local.
- Tests des agents (fakes Laravel AI) et des schémas de sortie structurée.
- Fichier CHANGELOG.

### Modifié
- Les appels au modèle passent par le **SDK Laravel AI** (`laravel/ai`, fournisseur Anthropic)
  avec deux agents à sortie structurée : `MenuReader` (vision) et `MenuOptimizer`
  (ingénierie de menu, prompt mis en cache). Le SDK Anthropic direct est retiré.
- L'export PDF utilise **spatie/laravel-pdf** avec le moteur Chromium (Browsershot) à la place
  de dompdf : mise en page CSS moderne, mêmes polices que le navigateur.
- La file de tâches par défaut passe de `database` à `redis`.

### Supprimé
- Dépendances `anthropic-ai/sdk` et `barryvdh/laravel-dompdf`.

## [0.2.0] - 2026-10-10

### Modifié
- Réécriture complète du MVP en **Laravel 13** (PHP 8.3+, cible 8.5), Blade et Tailwind CSS 4,
  à la place de la version Next.js : pipeline en job, SQLite, export PDF dompdf, observatoire des
  prix, mode démo, tests PHPUnit, workflow GitHub Actions (PHP 8.3 / 8.4 / 8.5), Laravel Boost.

## [0.1.0] - 2026-10-10

### Ajouté
- Premier MVP (Next.js) : upload de photos de carte, transcription structurée par Claude,
  principes d'Omnes, diagnostic d'ingénierie de menu, carte optimisée en PDF, base de plats et
  observatoire des prix, mode démo.

### Supprimé
- Ancienne application PhoneGap / Sencha Touch « OnMangeOu » (2011).

[Unreleased]: https://github.com/MariusMez/OnMangeOu/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/MariusMez/OnMangeOu/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/MariusMez/OnMangeOu/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/MariusMez/OnMangeOu/releases/tag/v0.1.0
