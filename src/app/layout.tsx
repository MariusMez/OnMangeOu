import type { Metadata } from "next";
import Link from "next/link";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";

const geistSans = Geist({ variable: "--font-geist-sans", subsets: ["latin"] });
const geistMono = Geist_Mono({ variable: "--font-geist-mono", subsets: ["latin"] });

export const metadata: Metadata = {
  title: "OptiCarte — optimisation de cartes de restaurant",
  description:
    "Photographiez votre carte, obtenez un diagnostic d'ingénierie de menu et une carte optimisée en PDF.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  const mock = process.env.OPTICARTE_MOCK === "1";
  return (
    <html lang="fr" className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}>
      <body className="min-h-full flex flex-col font-sans">
        <header className="border-b border-border bg-card">
          <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <Link href="/" className="text-lg font-semibold tracking-tight">
              <span className="text-accent">Opti</span>Carte
            </Link>
            <nav className="flex gap-5 text-sm">
              <Link href="/" className="hover:text-accent">Analyser</Link>
              <Link href="/analyses" className="hover:text-accent">Historique</Link>
              <Link href="/stats" className="hover:text-accent">Observatoire des prix</Link>
            </nav>
          </div>
          {mock && (
            <div className="bg-amber-100 text-amber-900 text-center text-xs py-1">
              Mode démo : les analyses utilisent une carte fictive, aucune requête à l’API Claude.
            </div>
          )}
        </header>
        <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">{children}</main>
        <footer className="border-t border-border py-4 text-center text-xs text-muted">
          OptiCarte MVP — analyse propulsée par Claude. Les estimations de marge et de popularité sont indicatives.
        </footer>
      </body>
    </html>
  );
}
