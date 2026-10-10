import Anthropic from "@anthropic-ai/sdk";
import { betaZodOutputFormat } from "@anthropic-ai/sdk/helpers/beta/zod";
import {
  ExtractedMenuSchema,
  OptimizationSchema,
  type AnalysisInput,
  type ExtractedMenu,
  type Optimization,
} from "./schemas";
import type { OmnesReport } from "./omnes";
import type { NormalizedImage } from "./images";
import { MOCK_MENU, MOCK_OPTIMIZATION } from "./mock-data";

export const MODEL = process.env.OPTICARTE_MODEL ?? "claude-opus-5-5";

/** Mode démo : aucune requête API, fixtures locales. */
export function isMockMode(): boolean {
  return process.env.OPTICARTE_MOCK === "1";
}

let client: Anthropic | null = null;
function getClient(): Anthropic {
  // Identifiants résolus depuis l'environnement (ANTHROPIC_API_KEY, ANTHROPIC_AUTH_TOKEN
  // ou profil `ant auth login`).
  client ??= new Anthropic({ maxRetries: 2 });
  return client;
}

export class LlmError extends Error {}

const EXTRACTION_SYSTEM = `Tu es un expert en lecture de cartes de restaurant.
On te donne une ou plusieurs photos d'une même carte (pages, recto/verso, ardoise).
Transcris fidèlement TOUS les plats, dans l'ordre d'apparition, sans rien inventer.

Règles :
- Conserve l'orthographe originale dans "name" et "description" (les fautes sont relevées à part dans "spelling_issues").
- "price" est un nombre décimal (14,50 € → 14.5). Pour un prix double (ex: "12 / 18") prends le premier et garde le texte complet dans "price_text". Si illisible : null.
- "category" : entree, plat, dessert, boisson, menu (formules / menus complets), autre (accompagnements, suppléments, enfants…).
- "is_highlighted" : true seulement si la carte met visiblement le plat en avant (encadré, étoile, "suggestion", "plat du jour", "spécialité").
- Si une rubrique n'a pas de titre, nomme-la d'après son contenu (ex: "Plats").
- Déduis "cuisine_type" du contenu (bistrot, pizzeria, brasserie, gastronomique, asiatique…).
- Signale dans "reading_notes" les zones illisibles ou les doutes, sinon null.`;

const OPTIMIZATION_SYSTEM = `Tu es consultant en ingénierie de menu pour restaurants indépendants, spécialisé dans l'augmentation du ticket moyen et de la marge.
Tu reçois la carte extraite d'un restaurant (JSON), une analyse chiffrée selon les principes d'Omnes et le contexte du restaurateur.
Produis un diagnostic et une carte optimisée, en français, concrets et actionnables.

Principes d'Omnes (rappel) :
1. Ouverture de gamme : prix max / prix min d'une rubrique ≤ 2,5 (≤ 3 si plus de 9 plats).
2. Dispersion : en découpant la gamme en 3 zones de prix égales, la zone médiane doit contenir au moins autant de plats que les deux autres réunies.
3. Rapport prix demandé / prix offert entre 0,9 et 1 (nécessite les ventes ; sinon, resserrer l'offre autour du prix moyen).
4. Promotion : les plats mis en avant (suggestion, plat du jour, menu) doivent être dans la zone médiane.

Psychologie de la carte :
- Pas de symbole monétaire ni de colonne de prix alignée ; prix écrit à la suite de la description.
- 5 à 7 plats par rubrique maximum ; les positions de tête et de fin sont les plus lues.
- Un seul encadré / mise en avant par page, réservé à un plat à forte marge dans la zone médiane.
- Descriptions sensorielles courtes (origine, technique, texture) qui justifient le prix ; nommer les produits (race, terroir, maison).
- Ancrage : un plat cher bien placé rend les autres abordables, mais hors liste s'il écrase la gamme (encart « à partager »).
- Les plats à forte marge et faible popularité (énigmes) gagnent une meilleure description ou position ; les poids morts sont retirés ou repositionnés.

Contraintes de sortie :
- "corrections" reprend et complète les fautes relevées.
- "menu_engineering" : estime marge (coût matière probable vs prix) et popularité (plat classique attendu vs plat de niche) pour les plats principaux, au moins 5 plats si possible.
- "pricing_recommendations" : uniquement des changements justifiés (Omnes, cohérence, marge), rester réaliste (±15 % max), arrondis commerciaux (x,50 / x,90 / entier).
- "optimized_menu" : la carte complète réécrite (tous les plats conservés sauf retrait explicitement recommandé), ordre optimisé, nouvelles descriptions, prix recommandés appliqués, 1 à 2 "highlight" par rubrique maximum.
- Reste fidèle au style du restaurant (un bistrot ne devient pas gastronomique). Si les photos étaient partiellement illisibles, ne complète pas avec des plats inventés.`;

