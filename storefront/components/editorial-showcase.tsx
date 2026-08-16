"use client";

import Link from "next/link";
import { useEffect, useRef } from "react";
import { ProductCard } from "@/components/product-card";
import type { ProductCard as ProductCardType, ThemeEditorial } from "@/lib/types";

type EditorialShowcaseProps = {
  products: ProductCardType[];
  editorial?: ThemeEditorial;
  fallbackImage?: string | null;
  id?: string;
  reverse?: boolean;
};

export function EditorialShowcase({
  products,
  editorial,
  fallbackImage,
  id = "atelier-editorial-title",
  reverse = false,
}: EditorialShowcaseProps) {
  const campaignRef = useRef<HTMLDivElement>(null);
  const image = editorial?.image || fallbackImage;
  const kicker = editorial?.kicker || "Sezon seçkisi";
  const title = editorial?.title || "Unutulmaz bir gece";
  const cta = editorial?.cta || "Koleksiyonu keşfet";
  const ctaUrl = editorial?.cta_url || "/koleksiyon";

  useEffect(() => {
    const campaign = campaignRef.current;

    if (!campaign || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return;
    }

    let frame: number | null = null;

    const update = () => {
      const rect = campaign.getBoundingClientRect();
      const viewportCenter = window.innerHeight / 2;
      const elementCenter = rect.top + rect.height / 2;
      const offset = Math.max(-42, Math.min(42, (viewportCenter - elementCenter) * 0.075));

      campaign.style.setProperty("--etic-parallax-y", `${offset.toFixed(2)}px`);
      frame = null;
    };

    const requestUpdate = () => {
      if (frame === null) {
        frame = window.requestAnimationFrame(update);
      }
    };

    window.addEventListener("scroll", requestUpdate, { passive: true });
    window.addEventListener("resize", requestUpdate, { passive: true });
    requestUpdate();

    return () => {
      window.removeEventListener("scroll", requestUpdate);
      window.removeEventListener("resize", requestUpdate);

      if (frame !== null) {
        window.cancelAnimationFrame(frame);
      }
    };
  }, []);

  if (!products.length || editorial?.enabled === false) {
    return null;
  }

  const productGrid = (
    <div className="etic-editorial__products">
      {products.slice(0, 4).map((product) => (
        <ProductCard key={product.id} product={product} className="etic-editorial__product" />
      ))}
    </div>
  );

  const campaign = (
    <div ref={campaignRef} className="etic-editorial__campaign">
        {image ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img className="etic-editorial__image" src={image} alt="" loading="lazy" />
        ) : null}
        <div className="etic-editorial__shade" />
        <div className="etic-editorial__content">
          {kicker ? <p className="etic-editorial__kicker">{kicker}</p> : null}
          {title ? (
            <h2 id={id} className="etic-editorial__title">
              {title}
            </h2>
          ) : null}
          {cta ? (
            <Link className="etic-editorial__cta" href={ctaUrl}>
              {cta}
            </Link>
          ) : null}
        </div>
    </div>
  );

  return (
    <section className={`etic-editorial ${reverse ? "etic-editorial--reverse" : ""}`.trim()} aria-labelledby={id}>
      {reverse ? campaign : productGrid}
      {reverse ? productGrid : campaign}
    </section>
  );
}
