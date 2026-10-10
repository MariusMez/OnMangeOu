import { NextResponse } from "next/server";
import { getAnalysis } from "@/lib/db";
import { renderMenuPdf } from "@/lib/pdf";

export const runtime = "nodejs";

export async function GET(_request: Request, ctx: RouteContext<"/analyses/[id]/pdf">) {
  const { id } = await ctx.params;
  const analysis = getAnalysis(id);
  if (!analysis?.optimization) {
    return NextResponse.json({ error: "Analyse introuvable ou non terminée." }, { status: 404 });
  }
  const pdf = await renderMenuPdf(analysis.optimization.optimized_menu, { city: analysis.city });
  const slug = analysis.restaurant_name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "") || "carte";
  return new NextResponse(new Uint8Array(pdf), {
    headers: {
      "Content-Type": "application/pdf",
      "Content-Disposition": `attachment; filename="carte-optimisee-${slug}.pdf"`,
      "Cache-Control": "no-store",
    },
  });
}
