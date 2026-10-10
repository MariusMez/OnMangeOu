import Link from "next/link";
import { listAnalyses } from "@/lib/db";
import { StatusBadge } from "@/components/StatusBadge";

export const dynamic = "force-dynamic";

export default function AnalysesPage() {
  const analyses = listAnalyses();
  return (
    <div className="grid gap-6">
      <h1 className="text-2xl font-semibold tracking-tight">Historique des analyses</h1>
      {analyses.length === 0 ? (
        <p className="text-muted">
          Aucune analyse pour l’instant. <Link href="/" className="text-accent underline">Analyser une carte</Link>.
        </p>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-border bg-card">
          <table className="w-full text-sm">
            <thead className="bg-background text-left text-xs uppercase tracking-wide text-muted">
              <tr>
                <th className="px-4 py-2">Restaurant</th>
                <th className="px-4 py-2">Ville</th>
                <th className="px-4 py-2">Type</th>
                <th className="px-4 py-2">Date</th>
                <th className="px-4 py-2">Score Omnes</th>
                <th className="px-4 py-2">Statut</th>
              </tr>
            </thead>
            <tbody>
              {analyses.map((a) => (
                <tr key={a.id} className="border-t border-border hover:bg-background">
                  <td className="px-4 py-2 font-medium">
                    <Link href={`/analyses/${a.id}`} className="hover:text-accent">{a.restaurant_name}</Link>
                  </td>
                  <td className="px-4 py-2">{a.city}</td>
                  <td className="px-4 py-2">{a.restaurant_type}</td>
                  <td className="px-4 py-2 text-muted">{new Date(a.created_at).toLocaleString("fr-FR")}</td>
                  <td className="px-4 py-2">{a.omnes ? `${a.omnes.score} / 100` : "—"}</td>
                  <td className="px-4 py-2"><StatusBadge status={a.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