export async function extractMenu(images: NormalizedImage[]): Promise<ExtractedMenu> {
  if (isMockMode()) {
    await new Promise((r) => setTimeout(r, 800));
    return structuredClone(MOCK_MENU);
  }
  const response = await getClient().beta.messages.parse({
    model: MODEL,
    max_tokens: 16000,
    betas: ["server-side-fallback-2026-07-01"],
    fallbacks: "default",
    system: EXTRACTION_SYSTEM,
    output_config: {
      effort: "medium",
      format: betaZodOutputFormat(ExtractedMenuSchema),
    },
    messages: [
      {
        role: "user",
        content: [
          ...images.map((img) => ({
            type: "image" as const,
            source: {
              type: "base64" as const,
              media_type: img.mediaType,
              data: img.data.toString("base64"),
            },
          })),
          {
            type: "text",
            text: `Voici ${images.length} photo(s) de la carte. Transcris-la intégralement au format demandé.`,
          },
        ],
      },
    ],
  });
  if (response.stop_reason === "refusal") {
    throw new LlmError(
      `Le modèle a refusé la lecture de la carte (${response.stop_details?.category ?? "raison inconnue"}).`,
    );
  }
  if (response.stop_reason === "max_tokens") {
    throw new LlmError("La carte est trop longue pour une seule lecture : réessayez avec moins de pages.");
  }
  if (!response.parsed_output) {
    throw new LlmError("Réponse du modèle inexploitable lors de la lecture de la carte.");
  }
  return response.parsed_output;
}

export async function optimizeMenu(
  menu: ExtractedMenu,
  omnes: OmnesReport,
  input: AnalysisInput,
): Promise<Optimization> {
  if (isMockMode()) {
    await new Promise((r) => setTimeout(r, 1200));
    return structuredClone(MOCK_OPTIMIZATION);
  }
  const context = [
    `Restaurant : ${input.restaurant_name}`,
    `Ville : ${input.city}`,
    `Type déclaré : ${input.restaurant_type}`,
    input.goal ? `Objectif du restaurateur : ${input.goal}` : null,
  ]
    .filter(Boolean)
    .join("\n");

  const response = await getClient().beta.messages.parse({
    model: MODEL,
    max_tokens: 16000,
    betas: ["server-side-fallback-2026-07-01"],
    fallbacks: "default",
    system: [
      {
        type: "text",
        text: OPTIMIZATION_SYSTEM,
        cache_control: { type: "ephemeral" },
      },
    ],
    output_config: {
      effort: "high",
      format: betaZodOutputFormat(OptimizationSchema),
    },
    messages: [
      {
        role: "user",
        content: [
          { type: "text", text: `## Contexte\n${context}` },
          { type: "text", text: `## Carte extraite (JSON)\n${JSON.stringify(menu, null, 1)}` },
          {
            type: "text",
            text: `## Analyse Omnes (JSON)\n${JSON.stringify(omnes, null, 1)}\n\nRédige le diagnostic et la carte optimisée au format demandé.`,
          },
        ],
      },
    ],
  });
  if (response.stop_reason === "refusal") {
    throw new LlmError(
      `Le modèle a refusé l'optimisation (${response.stop_details?.category ?? "raison inconnue"}).`,
    );
  }
  if (response.stop_reason === "max_tokens") {
    throw new LlmError("La réponse d'optimisation a été tronquée : réessayez avec une carte plus courte.");
  }
  if (!response.parsed_output) {
    throw new LlmError("Réponse du modèle inexploitable lors de l'optimisation.");
  }
  return response.parsed_output;
}

/** Message d'erreur lisible pour l'utilisateur à partir d'une exception SDK. */
export function describeError(error: unknown): string {
  if (error instanceof LlmError) return error.message;
  if (error instanceof Anthropic.AuthenticationError) {
    return "Clé API Anthropic absente ou invalide. Renseignez ANTHROPIC_API_KEY (ou OPTICARTE_MOCK=1 pour le mode démo).";
  }
  if (error instanceof Anthropic.RateLimitError) {
    return "Limite de débit de l'API atteinte : réessayez dans quelques instants.";
  }
  if (error instanceof Anthropic.BadRequestError) {
    return `Requête refusée par l'API : ${error.message}`;
  }
  if (error instanceof Anthropic.APIConnectionError) {
    return "Impossible de joindre l'API Anthropic (réseau).";
  }
  if (error instanceof Anthropic.APIError) {
    return `Erreur API ${error.status ?? ""}: ${error.message}`;
  }
  if (error instanceof Error) return error.message;
  return "Erreur inconnue";
}
