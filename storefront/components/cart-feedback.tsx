"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { AnimatePresence, motion } from "motion/react";
import { onCartAdded, type CartAddedDetail } from "@/lib/cart-feedback";

const TOAST_MS = 4200;

export function CartFeedback() {
  const [toast, setToast] = useState<CartAddedDetail | null>(null);

  useEffect(() => {
    return onCartAdded((detail) => {
      setToast(detail);
    });
  }, []);

  useEffect(() => {
    if (!toast) {
      return;
    }

    const timer = window.setTimeout(() => setToast(null), TOAST_MS);
    return () => window.clearTimeout(timer);
  }, [toast]);

  return (
    <AnimatePresence>
      {toast ? (
        <motion.aside
          key={`${toast.name}-${toast.price ?? ""}`}
          className="etic-cart-toast"
          role="status"
          aria-live="polite"
          initial={{ opacity: 0, y: 18, scale: 0.96 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={{ opacity: 0, y: 12, scale: 0.98 }}
          transition={{ type: "spring", stiffness: 420, damping: 28 }}
        >
          {toast.image ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={toast.image} alt="" className="etic-cart-toast__media" />
          ) : (
            <span className="etic-cart-toast__media is-empty" />
          )}
          <div className="etic-cart-toast__copy">
            <p>Sepete eklendi</p>
            <strong>{toast.name}</strong>
            {toast.price ? <span>{toast.price}</span> : null}
          </div>
          <Link href={toast.href || "/sepet"} className="etic-cart-toast__cta">
            Sepeti gör
          </Link>
          <span className="etic-cart-toast__progress" />
        </motion.aside>
      ) : null}
    </AnimatePresence>
  );
}
