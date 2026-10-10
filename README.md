# OptiCarte — optimisation de cartes de restaurant par IA

MVP de l'idée « App optimisation des cartes & menus des restaurants » : un restaurateur
photographie sa carte, l'application la transcrit avec Claude (vision), applique les principes
d'Omnes et l'ingénierie de menu, propose corrections, descriptions, mise en page et prix, puis
génère une carte optimisée en PDF. Chaque analyse alimente une base de plats (prix, année,
ville, type de restaurant) qui sert d'observatoire des prix.

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

Prérequis : Node.js 22+.

```bash
npm install
cp .env.example .env.local   # puis renseigner ANTHROPIC_API_KEY
npm run dev
```

Ouvrir http://localhost:3000.

Sans clé API, le mode démo permet de parcourir toute l'interface avec une carte fictive :

```bash
OPTICARTE_MOCK=1 npm run dev
```

### Variables d'environnement

| Variable | Rôle | Défaut |
| --- | --- | --- |
| `ANTHROPIC_API_KEY` | Clé API Anthropic (ou profil `ant auth login`) | — |
| `OPTICARTE_MODEL` | Modèle Claude utilisé | `claude-opus-5-5` |
| `OPTICARTE_MOCK` | `1` pour le mode démo sans appel API | `0` |
| `OPTICARTE_DATA_DIR` | Dossier de la base SQLite et des photos | `./data` |

### Scripts

```bash
npm run dev        # serveur de développement
npm run build      # build de production
npm run start      # serveur de production
npm test           # tests unitaires (principes d'Omnes, formatage des prix)
npm run typecheck  # vérification TypeScript
npm run lint       # ESLint
```

## Architecture

```
src/
  app/
    page.tsx                      formulaire d'upload
    api/analyze/route.ts          POST : crée l'analyse et lance le pipeline
    analyses/page.tsx             historique
    analyses/[id]/page.tsx        résultat (diagnostic, Omnes, carte optimisée)
    analyses/[id]/pdf/route.ts    export PDF
    analyses/[id]/images/[n]/     photos normalisées
    stats/page.tsx                observatoire des prix
  lib/
    schemas.ts     schémas zod partagés (sorties structurées Claude, base, UI)
    llm.ts         appels Claude : extraction (vision) puis optimisation
    omnes.ts       principes d'Omnes
    pipeline.ts    orchestration d'une analyse
    db.ts          SQLite (better-sqlite3) : analyses + base de plats + statistiques
    pdf.ts         rendu PDF (PDFKit)
    images.ts      normalisation des photos (sharp)
    mock-data.ts   fixtures du mode démo
tests/omnes.test.ts
```

Pipeline d'une analyse :

1. Les photos sont normalisées (orientation EXIF, 1600 px max, JPEG) et stockées dans `data/uploads/<id>/`.
2. `extractMenu` envoie les images à Claude avec une sortie structurée (`ExtractedMenuSchema`).
3. `computeOmnes` calcule les principes d'Omnes sur les prix lus.
4. Les plats sont insérés dans la table `dishes` (base de prix datée et localisée).
5. `optimizeMenu` envoie la carte, le rapport Omnes et le contexte du restaurateur à Claude,
   qui renvoie le diagnostic et la carte optimisée (`OptimizationSchema`).
6. La page de résultat se rafraîchit automatiquement jusqu'à la fin du traitement.

Les appels Claude utilisent `claude-opus-5-5`, les sorties structurées (zod) et le repli
serveur automatique en cas de refus (`fallbacks: "default"`). Le prompt d'optimisation est mis
en cache (prompt caching).

## Limites du MVP et prochaines étapes

- **Collecte Google Places** : non implémentée. La base de plats est alimentée uniquement par
  les cartes uploadées. Piste : un script d'import qui récupère les photos de menus via l'API
  Places, les passe dans `extractMenu` et les insère avec le type et la localisation du lieu.
- **Rapport prix demandé / prix offert** : affiché à titre informatif, il nécessite les données
  de ventes (export de caisse) pour être calculé. La matrice d'ingénierie de menu est donc une
  estimation du modèle, pas une mesure.
- **Pas d'authentification** : toutes les analyses sont visibles par tous les utilisateurs du
  serveur. À ajouter avant toute mise en ligne.
- **Traitement en arrière-plan** dans le processus Next.js : suffisant pour un MVP, à déplacer
  dans une file de tâches pour la production.
- **PDF** : une mise en page unique. Pistes : plusieurs gabarits, logo, format carte pliée.
