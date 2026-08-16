"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { AnimatePresence, motion } from "motion/react";
import type { ProductDetail, Variant } from "@/lib/types";
import { useStorefront } from "@/lib/store";
import { track } from "@/components/tracking";
import { ApiError } from "@/lib/api";
import { announceCartAdded, cartSourceImage } from "@/lib/cart-feedback";
import { productPath } from "@/lib/paths";
import { SizeChart } from "@/components/size-chart";

function optionValues(variants: Variant[], handle: string) {
  const seen = new Map<number, { id: number; name: string }>();

  for (const variant of variants) {
    for (const value of variant.values) {
      if (value.option === handle && !seen.has(value.id)) {
        seen.set(value.id, { id: value.id, name: value.name });
      }
    }
  }

  return [...seen.values()];
}

function optionHandles(variants: Variant[]) {
  const rank = (handle: string) => (handle === "color" ? 0 : handle === "size" ? 1 : 2);

  return [...new Set(variants.flatMap((variant) => variant.values.map((value) => value.option).filter(Boolean)))]
    .filter((handle): handle is string => Boolean(handle))
    .sort((left, right) => rank(left) - rank(right));
}

function matchVariant(variants: Variant[], selected: Record<string, number>) {
  const handles = Object.keys(selected);

  if (!handles.length) {
    return variants[0];
  }

  return variants.find((variant) =>
    handles.every((handle) => variant.values.some((value) => value.option === handle && value.id === selected[handle])),
  );
}

function initialSelection(variants: Variant[], handles: string[]) {
  const preferred = variants.find((variant) => variant.purchasable) ?? variants[0];
  const selected: Record<string, number> = {};

  for (const handle of handles) {
    const value = preferred?.values.find((item) => item.option === handle);
    if (value) {
      selected[handle] = value.id;
    }
  }

  return selected;
}

function optionLabel(handle: string) {
  if (handle === "size") {
    return "Beden";
  }

  if (handle === "color") {
    return "Renk";
  }

  return handle;
}

