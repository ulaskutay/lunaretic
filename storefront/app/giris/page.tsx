"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";

export default function LoginPage() {
  const { bootstrap, setAuthToken } = useStorefront();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const storeName = bootstrap.store.name;

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      const response = await storeApi.login(String(form.get("email")), String(form.get("password")));
      setAuthToken(response.data.token);
      router.push("/hesabim");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Giriş başarısız.");
    }
  }

  return (
    <section className="etic-auth">
      <div className="etic-auth__shell">
        <aside className="etic-auth__hero" aria-hidden="true">
          <p className="etic-auth__kicker">{storeName}</p>
          <h1 className="etic-auth__title">Tekrar hoş geldiniz</h1>
          <p className="etic-auth__lead">
            Siparişlerinizi takip edin, adreslerinizi kaydedin ve koleksiyonları daha hızlı keşfedin.
          </p>
          <ul className="etic-auth__perks">
            <li>Sipariş geçmişi ve durum takibi</li>
            <li>Hızlı ödeme için kayıtlı bilgiler</li>
            <li>Yeni koleksiyonlardan ilk siz haberdar olun</li>
          </ul>
        </aside>

        <div className="etic-auth__card">
          <div className="etic-auth__card-head">
            <h2 className="etic-auth__card-title">Giriş yap</h2>
            <p className="etic-auth__card-copy">Hesabınıza erişmek için bilgilerinizi girin.</p>
          </div>
          {error ? <p className="etic-auth__error">{error}</p> : null}
          <form onSubmit={submit} className="etic-auth__form">
            <label className="etic-auth__field">
              <span className="etic-auth__label">E-posta</span>
              <input type="email" name="email" className="etic-auth__input" autoComplete="email" required />
            </label>
            <label className="etic-auth__field">
              <span className="etic-auth__label">Şifre</span>
              <input type="password" name="password" className="etic-auth__input" autoComplete="current-password" required />
            </label>
            <button type="submit" className="etic-auth__submit">Giriş yap</button>
          </form>
          <p className="etic-auth__switch">
            Henüz hesabınız yok mu? <Link href="/kayit">Kayıt olun</Link>
          </p>
        </div>
      </div>
    </section>
  );
}
