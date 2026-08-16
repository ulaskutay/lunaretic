import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { OrderSuccess } from "@/components/order-success";
import { storeApi } from "@/lib/api";

export const metadata: Metadata = {
  title: "Siparişiniz alındı",
  robots: {
    index: false,
    follow: false,
  },
};

export default async function SuccessPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  const order = await storeApi.order(id).catch(() => null);
  if (!order) {
    notFound();
  }

  return <OrderSuccess order={order} />;
}
