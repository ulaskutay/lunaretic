import { storeApi } from "@/lib/api";

export default async function SuccessPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const order = await storeApi.order(id);

  return (
    <>
      <h1 className="text-2xl font-semibold">Siparişiniz alındı</h1>
      <p className="mt-4">Sipariş no: {order.reference ?? order.id}</p>
      <p className="mt-2 text-sm text-neutral-600">Durum: {order.status_label}</p>
    </>
  );
}
