"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import type { GalleryImage } from "@/lib/types";

function wrapIndex(index: number, total: number) {
  return (index + total) % total;
}

function normalizeImages(images: Array<string | GalleryImage>): GalleryImage[] {
  return images.map((image) =>
    typeof image === "string" ? { src: image, thumb: image, zoom: image } : image,
  );
}

export function ProductGallery({
  images,
  alt,
}: {
  images: Array<string | GalleryImage>;
  alt: string;
}) {
  const [active, setActive] = useState(0);
  const [lightbox, setLightbox] = useState(false);
  const [zoomed, setZoomed] = useState(false);
  const [origin, setOrigin] = useState("50% 50%");
  const swipe = useRef<{ x: number; y: number; moved: boolean } | null>(null);
  const items = normalizeImages(images);
  const current = items[active] ?? items[0];
  const multiple = items.length > 1;

  const go = useCallback(
    (direction: number) => {
      if (!items.length) {
        return;
      }
      setActive((index) => wrapIndex(index + direction, items.length));
      setZoomed(false);
      setOrigin("50% 50%");
    },
    [items.length],
  );

  useEffect(() => {
    if (!lightbox) {
      return;
    }

    const previous = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    function onKey(event: KeyboardEvent) {
      if (event.key === "Escape") {
        setLightbox(false);
        setZoomed(false);
      }
      if (event.key === "ArrowRight") {
        go(1);
      }
      if (event.key === "ArrowLeft") {
        go(-1);
      }
    }

    window.addEventListener("keydown", onKey);

    return () => {
      document.body.style.overflow = previous;
      window.removeEventListener("keydown", onKey);
    };
  }, [lightbox, go]);

  function pointOrigin(event: { currentTarget: HTMLElement; clientX: number; clientY: number }) {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    setOrigin(`${Math.min(100, Math.max(0, x))}% ${Math.min(100, Math.max(0, y))}%`);
  }

  function onPointerDown(event: React.PointerEvent<HTMLDivElement>) {
    swipe.current = { x: event.clientX, y: event.clientY, moved: false };
  }

  function onPointerUp(event: React.PointerEvent<HTMLDivElement>) {
    const start = swipe.current;
    swipe.current = start ? { ...start, moved: false } : null;

    if (!start || !multiple) {
      swipe.current = null;
      return;
    }

    const dx = event.clientX - start.x;
    const dy = event.clientY - start.y;

    if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy)) {
      swipe.current = { ...start, moved: true };
      go(dx < 0 ? 1 : -1);
    } else {
      swipe.current = null;
    }
  }

  if (!current) {
    return <div className="etic-pdp__stage" />;
  }

  const lightboxNode = lightbox
    ? createPortal(
        <div
          className={`etic-pdp-lightbox${zoomed ? " is-zoomed" : ""}`}
          role="dialog"
          aria-modal="true"
          aria-label={alt}
          style={{ position: "fixed", inset: 0, zIndex: 200 }}
          onClick={(event) => {
            if (event.target === event.currentTarget) {
              setLightbox(false);
              setZoomed(false);
            }
          }}
        >
          <button type="button" className="etic-pdp-lightbox__close" onClick={() => { setLightbox(false); setZoomed(false); }} aria-label="Kapat">
            ×
          </button>
          {multiple ? (
            <button type="button" className="etic-pdp-lightbox__nav is-prev" onClick={() => go(-1)} aria-label="Önceki görsel">
              ‹
            </button>
          ) : null}
          <div
            className="etic-pdp-lightbox__frame"
            onMouseMove={(event) => {
              if (zoomed) {
                pointOrigin(event);
              }
            }}
            onClick={() => setZoomed((value) => !value)}
          >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={current.zoom} alt={alt} style={{ transformOrigin: origin }} />
          </div>
          {multiple ? (
            <button type="button" className="etic-pdp-lightbox__nav is-next" onClick={() => go(1)} aria-label="Sonraki görsel">
              ›
            </button>
          ) : null}
          <p className="etic-pdp-lightbox__meta">
            {multiple ? `${active + 1} / ${items.length}` : null}
            <span>{zoomed ? "Küçültmek için tıklayın" : "Yakınlaştırmak için tıklayın"}</span>
          </p>
        </div>,
        document.body,
      )
    : null;

  return (
    <div className="etic-pdp__gallery">
      <div
        className="etic-pdp__stage"
        onPointerDown={onPointerDown}
        onPointerUp={onPointerUp}
      >
        <button
          type="button"
          className="etic-pdp__inspect"
          onClick={() => {
            if (swipe.current?.moved) {
              swipe.current = null;
              return;
            }
            setLightbox(true);
          }}
          aria-label="Görseli incele"
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={current.src} alt={alt} decoding="async" fetchPriority="high" data-etic-cart-source />
        </button>
        {multiple ? (
          <>
            <button type="button" className="etic-pdp__nav is-prev" onClick={() => go(-1)} aria-label="Önceki görsel">
              ‹
            </button>
            <button type="button" className="etic-pdp__nav is-next" onClick={() => go(1)} aria-label="Sonraki görsel">
              ›
            </button>
          </>
        ) : null}
        <span className="etic-pdp__inspect-hint">İncele</span>
      </div>
      {multiple ? (
        <div className="etic-pdp__thumbs">
          {items.map((image, index) => (
            <button
              key={`${image.thumb}-${index}`}
              type="button"
              className={index === active ? "is-active" : undefined}
              onClick={() => setActive(index)}
              aria-label={`${alt} ${index + 1}`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={image.thumb} alt="" loading="lazy" decoding="async" />
            </button>
          ))}
        </div>
      ) : null}
      {lightboxNode}
    </div>
  );
}
