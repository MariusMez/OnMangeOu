import {
  CATEGORY_LABELS,
  type DishCategory,
  type ExtractedMenu,
} from "./schemas";

/**
 * Principes d'Omnes : quatre règles classiques d'ingénierie de menu
 * utilisées en restauration pour construire une carte cohérente.
 * Calculs déterministes, sans LLM, par catégorie (entrées, plats, desserts…).
 */

export type OmnesStatus = "ok" | "warning" | "info";

export interface OmnesPrinciple {
  key: "ouverture" | "dispersion" | "rapport" | "promotion";
  title: string;
  status: OmnesStatus;
  value: string;
  detail: string;
}

export interface PriceZone {
  from: number;
  to: number;
  count: number;
  items: string[];
}

export interface OmnesCategoryReport {
  category: DishCategory;
  label: string;
  count: number;
  priced_count: number;
  min: number;
  max: number;
  mean: number;
  median: number;
  opening_ratio: number;
  opening_threshold: number;
  zones: { low: PriceZone; mid: PriceZone; high: PriceZone };
  principles: OmnesPrinciple[];
}

export interface OmnesReport {
  categories: OmnesCategoryReport[];
  skipped: { category: DishCategory; label: string; reason: string }[];
  score: number;
  notes: string[];
}

interface PricedItem {
  name: string;
  price: number;
  highlighted: boolean;
}

/** Catégories sur lesquelles les principes d'Omnes ont un sens. */
const ANALYSED_CATEGORIES: DishCategory[] = ["entree", "plat", "dessert", "boisson"];
const MIN_ITEMS = 3;

export function round(n: number, digits = 2): number {
  const f = 10 ** digits;
  return Math.round(n * f) / f;
}

function median(values: number[]): number {
  const sorted = [...values].sort((a, b) => a - b);
  const mid = Math.floor(sorted.length / 2);
  return sorted.length % 2 === 0
    ? (sorted[mid - 1] + sorted[mid]) / 2
    : sorted[mid];
}

export function groupPricedItems(menu: ExtractedMenu): Map<DishCategory, PricedItem[]> {
  const groups = new Map<DishCategory, PricedItem[]>();
  for (const section of menu.sections) {
    for (const item of section.items) {
      if (item.price === null || !Number.isFinite(item.price) || item.price <= 0) continue;
      const list = groups.get(item.category) ?? [];
      list.push({ name: item.name, price: item.price, highlighted: item.is_highlighted });
      groups.set(item.category, list);
    }
  }
  return groups;
}

