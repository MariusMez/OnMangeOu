import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // L'app lit SQLite à chaque requête : on reste en rendu dynamique classique.
  cacheComponents: false,
  serverExternalPackages: ["better-sqlite3", "pdfkit", "sharp"],
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
