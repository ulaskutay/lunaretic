"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";
import type { Order, User } from "@/lib/types";

export default function AccountPage() {
  const { authToken, setAuthToken } = useStorefront();
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);

  useEffect(() => {
    if (!authToken) {
      router.replace("/giris");
      return;
    }

    storeApi.account(authToken).then((response) => {
      setUser(response.data.user);
      setOrders(response.data.orders);
    }).catch(() => router.replace("/giris"));
  }, [authToken, router]);

  function logout() {
    setAuthToken(null);
    router.push("/");
  }

  if (!user) {
    return <p>Yükleniyor…</p>;
  }

  return (
    <>
      <h1 className="mb-6 text-2xl font-semibold">Hesabım</h1>
      <p className="text-sm text-neutral-600">{user.email}</p>
      <button className="mt-2 text-sm underline" onClick={logout}>
        Çıkış
      </button>
      <h2 className="mt-8 font-medium">Siparişler</h2>
      <ul className="mt-3 space-y-2">
        {orders.length ? (
          orders.map((order) => (
            <li key={order.id} className="rounded bg-white p-3">
              #{order.reference ?? order.id} — {order.status_label}
            </li>
          ))
        ) : (
          <li>Henüz sipariş yok.</li>
        )}
      </ul>
    </>
  );
}
