import Link from "next/link";
import { storeApi } from "@/lib/api";
import { ProductCard } from "@/components/product-card";
import { CatalogFilters } from "@/components/catalog-filters";
import { CatalogToolbar } from "@/components/catalog-toolbar";

type Search = Record<string, string | string[] | undefined>;

function queryFrom(search: Search, extra: Record<string, string> = {}) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(search)) {
    if (typeof value === "string" && value !== "") {
      params.set(key, value);
    }
  }
  for (const [key, value] of Object.entries(extra)) {
    params.set(key, value);
  }
  return params.toString();
}

export default async function CatalogPage({
  searchParams,
  collection,
  title,
}: {
  searchParams: Promise<Search>;
  collection?: string;
  title?: string;
}) {
  const search = await searchParams;
  const catalog = await storeApi.catalog(queryFrom(search, collection ? { collection } : {}));
  const heading =
    search.sort === "best_selling"
      ? "Çok satanlar"
      : title ?? catalog.collection?.name ?? (typeof search.q === "string" ? search.q : "Koleksiyon");
  const firstItem = catalog.meta.total
    ? (catalog.meta.current_page - 1) * catalog.meta.per_page + 1
    : 0;
  const lastItem = Math.min(catalog.meta.current_page * catalog.meta.per_page, catalog.meta.total);

  return (
    <section className="etic-catalog" data-etic-catalog data-grid-columns="3">
      <header className="etic-catalog__header">
        <p>Atelier seçkisi</p>
        <h1>{heading}</h1>
        <span>{catalog.meta.total} ürün</span>
      </header>

      <CatalogToolbar current={search} collectionSlug={collection} />

      <div className="etic-catalog__layout">
        <CatalogFilters
          facets={catalog.facets}
          collections={catalog.collections}
          current={search}
          collectionSlug={collection}
        />
        <div className="etic-catalog__results">
          <p className="etic-catalog__result-count">{firstItem}–{lastItem} / {catalog.meta.total}</p>
          <div className="etic-product-grid">
          {catalog.data.length ? (
            catalog.data.map((product) => <ProductCard key={product.id} product={product} />)
          ) : (
            <p className="col-span-full text-sm text-neutral-600">Ürün bulunamadı.</p>
          )}
          </div>
          {catalog.meta.last_page > 1 ? (
            <div className="etic-catalog__pagination">
              {Array.from({ length: catalog.meta.last_page }, (_, index) => index + 1).map((page) => (
                <Link
                  key={page}
                  href={`?${queryFrom(search, { page: String(page) })}`}
                  className={page === catalog.meta.current_page ? "is-active" : ""}
                >
                  {page}
                </Link>
              ))}
            </div>
          ) : null}
        </div>
      </div>
    </section>
  );
}
