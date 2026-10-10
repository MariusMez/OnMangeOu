import { fmt, type OmnesReport, type OmnesStatus } from "@/lib/omnes";

const STATUS_STYLE: Record<OmnesStatus, string> = {
  ok: "bg-emerald-100 text-emerald-800",
  warning: "bg-red-100 text-red-800",
  info: "bg-zinc-100 text-zinc-700",
};
const STATUS_LABEL: Record<OmnesStatus, string> = { ok: "OK", warning: "À corriger", info: "Info" };

export function OmnesPanel({ report }: { report: OmnesReport }) {
  return (
    <section className="grid gap-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold">
          Principes d’Omnes <span className="text-sm font-normal text-muted">(calculés sur les prix lus)</span>
        </h2>
        <div className="text-sm">
          Conformité : <span className="font-semibold">{report.score} %</span>
        </div>
      </div>

      {report.notes.length > 0 && (
        <ul className="list-disc pl-5 text-sm text-muted">
          {report.notes.map((n) => <li key={n}>{n}</li>)}
        </ul>
      )}

      {report.categories.length === 0 && (
        <p className="text-sm text-muted">Pas assez de plats avec prix lisible pour appliquer les principes.</p>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        {report.categories.map((c) => (
          <div key={c.category} className="rounded-xl border border-border bg-card p-4 text-sm">
            <div className="flex items-baseline justify-between">
              <h3 className="font-semibold">{c.label}</h3>
              <span className="text-xs text-muted">
                {c.priced_count} plats · {fmt(c.min)} → {fmt(c.max)} · moyenne {fmt(c.mean)}
              </span>
            </div>
            <div className="mt-3 grid grid-cols-3 gap-1 text-center text-xs">
              {(["low", "mid", "high"] as const).map((z) => (
                <div key={z} className={`rounded px-1 py-1.5 ${z === "mid" ? "bg-accent-soft" : "bg-background"}`}>
                  <div className="font-semibold">{c.zones[z].count}</div>
                  <div className="text-muted">{fmt(c.zones[z].from)} – {fmt(c.zones[z].to)}</div>
                </div>
              ))}
            </div>
            <ul className="mt-3 grid gap-2">
              {c.principles.map((p) => (
                <li key={p.key} className="grid gap-0.5">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{p.title}</span>
                    <span className={`rounded-full px-2 py-0.5 text-xs ${STATUS_STYLE[p.status]}`}>{STATUS_LABEL[p.status]}</span>
                  </div>
                  <div className="text-xs text-muted">{p.value}</div>
                  <div className="text-xs">{p.detail}</div>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>

      {report.skipped.length > 0 && (
        <p className="text-xs text-muted">
          Non analysé : {report.skipped.map((s) => `${s.label} (${s.reason})`).join(" · ")}
        </p>
      )}
    </section>
  );
}
