import Link from "next/link";
import type { ProductCard as ProductCardType } from "@/lib/types";
import { productPath } from "@/lib/paths";

export function ProductCard({ product, className = "" }: { product: ProductCardType; className?: string }) {
  const colors = product.color_variants ?? [];

  return (
    <article className={`etic-product ${className}`.trim()}>
      <Link href={productPath(product.slug)} className="etic-product__link">
        <div className="etic-product__media">
          {product.image ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={product.image}
              alt={product.name}
              decoding="async"
            />
          ) : null}
          {!product.in_stock ? <span className="etic-product__badge">Stokta yok</span> : null}
        </div>
        <h2 className="etic-product__name">{product.name}</h2>
        {product.brand ? <p className="etic-product__brand">{product.brand}</p> : null}
        {product.price ? <p className="etic-product__price">{product.price.formatted}</p> : null}
      </Link>
      {colors.length > 1 ? (
        <div className="etic-product__colors">
          {colors.map((item) =>
            item.slug ? (
              <Link
                key={item.id}
                href={productPath(item.slug)}
                className={`etic-product__color${item.active ? " is-active" : ""}`}
                title={item.color || item.name || undefined}
              >
                {item.image ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.image} alt={item.color || item.name || ""} />
                ) : (
                  <span />
                )}
              </Link>
            ) : null,
          )}
        </div>
      ) : null}
    </article>
  );
}
