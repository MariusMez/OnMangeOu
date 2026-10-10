export const dynamic = "force-dynamic";

import { UploadForm } from "@/components/UploadForm";

export default function Home() {
  return (
    <div className="grid gap-8">
      <section className="grid gap-3">
        <h1 className="text-3xl font-semibold tracking-tight">
          Votre carte vend-elle vraiment vos meilleurs plats ?
        </h1>
        <p className="max-w-2xl text-muted">
          Photographiez votre carte. OptiCarte la transcrit, applique les principes d’Omnes
          et l’ingénierie de menu, corrige les fautes, réécrit les descriptions et génère une
          carte optimisée prête à imprimer.
        </p>
      </section>
      <UploadForm />
      <section className="grid gap-3 text-sm text-muted sm:grid-cols-3">
        <Step n={1} title="Lecture" text="Transcription fidèle des plats, prix et rubriques par le modèle de vision." />
        <Step n={2} title="Diagnostic" text="Ouverture de gamme, dispersion des prix, promotion, matrice étoiles / vaches à lait / énigmes / poids morts." />
        <Step n={3} title="Carte optimisée" text="Nouvelles descriptions, ordre et prix recommandés, export PDF." />
      </section>
    </div>
  );
}

function Step({ n, title, text }: { n: number; title: string; text: string }) {
  return (
    <div className="rounded-lg border border-border bg-card p-4">
      <div className="mb-1 text-xs font-semibold uppercase tracking-wide text-accent">Étape {n}</div>
      <div className="font-medium text-foreground">{title}</div>
      <p className="mt-1">{text}</p>
    </div>
  );
}
