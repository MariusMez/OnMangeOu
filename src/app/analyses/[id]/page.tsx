import Link from "next/link";
import { notFound } from "next/navigation";
import { getAnalysis } from "@/lib/db";
import { fmt } from "@/lib/omnes";
import {
  CATEGORY_LABELS,
  MENU_ENGINEERING_LABELS,
  type DishCategory,
} from "@/lib/schemas";
import { formatMenuPrice } from "@/lib/pdf";
import { AutoRefresh } from "@/components/AutoRefresh";
import { StatusBadge } from "@/components/StatusBadge";
import { OmnesPanel } from "@/components/OmnesPanel";

export const dynamic = "force-dynamic";

export default async function AnalysisPage(props: PageProps<"/analyses/[id]">) {
  const { id } = await props.params;
  const analysis = getAnalysis(id);
  if (!analysis) notFound();

  const running = analysis.status === "pending" || analysis.status === "extracting" || analysis.status === "optimizing";
  const { menu, omnes, optimization } = analysis;

  return (
    <div className="grid gap-8">
      {running && <AutoRefresh />}

      <header className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">{analysis.restaurant_name}</h1>
          <p className="text-sm text-muted">
            {analysis.city} · {analysis.restaurant_type} · {new Date(analysis.created_at).toLocaleString("fr-FR")}
            {analysis.mock && " · démo"}
          </p>
          {analysis.goal && <p className="mt-1 text-sm">Objectif : {analysis.goal}</p>}
        </div>
        <div className="flex items-center gap-3">
          <StatusBadge status={analysis.status} />
          {optimization && (
            <a
              href={`/analyses/${analysis.id}/pdf`}
              className="rounded-md bg-accent px-4 py-2 text-sm font-medium text-white hover:opacity-90"
            >
              Télécharger la carte optimisée (PDF)
            </a>
          )}
        </div>
      </header>

      {analysis.status === "error" && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          <p className="font-medium">L’analyse a échoué.</p>
          <p>{analysis.error}</p>
          <Link href="/" className="mt-2 inline-block underline">Relancer une analyse</Link>
        </div>
      )}

      {running && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          {analysis.status === "optimizing"
            ? "Carte lue. Le consultant IA rédige le diagnostic et la carte optimisée (1 à 3 minutes)…"
            : "Lecture des photos en cours…"}
        </div>
      )}

      {analysis.image_count > 0 && (
        <section className="grid gap-2">
          <h2 className="text-sm font-semibold uppercase tracking-wide text-muted">Photos analysées</h2>
          <div className="flex flex-wrap gap-3">
            {Array.from({ length: analysis.image_count }, (_, i) => (
              <a key={i} href={`/analyses/${analysis.id}/images/${i + 1}`} target="_blank" rel="noreferrer">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={`/analyses/${analysis.id}/images/${i + 1}`}
                  alt={`Photo ${i + 1} de la carte`}
                  className="h-28 rounded-md border border-border object-cover"
                />
              </a>
            ))}
          </div>
        </section>
      )}

      {optimization && (
        <section className="grid gap-4 rounded-xl border border-border bg-card p-6">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 className="text-lg font-semibold">Diagnostic</h2>
            <Score label="Note de la carte actuelle" value={optimization.overall_score} />
          </div>
          <p className="leading-relaxed">{optimization.summary}</p>
          {optimization.strengths.length > 0 && (
            <div>
              <h3 className="mb-1 text-sm font-semibold text-muted">Points forts</h3>
              <ul className="list-disc pl-5 text-sm">
                {optimization.strengths.map((s) => <li key={s}>{s}</li>)}
              </ul>
            </div>
          )}
        </section>
      )}

      {omnes && <OmnesPanel report={omnes} />}

      {optimization && (
        <div className="grid gap-8 lg:grid-cols-2">
          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Mise en page & structure</h2>
            <ul className="grid gap-2">
              {optimization.layout_recommendations.map((r) => (
                <li key={r.title} className="rounded-lg border border-border bg-card p-3 text-sm">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{r.title}</span>
                    <Priority value={r.priority} />
                  </div>
                  <p className="mt-1 text-muted">{r.detail}</p>
                </li>
              ))}
            </ul>
          </section>

          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Prix recommandés</h2>
            {optimization.pricing_recommendations.length === 0 ? (
              <p className="text-sm text-muted">Aucun ajustement de prix recommandé.</p>
            ) : (
              <table className="w-full rounded-lg border border-border bg-card text-sm">
                <thead className="text-left text-xs uppercase tracking-wide text-muted">
                  <tr>
                    <th className="px-3 py-2">Plat</th>
                    <th className="px-3 py-2">Actuel</th>
                    <th className="px-3 py-2">Proposé</th>
                  </tr>
                </thead>
                <tbody>
                  {optimization.pricing_recommendations.map((p) => (
                    <tr key={p.item} className="border-t border-border align-top">
                      <td className="px-3 py-2">
                        <div className="font-medium">{p.item}</div>
                        <div className="text-xs text-muted">{p.rationale}</div>
                      </td>
                      <td className="px-3 py-2 whitespace-nowrap">{p.current_price === null ? "—" : fmt(p.current_price)}</td>
                      <td className="px-3 py-2 whitespace-nowrap font-medium text-accent">{p.suggested_price === null ? "—" : fmt(p.suggested_price)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </section>
        </div>
      )}

      {optimization && optimization.menu_engineering.length > 0 && (
        <section className="grid gap-3">
          <h2 className="text-lg font-semibold">Matrice d’ingénierie de menu <span className="text-sm font-normal text-muted">(estimée sans données de ventes)</span></h2>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {(["etoile", "vache_a_lait", "enigme", "poids_mort"] as const).map((cls) => {
              const items = optimization.menu_engineering.filter((m) => m.classification === cls);
              return (
                <div key={cls} className="rounded-lg border border-border bg-card p-3 text-sm">
                  <h3 className="mb-2 font-semibold">{MENU_ENGINEERING_LABELS[cls]}</h3>
                  {items.length === 0 ? (
                    <p className="text-xs text-muted">—</p>
                  ) : (
                    <ul className="grid gap-2">
                      {items.map((m) => (
                        <li key={m.item}>
                          <div className="font-medium">{m.item}</div>
                          <div className="text-xs text-muted">marge {m.estimated_margin} · popularité {m.estimated_popularity}</div>
                          <div className="text-xs">{m.action}</div>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              );
            })}
          </div>
        </section>
      )}

      {optimization && (
        <div className="grid gap-8 lg:grid-cols-2">
          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Corrections</h2>
            {optimization.corrections.length === 0 ? (
              <p className="text-sm text-muted">Aucune faute relevée.</p>
            ) : (
              <ul className="grid gap-2 text-sm">
                {optimization.corrections.map((c, i) => (
                  <li key={i} className="rounded-lg border border-border bg-card p-3">
                    <span className="line-through text-muted">{c.original}</span>{" → "}
                    <span className="font-medium">{c.corrected}</span>
                    <div className="text-xs text-muted">{c.reason}</div>
                  </li>
                ))}
              </ul>
            )}
          </section>
          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Descriptions réécrites</h2>
            <ul className="grid gap-2 text-sm">
              {optimization.description_rewrites.map((d, i) => (
                <li key={i} className="rounded-lg border border-border bg-card p-3">
                  <div className="text-xs uppercase tracking-wide text-muted">{d.section} · {d.original_name}</div>
                  <div className="font-medium">{d.new_name}</div>
                  <div className="italic">{d.new_description}</div>
                  <div className="mt-1 text-xs text-muted">{d.rationale}</div>
                </li>
              ))}
            </ul>
          </section>
        </div>
      )}

      {optimization && (
        <section className="grid gap-3">
          <h2 className="text-lg font-semibold">Aperçu de la carte optimisée</h2>
          <div className="mx-auto w-full max-w-2xl rounded-xl border border-border bg-white p-8 shadow-sm">
            <h3 className="text-center text-2xl font-semibold">{optimization.optimized_menu.title}</h3>
            {optimization.optimized_menu.tagline && (
              <p className="text-center text-sm italic text-muted">{optimization.optimized_menu.tagline}</p>
            )}
            {optimization.optimized_menu.sections.map((s) => (
              <div key={s.name} className="mt-6">
                <h4 className="text-center text-sm font-semibold uppercase tracking-widest text-accent">{s.name}</h4>
                {s.intro && <p className="text-center text-xs italic text-muted">{s.intro}</p>}
                <ul className="mt-3 grid gap-2.5">
                  {s.items.map((it) => (
                    <li key={it.name} className={`${it.highlight ? "border-l-2 border-accent pl-3" : "pl-3.5"}`}>
                      <div className="flex items-baseline justify-between gap-4">
                        <span className="font-medium">{it.name}</span>
                        <span className="text-sm">{formatMenuPrice(it.price)}</span>
                      </div>
                      {it.description && <div className="text-sm text-muted">{it.description}</div>}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
            {optimization.optimized_menu.footer_note && (
              <p className="mt-8 border-t border-border pt-3 text-center text-xs text-muted">{optimization.optimized_menu.footer_note}</p>
            )}
          </div>
        </section>
      )}

      {menu && (
        <details className="rounded-xl border border-border bg-card p-4">
          <summary className="cursor-pointer text-sm font-semibold">
            Carte transcrite ({menu.sections.reduce((n, s) => n + s.items.length, 0)} plats)
            {menu.reading_notes && <span className="ml-2 font-normal text-muted">— {menu.reading_notes}</span>}
          </summary>
          <div className="mt-4 grid gap-4 text-sm">
            {menu.sections.map((s) => (
              <div key={s.name}>
                <h4 className="font-semibold">{s.name}</h4>
                <ul className="mt-1 grid gap-1">
                  {s.items.map((it, i) => (
                    <li key={i} className="flex items-baseline justify-between gap-3">
                      <span>
                        {it.is_highlighted && <span className="mr-1 text-accent">★</span>}
                        {it.name}
                        {it.description && <span className="text-muted"> — {it.description}</span>}
                        <span className="ml-2 rounded bg-accent-soft px-1.5 text-[10px] uppercase text-accent">
                          {CATEGORY_LABELS[it.category as DishCategory]}
                        </span>
                      </span>
                      <span className="whitespace-nowrap text-muted">{it.price_text ?? (it.price !== null ? fmt(it.price) : "—")}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </details>
      )}
    </div>
  );
}

function Score({ label, value }: { label: string; value: number }) {
  const tone = value >= 75 ? "text-emerald-700" : value >= 50 ? "text-amber-700" : "text-red-700";
  return (
    <div className="text-right">
      <div className="text-xs uppercase tracking-wide text-muted">{label}</div>
      <div className={`text-2xl font-semibold ${tone}`}>{Math.round(value)} / 100</div>
    </div>
  );
}

function Priority({ value }: { value: "haute" | "moyenne" | "basse" }) {
  const cls =
    value === "haute" ? "bg-red-100 text-red-800" : value === "moyenne" ? "bg-amber-100 text-amber-800" : "bg-zinc-100 text-zinc-700";
  return <span className={`rounded-full px-2 py-0.5 text-xs ${cls}`}>{value}</span>;
}
