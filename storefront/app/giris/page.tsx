"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";

export default function LoginPage() {
  const { setAuthToken } = useStorefront();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

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
    <>
      <h1 className="mb-6 text-2xl font-semibold">Giriş</h1>
      {error ? <p className="mb-4 text-sm text-red-700">{error}</p> : null}
      <form onSubmit={submit} className="max-w-sm space-y-3">
        <input type="email" name="email" className="w-full rounded border px-3 py-2" placeholder="E-posta" required />
        <input type="password" name="password" className="w-full rounded border px-3 py-2" placeholder="Şifre" required />
        <button className="rounded-full bg-neutral-900 px-6 py-2 text-white">Giriş yap</button>
      </form>
      <p className="mt-4 text-sm">
        <Link href="/kayit">Hesap oluştur</Link>
      </p>
    </>
  );
}
