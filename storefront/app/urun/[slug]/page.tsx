import Link from "next/link";
import { notFound } from "next/navigation";
import { storeApi } from "@/lib/api";
import { AddToCart } from "@/components/add-to-cart";
import { ProductAccordions } from "@/components/product-accordions";
import { ProductGallery } from "@/components/product-gallery";

export default async function ProductPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;

  try {
    const [{ data: product }, shipping] = await Promise.all([
      storeApi.product(slug),
      storeApi.page("kargo").catch(() => null),
    ]);
    const gallery = product.gallery.length ? product.gallery : product.image ? [product.image] : [];
    const collection = product.collections?.[0];

    return (
      <article className="etic-pdp">
        <nav className="etic-pdp__crumbs" aria-label="Sayfa yolu">
          <Link href="/">Ana sayfa</Link>
          <span>/</span>
          <Link href="/koleksiyon">Koleksiyon</Link>
          {collection ? (
            <>
              <span>/</span>
              <Link href={`/koleksiyon/${collection.slug}`}>{collection.name}</Link>
            </>
          ) : null}
        </nav>
        <div className="etic-pdp__media">
          <ProductGallery images={gallery} alt={product.name} />
        </div>
        <div className="etic-pdp__info">
          {product.brand ? <p className="etic-pdp__brand">{product.brand}</p> : null}
          <h1 className="etic-pdp__title">{product.name}</h1>
          <AddToCart product={product} />
          {product.description ? (
            <div className="etic-pdp__copy" dangerouslySetInnerHTML={{ __html: product.description }} />
          ) : null}
          <ProductAccordions shippingHtml={shipping?.body || shipping?.content} productName={product.name} />
        </div>
      </article>
    );
  } catch {
    notFound();
  }
}
