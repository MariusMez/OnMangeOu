import fs from "node:fs";
import { NextResponse } from "next/server";
import { uploadedImagePath } from "@/lib/pipeline";

export const runtime = "nodejs";

export async function GET(_request: Request, ctx: RouteContext<"/analyses/[id]/images/[n]">) {
  const { id, n } = await ctx.params;
  const index = Number.parseInt(n, 10);
  if (!/^[a-f0-9-]+$/i.test(id) || !Number.isInteger(index) || index < 1) {
    return new NextResponse("Not found", { status: 404 });
  }
  const p = uploadedImagePath(id, index);
  if (!p) return new NextResponse("Not found", { status: 404 });
  return new NextResponse(new Uint8Array(fs.readFileSync(p)), {
    headers: { "Content-Type": "image/jpeg", "Cache-Control": "private, max-age=3600" },
  });
}
