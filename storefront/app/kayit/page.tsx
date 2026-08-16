"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";

export default function RegisterPage() {
  const { setAuthToken } = useStorefront();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

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
    <>
      <h1 className="mb-6 text-2xl font-semibold">Kayıt</h1>
      {error ? <p className="mb-4 text-sm text-red-700">{error}</p> : null}
      <form onSubmit={submit} className="max-w-sm space-y-3">
        <input name="name" className="w-full rounded border px-3 py-2" placeholder="Ad soyad" required />
        <input type="email" name="email" className="w-full rounded border px-3 py-2" placeholder="E-posta" required />
        <input type="password" name="password" className="w-full rounded border px-3 py-2" placeholder="Şifre" required />
        <input type="password" name="password_confirmation" className="w-full rounded border px-3 py-2" placeholder="Şifre tekrar" required />
        <button className="rounded-full bg-neutral-900 px-6 py-2 text-white">Kayıt ol</button>
      </form>
    </>
  );
}
