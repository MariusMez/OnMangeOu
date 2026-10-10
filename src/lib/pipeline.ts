import fs from "node:fs";
import path from "node:path";
import {
  createAnalysis,
  getAnalysis,
  insertDishes,
  saveExtraction,
  saveOptimization,
  setAnalysisStatus,
  UPLOADS_DIR,
} from "./db";
import { normalizeImage, type NormalizedImage } from "./images";
import { describeError, extractMenu, isMockMode, MODEL, optimizeMenu } from "./llm";
import { computeOmnes } from "./omnes";
import type { AnalysisInput } from "./schemas";

/**
 * Orchestration d'une analyse : normalisation des photos → extraction LLM →
 * principes d'Omnes → optimisation LLM → base de plats.
 * L'analyse est créée immédiatement ; le traitement continue en arrière-plan
 * et la page de résultat se rafraîchit jusqu'à "done" ou "error".
 */
export async function startAnalysis(
  input: AnalysisInput,
  files: { buffer: Buffer; name: string }[],
): Promise<string> {
  const images: NormalizedImage[] = [];
  for (const file of files) {
    images.push(await normalizeImage(file.buffer));
  }

  const id = createAnalysis({
    ...input,
    image_count: images.length,
    model: isMockMode() ? "mock" : MODEL,
    mock: isMockMode(),
  });

  const dir = path.join(UPLOADS_DIR, id);
  fs.mkdirSync(dir, { recursive: true });
  images.forEach((img, i) => {
    fs.writeFileSync(path.join(dir, `${i + 1}.jpg`), img.data);
  });

  void runAnalysis(id, input, images);
  return id;
}

async function runAnalysis(id: string, input: AnalysisInput, images: NormalizedImage[]) {
  try {
    setAnalysisStatus(id, "extracting");
    const menu = await extractMenu(images);
    const omnes = computeOmnes(menu);
    saveExtraction(id, menu, omnes);

    const analysis = getAnalysis(id);
    if (analysis) insertDishes(analysis, menu);

    const optimization = await optimizeMenu(menu, omnes, input);
    saveOptimization(id, optimization);
  } catch (error) {
    console.error(`[analysis ${id}]`, error);
    setAnalysisStatus(id, "error", describeError(error));
  }
}

export function uploadedImagePath(id: string, index: number): string | null {
  const p = path.join(UPLOADS_DIR, id, `${index}.jpg`);
  return fs.existsSync(p) ? p : null;
}
