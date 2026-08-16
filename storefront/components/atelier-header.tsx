"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { AnimatePresence, motion } from "motion/react";
import type { Bootstrap } from "@/lib/types";
import { useStorefront } from "@/lib/store";
import { MegaMenuContent, megaTiles } from "@/components/mega-menu";

function IconUser() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="12" cy="8" r="3.25" fill="none" stroke="currentColor" strokeWidth="1.4" />
      <path d="M5.5 19.2c.8-3.3 3.4-5.2 6.5-5.2s5.7 1.9 6.5 5.2" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  );
}

function IconSearch() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" strokeWidth="1.4" />
      <path d="M16 16.5 20 20.5" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  );
}

function IconBag() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M7.5 8.5V7.2A4.5 4.5 0 0 1 12 2.8a4.5 4.5 0 0 1 4.5 4.4v1.3" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
      <path d="M6.4 8.5h11.2l.7 11.2H5.7Z" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round" />
    </svg>
  );
}

function IconMenu() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M5 8h14M5 12h14M5 16h14" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  );
}

export function AtelierHeader({ bootstrap }: { bootstrap: Bootstrap }) {
  const { cartCount, authToken } = useStorefront();
  const router = useRouter();
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [navOpen, setNavOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [activeMega, setActiveMega] = useState<number | null>(null);
  const theme = bootstrap.theme;
  const overlay = theme.header_style === "overlay";
  const isHome = pathname === "/";
  const links = bootstrap.menus.header.length
    ? bootstrap.menus.header
    : [
        { id: 1, label: "Ürünler", url: "/koleksiyon", children: [] },
        { id: 2, label: "Blog", url: "/blog", children: [] },
        { id: 3, label: "Hakkımızda", url: "/sayfa/hakkimizda", children: [] },
      ];

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    setNavOpen(false);
    setSearchOpen(false);
    setActiveMega(null);
  }, [pathname]);

  useEffect(() => {
    const mq = window.matchMedia("(min-width: 768px)");
    const onResize = () => {
      if (mq.matches) {
        setNavOpen(false);
        setActiveMega(null);
      }
    };
    mq.addEventListener("change", onResize);
    return () => mq.removeEventListener("change", onResize);
  }, []);

  const tiles = megaTiles(theme);

  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const q = new FormData(event.currentTarget).get("q");
    router.push(`/ara?q=${encodeURIComponent(String(q ?? ""))}`);
    setSearchOpen(false);
  }

  const classes = [
    "etic-header",
    overlay ? "etic-header--overlay" : "etic-header--solid",
    isHome ? "etic-header--home" : "",
    theme.announcement ? "etic-header--has-announcement" : "",
    scrolled ? "is-scrolled" : "",
    navOpen ? "is-nav-open" : "",
    searchOpen ? "is-search-open" : "",
    activeMega !== null ? "is-mega-open" : "",
  ]
    .filter(Boolean)
    .join(" ");

  return (
    <>
      {theme.announcement ? <div className="etic-announcement">{theme.announcement}</div> : null}
      <header className={classes} data-etic-header>
        <div className="etic-header__bg" aria-hidden="true" />
        <div className="etic-header__bar">
        <Link href="/" className="etic-header__logo">
          {theme.logo ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={theme.logo} alt={theme.logo_text} />
          ) : (
            theme.logo_text
          )}
        </Link>
        <nav className="etic-header__nav" aria-label="Ana menü">
          {links.map((item) =>
            item.children?.length ? (
              <div
                key={item.id}
                className="etic-header__item"
                data-mega-trigger={item.id}
                onMouseEnter={() => setActiveMega(item.id)}
                onMouseLeave={() => setActiveMega(null)}
                onFocus={() => setActiveMega(item.id)}
              >
                <Link href={item.url}>{item.label}</Link>
                <div className="etic-header__mobile-mega">
                  <MegaMenuContent item={item} tiles={tiles} />
                </div>
              </div>
            ) : (
              <Link key={item.id} href={item.url}>
                {item.label}
              </Link>
            ),
          )}
        </nav>
        <div className="etic-header__tools">
          <Link href={authToken ? "/hesabim" : "/giris"} className="etic-icon-btn" aria-label={authToken ? "Hesabım" : "Giriş"}>
            <IconUser />
          </Link>
          <button
            type="button"
            className="etic-icon-btn"
            aria-label="Ara"
            aria-expanded={searchOpen}
            onClick={() => {
              setSearchOpen((open) => !open);
              setNavOpen(false);
            }}
          >
            <IconSearch />
          </button>
          <Link href="/sepet" className="etic-icon-btn" data-etic-cart-target aria-label={cartCount ? `Sepet (${cartCount})` : "Sepet"}>
            <IconBag />
            <AnimatePresence>
              {cartCount > 0 ? (
                <motion.span
                  key={cartCount}
                  className="etic-icon-btn__badge"
                  initial={{ scale: 0.45 }}
                  animate={{ scale: 1 }}
                  exit={{ scale: 0.45, opacity: 0 }}
                  transition={{ type: "spring", stiffness: 560, damping: 18 }}
                >
                  {cartCount > 99 ? "99+" : cartCount}
                </motion.span>
              ) : null}
            </AnimatePresence>
          </Link>
          <button
            type="button"
            className="etic-icon-btn etic-header__burger"
            aria-label="Menü"
            aria-expanded={navOpen}
            onClick={() => {
              setNavOpen((open) => !open);
              setSearchOpen(false);
            }}
          >
            <IconMenu />
          </button>
        </div>
        </div>
        <div className="etic-header__mega-layer" data-etic-mega-layer>
          {links
            .filter((item) => item.children?.length)
            .map((item) => (
              <div
                key={item.id}
                className={`etic-header__mega-panel${activeMega === item.id ? " is-open" : ""}`}
                data-mega-panel={item.id}
                hidden={activeMega !== item.id}
                onMouseEnter={() => setActiveMega(item.id)}
                onMouseLeave={() => setActiveMega(null)}
              >
                <MegaMenuContent item={item} tiles={tiles} />
              </div>
            ))}
        </div>
        {searchOpen ? (
          <div className="etic-search">
            <form onSubmit={search} className="etic-search__form">
              <input type="search" name="q" placeholder="Ara" autoComplete="off" autoFocus />
              <button type="submit" className="etic-search__submit">
                Ara
              </button>
              <button type="button" className="etic-search__close" onClick={() => setSearchOpen(false)}>
                Kapat
              </button>
            </form>
          </div>
        ) : null}
      </header>
    </>
  );
}
