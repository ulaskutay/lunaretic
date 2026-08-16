"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

type Search = Record<string, string | string[] | undefined>;

function catalogRoot(element: HTMLElement) {
  return element.closest<HTMLElement>("[data-etic-catalog]");
}

function setBodyLock(locked: boolean) {
  document.body.classList.toggle("etic-catalog-filters-lock", locked);
}

export function CatalogToolbar({
  current,
  collectionSlug,
  onToggleFilters,
  filtersOpen,
}: {
  current: Search;
  collectionSlug?: string;
  onToggleFilters?: () => void;
  filtersOpen?: boolean;
}) {
  const router = useRouter();
  const [filtersVisible, setFiltersVisible] = useState(true);
  const [columns, setColumns] = useState(3);
  const [mobile, setMobile] = useState(false);

  useEffect(() => {
    const media = window.matchMedia("(max-width: 899px)");
    const sync = () => setMobile(media.matches);
    sync();
    media.addEventListener("change", sync);
    return () => media.removeEventListener("change", sync);
  }, []);

  useEffect(() => {
    return () => setBodyLock(false);
  }, []);

  function toggleFilters(element: HTMLButtonElement) {
    if (mobile) {
      onToggleFilters?.();
      return;
    }

    const visible = !filtersVisible;
    setFiltersVisible(visible);
    catalogRoot(element)?.classList.toggle("is-filters-hidden", !visible);
  }

  function setGrid(element: HTMLButtonElement, value: number) {
    setColumns(value);
    const root = catalogRoot(element);
    if (root) root.dataset.gridColumns = String(value);
  }

  function sort(value: string) {
    const params = new URLSearchParams();
    for (const [key, item] of Object.entries(current)) {
      if (typeof item === "string" && item && key !== "page") params.set(key, item);
    }
    params.set("sort", value);
    router.push(`${collectionSlug ? `/koleksiyon/${collectionSlug}` : "/koleksiyon"}?${params.toString()}`);
  }

  const filterLabel = mobile
    ? filtersOpen
      ? "Filtreleri kapat"
      : "Filtreler"
    : filtersVisible
      ? "Filtreleri gizle"
      : "Filtreleri göster";

  return (
    <div className="etic-catalog__controls">
      <button
        type="button"
        className="etic-catalog__control"
        aria-expanded={mobile ? Boolean(filtersOpen) : filtersVisible}
        onClick={(event) => toggleFilters(event.currentTarget)}
      >
        <span>{filterLabel}</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4" /></svg>
      </button>
      <label className="etic-catalog__sort">
        <span>Sıralama</span>
        <select value={typeof current.sort === "string" ? current.sort : "newest"} onChange={(event) => sort(event.target.value)}>
          <option value="newest">Yeni</option>
          <option value="best_selling">Çok satanlar</option>
          <option value="price_asc">Fiyat artan</option>
          <option value="price_desc">Fiyat azalan</option>
        </select>
      </label>
      <div className="etic-catalog__grid-options" aria-label="Ürün grid görünümü">
        {[2, 3, 4].map((value) => (
          <button
            key={value}
            type="button"
            className={columns === value ? "is-active" : ""}
            aria-label={`${value} sütunlu görünüm`}
            aria-pressed={columns === value}
            onClick={(event) => setGrid(event.currentTarget, value)}
          >
            {Array.from({ length: value }, (_, index) => <i key={index} />)}
          </button>
        ))}
      </div>
    </div>
  );
}

export function useCatalogFilterDrawer() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    setBodyLock(open);
    return () => setBodyLock(false);
  }, [open]);

  useEffect(() => {
    if (!open) {
      return;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setOpen(false);
      }
    };

    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [open]);

  return {
    open,
    toggle: () => setOpen((value) => !value),
    close: () => setOpen(false),
  };
}
