import CatalogPage from "@/components/catalog-page";

export default function Page({ searchParams }: { searchParams: Promise<Record<string, string | string[] | undefined>> }) {
  return <CatalogPage searchParams={searchParams} title="Arama" />;
}
