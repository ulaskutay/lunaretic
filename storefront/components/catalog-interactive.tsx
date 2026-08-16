"use client";

import type { ReactNode } from "react";
import { CatalogFilters } from "@/components/catalog-filters";
import { CatalogToolbar, useCatalogFilterDrawer } from "@/components/catalog-toolbar";
import type { CollectionCard, Facets } from "@/lib/types";

export function CatalogInteractive({
  children,
  facets,
  collections,
  current,
  collectionSlug,
}: {
  children: ReactNode;
  facets: Facets;
  collections: CollectionCard[];
  current: Record<string, string | string[] | undefined>;
  collectionSlug?: string;
}) {
  const drawer = useCatalogFilterDrawer();

  return (
    <>
      <CatalogToolbar
        current={current}
        collectionSlug={collectionSlug}
        filtersOpen={drawer.open}
        onToggleFilters={drawer.toggle}
      />
      <div className={`etic-catalog__layout${drawer.open ? " is-filters-open" : ""}`}>
        <CatalogFilters
          facets={facets}
          collections={collections}
          current={current}
          collectionSlug={collectionSlug}
          onClose={drawer.close}
        />
        {children}
      </div>
    </>
  );
}
