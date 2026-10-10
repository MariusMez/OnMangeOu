import { z } from "zod";

/**
 * Schémas partagés entre le pipeline LLM (sorties structurées),
 * la base de données et l'interface.
 * Les champs facultatifs sont `nullable()` (et non `optional()`) : c'est le
 * format attendu par les sorties structurées de l'API Claude.
 */

export const DISH_CATEGORIES = [
  "entree",
  "plat",
  "dessert",
  "boisson",
  "menu",
  "autre",
] as const;
export type DishCategory = (typeof DISH_CATEGORIES)[number];

export const CATEGORY_LABELS: Record<DishCategory, string> = {
  entree: "Entrées",
  plat: "Plats",
  dessert: "Desserts",
  boisson: "Boissons",
  menu: "Menus & formules",
  autre: "Autres",
};

export const MenuItemSchema = z.object({
  name: z.string().describe("Nom du plat tel qu'écrit sur la carte"),
  description: z
    .string()
    .nullable()
    .describe("Description ou liste d'ingrédients si présente, sinon null"),
  price: z
    .number()
    .nullable()
    .describe("Prix numérique en unité monétaire (ex: 14.5). null si absent ou illisible"),
  price_text: z
    .string()
    .nullable()
    .describe("Prix tel qu'écrit (ex: '14,50 €', '12/18'). null si absent"),
  category: z
    .enum(DISH_CATEGORIES)
    .describe("Catégorie fonctionnelle du plat"),
  tags: z
    .array(z.string())
    .describe("Mentions: végétarien, épicé, fait maison, suggestion, nouveau…"),
  is_highlighted: z
    .boolean()
    .describe("true si le plat est mis en avant (encadré, 'suggestion du chef', 'plat du jour'…)"),
});
export type MenuItem = z.infer<typeof MenuItemSchema>;

export const MenuSectionSchema = z.object({
  name: z.string().describe("Titre de la rubrique tel qu'écrit sur la carte"),
  items: z.array(MenuItemSchema),
});
export type MenuSection = z.infer<typeof MenuSectionSchema>;

export const ExtractedMenuSchema = z.object({
  restaurant_name: z.string().nullable(),
  cuisine_type: z
    .string()
    .nullable()
    .describe("Type de cuisine déduit de la carte (ex: bistrot, pizzeria, gastronomique)"),
  currency: z.string().describe("Code devise ISO, EUR par défaut"),
  language: z.string().describe("Langue principale de la carte, code ISO (fr, en…)"),
  sections: z.array(MenuSectionSchema),
  spelling_issues: z
    .array(
      z.object({
        original: z.string(),
        corrected: z.string(),
        reason: z.string(),
      }),
    )
    .describe("Fautes d'orthographe, de typographie ou d'accord relevées"),
  reading_notes: z
    .string()
    .nullable()
    .describe("Difficultés de lecture: zones floues, prix illisibles…"),
});
export type ExtractedMenu = z.infer<typeof ExtractedMenuSchema>;

export const PRIORITIES = ["haute", "moyenne", "basse"] as const;

export const MENU_ENGINEERING_CLASSES = [
  "etoile",
  "vache_a_lait",
  "enigme",
  "poids_mort",
] as const;
export const MENU_ENGINEERING_LABELS: Record<
  (typeof MENU_ENGINEERING_CLASSES)[number],
  string
> = {
  etoile: "Étoile (populaire & rentable)",
  vache_a_lait: "Vache à lait (populaire, peu rentable)",
  enigme: "Énigme (rentable, peu populaire)",
  poids_mort: "Poids mort (ni populaire ni rentable)",
};

export const OptimizedMenuSchema = z.object({
  title: z.string().describe("Titre de la carte (nom du restaurant ou 'La Carte')"),
  tagline: z.string().nullable().describe("Phrase d'accroche courte, ou null"),
  sections: z.array(
    z.object({
      name: z.string(),
      intro: z.string().nullable().describe("Courte phrase d'intro de rubrique, ou null"),
      items: z.array(
        z.object({
          name: z.string(),
          description: z.string().nullable(),
          price: z.number().nullable(),
          highlight: z
            .boolean()
            .describe("true pour les plats à mettre en avant visuellement"),
        }),
      ),
    }),
  ),
  footer_note: z
    .string()
    .nullable()
    .describe("Mentions légales / allergènes / 'prix nets, service compris'…"),
});
export type OptimizedMenu = z.infer<typeof OptimizedMenuSchema>;

export const OptimizationSchema = z.object({
  summary: z
    .string()
    .describe("Diagnostic global en 3 à 5 phrases, orienté chiffre d'affaires"),
  overall_score: z
    .number()
    .describe("Note globale de la carte actuelle de 0 à 100"),
  strengths: z.array(z.string()).describe("Points forts de la carte actuelle"),
  corrections: z
    .array(
      z.object({
        original: z.string(),
        corrected: z.string(),
        reason: z.string(),
      }),
    )
    .describe("Corrections orthographiques et typographiques"),
  description_rewrites: z
    .array(
      z.object({
        section: z.string(),
        original_name: z.string(),
        new_name: z.string(),
        new_description: z.string(),
        rationale: z.string(),
      }),
    )
    .describe("Réécritures de noms et descriptions pour donner envie et justifier le prix"),
  layout_recommendations: z
    .array(
      z.object({
        title: z.string(),
        detail: z.string(),
        priority: z.enum(PRIORITIES),
      }),
    )
    .describe("Recommandations de structure et de mise en page"),
  pricing_recommendations: z
    .array(
      z.object({
        item: z.string(),
        current_price: z.number().nullable(),
        suggested_price: z.number().nullable(),
        rationale: z.string(),
      }),
    )
    .describe("Ajustements de prix, y compris pour corriger les écarts aux principes d'Omnes"),
  menu_engineering: z
    .array(
      z.object({
        item: z.string(),
        classification: z.enum(MENU_ENGINEERING_CLASSES),
        estimated_margin: z
          .enum(["faible", "moyenne", "forte"])
          .describe("Marge estimée d'après les ingrédients et le prix"),
        estimated_popularity: z.enum(["faible", "moyenne", "forte"]),
        action: z.string().describe("Action concrète: mettre en avant, repositionner, retirer…"),
      }),
    )
    .describe("Matrice d'ingénierie de menu estimée (sans données de ventes)"),
  optimized_menu: OptimizedMenuSchema,
});
export type Optimization = z.infer<typeof OptimizationSchema>;

/** Métadonnées saisies par le restaurateur lors de l'upload. */
export const AnalysisInputSchema = z.object({
  restaurant_name: z.string().trim().min(1, "Le nom du restaurant est requis"),
  city: z.string().trim().min(1, "La ville est requise"),
  restaurant_type: z.string().trim().min(1, "Le type de restaurant est requis"),
  goal: z.string().trim().default(""),
});
export type AnalysisInput = z.infer<typeof AnalysisInputSchema>;
