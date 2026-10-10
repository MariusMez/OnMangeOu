import { describe, it } from "node:test";
import assert from "node:assert/strict";
import { analyseCategory, computeOmnes } from "../src/lib/omnes";
import { MOCK_MENU } from "../src/lib/mock-data";
import { formatMenuPrice } from "../src/lib/pdf";

describe("principes d'Omnes", () => {
  it("valide une gamme resserrée dont la zone médiane domine", () => {
    const report = analyseCategory("plat", [
      { name: "A", price: 14, highlighted: false },
      { name: "B", price: 16.5, highlighted: false },
      { name: "C", price: 17, highlighted: true },
      { name: "D", price: 17.5, highlighted: false },
      { name: "E", price: 18, highlighted: false },
      { name: "F", price: 21, highlighted: false },
    ]);
    assert.equal(report.opening_ratio, 1.5);
    assert.equal(report.opening_threshold, 2.5);
    const byKey = Object.fromEntries(report.principles.map((p) => [p.key, p.status]));
    assert.equal(byKey.ouverture, "ok");
    assert.equal(byKey.dispersion, "ok");
    assert.equal(byKey.promotion, "ok");
    assert.equal(byKey.rapport, "info");
  });

  it("signale une ouverture de gamme trop large et une promotion hors zone médiane", () => {
    const report = analyseCategory("plat", [
      { name: "Pâtes", price: 12, highlighted: false },
      { name: "Burger", price: 15, highlighted: false },
      { name: "Côte de bœuf", price: 58, highlighted: true },
    ]);
    const byKey = Object.fromEntries(report.principles.map((p) => [p.key, p]));
    assert.equal(byKey.ouverture.status, "warning");
    assert.equal(report.opening_ratio, 4.83);
    assert.equal(byKey.promotion.status, "warning");
    assert.match(byKey.promotion.detail, /Côte de bœuf/);
    assert.equal(report.zones.high.count, 1);
  });

  it("utilise un seuil de 3 au-delà de 9 plats", () => {
    const items = Array.from({ length: 10 }, (_, i) => ({
      name: `P${i}`,
      price: 10 + i * 2,
      highlighted: false,
    }));
    const report = analyseCategory("plat", items);
    assert.equal(report.opening_threshold, 3);
    assert.equal(report.principles[0].status, "ok");
  });

  it("ignore les catégories trop petites et les plats sans prix", () => {
    const report = computeOmnes({
      ...MOCK_MENU,
      sections: [
        ...MOCK_MENU.sections,
        {
          name: "Boissons",
          items: [
            { name: "Café", description: null, price: 2, price_text: "2€", category: "boisson", tags: [], is_highlighted: false },
            { name: "Thé", description: null, price: null, price_text: null, category: "boisson", tags: [], is_highlighted: false },
          ],
        },
      ],
    });
    assert.deepEqual(
      report.categories.map((c) => c.category),
      ["entree", "plat", "dessert"],
    );
    assert.equal(report.skipped[0]?.category, "boisson");
    assert.ok(report.notes.some((n) => n.includes("sans prix")));
    assert.ok(report.notes.some((n) => n.includes("plus de 7 plats")));
  });

  it("calcule un score global entre 0 et 100", () => {
    const report = computeOmnes(MOCK_MENU);
    assert.ok(report.score >= 0 && report.score <= 100);
    // Dans la carte de démo, le foie gras à 19 € casse l'ouverture de gamme des entrées.
    const entrees = report.categories.find((c) => c.category === "entree");
    assert.equal(entrees?.principles[0].status, "warning");
  });
});

describe("prix sur la carte", () => {
  it("retire le symbole monétaire et les centimes nuls", () => {
    assert.equal(formatMenuPrice(14), "14");
    assert.equal(formatMenuPrice(14.5), "14,50");
    assert.equal(formatMenuPrice(null), "");
  });
});
