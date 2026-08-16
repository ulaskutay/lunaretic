import { storeApi } from "@/lib/api";
import { StoreProvider } from "@/lib/store";
import { PageShell } from "@/components/page-shell";
import { Tracking } from "@/components/tracking";
import "./globals.css";
import type { Metadata } from "next";
import type { Bootstrap } from "@/lib/types";
import type { CSSProperties } from "react";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Etic Commerce",
  description: "Türk pazarına özel e-ticaret.",
  icons: {
    icon: "/favicon.svg",
    shortcut: "/favicon.svg",
    apple: "/favicon.svg",
  },
};

const fallbackBootstrap: Bootstrap = {
  store: { name: "Etic Commerce", handle: "boxers", locale: "tr", currency: "TRY" },
  menus: { header: [], footer: [] },
  tracking: {
    ga4_measurement_id: null,
    gtm_container_id: null,
    meta_pixel_id: null,
    search_console_verification: null,
  },
  theme: {
    handle: "default",
    name: "Default",
    logo_text: "Etic Commerce",
    logo: null,
    favicon: null,
    announcement: null,
    header_style: "simple",
    container: "default",
    footer_text: null,
    social: { instagram: null, tiktok: null, facebook: null, whatsapp: null },
    hero: {
      kicker: null,
      title: null,
      cta_primary: null,
      cta_primary_url: null,
      cta_secondary: null,
      cta_secondary_url: null,
      image: null,
    },
    featured: { title: null },
    editorial: {
      kicker: null,
      title: null,
      cta: null,
      cta_url: null,
      image: null,
    },
    editorial_secondary: {
      kicker: null,
      title: null,
      cta: null,
      cta_url: null,
      image: null,
    },
    best_sellers: { title: null, cta: null, url: "/koleksiyon?sort=best_selling" },
    banners: {
      left: { image: null, title: null, subtitle: null, cta: null, url: null },
      right: { image: null, title: null, subtitle: null, cta: null, url: null },
    },
    countdown: { title: null, description: null, ends_at: null },
    css_variables: {},
  },
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const bootstrap = await storeApi.bootstrap().catch(() => fallbackBootstrap);

  const themeStyle = bootstrap.theme.css_variables as CSSProperties;

  const heading = bootstrap.theme.css_variables["--etic-font-heading"] ?? "";
  const body = bootstrap.theme.css_variables["--etic-font-body"] ?? "";
  const needsAtelierFonts = heading.includes("Playfair") || body.includes("Montserrat");

  return (
    <html lang="tr" data-theme={bootstrap.theme.handle} style={themeStyle}>
      <head>
        <link rel="icon" href={bootstrap.theme.favicon || "/favicon.svg"} />
        <link rel="apple-touch-icon" href={bootstrap.theme.favicon || "/favicon.svg"} />
        {needsAtelierFonts ? (
          <>
          <link rel="preconnect" href="https://fonts.bunny.net" />
          <link
            href="https://fonts.bunny.net/css?family=playfair-display:400,500,600,700|montserrat:300,400,500,600&display=swap"
            rel="stylesheet"
            precedence="default"
          />
          </>
        ) : null}
      </head>
      <body>
        <StoreProvider bootstrap={bootstrap}>
          <Tracking config={bootstrap.tracking} />
          <PageShell bootstrap={bootstrap}>{children}</PageShell>
        </StoreProvider>
      </body>
    </html>
  );
}
