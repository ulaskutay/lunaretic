"use client";

import { Suspense } from "react";
import { usePathname } from "next/navigation";
import type { ReactNode } from "react";
import type { Bootstrap } from "@/lib/types";
import { Footer, Header } from "@/components/chrome";
import { CartFeedback } from "@/components/cart-feedback";
import { NavigationProgress } from "@/components/navigation-progress";

export function PageShell({ bootstrap, children }: { bootstrap: Bootstrap; children: ReactNode }) {
  const pathname = usePathname();
  const overlay = bootstrap.theme.handle === "atelier" || bootstrap.theme.header_style === "overlay";
  const flush = overlay && pathname === "/";
  const catalogPage = overlay && (pathname.startsWith("/koleksiyon") || pathname.startsWith("/ara"));
  const productPage = overlay && pathname.startsWith("/urun");
  const cartPage = pathname.startsWith("/sepet") || pathname.startsWith("/odeme");
  const authPage = pathname.startsWith("/giris") || pathname.startsWith("/kayit") || pathname.startsWith("/hesabim");
  const wide = overlay || bootstrap.theme.container === "wide";
  const mainClass = flush
    ? "etic-main etic-main--flush min-h-[70vh]"
    : productPage
      ? "etic-main etic-main--pdp min-h-[70vh]"
      : cartPage
        ? "etic-main etic-main--cart min-h-[70vh]"
        : authPage
          ? "etic-main etic-main--auth min-h-[70vh]"
        : catalogPage
        ? "etic-main etic-main--catalog min-h-[70vh]"
        : `etic-main mx-auto min-h-[70vh] px-4 py-8 ${wide ? "max-w-7xl" : "max-w-6xl"}`;

  return (
    <div className={flush ? "etic-body etic-body--flush" : overlay ? "etic-body" : undefined}>
      <Suspense>
        <NavigationProgress />
      </Suspense>
      <Header bootstrap={bootstrap} />
      <main className={mainClass}>
        {children}
      </main>
      <Footer bootstrap={bootstrap} />
      <CartFeedback />
    </div>
  );
}
