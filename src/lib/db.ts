import Database from "better-sqlite3";
import fs from "node:fs";
import path from "node:path";
import { randomUUID } from "node:crypto";
import type { ExtractedMenu, Optimization } from "./schemas";
import type { OmnesReport } from "./omnes";

export type AnalysisStatus = "pending" | "extracting" | "optimizing" | "done" | "error";

export interface AnalysisRow {
  id: string;
  created_at: string;
  status: AnalysisStatus;
  restaurant_name: string;
  city: string;
  restaurant_type: string;
  goal: string;
  image_count: number;
  model: string;
  mock: number;
  error: string | null;
  menu_json: string | null;
  omnes_json: string | null;
  optimization_json: string | null;
}

export interface Analysis {
  id: string;
  created_at: string;
  status: AnalysisStatus;
  restaurant_name: string;
  city: string;
  restaurant_type: string;
  goal: string;
  image_count: number;
  model: string;
  mock: boolean;
  error: string | null;
  menu: ExtractedMenu | null;
  omnes: OmnesReport | null;
  optimization: Optimization | null;
}

export const DATA_DIR = process.env.OPTICARTE_DATA_DIR ?? path.join(process.cwd(), "data");
export const UPLOADS_DIR = path.join(DATA_DIR, "uploads");

let db: Database.Database | null = null;

export function getDb(): Database.Database {
  if (db) return db;
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.mkdirSync(UPLOADS_DIR, { recursive: true });
  db = new Database(path.join(DATA_DIR, "opticarte.db"));
  db.pragma("journal_mode = WAL");
  db.exec(`
    CREATE TABLE IF NOT EXISTS analyses (
      id TEXT PRIMARY KEY,
      created_at TEXT NOT NULL,
      status TEXT NOT NULL,
      restaurant_name TEXT NOT NULL,
      city TEXT NOT NULL,
      restaurant_type TEXT NOT NULL,
      goal TEXT NOT NULL DEFAULT '',
      image_count INTEGER NOT NULL DEFAULT 0,
      model TEXT NOT NULL DEFAULT '',
      mock INTEGER NOT NULL DEFAULT 0,
      error TEXT,
      menu_json TEXT,
      omnes_json TEXT,
      optimization_json TEXT
    );
    CREATE TABLE IF NOT EXISTS dishes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      analysis_id TEXT NOT NULL REFERENCES analyses(id) ON DELETE CASCADE,
      restaurant_name TEXT NOT NULL,
      city TEXT NOT NULL,
      restaurant_type TEXT NOT NULL,
      year INTEGER NOT NULL,
      section TEXT NOT NULL,
      name TEXT NOT NULL,
      description TEXT,
      category TEXT NOT NULL,
      price REAL,
      currency TEXT NOT NULL DEFAULT 'EUR',
      is_highlighted INTEGER NOT NULL DEFAULT 0
    );
    CREATE INDEX IF NOT EXISTS dishes_category_idx ON dishes(category, city, restaurant_type, year);
  `);
  return db;
}

function rowToAnalysis(row: AnalysisRow): Analysis {
  return {
    id: row.id,
    created_at: row.created_at,
    status: row.status,
    restaurant_name: row.restaurant_name,
    city: row.city,
    restaurant_type: row.restaurant_type,
    goal: row.goal,
    image_count: row.image_count,
    model: row.model,
    mock: row.mock === 1,
    error: row.error,
    menu: row.menu_json ? (JSON.parse(row.menu_json) as ExtractedMenu) : null,
    omnes: row.omnes_json ? (JSON.parse(row.omnes_json) as OmnesReport) : null,
    optimization: row.optimization_json
      ? (JSON.parse(row.optimization_json) as Optimization)
      : null,
  };
}

export function createAnalysis(input: {
  restaurant_name: string;
  city: string;
  restaurant_type: string;
  goal: string;
  image_count: number;
  model: string;
  mock: boolean;
}): string {
  const id = randomUUID().slice(0, 8);
  getDb()
    .prepare(
      `INSERT INTO analyses (id, created_at, status, restaurant_name, city, restaurant_type, goal, image_count, model, mock)
       VALUES (@id, @created_at, 'pending', @restaurant_name, @city, @restaurant_type, @goal, @image_count, @model, @mock)`,
    )
    .run({
      id,
      created_at: new Date().toISOString(),
      restaurant_name: input.restaurant_name,
      city: input.city,
      restaurant_type: input.restaurant_type,
      goal: input.goal,
      image_count: input.image_count,
      model: input.model,
      mock: input.mock ? 1 : 0,
    });
  return id;
}

export function setAnalysisStatus(id: string, status: AnalysisStatus, error?: string) {
  getDb()
    .prepare(`UPDATE analyses SET status = ?, error = ? WHERE id = ?`)
    .run(status, error ?? null, id);
}

export function saveExtraction(id: string, menu: ExtractedMenu, omnes: OmnesReport) {
  getDb()
    .prepare(`UPDATE analyses SET menu_json = ?, omnes_json = ?, status = 'optimizing' WHERE id = ?`)
    .run(JSON.stringify(menu), JSON.stringify(omnes), id);
}

export function saveOptimization(id: string, optimization: Optimization) {
  getDb()
    .prepare(`UPDATE analyses SET optimization_json = ?, status = 'done', error = NULL WHERE id = ?`)
    .run(JSON.stringify(optimization), id);
}

