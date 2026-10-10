import { NextResponse } from "next/server";
import { ACCEPTED_IMAGE_TYPES, MAX_IMAGES, MAX_UPLOAD_BYTES } from "@/lib/images";
import { startAnalysis } from "@/lib/pipeline";
import { AnalysisInputSchema } from "@/lib/schemas";

export const runtime = "nodejs";

export async function POST(request: Request) {
  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return NextResponse.json({ error: "Formulaire invalide." }, { status: 400 });
  }

  const parsed = AnalysisInputSchema.safeParse({
    restaurant_name: form.get("restaurant_name"),
    city: form.get("city"),
    restaurant_type: form.get("restaurant_type"),
    goal: form.get("goal") ?? "",
  });
  if (!parsed.success) {
    return NextResponse.json(
      { error: parsed.error.issues.map((i) => i.message).join(" ") },
      { status: 400 },
    );
  }

  const images = form.getAll("images").filter((f): f is File => f instanceof File && f.size > 0);
  if (images.length === 0) {
    return NextResponse.json({ error: "Ajoutez au moins une photo de la carte." }, { status: 400 });
  }
  if (images.length > MAX_IMAGES) {
    return NextResponse.json({ error: `Maximum ${MAX_IMAGES} photos par analyse.` }, { status: 400 });
  }
  const files: { buffer: Buffer; name: string }[] = [];
  for (const file of images) {
    if (file.type && !ACCEPTED_IMAGE_TYPES.includes(file.type)) {
      return NextResponse.json({ error: `Format non pris en charge : ${file.name}` }, { status: 400 });
    }
    if (file.size > MAX_UPLOAD_BYTES) {
      return NextResponse.json({ error: `Fichier trop volumineux (25 Mo max) : ${file.name}` }, { status: 400 });
    }
    files.push({ buffer: Buffer.from(await file.arrayBuffer()), name: file.name });
  }

  try {
    const id = await startAnalysis(parsed.data, files);
    return NextResponse.json({ id }, { status: 202 });
  } catch (error) {
    console.error("[analyze]", error);
    return NextResponse.json(
      { error: "Impossible de lire une des photos. Vérifiez le format et réessayez." },
      { status: 422 },
    );
  }
}