export function AddToCart({ product }: { product: ProductDetail }) {
  const { addToCart } = useStorefront();
  const router = useRouter();
  const siblingColors = (product.color_variants ?? []).filter((item) => item.slug);
  const showSiblingColors = siblingColors.length > 1;
  const handles = useMemo(() => {
    const all = optionHandles(product.variants);
    return showSiblingColors ? all.filter((handle) => handle !== "color") : all;
  }, [product.variants, showSiblingColors]);
  const [selected, setSelected] = useState(() => initialSelection(product.variants, handles));
  const [variantId, setVariantId] = useState(product.variants[0]?.id ?? 0);
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState<"cart" | "buy" | null>(null);
  const [added, setAdded] = useState(false);
  const fallback = !handles.length && product.variants.length > 1;
  const current = fallback
    ? product.variants.find((item) => item.id === variantId) ?? product.variants[0]
    : matchVariant(product.variants, selected) ?? product.variants[0];

  useEffect(() => {
    if (!added) {
      return;
    }

    const timer = window.setTimeout(() => setAdded(false), 2200);
    return () => window.clearTimeout(timer);
  }, [added]);

  function isAvailable(handle: string, valueId: number) {
    const next = { ...selected, [handle]: valueId };
    return Boolean(matchVariant(product.variants, next)?.purchasable);
  }

  async function purchase(intent: "cart" | "buy") {
    if (!current) {
      setError("Varyant seçin.");
      return;
    }

    if (handles.includes("size") && !selected.size) {
      setError("Beden seçin.");
      return;
    }

    setError(null);
    setPending(intent);

    try {
      const events = await addToCart(current.id, 1);
      events.forEach((item) => {
        const { event: name, ...params } = item;
        track(name, params);
      });

      if (intent === "buy") {
        router.push("/odeme");
        return;
      }

      announceCartAdded({
        name: product.name,
        image: product.image || product.gallery[0] || null,
        price: current.price?.formatted ?? null,
        source: cartSourceImage(),
      });
      setAdded(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Sepete eklenemedi.");
    } finally {
      setPending(null);
    }
  }

  return (
    <form
      className="etic-pdp__form"
      onSubmit={(event) => {
        event.preventDefault();
        void purchase("cart");
      }}
    >
      {current?.price ? (
        <div className="etic-pdp__price">
          <strong>{current.price.formatted}</strong>
          {current.compare_price && current.compare_price.value > 0 ? <s>{current.compare_price.formatted}</s> : null}
          <span>KDV dahildir</span>
        </div>
      ) : null}

      {error ? <p className="etic-pdp__error">{error}</p> : null}

      {showSiblingColors ? (
        <fieldset className="etic-pdp__option">
          <legend className="etic-pdp__option-label">Renk</legend>
          <div className="etic-pdp__swatches">
            {siblingColors.map((item) => (
              <Link
                key={item.id}
                href={productPath(item.slug)}
                title={item.color || item.name || undefined}
                className={item.active ? "is-active" : undefined}
              >
                {item.image ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.image} alt={item.color || item.name || "Renk"} />
                ) : (
                  <span>{item.color || "Renk"}</span>
                )}
              </Link>
            ))}
          </div>
        </fieldset>
      ) : null}

      {handles.map((handle) => (
        <fieldset key={handle} className="etic-pdp__option">
          <legend className="etic-pdp__option-label">{optionLabel(handle)}</legend>
          {handle === "size" ? <SizeChart /> : null}
          <div className={handle === "size" ? "etic-pdp__sizes" : "etic-pdp__choices"}>
            {optionValues(product.variants, handle).map((value) => {
              const active = selected[handle] === value.id;
              const available = isAvailable(handle, value.id);

              return (
                <button
                  key={value.id}
                  type="button"
                  className={`${active ? "is-active" : ""} ${available ? "" : "is-unavailable"}`.trim()}
                  onClick={() => setSelected((currentSelection) => ({ ...currentSelection, [handle]: value.id }))}
                >
                  {value.name}
                </button>
              );
            })}
          </div>
        </fieldset>
      ))}

      {fallback ? (
        <label className="etic-pdp__fallback">
          Varyant
          <select
            value={current?.id ?? 0}
            onChange={(event) => setVariantId(Number(event.target.value))}
          >
            {product.variants.map((item) => (
              <option key={item.id} value={item.id}>
                {item.values.map((value) => value.name).join(" / ") || item.sku}
              </option>
            ))}
          </select>
        </label>
      ) : null}

      <ul className="etic-pdp__meta">
        <li>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="1.4" />
            <path d="M3.6 9.5h16.8M3.6 14.5h16.8M12 3c2.6 3.2 3.9 6.4 3.9 9s-1.3 5.8-3.9 9c-2.6-3.2-3.9-6.4-3.9-9s1.3-5.8 3.9-9Z" fill="none" stroke="currentColor" strokeWidth="1.4" />
          </svg>
          Ücretsiz kargo
        </li>
        <li>
          <span className={current?.purchasable ? "is-ready" : "is-empty"} />
          {current?.purchasable ? "Stokta, kargoya hazır" : "Stokta yok"}
        </li>
      </ul>

      <div className="etic-pdp__actions">
        <button
          type="submit"
          className={`etic-pdp__cart${added ? " is-added" : ""}`}
          disabled={Boolean(pending) || added || !current?.purchasable}
        >
          <AnimatePresence mode="wait" initial={false}>
            <motion.span
              key={added ? "added" : pending === "cart" ? "pending" : "idle"}
              className="etic-pdp__cart-label"
              initial={{ opacity: 0, y: 6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              transition={{ duration: 0.18 }}
            >
              {added ? (
                <>
                  <svg className="etic-pdp__cart-check" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 12.5 9.2 17 19 7.5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                  </svg>
                  Eklendi
                </>
              ) : pending === "cart" ? (
                "Ekleniyor…"
              ) : current?.purchasable ? (
                "Sepete ekle"
              ) : (
                "Stokta yok"
              )}
            </motion.span>
          </AnimatePresence>
        </button>
        <button
          type="button"
          className="etic-pdp__buy"
          disabled={Boolean(pending) || !current?.purchasable}
          onClick={() => void purchase("buy")}
        >
          {pending === "buy" ? "Yönlendiriliyor…" : "Hemen al"}
        </button>
      </div>
    </form>
  );
}