export function getAnalysis(id: string): Analysis | null {
  const row = getDb().prepare(`SELECT * FROM analyses WHERE id = ?`).get(id) as
    | AnalysisRow
    | undefined;
  return row ? rowToAnalysis(row) : null;
}

export function listAnalyses(limit = 50): Analysis[] {
  const rows = getDb()
    .prepare(`SELECT * FROM analyses ORDER BY created_at DESC LIMIT ?`)
    .all(limit) as AnalysisRow[];
  return rows.map(rowToAnalysis);
}

export function deleteAnalysis(id: string) {
  const d = getDb();
  d.prepare(`DELETE FROM dishes WHERE analysis_id = ?`).run(id);
  d.prepare(`DELETE FROM analyses WHERE id = ?`).run(id);
}

/** Alimente la base de plats (prix / année / ville / type) à partir d'une carte extraite. */
export function insertDishes(analysis: Analysis, menu: ExtractedMenu) {
  const d = getDb();
  const year = new Date(analysis.created_at).getFullYear();
  const insert = d.prepare(
    `INSERT INTO dishes (analysis_id, restaurant_name, city, restaurant_type, year, section, name, description, category, price, currency, is_highlighted)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
  );
  const tx = d.transaction(() => {
    d.prepare(`DELETE FROM dishes WHERE analysis_id = ?`).run(analysis.id);
    for (const section of menu.sections) {
      for (const item of section.items) {
        insert.run(
          analysis.id,
          analysis.restaurant_name,
          analysis.city,
          analysis.restaurant_type,
          year,
          section.name,
          item.name,
          item.description,
          item.category,
          item.price,
          menu.currency || "EUR",
          item.is_highlighted ? 1 : 0,
        );
      }
    }
  });
  tx();
}

export interface PriceStatRow {
  category: string;
  city: string;
  restaurant_type: string;
  year: number;
  dishes: number;
  restaurants: number;
  avg_price: number;
  min_price: number;
  max_price: number;
}

export interface StatsFilters {
  city?: string;
  restaurant_type?: string;
}

export function getPriceStats(filters: StatsFilters = {}): PriceStatRow[] {
  const where: string[] = ["price IS NOT NULL"];
  const params: Record<string, string> = {};
  if (filters.city) {
    where.push("city = @city");
    params.city = filters.city;
  }
  if (filters.restaurant_type) {
    where.push("restaurant_type = @restaurant_type");
    params.restaurant_type = filters.restaurant_type;
  }
  return getDb()
    .prepare(
      `SELECT category, city, restaurant_type, year,
              COUNT(*) AS dishes,
              COUNT(DISTINCT analysis_id) AS restaurants,
              ROUND(AVG(price), 2) AS avg_price,
              MIN(price) AS min_price,
              MAX(price) AS max_price
       FROM dishes
       WHERE ${where.join(" AND ")}
       GROUP BY category, city, restaurant_type, year
       ORDER BY year DESC, city, restaurant_type, category`,
    )
    .all(params) as PriceStatRow[];
}

export interface YearTrendRow {
  category: string;
  year: number;
  dishes: number;
  avg_price: number;
}

export function getYearTrend(filters: StatsFilters = {}): YearTrendRow[] {
  const where: string[] = ["price IS NOT NULL"];
  const params: Record<string, string> = {};
  if (filters.city) {
    where.push("city = @city");
    params.city = filters.city;
  }
  if (filters.restaurant_type) {
    where.push("restaurant_type = @restaurant_type");
    params.restaurant_type = filters.restaurant_type;
  }
  return getDb()
    .prepare(
      `SELECT category, year, COUNT(*) AS dishes, ROUND(AVG(price), 2) AS avg_price
       FROM dishes WHERE ${where.join(" AND ")}
       GROUP BY category, year ORDER BY category, year`,
    )
    .all(params) as YearTrendRow[];
}

export function getDistinctFilters(): { cities: string[]; types: string[] } {
  const d = getDb();
  const cities = (d.prepare(`SELECT DISTINCT city FROM dishes ORDER BY city`).all() as { city: string }[]).map((r) => r.city);
  const types = (
    d.prepare(`SELECT DISTINCT restaurant_type FROM dishes ORDER BY restaurant_type`).all() as {
      restaurant_type: string;
    }[]
  ).map((r) => r.restaurant_type);
  return { cities, types };
}

export interface TopDishRow {
  name: string;
  category: string;
  occurrences: number;
  avg_price: number;
}

export function getTopDishes(limit = 15): TopDishRow[] {
  return getDb()
    .prepare(
      `SELECT LOWER(TRIM(name)) AS name, category, COUNT(*) AS occurrences, ROUND(AVG(price), 2) AS avg_price
       FROM dishes WHERE price IS NOT NULL
       GROUP BY LOWER(TRIM(name)), category
       HAVING COUNT(*) > 1
       ORDER BY occurrences DESC, name LIMIT ?`,
    )
    .all(limit) as TopDishRow[];
}

export function getDbSummary(): { analyses: number; dishes: number; restaurants: number } {
  const d = getDb();
  const analyses = (d.prepare(`SELECT COUNT(*) AS n FROM analyses WHERE status = 'done'`).get() as { n: number }).n;
  const dishes = (d.prepare(`SELECT COUNT(*) AS n FROM dishes`).get() as { n: number }).n;
  const restaurants = (
    d.prepare(`SELECT COUNT(DISTINCT LOWER(restaurant_name) || '|' || LOWER(city)) AS n FROM dishes`).get() as {
      n: number;
    }
  ).n;
  return { analyses, dishes, restaurants };
}
