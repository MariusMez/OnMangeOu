import type { AnalysisStatus } from "@/lib/db";

const LABELS: Record<AnalysisStatus, { text: string; cls: string }> = {
  pending: { text: "En attente", cls: "bg-zinc-100 text-zinc-700" },
  extracting: { text: "Lecture de la carte…", cls: "bg-amber-100 text-amber-800" },
  optimizing: { text: "Optimisation…", cls: "bg-amber-100 text-amber-800" },
  done: { text: "Terminée", cls: "bg-emerald-100 text-emerald-800" },
  error: { text: "Erreur", cls: "bg-red-100 text-red-800" },
};

export function StatusBadge({ status }: { status: AnalysisStatus }) {
  const l = LABELS[status];
  return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${l.cls}`}>{l.text}</span>;
}
