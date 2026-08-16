import Link from "next/link";
import type { CollectionCard } from "@/lib/types";

export function CatalogCategoryBar({
  collections,
  collectionSlug,
}: {
  collections: CollectionCard[];
  collectionSlug?: string;
}) {
  if (!collections.length) {
    return null;
  }

  return (
    <nav className="etic-catalog__category-bar" aria-label="Ürün kategorileri">
      <Link href="/koleksiyon" className={!collectionSlug ? "is-active" : ""}>
        Tümü
      </Link>
      {collections.map((item) => (
        <Link
          key={item.id}
          href={`/koleksiyon/${item.slug}`}
          className={collectionSlug === item.slug ? "is-active" : ""}
        >
          {item.name}
        </Link>
      ))}
    </nav>
  );
}