export function analyseCategory(
  category: DishCategory,
  items: PricedItem[],
): OmnesCategoryReport {
  const prices = items.map((i) => i.price);
  const min = Math.min(...prices);
  const max = Math.max(...prices);
  const mean = prices.reduce((a, b) => a + b, 0) / prices.length;
  const n = prices.length;

  // Principe 1 : ouverture de gamme (prix max / prix min)
  const openingThreshold = n < 9 ? 2.5 : 3;
  const openingRatio = max / min;

  // Principe 2 : dispersion des prix en trois zones de largeur égale
  const width = (max - min) / 3;
  const zoneOf = (p: number): "low" | "mid" | "high" => {
    if (width === 0) return "mid";
    if (p < min + width) return "low";
    if (p < min + 2 * width) return "mid";
    return "high";
  };
  const zones: OmnesCategoryReport["zones"] = {
    low: { from: round(min), to: round(min + width), count: 0, items: [] },
    mid: { from: round(min + width), to: round(min + 2 * width), count: 0, items: [] },
    high: { from: round(min + 2 * width), to: round(max), count: 0, items: [] },
  };
  for (const item of items) {
    const z = zones[zoneOf(item.price)];
    z.count += 1;
    z.items.push(item.name);
  }

  const principles: OmnesPrinciple[] = [];

  principles.push({
    key: "ouverture",
    title: "Ouverture de gamme",
    status: openingRatio <= openingThreshold ? "ok" : "warning",
    value: `×${round(openingRatio, 2)} (max ${openingThreshold})`,
    detail:
      openingRatio <= openingThreshold
        ? `Le prix le plus élevé (${fmt(max)}) reste dans un rapport acceptable avec le plus bas (${fmt(min)}).`
        : `Écart trop large entre ${fmt(min)} et ${fmt(max)} : le client ne sait plus se repérer. Resserrer la gamme ou créer une rubrique dédiée.`,
  });

  const midDominates = zones.mid.count >= zones.low.count + zones.high.count;
  principles.push({
    key: "dispersion",
    title: "Dispersion des prix",
    status: width === 0 ? "info" : midDominates ? "ok" : "warning",
    value: `${zones.low.count} / ${zones.mid.count} / ${zones.high.count} (bas / médian / haut)`,
    detail:
      width === 0
        ? "Tous les plats sont au même prix : la dispersion ne s'applique pas."
        : midDominates
          ? "La zone médiane concentre l'offre : la carte guide naturellement vers le cœur de gamme."
          : `La zone médiane (${fmt(zones.mid.from)}–${fmt(zones.mid.to)}) devrait contenir au moins autant de plats que les zones basse et haute réunies.`,
  });

  principles.push({
    key: "rapport",
    title: "Prix demandé / prix offert",
    status: "info",
    value: `prix moyen offert ${fmt(mean)}`,
    detail:
      "Le ratio prix moyen demandé (ventes réelles) / prix moyen offert doit se situer entre 0,9 et 1. Il nécessite les données de ventes : à importer dans une version ultérieure.",
  });

  const highlighted = items.filter((i) => i.highlighted);
  const highlightedOutsideMid = highlighted.filter((i) => zoneOf(i.price) !== "mid");
  principles.push({
    key: "promotion",
    title: "Promotion (plats mis en avant)",
    status:
      highlighted.length === 0
        ? "info"
        : highlightedOutsideMid.length === 0
          ? "ok"
          : "warning",
    value:
      highlighted.length === 0
        ? "aucun plat mis en avant"
        : `${highlighted.length} mis en avant, ${highlightedOutsideMid.length} hors zone médiane`,
    detail:
      highlighted.length === 0
        ? "Aucune suggestion ni plat du jour repéré : mettre en avant un plat rentable situé dans la zone médiane."
        : highlightedOutsideMid.length === 0
          ? "Les plats mis en avant sont dans la zone médiane : la promotion est cohérente avec la gamme."
          : `Plats promus hors zone médiane : ${highlightedOutsideMid.map((i) => i.name).join(", ")}. Une promotion doit porter sur le cœur de gamme.`,
  });

  return {
    category,
    label: CATEGORY_LABELS[category],
    count: items.length,
    priced_count: n,
    min: round(min),
    max: round(max),
    mean: round(mean),
    median: round(median(prices)),
    opening_ratio: round(openingRatio, 2),
    opening_threshold: openingThreshold,
    zones,
    principles,
  };
}

export function computeOmnes(menu: ExtractedMenu): OmnesReport {
  const groups = groupPricedItems(menu);
  const categories: OmnesCategoryReport[] = [];
  const skipped: OmnesReport["skipped"] = [];

  for (const category of ANALYSED_CATEGORIES) {
    const items = groups.get(category) ?? [];
    if (items.length < MIN_ITEMS) {
      if (items.length > 0) {
        skipped.push({
          category,
          label: CATEGORY_LABELS[category],
          reason: `${items.length} plat(s) avec prix : il en faut au moins ${MIN_ITEMS} pour appliquer les principes.`,
        });
      }
      continue;
    }
    categories.push(analyseCategory(category, items));
  }

  const evaluated = categories.flatMap((c) =>
    c.principles.filter((p) => p.status !== "info"),
  );
  const ok = evaluated.filter((p) => p.status === "ok").length;
  const score = evaluated.length === 0 ? 0 : Math.round((ok / evaluated.length) * 100);

  const notes: string[] = [];
  const totalItems = menu.sections.reduce((n, s) => n + s.items.length, 0);
  const unpriced = menu.sections.reduce(
    (n, s) => n + s.items.filter((i) => i.price === null).length,
    0,
  );
  if (unpriced > 0) {
    notes.push(`${unpriced} plat(s) sur ${totalItems} sans prix lisible : ils sont exclus des calculs.`);
  }
  const bigSections = menu.sections.filter((s) => s.items.length > 7);
  if (bigSections.length > 0) {
    notes.push(
      `Rubriques de plus de 7 plats (${bigSections.map((s) => s.name).join(", ")}) : au-delà, le choix devient anxiogène et dilue les ventes.`,
    );
  }
  return { categories, skipped, score, notes };
}

export function fmt(n: number): string {
  const s = n.toFixed(2).replace(".", ",");
  return `${s.endsWith(",00") ? s.slice(0, -3) : s} €`;
}
