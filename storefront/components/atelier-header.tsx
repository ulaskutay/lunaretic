"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { FormEvent, useEffect, useRef, useState } from "react";
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

function IconClose() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  );
}

type SearchSuggestion = {
  id: number;
  name: string;
  url: string;
  image: string | null;
  price: string | null;
};

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export function AtelierHeader({ bootstrap }: { bootstrap: Bootstrap }) {
  const { cartCount, authToken } = useStorefront();
  const router = useRouter();
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [navOpen, setNavOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [activeMega, setActiveMega] = useState<number | null>(null);
  const headerRef = useRef<HTMLElement | null>(null);
  const searchRef = useRef<HTMLDivElement | null>(null);
  const suggestionsTimer = useRef<number | null>(null);
  const suggestionsController = useRef<AbortController | null>(null);
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
    setSearchQuery("");
    setSuggestions([]);
    setActiveMega(null);
  }, [pathname]);

  function fetchSuggestions(term: string) {
    if (term.length < 2) {
      setSuggestions([]);
      return;
    }

    suggestionsController.current?.abort();
    suggestionsController.current = new AbortController();

    fetch(`${API_URL}/search/suggestions?q=${encodeURIComponent(term)}`, {
      headers: { Accept: "application/json" },
      signal: suggestionsController.current.signal,
    })
      .then((response) => response.json())
      .then((payload) => setSuggestions(payload.data ?? []))
      .catch(() => {});
  }

  function onSearchInput(value: string) {
    setSearchQuery(value);

    if (suggestionsTimer.current) {
      window.clearTimeout(suggestionsTimer.current);
    }

    suggestionsTimer.current = window.setTimeout(() => {
      fetchSuggestions(value.trim());
    }, 200);
  }

  function closeSearch() {
    setSearchOpen(false);
    setSearchQuery("");
    setSuggestions([]);
  }

  useEffect(() => {
    const mq = window.matchMedia("(min-width: 768px)");
    const onResize = () => {
      if (mq.matches) {
        setNavOpen(false);
        setActiveMega(null);
      }

      if (searchOpen && headerRef.current && searchRef.current) {
        searchRef.current.style.setProperty("--etic-search-offset", `${headerRef.current.getBoundingClientRect().bottom}px`);
      }
    };
    mq.addEventListener("change", onResize);
    window.addEventListener("resize", onResize, { passive: true });
    return () => {
      mq.removeEventListener("change", onResize);
      window.removeEventListener("resize", onResize);
    };
  }, [searchOpen]);

  useEffect(() => {
    document.body.classList.toggle("etic-search-open", searchOpen);

    if (! searchOpen || ! headerRef.current || ! searchRef.current) {
      return;
    }

    searchRef.current.style.setProperty("--etic-search-offset", `${headerRef.current.getBoundingClientRect().bottom}px`);
    const input = searchRef.current.querySelector<HTMLInputElement>('input[type="search"]');
    input?.focus();

    return () => {
      document.body.classList.remove("etic-search-open");
    };
  }, [searchOpen]);

  useEffect(() => {
    if (! searchOpen) {
      return;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        closeSearch();
      }
    };

    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [searchOpen]);

  const tiles = megaTiles(theme);

  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    router.push(`/ara?q=${encodeURIComponent(searchQuery)}`);
    closeSearch();
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
      <header ref={headerRef} className={classes} data-etic-header>
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
              if (searchOpen) {
                setSearchQuery("");
                setSuggestions([]);
              }
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
              closeSearch();
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
          <div ref={searchRef} className="etic-search-layer" data-etic-search>
            <button type="button" className="etic-search-layer__backdrop" aria-label="Aramayı kapat" onClick={closeSearch} />
            <div className="etic-search-layer__panel">
              <form onSubmit={search} className="etic-search__form">
                <label className="etic-search__label" htmlFor="etic-search-input">
                  Ürün ara
                </label>
                <input
                  id="etic-search-input"
                  type="search"
                  name="q"
                  value={searchQuery}
                  onChange={(event) => onSearchInput(event.target.value)}
                  placeholder="Ne aramıştınız?"
                  autoComplete="off"
                  enterKeyHint="search"
                />
                <button type="submit" className="etic-search__submit">
                  Ara
                </button>
                <button type="button" className="etic-search__close" aria-label="Kapat" onClick={closeSearch}>
                  <IconClose />
                </button>
              </form>
              {suggestions.length > 0 ? (
                <div className="etic-search-suggestions">
                  <p className="etic-search-suggestions__label">Öneriler</p>
                  <ul className="etic-search-suggestions__list">
                    {suggestions.map((item) => (
                      <li key={item.id} className="etic-search-suggestions__item">
                        <Link href={item.url} onClick={closeSearch}>
                          {item.image ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img className="etic-search-suggestions__thumb" src={item.image} alt="" loading="lazy" />
                          ) : (
                            <span className="etic-search-suggestions__thumb" aria-hidden="true" />
                          )}
                          <span className="etic-search-suggestions__name">{item.name}</span>
                          {item.price ? <span className="etic-search-suggestions__price">{item.price}</span> : null}
                        </Link>
                      </li>
                    ))}
                  </ul>
                </div>
              ) : null}
            </div>
          </div>
        ) : null}
      </header>
    </>
  );
}
