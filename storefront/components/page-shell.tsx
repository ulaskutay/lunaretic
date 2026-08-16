"use client";

import { usePathname } from "next/navigation";
import type { ReactNode } from "react";
import type { Bootstrap } from "@/lib/types";
import { Footer, Header } from "@/components/chrome";
import { CartFeedback } from "@/components/cart-feedback";

export function PageShell({ bootstrap, children }: { bootstrap: Bootstrap; children: ReactNode }) {
  const pathname = usePathname();
  const overlay = bootstrap.theme.handle === "atelier" || bootstrap.theme.header_style === "overlay";
  const flush = overlay && pathname === "/";
  const catalogPage = overlay && (pathname.startsWith("/koleksiyon") || pathname.startsWith("/ara"));
  const wide = overlay || bootstrap.theme.container === "wide";
  const mainClass = flush
    ? "etic-main etic-main--flush min-h-[70vh]"
    : productPage
      ? "etic-main etic-main--pdp min-h-[70vh]"
      : catalogPage
        ? `etic-main etic-main--catalog mx-auto min-h-[70vh] px-4 py-8 ${wide ? "max-w-7xl" : "max-w-6xl"}`
        : `etic-main mx-auto min-h-[70vh] px-4 py-8 ${wide ? "max-w-7xl" : "max-w-6xl"}`;

  return (
    <div className={flush ? "etic-body etic-body--flush" : overlay ? "etic-body" : undefined}>
      <Header bootstrap={bootstrap} />
      <main className={mainClass}>
        {children}
      </main>
      <Footer bootstrap={bootstrap} />
      <CartFeedback />
    </div>
  );
}
