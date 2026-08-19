"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import type { Bootstrap } from "@/lib/types";
import { useStorefront } from "@/lib/store";
import { AtelierHeader } from "@/components/atelier-header";
import { MegaPanel, megaTiles } from "@/components/mega-menu";
import { PaymentBadges } from "@/components/payment-badges";

export function Header({ bootstrap }: { bootstrap: Bootstrap }) {
  if (bootstrap.theme.handle === "atelier" || bootstrap.theme.header_style === "overlay") {
    return <AtelierHeader bootstrap={bootstrap} />;
  }

  return <DefaultHeader bootstrap={bootstrap} />;
}

function DefaultHeader({ bootstrap }: { bootstrap: Bootstrap }) {
  const { cartCount, authToken } = useStorefront();
  const router = useRouter();

  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const q = new FormData(event.currentTarget).get("q");
    router.push(`/ara?q=${encodeURIComponent(String(q ?? ""))}`);
  }

  const theme = bootstrap.theme;
  const links = bootstrap.menus.header.length
    ? bootstrap.menus.header
    : [
        { id: 1, label: "Ürünler", url: "/koleksiyon", children: [] },
        { id: 2, label: "Blog", url: "/blog", children: [] },
        { id: 3, label: "Hakkımızda", url: "/sayfa/hakkimizda", children: [] },
      ];

  return (
    <>
      {theme.announcement ? (
        <div className="bg-brand px-4 py-2 text-center text-xs text-brand-fg">{theme.announcement}</div>
      ) : null}
      <header className="relative border-b bg-surface">
        <div className="mx-auto flex max-w-6xl items-stretch justify-between gap-4 px-4 py-4">
          <Link href="/" className="self-center text-lg font-semibold tracking-tight">
            {theme.logo ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={theme.logo} alt={theme.logo_text} className="h-8 w-auto" />
            ) : (
              theme.logo_text
            )}
          </Link>
        <nav className="hidden items-stretch gap-6 text-sm md:flex">
          {links.map((item) =>
            item.children?.length ? (
              <div key={item.id} className="etic-header__item group">
                <Link href={item.url}>{item.label}</Link>
                <MegaPanel item={item} tiles={megaTiles(theme)} />
              </div>
            ) : (
              <Link key={item.id} href={item.url}>
                {item.label}
              </Link>
            ),
          )}
        </nav>
        <div className="flex items-center gap-4 self-center text-sm">
          <form onSubmit={search} className="hidden md:block">
            <input
              type="search"
              name="q"
              placeholder="Ara"
              className="rounded-full border px-3 py-1.5 text-sm"
            />
          </form>
          <Link href="/sepet" data-etic-cart-target>Sepet ({cartCount})</Link>
          {authToken ? <Link href="/hesabim">Hesabım</Link> : <Link href="/giris">Giriş</Link>}
        </div>
        </div>
      </header>
    </>
  );
}

export function Footer({ bootstrap }: { bootstrap: Bootstrap }) {
  const [newsletterStatus, setNewsletterStatus] = useState("");
  const links = bootstrap.menus.footer.length
    ? bootstrap.menus.footer
    : [
        { id: 1, label: "Gizlilik", url: "/sayfa/gizlilik", children: [] },
        { id: 2, label: "İade", url: "/sayfa/iade", children: [] },
        { id: 3, label: "Koşullar", url: "/sayfa/kullanim-kosullari", children: [] },
      ];
  const theme = bootstrap.theme;
  const newsletter = theme.newsletter;
  const footerImage =
    theme.footer_image ?? theme.banners?.right.image ?? theme.editorial_secondary?.image ?? null;
  const socials = [
    { label: "Instagram", url: theme.social.instagram },
    { label: "TikTok", url: theme.social.tiktok },
    { label: "Facebook", url: theme.social.facebook },
    {
      label: "WhatsApp",
      url: theme.social.whatsapp
        ? `https://wa.me/${theme.social.whatsapp.replace(/\D/g, "")}`
        : null,
    },
  ].filter((social) => social.url);

  function subscribe(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const email = String(new FormData(form).get("email") ?? "");

    if (!email) return;

    window.localStorage.setItem("etic-newsletter-email", email);
    form.reset();
    setNewsletterStatus("E-posta tercihiniz bu cihazda kaydedildi.");
  }

  return (
    <footer className="etic-footer">
      <div className="etic-footer__inner">
        <div className="etic-footer__main">
          <Link href="/" className="etic-footer__logo">
            {theme.logo ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={theme.logo} alt={theme.logo_text} />
            ) : (
              theme.logo_text
            )}
          </Link>

          <div className="etic-footer__columns">
            <div className="etic-footer__column">
              <p className="etic-footer__label">Koleksiyonlar</p>
              <Link href="/koleksiyon">Tüm ürünler</Link>
              <Link href="/koleksiyon?sort=best_selling">Çok satanlar</Link>
              <Link href="/koleksiyon?sort=newest">Yeni gelenler</Link>
            </div>

            <div className="etic-footer__column">
              <p className="etic-footer__label">Yardımcı bağlantılar</p>
              {links.flatMap((item) => [
                <Link key={item.id} href={item.url}>
                  {item.label}
                </Link>,
                ...(item.children ?? []).map((child) => (
                  <Link key={child.id} href={child.url}>
                    {child.label}
                  </Link>
                )),
              ])}
            </div>

            <div className="etic-footer__column etic-footer__about">
              <p className="etic-footer__label">Hakkımızda</p>
              <p>
                {theme.footer_text ||
                  "Zamansız tasarımlar, özenli detaylar ve günlük yaşama eşlik eden seçkiler."}
              </p>
            </div>
          </div>

          <div className="etic-footer__social-row">
            <p>Bizi takip edin.</p>
            <div className="etic-footer__socials">
              {socials.map((social) => (
                <a key={social.label} href={social.url!} target="_blank" rel="noreferrer">
                  {social.label}
                </a>
              ))}
            </div>
          </div>

          <div className="etic-footer__meta">
            <p className="etic-footer__copyright">
              &copy; {new Date().getFullYear()} {theme.logo_text}
            </p>
            <PaymentBadges />
          </div>
        </div>

        <aside className="etic-footer__aside">
          {footerImage ? (
            <div className="etic-footer__media">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={footerImage} alt="" loading="lazy" />
            </div>
          ) : null}

          {newsletter?.enabled !== false ? (
          <div className="etic-footer__newsletter">
            <p className="etic-footer__label">{newsletter?.kicker || "Haftalık bültenimiz"}</p>
            <h2>{newsletter?.title || "Detayları kaçırma"}</h2>
            <p>
              {newsletter?.description ||
                "Yeni koleksiyonlar, özel teklifler ve ilham veren seçkiler e-posta kutunda."}
            </p>
            <form className="etic-footer__form" onSubmit={subscribe}>
              <label className="sr-only" htmlFor="newsletter-email">
                E-posta adresi
              </label>
              <input
                id="newsletter-email"
                name="email"
                type="email"
                required
                placeholder={newsletter?.placeholder || "e-posta adresiniz"}
              />
              <button type="submit" aria-label="Bültene katıl">
                &rarr;
              </button>
            </form>
            <p className="etic-footer__form-status" aria-live="polite">
              {newsletterStatus}
            </p>
          </div>
          ) : null}
        </aside>
      </div>
    </footer>
  );
}
