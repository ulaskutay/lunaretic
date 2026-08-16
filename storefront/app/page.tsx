import Link from "next/link";
import type { CSSProperties } from "react";
import { storeApi } from "@/lib/api";
import { CountdownBanner } from "@/components/countdown-banner";
import { EditorialShowcase } from "@/components/editorial-showcase";
import { NewsletterBenefits } from "@/components/newsletter-benefits";
import { ProductCard } from "@/components/product-card";
import { ShopTheLook } from "@/components/shop-the-look";

export default async function HomePage() {
  const [catalog, bootstrap, bestSellers] = await Promise.all([
    storeApi.catalog(),
    storeApi.bootstrap().catch(() => null),
    storeApi.catalog("sort=best_selling"),
  ]);
  const heroProduct = catalog.data[0];
  const overlay = bootstrap?.theme.handle === "atelier" || bootstrap?.theme.header_style === "overlay";
  const hero = bootstrap?.theme.hero;
  const title = hero?.title || "Rahatlık, sade tasarım";
  const titleLines = title.split(/\r\n|\n|\r/);
  const image = hero?.image || heroProduct?.image;
  const fallbackBannerImage = catalog.data[catalog.data.length - 1]?.image || null;
  const leftBanner = bootstrap?.theme.banners?.left;
  const rightBanner = bootstrap?.theme.banners?.right;
  const leftBannerImage = leftBanner?.image || bootstrap?.theme.editorial?.image || fallbackBannerImage;
  const rightBannerImage = rightBanner?.image || bootstrap?.theme.editorial_secondary?.image || fallbackBannerImage;

  if (overlay) {
    return (
      <>
        {bootstrap?.theme.hero?.enabled !== false ? (
        <section className="etic-hero">
          {image ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img className="etic-hero__image" src={image} alt="" />
          ) : null}
          <div className="etic-hero__shade" />
          <div className="etic-hero__content">
            {hero?.kicker ? <p className="etic-hero__kicker">{hero.kicker}</p> : null}
            <h1 className="etic-hero__title">
              {titleLines.map((line) => (
                <span key={line}>{line}</span>
              ))}
            </h1>
            <div className="etic-hero__actions">
              {hero?.cta_primary ? (
                <Link href={hero.cta_primary_url || "/koleksiyon"} className="etic-hero__cta">
                  {hero.cta_primary}
                </Link>
              ) : (
                <Link href="/koleksiyon" className="etic-hero__cta">
                  Ürünleri gör
                </Link>
              )}
              {hero?.cta_secondary ? (
                <Link href={hero.cta_secondary_url || "/sayfa/hakkimizda"} className="etic-hero__link">
                  {hero.cta_secondary}
                </Link>
              ) : null}
            </div>
          </div>
        </section>
        ) : null}
        {bootstrap?.theme.countdown?.enabled !== false ? (
          <CountdownBanner countdown={bootstrap?.theme.countdown} />
        ) : null}
        {bootstrap?.theme.featured?.enabled !== false && catalog.data.length ? (
          <section className="etic-featured" aria-labelledby="atelier-featured-title">
            <div className="etic-featured__inner">
              <div className="etic-featured__heading">
                <span aria-hidden="true" />
                <h2 id="atelier-featured-title" className="etic-featured__title">
                  {bootstrap?.theme.featured?.title || "Yeni gelenler — Koleksiyon"}
                </h2>
                <span aria-hidden="true" />
              </div>
              <div
                className="etic-featured__grid"
                style={{ "--etic-featured-columns": Math.min(4, catalog.data.length) } as CSSProperties}
              >
                {catalog.data.slice(0, 4).map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>
            </div>
          </section>
        ) : null}
        <EditorialShowcase
          products={catalog.data}
          editorial={bootstrap?.theme.editorial}
          fallbackImage={catalog.data[catalog.data.length - 1]?.image}
        />
        <EditorialShowcase
          id="atelier-editorial-secondary-title"
          products={catalog.data}
          editorial={bootstrap?.theme.editorial_secondary}
          fallbackImage={catalog.data[catalog.data.length - 1]?.image}
          reverse
        />
        {bootstrap?.theme.best_sellers?.enabled !== false && bestSellers.data.length ? (
          <section className="etic-best-sellers" aria-labelledby="atelier-best-sellers-title">
            <div className="etic-best-sellers__heading">
              <span aria-hidden="true" />
              <h2 id="atelier-best-sellers-title">{bootstrap?.theme.best_sellers?.title || "Çok satanlar"}</h2>
              <span aria-hidden="true" />
            </div>
            <div
              className="etic-best-sellers__grid"
              style={{ "--etic-best-seller-columns": Math.min(4, bestSellers.data.length) } as CSSProperties}
            >
              {bestSellers.data.slice(0, 8).map((product) => (
                <ProductCard key={product.id} product={product} className="etic-best-sellers__product" />
              ))}
            </div>
            <div className="etic-best-sellers__footer">
              <Link href={bootstrap?.theme.best_sellers?.url || "/koleksiyon?sort=best_selling"}>
                {bootstrap?.theme.best_sellers?.cta || "Tümünü gör"}
              </Link>
            </div>
          </section>
        ) : null}
        <ShopTheLook
          shopLook={bootstrap?.theme.shop_look}
          fallbackImage={leftBannerImage || bootstrap?.theme.editorial?.image || image}
        />
        {bootstrap?.theme.banners?.enabled !== false && (leftBannerImage || rightBannerImage) ? (
          <section className="etic-dual-banners" aria-label="Koleksiyon seçkileri">
            <article className="etic-dual-banner">
              {leftBannerImage ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img className="etic-dual-banner__image" src={leftBannerImage} alt="" loading="lazy" />
              ) : null}
              <div className="etic-dual-banner__shade" />
              <div className="etic-dual-banner__content">
                <h2 className="etic-dual-banner__title">{leftBanner?.title || "Zarif konfor"}</h2>
                {leftBanner?.subtitle ? <p className="etic-dual-banner__subtitle">{leftBanner.subtitle}</p> : null}
                <Link className="etic-dual-banner__cta" href={leftBanner?.url || "/koleksiyon"}>
                  {leftBanner?.cta || "Keşfet"}
                </Link>
              </div>
            </article>

            <article className="etic-dual-banner etic-dual-banner--centered">
              {rightBannerImage ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img className="etic-dual-banner__image" src={rightBannerImage} alt="" loading="lazy" />
              ) : null}
              <div className="etic-dual-banner__shade" />
              <div className="etic-dual-banner__content">
                <h2 className="etic-dual-banner__title">{rightBanner?.title || "Sezonun dokusu"}</h2>
                <p className="etic-dual-banner__subtitle">{rightBanner?.subtitle || "Yeni sezon seçkisi"}</p>
                <Link className="etic-dual-banner__cta" href={rightBanner?.url || "/koleksiyon"}>
                  {rightBanner?.cta || "Şimdi keşfet"}
                </Link>
              </div>
            </article>
          </section>
        ) : null}
        <NewsletterBenefits newsletter={bootstrap?.theme.newsletter} />
      </>
    );
  }

  return (
    <>
      <section className="mb-10 grid gap-8 md:grid-cols-2 md:items-center">
        <div>
          <p className="text-xs uppercase tracking-[0.2em] text-neutral-500">Boxer koleksiyonu</p>
          <h1 className="mt-3 text-4xl font-semibold">Rahatlık, sade tasarım.</h1>
          <p className="mt-4 max-w-md text-neutral-600">
            Next.js vitrin, Laravel + Lunar ticaret motoruna bağlanır. Renk ve beden varyantlarıyla stoklu satış.
          </p>
          <Link href="/koleksiyon" className="mt-6 inline-block rounded-full bg-neutral-900 px-6 py-3 text-sm text-white">
            Alışverişe başla
          </Link>
        </div>
        <div className="aspect-[4/5] overflow-hidden rounded-3xl bg-neutral-100">
          {heroProduct?.image ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={heroProduct.image} alt={heroProduct.name} className="h-full w-full object-cover" />
          ) : null}
        </div>
      </section>
      <h2 className="mb-4 text-xl font-medium">Öne çıkanlar</h2>
      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        {catalog.data.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </>
  );
}
