"use client";

import Link from "next/link";
import { useStorefront } from "@/lib/store";
import { CartTotals, CouponForm } from "@/components/cart-widgets";

export default function CartPage() {
  const { cart, updateLine, removeLine } = useStorefront();

  if (!cart || cart.lines.length === 0) {
    return (
      <>
        <h1 className="mb-6 text-2xl font-semibold">Sepet</h1>
        <p>Sepetiniz boş.</p>
      </>
    );
  }

  return (
    <>
      <h1 className="mb-6 text-2xl font-semibold">Sepet</h1>
      <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div className="space-y-4">
          {cart.lines.map((line) => (
            <div key={line.id} className="flex items-center justify-between rounded-2xl bg-white p-4">
              <div className="flex items-center gap-3">
                <div className="h-16 w-16 overflow-hidden rounded-xl bg-neutral-100">
                  {line.image ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={line.image} alt="" className="h-full w-full object-contain" />
                  ) : null}
                </div>
                <div>
                  <p className="font-medium">{line.name ?? line.sku}</p>
                  <p className="text-sm text-neutral-500">{line.total?.formatted}</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <input
                  type="number"
                  min={0}
                  defaultValue={line.quantity}
                  className="w-16 rounded border px-2 py-1"
                  onBlur={(event) => updateLine(line.id, Number(event.target.value))}
                />
                <button className="text-sm text-red-600" onClick={() => removeLine(line.id)}>
                  Sil
                </button>
              </div>
            </div>
          ))}
        </div>
        <aside className="space-y-4">
          <CouponForm />
          <div className="rounded-2xl bg-white p-4">
            <CartTotals />
          </div>
          <Link href="/odeme" className="inline-block w-full rounded-full bg-neutral-900 px-6 py-3 text-center text-white">
            Ödemeye geç
          </Link>
        </aside>
      </div>
    </>
  );
}
