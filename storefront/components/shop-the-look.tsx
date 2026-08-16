"use client";

import { useState, type CSSProperties } from "react";
import { ProductCard } from "@/components/product-card";
import type { ThemeShopLook } from "@/lib/types";

type HotspotStyle = CSSProperties & {
  "--hotspot-x": string;
  "--hotspot-y": string;
};

export function ShopTheLook({
  shopLook,
  fallbackImage,
}: {
  shopLook?: ThemeShopLook;
  fallbackImage?: string | null;
}) {
  const [activeIndex, setActiveIndex] = useState(0);
  const hotspots = shopLook?.hotspots?.filter((hotspot) => hotspot.product) ?? [];
  const image = shopLook?.image || fallbackImage;
  const activeHotspot = hotspots[activeIndex] || hotspots[0];

  if (shopLook?.enabled === false || !image || !activeHotspot) return null;

  return (
    <section className="etic-shop-look" aria-labelledby="atelier-shop-look-title">
      <div className="etic-shop-look__heading">
        <p>{shopLook?.kicker || "Stili keşfet"}</p>
        <h2 id="atelier-shop-look-title">{shopLook?.title || "Görünümü tamamla"}</h2>
      </div>

      <div className="etic-shop-look__stage">
        <div className="etic-shop-look__visual">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={image} alt="" loading="lazy" />
          {hotspots.map((hotspot, index) => (
            <button
              key={`${hotspot.product.id}-${index}`}
              type="button"
              className={`etic-shop-look__hotspot ${activeIndex === index ? "is-active" : ""}`}
              style={
                {
                  "--hotspot-x": `${hotspot.x}%`,
                  "--hotspot-y": `${hotspot.y}%`,
                } as HotspotStyle
              }
              aria-controls={`atelier-shop-look-product-${index}`}
              aria-expanded={activeIndex === index}
              aria-label={`${hotspot.product.name} ürününü göster`}
              onClick={() => setActiveIndex(index)}
            >
              <span>{index + 1}</span>
            </button>
          ))}
        </div>

        <div className="etic-shop-look__products" aria-live="polite">
          <p className="etic-shop-look__counter">
            <span>{String(activeIndex + 1).padStart(2, "0")}</span>
            <span aria-hidden="true">/</span>
            <span>{String(hotspots.length).padStart(2, "0")}</span>
          </p>
          <div id={`atelier-shop-look-product-${activeIndex}`} className="etic-shop-look__product">
            <ProductCard key={activeHotspot.product.id} product={activeHotspot.product} />
          </div>
          <div className="etic-shop-look__mobile-nav" aria-label="Görünüm ürünleri">
            {hotspots.map((hotspot, index) => (
              <button
                key={`${hotspot.product.id}-nav`}
                type="button"
                className={activeIndex === index ? "is-active" : ""}
                aria-label={`${index + 1}. ürünü göster`}
                aria-expanded={activeIndex === index}
                onClick={() => setActiveIndex(index)}
              >
                {index + 1}
              </button>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
