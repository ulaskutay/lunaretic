"use client";

import { useRouter } from "next/navigation";
import type { CollectionCard, Facets } from "@/lib/types";

export function CatalogFilters({
  facets,
  collections,
  current,
  collectionSlug,
}: {
  facets: Facets;
  collections: CollectionCard[];
  current: Record<string, string | string[] | undefined>;
  collectionSlug?: string;
}) {
  const router = useRouter();
  const value = (key: string) => (typeof current[key] === "string" ? current[key] : "");
  const clearPath = collectionSlug ? `/koleksiyon/${collectionSlug}` : "/koleksiyon";

  function submit(formData: FormData) {
    const params = new URLSearchParams();
    for (const [key, item] of formData.entries()) {
      if (String(item)) {
        params.set(key, String(item));
      }
    }
    const path = collectionSlug ? `/koleksiyon/${collectionSlug}` : "/koleksiyon";
    router.push(`${path}?${params.toString()}`);
  }

  return (
    <aside className="etic-catalog__filters">
      {collections.length ? (
        <nav className="etic-catalog__collections" aria-label="Ürün kategorileri">
          <h2>Ürün kategorileri</h2>
          {collections.map((item) => (
            <a key={item.id} href={`/koleksiyon/${item.slug}`} className={collectionSlug === item.slug ? "is-active" : ""}>
              <span>{item.name}</span>
              <i aria-hidden="true" />
            </a>
          ))}
        </nav>
      ) : null}
      <h2>Filtreler</h2>
      <form action={submit}>
        <input type="hidden" name="q" defaultValue={value("q")} />
        <input type="hidden" name="sort" defaultValue={value("sort") || "newest"} />
        {facets.sizes.length ? (
          <label>
            Beden
            <select name="beden" defaultValue={value("beden")}>
              <option value="">Tümü</option>
              {facets.sizes.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name}
                </option>
              ))}
            </select>
          </label>
        ) : null}
        {facets.brands.length ? (
          <label>
            Marka
            <select name="marka" defaultValue={value("marka")}>
              <option value="">Tümü</option>
              {facets.brands.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name}
                </option>
              ))}
            </select>
          </label>
        ) : null}
        <div className="etic-catalog__price">
          <label>
            Min
            <input type="number" name="min" min={0} defaultValue={value("min")} />
          </label>
          <label>
            Max
            <input type="number" name="max" min={0} defaultValue={value("max")} />
          </label>
        </div>
        <label>
          Stok durumu
          <select name="stok" defaultValue={value("stok") === "yok" || value("stoksuz") === "1" ? "yok" : value("stok") === "1" ? "1" : ""}>
            <option value="">Tümü</option>
            <option value="1">Stokta</option>
            <option value="yok">Stokta yok</option>
          </select>
        </label>
        <button className="etic-catalog__apply">Uygula</button>
        <a href={clearPath} className="etic-catalog__clear">Temizle</a>
      </form>
    </aside>
  );
}
