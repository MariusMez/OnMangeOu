import sharp from "sharp";

export const ACCEPTED_IMAGE_TYPES = ["image/jpeg", "image/png", "image/webp", "image/heic", "image/heif"];
export const MAX_IMAGES = 6;
export const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

export interface NormalizedImage {
  data: Buffer;
  mediaType: "image/jpeg";
  width: number;
  height: number;
}

/**
 * Normalise une photo de carte avant envoi au modèle :
 * orientation EXIF appliquée, redimensionnée à 1600 px max, JPEG qualité 85.
 * Cela garde chaque image bien sous la limite de 5 Mo de l'API.
 */
export async function normalizeImage(input: Buffer): Promise<NormalizedImage> {
  const pipeline = sharp(input, { failOn: "none" })
    .rotate()
    .resize({ width: 1600, height: 1600, fit: "inside", withoutEnlargement: true })
    .jpeg({ quality: 85, mozjpeg: true });
  const { data, info } = await pipeline.toBuffer({ resolveWithObject: true });
  return { data, mediaType: "image/jpeg", width: info.width, height: info.height };
}
