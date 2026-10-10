"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";

/** Recharge la page tant que l'analyse est en cours. */
export function AutoRefresh({ intervalMs = 2500 }: { intervalMs?: number }) {
  const router = useRouter();
  useEffect(() => {
    const t = setInterval(() => router.refresh(), intervalMs);
    return () => clearInterval(t);
  }, [router, intervalMs]);
  return null;
}
