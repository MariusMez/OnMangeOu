import PDFDocument from "pdfkit";
import type { OptimizedMenu } from "./schemas";

/** Prix sans symbole monétaire (psychologie de carte) : 14 → "14", 14.5 → "14,50". */
export function formatMenuPrice(price: number | null): string {
  if (price === null) return "";
  const s = price.toFixed(2).replace(".", ",");
  return s.endsWith(",00") ? s.slice(0, -3) : s;
}

const INK = "#1f1a17";
const ACCENT = "#8b2f1d";
const MUTED = "#6b625c";

/**
 * Génère la carte optimisée en PDF A4 avec PDFKit (polices standard Helvetica,
 * qui couvrent les accents français).
 */
export function renderMenuPdf(menu: OptimizedMenu, meta: { city?: string }): Promise<Buffer> {
  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({
      size: "A4",
      margins: { top: 56, bottom: 56, left: 60, right: 60 },
      info: { Title: menu.title, Author: "OptiCarte" },
    });
    const chunks: Buffer[] = [];
    doc.on("data", (c: Buffer) => chunks.push(c));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    const width = doc.page.width - doc.page.margins.left - doc.page.margins.right;
    const bottomLimit = () => doc.page.height - doc.page.margins.bottom;

    const ensureSpace = (needed: number) => {
      if (doc.y + needed > bottomLimit()) doc.addPage();
    };

    // En-tête
    doc.fillColor(INK).font("Helvetica-Bold").fontSize(28).text(menu.title, { align: "center" });
    if (menu.tagline) {
      doc.moveDown(0.2);
      doc.font("Helvetica-Oblique").fontSize(11).fillColor(MUTED).text(menu.tagline, { align: "center" });
    }
    if (meta.city) {
      doc.moveDown(0.1);
      doc.font("Helvetica").fontSize(9).fillColor(MUTED).text(meta.city.toUpperCase(), { align: "center", characterSpacing: 2 });
    }
    doc.moveDown(0.8);
    rule(doc, width);

    for (const section of menu.sections) {
      ensureSpace(90);
      doc.moveDown(0.9);
      doc
        .font("Helvetica-Bold")
        .fontSize(14)
        .fillColor(ACCENT)
        .text(section.name.toUpperCase(), { characterSpacing: 1.5, align: "center" });
      if (section.intro) {
        doc.moveDown(0.15);
        doc.font("Helvetica-Oblique").fontSize(9.5).fillColor(MUTED).text(section.intro, { align: "center" });
      }
      doc.moveDown(0.5);

      for (const item of section.items) {
        ensureSpace(48);
        const price = formatMenuPrice(item.price);
        const nameFont = item.highlight ? "Helvetica-Bold" : "Helvetica-Bold";
        const startY = doc.y;
        if (item.highlight) {
          // Liseré discret à gauche pour les plats mis en avant.
          doc.save().rect(doc.page.margins.left - 12, startY - 2, 3, 14).fill(ACCENT).restore();
        }
        doc.font(nameFont).fontSize(11.5).fillColor(INK);
        const nameWidth = width - 50;
        doc.text(item.name, doc.page.margins.left, startY, { width: nameWidth, continued: false });
        const nameBottom = doc.y;
        if (price) {
          doc
            .font("Helvetica")
            .fontSize(11)
            .fillColor(INK)
            .text(price, doc.page.margins.left + nameWidth, startY, { width: 50, align: "right" });
        }
        doc.y = Math.max(nameBottom, doc.y);
        if (item.description) {
          doc
            .font("Helvetica")
            .fontSize(9.5)
            .fillColor(MUTED)
            .text(item.description, doc.page.margins.left, doc.y, { width: nameWidth });
        }
        doc.moveDown(0.55);
      }
    }

    if (menu.footer_note) {
      ensureSpace(50);
      doc.moveDown(1);
      rule(doc, width);
      doc.moveDown(0.5);
      doc.font("Helvetica").fontSize(8).fillColor(MUTED).text(menu.footer_note, { align: "center" });
    }

    doc.end();
  });
}

function rule(doc: PDFKit.PDFDocument, width: number) {
  const y = doc.y;
  doc
    .save()
    .moveTo(doc.page.margins.left, y)
    .lineTo(doc.page.margins.left + width, y)
    .lineWidth(0.6)
    .strokeColor(ACCENT)
    .stroke()
    .restore();
  doc.y = y + 4;
}
