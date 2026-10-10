"use client";

import { useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";

const RESTAURANT_TYPES = [
  "Bistrot / brasserie",
  "Pizzeria / italien",
  "Gastronomique",
  "Restauration rapide",
  "Asiatique",
  "Crêperie",
  "Bar à vin / tapas",
  "Cuisine du monde",
  "Autre",
];

export function UploadForm() {
  const router = useRouter();
  const [files, setFiles] = useState<File[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    if (files.length === 0) {
      setError("Ajoutez au moins une photo de la carte.");
      return;
    }
    setSubmitting(true);
    const form = new FormData(event.currentTarget);
    form.delete("images");
    for (const f of files) form.append("images", f);
    try {
      const res = await fetch("/api/analyze", { method: "POST", body: form });
      const json = (await res.json()) as { id?: string; error?: string };
      if (!res.ok || !json.id) {
        setError(json.error ?? "Échec de l’envoi.");
        setSubmitting(false);
        return;
      }
      router.push(`/analyses/${json.id}`);
    } catch {
      setError("Impossible de contacter le serveur.");
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="grid gap-5 rounded-xl border border-border bg-card p-6 shadow-sm">
      <div className="grid gap-4 sm:grid-cols-2">
        <label className="grid gap-1 text-sm">
          <span className="font-medium">Nom du restaurant</span>
          <input name="restaurant_name" required className="rounded-md border border-border px-3 py-2" placeholder="Chez Lucien" />
        </label>
        <label className="grid gap-1 text-sm">
          <span className="font-medium">Ville</span>
          <input name="city" required className="rounded-md border border-border px-3 py-2" placeholder="Lyon" />
        </label>
        <label className="grid gap-1 text-sm">
          <span className="font-medium">Type de restaurant</span>
          <select name="restaurant_type" required className="rounded-md border border-border bg-white px-3 py-2">
            {RESTAURANT_TYPES.map((t) => (
              <option key={t} value={t}>{t}</option>
            ))}
          </select>
        </label>
        <label className="grid gap-1 text-sm">
          <span className="font-medium">Objectif (facultatif)</span>
          <input name="goal" className="rounded-md border border-border px-3 py-2" placeholder="Augmenter le ticket moyen le midi" />
        </label>
      </div>

      <label className="grid gap-2 text-sm">
        <span className="font-medium">Photos de la carte (1 à 6, JPG / PNG / WEBP)</span>
        <input
          type="file"
          name="images"
          accept="image/*"
          multiple
          onChange={(e) => setFiles(Array.from(e.target.files ?? []).slice(0, 6))}
          className="rounded-md border border-dashed border-border bg-background px-3 py-6 file:mr-4 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1.5 file:text-white"
        />
        {files.length > 0 && (
          <ul className="flex flex-wrap gap-2 text-xs text-muted">
            {files.map((f) => (
              <li key={f.name} className="rounded bg-accent-soft px-2 py-1">
                {f.name} · {(f.size / 1024 / 1024).toFixed(1)} Mo
              </li>
            ))}
          </ul>
        )}
      </label>

      {error && <p className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}

      <button
        type="submit"
        disabled={submitting}
        className="rounded-md bg-accent px-4 py-2.5 font-medium text-white hover:opacity-90 disabled:opacity-50"
      >
        {submitting ? "Envoi en cours…" : "Analyser ma carte"}
      </button>
    </form>
  );
}
