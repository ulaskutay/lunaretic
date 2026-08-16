"use client";

import { type FormEvent, useState } from "react";
import type { ThemeNewsletter } from "@/lib/types";

const fallbackBenefits = [
  { title: "Kolay iade", description: "Siparişinizi 30 gün içinde kolayca iade edin." },
  { title: "Hızlı gönderim", description: "Siparişiniz özenle hazırlanır ve hızla kargoya verilir." },
  { title: "Müşteri desteği", description: "Sorularınız için ekibimiz her zaman yanınızda." },
];

function BenefitIcon({ index }: { index: number }) {
  if (index === 0) {
    return (
      <svg viewBox="0 0 32 32" aria-hidden="true">
        <path d="M8 10h15l3 5v10H8zM8 15h18M12 10V7h8v3M5 20a6 6 0 1 0 2-4.5M5 14v6h6" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    );
  }

  if (index === 1) {
    return (
      <svg viewBox="0 0 32 32" aria-hidden="true">
        <path d="M4 8h15v13H4zM19 13h5l4 5v3h-9zM9 25a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM23 25a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    );
  }

  return (
    <svg viewBox="0 0 32 32" aria-hidden="true">
      <path d="M6 17v-2a10 10 0 0 1 20 0v2M6 17v6h4v-8H8a2 2 0 0 0-2 2ZM26 17v6h-4v-8h2a2 2 0 0 1 2 2ZM22 24c-1 2-3 3-6 3" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export function NewsletterBenefits({ newsletter }: { newsletter?: ThemeNewsletter }) {
  const [status, setStatus] = useState("");
  const benefits = newsletter?.benefits?.length ? newsletter.benefits : fallbackBenefits;

  function subscribe(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const email = String(new FormData(form).get("email") ?? "");

    if (!email) return;

    window.localStorage.setItem("etic-newsletter-email", email);
    form.reset();
    setStatus("E-posta tercihiniz bu cihazda kaydedildi.");
  }

  if (newsletter?.enabled === false) {
    return null;
  }

  return (
    <section className="etic-newsletter-benefits" aria-labelledby="atelier-newsletter-title">
      <div className="etic-newsletter-benefits__intro">
        <h2 id="atelier-newsletter-title">{newsletter?.title || "Detayları kaçırma"}</h2>
        <p>{newsletter?.description || "Yeni koleksiyonlar, özel teklifler ve ilham veren seçkiler e-posta kutunda."}</p>
        <form className="etic-newsletter-benefits__form" onSubmit={subscribe}>
          <label className="sr-only" htmlFor="benefits-newsletter-email">E-posta adresi</label>
          <input
            id="benefits-newsletter-email"
            type="email"
            name="email"
            required
            placeholder={newsletter?.placeholder || "e-posta adresiniz"}
          />
          <button type="submit">{newsletter?.cta || "Katıl"}</button>
        </form>
        <p className="etic-newsletter-benefits__status" aria-live="polite">{status}</p>
      </div>

      <div className="etic-newsletter-benefits__grid">
        {benefits.slice(0, 3).map((benefit, index) => (
          <article key={`${benefit.title}-${index}`} className="etic-newsletter-benefits__item">
            <div className="etic-newsletter-benefits__icon"><BenefitIcon index={index} /></div>
            <div>
              <h3>{benefit.title}</h3>
              <p>{benefit.description}</p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
