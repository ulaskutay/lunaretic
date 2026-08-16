"use client";

import Link from "next/link";
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
    return <p className="etic-account__loading">Yükleniyor…</p>;
  }

  const firstName = user.name.trim().split(/\s+/)[0] || "Merhaba";
  const initial = user.name.trim().charAt(0).toUpperCase();

  return (
    <section className="etic-account">
      <header className="etic-account__head">
        <p className="etic-account__kicker">Hesabım</p>
        <h1>Merhaba, {firstName}</h1>
        <p className="etic-account__lead">Siparişlerinizi görüntüleyin ve hesap bilgilerinizi yönetin.</p>
      </header>

      <div className="etic-account__layout">
        <aside className="etic-account__profile">
          <div className="etic-account__avatar" aria-hidden="true">{initial}</div>
          <div className="etic-account__identity">
            <p className="etic-account__name">{user.name}</p>
            <p className="etic-account__email">{user.email}</p>
          </div>
          <nav className="etic-account__nav" aria-label="Hesap menüsü">
            <span className="etic-account__nav-item is-active">Siparişlerim</span>
            <Link href="/koleksiyon" className="etic-account__nav-item">Alışverişe devam et</Link>
          </nav>
          <div className="etic-account__logout">
            <button type="button" onClick={logout}>Çıkış yap</button>
          </div>
        </aside>

        <div className="etic-account__main">
          <div className="etic-account__panel">
            <div className="etic-account__panel-head">
              <h2>Siparişlerim</h2>
              <p>{orders.length} kayıt</p>
            </div>

            {orders.length === 0 ? (
              <div className="etic-account__empty">
                <p>Henüz bir siparişiniz yok.</p>
                <Link href="/koleksiyon" className="etic-account__cta">Koleksiyonu keşfet</Link>
              </div>
            ) : (
              <ul className="etic-account__orders">
                {orders.map((order) => (
                  <li key={order.id}>
                    <Link href={`/hesabim/siparis/${order.id}`} className="etic-account__order">
                      <div className="etic-account__order-copy">
                        <p className="etic-account__order-ref">#{order.reference ?? order.id}</p>
                        {order.created_at ? (
                          <p className="etic-account__order-date">
                            {new Date(order.created_at).toLocaleDateString("tr-TR", {
                              day: "numeric",
                              month: "long",
                              year: "numeric",
                            })}
                          </p>
                        ) : null}
                      </div>
                      <span className="etic-account__order-status">{order.status_label}</span>
                      {order.total ? <p className="etic-account__order-total">{order.total}</p> : null}
                      <span className="etic-account__order-action" aria-hidden="true">Detay →</span>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
