import { fmt } from "@/lib/omnes";
import { CATEGORY_LABELS, type DishCategory } from "@/lib/schemas";
import {
  getDbSummary,
  getDistinctFilters,
  getPriceStats,
  getTopDishes,
  getYearTrend,
} from "@/lib/db";

export const dynamic = "force-dynamic";

function label(category: string) {
  return CATEGORY_LABELS[category as DishCategory] ?? category;
}

export default async function StatsPage(props: PageProps<"/stats">) {
  const sp = await props.searchParams;
  const city = typeof sp.city === "string" && sp.city ? sp.city : undefined;
  const restaurant_type = typeof sp.type === "string" && sp.type ? sp.type : undefined;
  const filters = { city, restaurant_type };

  const summary = getDbSummary();
  const { cities, types } = getDistinctFilters();
  const stats = getPriceStats(filters);
  const trend = getYearTrend(filters);
  const top = getTopDishes();

  const trendByCategory = new Map<string, typeof trend>();
  for (const row of trend) {
    trendByCategory.set(row.category, [...(trendByCategory.get(row.category) ?? []), row]);
  }

  return (
    <div className="grid gap-8">
      <header className="grid gap-2">
        <h1 className="text-2xl font-semibold tracking-tight">Observatoire des prix</h1>
        <p className="text-sm text-muted">
          Chaque carte analysée alimente une base de plats datée et localisée : {summary.dishes} plats,{" "}
          {summary.restaurants} restaurants, {summary.analyses} analyses terminées.
        </p>
      </header>

      <form className="flex flex-wrap items-end gap-3 rounded-xl border border-border bg-card p-4 text-sm">
        <label className="grid gap-1">
          <span className="text-xs uppercase tracking-wide text-muted">Ville</span>
          <select name="city" defaultValue={city ?? ""} className="rounded-md border border-border bg-white px-3 py-1.5">
            <option value="">Toutes</option>
            {cities.map((c) => <option key={c} value={c}>{c}</option>)}
          </select>
        </label>
        <label className="grid gap-1">
          <span className="text-xs uppercase tracking-wide text-muted">Type</span>
          <select name="type" defaultValue={restaurant_type ?? ""} className="rounded-md border border-border bg-white px-3 py-1.5">
            <option value="">Tous</option>
            {types.map((t) => <option key={t} value={t}>{t}</option>)}
          </select>
        </label>
        <button className="rounded-md bg-accent px-3 py-1.5 text-white">Filtrer</button>
      </form>

      {stats.length === 0 ? (
        <p className="text-muted">Aucune donnée pour ces filtres. Analysez une carte pour alimenter l’observatoire.</p>
      ) : (
        <>
          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Évolution du prix moyen par année</h2>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {[...trendByCategory.entries()].map(([category, rows]) => (
                <div key={category} className="rounded-xl border border-border bg-card p-4 text-sm">
                  <h3 className="font-semibold">{label(category)}</h3>
                  <table className="mt-2 w-full">
                    <tbody>
                      {rows.map((r, i) => {
                        const prev = rows[i - 1];
                        const delta = prev ? ((r.avg_price - prev.avg_price) / prev.avg_price) * 100 : null;
                        return (
                          <tr key={r.year} className="border-t border-border">
                            <td className="py-1">{r.year}</td>
                            <td className="py-1 text-right font-medium">{fmt(r.avg_price)}</td>
                            <td className="py-1 text-right text-xs text-muted">
                              {delta === null ? `${r.dishes} plats` : `${delta >= 0 ? "+" : ""}${delta.toFixed(1)} %`}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              ))}
            </div>
          </section>

          <section className="grid gap-3">
            <h2 className="text-lg font-semibold">Prix par zone, type de restaurant et catégorie</h2>
            <div className="overflow-x-auto rounded-xl border border-border bg-card">
              <table className="w-full text-sm">
                <thead className="bg-background text-left text-xs uppercase tracking-wide text-muted">
                  <tr>
                    <th className="px-3 py-2">Année</th>
                    <th className="px-3 py-2">Ville</th>
                    <th className="px-3 py-2">Type</th>
                    <th className="px-3 py-2">Catégorie</th>
                    <th className="px-3 py-2 text-right">Plats</th>
                    <th className="px-3 py-2 text-right">Restaurants</th>
                    <th className="px-3 py-2 text-right">Moyenne</th>
                    <th className="px-3 py-2 text-right">Min</th>
                    <th className="px-3 py-2 text-right">Max</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.map((r, i) => (
                    <tr key={i} className="border-t border-border">
                      <td className="px-3 py-1.5">{r.year}</td>
                      <td className="px-3 py-1.5">{r.city}</td>
                      <td className="px-3 py-1.5">{r.restaurant_type}</td>
                      <td className="px-3 py-1.5">{label(r.category)}</td>
                      <td className="px-3 py-1.5 text-right">{r.dishes}</td>
                      <td className="px-3 py-1.5 text-right">{r.restaurants}</td>
                      <td className="px-3 py-1.5 text-right font-medium">{fmt(r.avg_price)}</td>
                      <td className="px-3 py-1.5 text-right text-muted">{fmt(r.min_price)}</td>
                      <td className="px-3 py-1.5 text-right text-muted">{fmt(r.max_price)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}

      {top.length > 0 && (
        <section className="grid gap-3">
          <h2 className="text-lg font-semibold">Plats les plus fréquents</h2>
          <div className="overflow-x-auto rounded-xl border border-border bg-card">
            <table className="w-full text-sm">
              <thead className="bg-background text-left text-xs uppercase tracking-wide text-muted">
                <tr>
                  <th className="px-3 py-2">Plat</th>
                  <th className="px-3 py-2">Catégorie</th>
                  <th className="px-3 py-2 text-right">Cartes</th>
                  <th className="px-3 py-2 text-right">Prix moyen</th>
                </tr>
              </thead>
              <tbody>
                {top.map((t) => (
                  <tr key={`${t.name}-${t.category}`} className="border-t border-border">
                    <td className="px-3 py-1.5 capitalize">{t.name}</td>
                    <td className="px-3 py-1.5">{label(t.category)}</td>
                    <td className="px-3 py-1.5 text-right">{t.occurrences}</td>
                    <td className="px-3 py-1.5 text-right font-medium">{fmt(t.avg_price)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}
    </div>
  );
}
