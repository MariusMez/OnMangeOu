# Changelog

Toutes les évolutions notables de ce projet sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet adhère au
[Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

### Modifié
- PHP 8.4 minimum (au lieu de 8.3) : les dépendances passent à Symfony 8.1, qui l’exige. La CI ne
  teste plus que PHP 8.4 et 8.5.

### Corrigé
- Observatoire des prix : le nombre de restaurants était faux sur MySQL, où `||` n’est pas une
  concaténation. Il est maintenant compté par sous-requête, quel que soit le moteur.

## [0.4.0] - 2026-10-10

### Ajouté
- **Design « Saffron Slate »** issu du projet Stitch « Scan de menu » : palette safran, titres
  en Noto Serif et texte en Manrope, surfaces tonales sans filets, cartes arrondies, puces
  colorées (tertiaire = marge forte / conforme, erreur = à corriger) et tuiles statistiques.
- Composants Blade réutilisables : `x-icon` (icônes SVG en ligne), `x-chip`, `x-nav-link`.
- Navigation basse flottante sur mobile et barre d'application en haut sur bureau.
- Écran de scan refondu : viseur avec coins et ligne de balayage servant de zone de dépôt,
  boutons « Importer des photos » / « Depuis le site web », analyses récentes en colonne latérale.
- Page de résultat refondue : comparaison « carte actuelle / carte recommandée » côte à côte,
  tuiles de notes, matrice d'ingénierie de menu en quatre colonnes colorées.

### Modifié
- Les polices sont désormais **auto-hébergées** (`@fontsource/manrope`, `@fontsource/noto-serif`)
  et embarquées par Vite : plus aucune requête vers un CDN de polices.
- Historique et observatoire des prix restylés (vignettes, puces de statut, tableau à lignes
  alternées, cartes de plats avec barre d'accent).

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
