"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";

export default function RegisterPage() {
  const { bootstrap, setAuthToken } = useStorefront();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const storeName = bootstrap.store.name;

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    try {
      const response = await storeApi.register({
        name: String(form.get("name")),
        email: String(form.get("email")),
        password: String(form.get("password")),
        password_confirmation: String(form.get("password_confirmation")),
      });
      setAuthToken(response.data.token);
      router.push("/hesabim");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kayıt başarısız.");
    }
  }

  return (
    <section className="etic-auth">
      <div className="etic-auth__shell">
        <aside className="etic-auth__hero" aria-hidden="true">
          <p className="etic-auth__kicker">{storeName}</p>
          <h1 className="etic-auth__title">Yeni bir hesap oluşturun</h1>
          <p className="etic-auth__lead">
            Birkaç adımda hesabınızı açın; sipariş geçmişiniz ve tercihleriniz tek yerde toplansın.
          </p>
          <ul className="etic-auth__perks">
            <li>Sipariş geçmişi ve durum takibi</li>
            <li>Hızlı ödeme için kayıtlı bilgiler</li>
            <li>Yeni koleksiyonlardan ilk siz haberdar olun</li>
          </ul>
        </aside>

        <div className="etic-auth__card">
          <div className="etic-auth__card-head">
            <h2 className="etic-auth__card-title">Kayıt ol</h2>
            <p className="etic-auth__card-copy">Alışverişe başlamak için bilgilerinizi tamamlayın.</p>
          </div>
          {error ? <p className="etic-auth__error">{error}</p> : null}
          <form onSubmit={submit} className="etic-auth__form">
            <label className="etic-auth__field">
              <span className="etic-auth__label">Ad soyad</span>
              <input name="name" className="etic-auth__input" autoComplete="name" required />
            </label>
            <label className="etic-auth__field">
              <span className="etic-auth__label">E-posta</span>
              <input type="email" name="email" className="etic-auth__input" autoComplete="email" required />
            </label>
            <label className="etic-auth__field">
              <span className="etic-auth__label">Şifre</span>
              <input type="password" name="password" className="etic-auth__input" autoComplete="new-password" required />
            </label>
            <label className="etic-auth__field">
              <span className="etic-auth__label">Şifre tekrar</span>
              <input type="password" name="password_confirmation" className="etic-auth__input" autoComplete="new-password" required />
            </label>
            <button type="submit" className="etic-auth__submit">Hesap oluştur</button>
          </form>
          <p className="etic-auth__switch">
            Zaten hesabınız var mı? <Link href="/giris">Giriş yapın</Link>
          </p>
        </div>
      </div>
    </section>
  );
}
